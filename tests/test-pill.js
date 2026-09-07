/**
 * Tri-state darkmode pill - behavioral battery (jsdom).
 * Runs the REAL engine JS extracted from inc/darkmode.php against the REAL
 * pill markup rendered by template-parts/ui/theme-pill.php.
 *
 * Harness: fresh JSDOM per scenario; matchMedia stubbed in beforeParse
 * (jsdom has no prefers-color-scheme); localStorage pre-seeded per scenario;
 * engine inlined in <head> so Part A runs at parse time and Part B at
 * DOMContentLoaded - exactly the production sequence.
 */
'use strict';

/**
 * Tri-state darkmode pill - behavioral battery (jsdom).
 *
 * Runs the REAL engine JS extracted from inc/darkmode.php against the REAL
 * pill markup rendered by template-parts/ui/theme-pill.php (both pulled
 * from SRC_DIR, which verify-battery.sh sets from the tree under test).
 *
 * Run via tests/verify-battery.sh (stages extraction into a scratch dir),
 * never directly - it needs engine-extracted.js + pill-markup.html.
 */
'use strict';

let JSDOM;
try {
	({ JSDOM } = require('jsdom')); // hosts with jsdom installed
} catch (e) {
	// This VPS: jsdom lives in the Hermes agent's node_modules.
	({ JSDOM } = require('/home/ubuntu/.hermes/hermes-agent/node_modules/jsdom'));
}
const fs = require('fs');
const SRC = process.env.SRC_DIR || __dirname;

(async () => {

const engine = fs.readFileSync(SRC + '/engine-extracted.js', 'utf8');

// Pill markup ×2 = desktop + mobile group (the multi-group sync contract).
const PILL = fs.readFileSync(SRC + '/pill-markup.html', 'utf8');
const TWO_PILLS = PILL + PILL;

function makeMq(startDark) {
	let dark = startDark;
	const listeners = [];
	const mq = {
		get matches() { return dark; },
		addEventListener: (t, fn) => listeners.push(fn),
		removeEventListener: () => {},
		addListener: (fn) => listeners.push(fn),
		removeListener: () => {},
		_fire(v) { dark = v; listeners.forEach((fn) => fn({ matches: v })); },
	};
	return mq;
}

async function run(opts) {
	const mq = makeMq(!!opts.osDark);
	const dom = new JSDOM(
		'<!DOCTYPE html><html><head><script>' + engine + '</script></head><body>' +
		TWO_PILLS + '</body></html>',
		{
			url: 'http://localhost.test/',
			runScripts: 'dangerously',
			pretendToBeVisual: true,
			beforeParse(window) {
				window.matchMedia = () => mq; // every query returns the one shared mq
				if (opts.stored !== undefined) {
					if (opts.stored === null) window.localStorage.removeItem('gwill-color-scheme');
					else window.localStorage.setItem('gwill-color-scheme', opts.stored);
				}
			},
		}
	);
	// jsdom fires DOMContentLoaded asynchronously after the constructor -
	// settle until readyState leaves 'loading' so Part B has provably run.
	const w = dom.window;
	while (w.document.readyState === 'loading') {
		await new Promise((res) => setTimeout(res, 10));
	}
	await new Promise((res) => setTimeout(res, 10));
	return { dom, mq };
}

let pass = 0, fail = 0;
function check(name, ok, extra) {
	if (ok) { pass++; console.log('PASS - ' + name); }
	else { fail++; console.log('FAIL - ' + name + (extra ? ' | ' + extra : '')); }
}
const html = (r) => r.dom.window.document.documentElement;

/* ── 1. Parse-time resolution (Part A) ─────────────────────────────────── */

let r = await run({ osDark: false, stored: null });
check('1a no key + OS light -> data-theme=light', html(r).dataset.theme === 'light',
	'theme=' + html(r).dataset.theme);

r = await run({ osDark: true, stored: null });
check('1b no key + OS dark -> data-theme=dark', html(r).dataset.theme === 'dark',
	'theme=' + html(r).dataset.theme);

r = await run({ osDark: false, stored: 'dark' });
check('1c stored dark -> data-theme=dark', html(r).dataset.theme === 'dark');

r = await run({ osDark: true, stored: 'light' });
check('1d stored light + OS dark -> data-theme=light (starter adaptation: explicit light beats the MQ)',
	html(r).dataset.theme === 'light', 'theme=' + html(r).dataset.theme);

r = await run({ osDark: true, stored: 'garbage-value' });
check('1e garbage key -> normalized to system (OS dark -> dark)',
	html(r).dataset.theme === 'dark', 'theme=' + html(r).dataset.theme);

/* ── 2. Pill syncs to the REAL stored state (the v1.12.70 fix) ────────── */

r = await run({ osDark: true, stored: 'dark' });
let groups = r.dom.window.document.querySelectorAll('[data-theme-group]');
let g0 = groups[0], g1 = groups[1];
check('2a both groups data-current=dark (not server default system)',
	g0.getAttribute('data-current') === 'dark' && g1.getAttribute('data-current') === 'dark');
check('2b dark seg aria-pressed=true, others false',
	g0.querySelector('[data-theme-set="dark"]').getAttribute('aria-pressed') === 'true' &&
	g0.querySelector('[data-theme-set="system"]').getAttribute('aria-pressed') === 'false' &&
	g0.querySelector('[data-theme-set="light"]').getAttribute('aria-pressed') === 'false');

r = await run({ osDark: true, stored: null });
groups = r.dom.window.document.querySelectorAll('[data-theme-group]');
check('2c no key -> groups stay system, system seg pressed',
	groups[0].getAttribute('data-current') === 'system' &&
	groups[0].querySelector('[data-theme-set="system"]').getAttribute('aria-pressed') === 'true');

/* ── 3. Click behaviour ────────────────────────────────────────────────── */

r = await run({ osDark: true, stored: null });
let w = r.dom.window;
g0 = w.document.querySelectorAll('[data-theme-group]')[0];
g0.querySelector('[data-theme-set="dark"]').click();
check('3a click dark -> key stored dark', w.localStorage.getItem('gwill-color-scheme') === 'dark');
check('3b click dark -> html data-theme=dark', w.document.documentElement.dataset.theme === 'dark');
check('3c click dark -> BOTH groups synced (data-current + pressed)',
	w.document.querySelectorAll('[data-theme-group]')[1].getAttribute('data-current') === 'dark' &&
	w.document.querySelectorAll('[data-theme-group]')[1].querySelector('[data-theme-set="dark"]').getAttribute('aria-pressed') === 'true');

g0.querySelector('[data-theme-set="system"]').click();
check('3d click system -> key REMOVED (starter contract)',
	w.localStorage.getItem('gwill-color-scheme') === null);
check('3e click system + OS dark -> follows OS (dark)', w.document.documentElement.dataset.theme === 'dark');
check('3f click system -> groups data-current=system', g0.getAttribute('data-current') === 'system');

g0.querySelector('[data-theme-set="light"]').click();
check('3g click light -> key light + theme light (even on dark OS)',
	w.localStorage.getItem('gwill-color-scheme') === 'light' &&
	w.document.documentElement.dataset.theme === 'light');

/* ── 4. OS live-follow (active ONLY on system) ────────────────────────── */

r = await run({ osDark: false, stored: null }); w = r.dom.window; const mq4 = r.mq;
check('4a setup: system + OS light -> light', w.document.documentElement.dataset.theme === 'light');
mq4._fire(true);
check('4b OS flips dark while system -> page flips dark', w.document.documentElement.dataset.theme === 'dark');
mq4._fire(false);
check('4c OS flips back -> page flips light', w.document.documentElement.dataset.theme === 'light');

r = await run({ osDark: false, stored: 'light' }); w = r.dom.window; const mq5 = r.mq;
mq5._fire(true);
check('4d explicit light: OS flip IGNORED (v1.12.70 fix - user override holds)',
	w.document.documentElement.dataset.theme === 'light');

r = await run({ osDark: true, stored: null }); w = r.dom.window; const mq6 = r.mq;
// user moves to explicit dark, then OS flips light - must stay dark
w.document.querySelectorAll('[data-theme-group]')[0].querySelector('[data-theme-set="dark"]').click();
mq6._fire(false);
check('4e explicit dark after click: OS flip ignored',
	w.document.documentElement.dataset.theme === 'dark' &&
	w.localStorage.getItem('gwill-color-scheme') === 'dark');

/* ── 5. Keyboard ───────────────────────────────────────────────────────── */

r = await run({ osDark: false, stored: null }); w = r.dom.window;
g0 = w.document.querySelectorAll('[data-theme-group]')[0];
g0.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true, cancelable: true }));
check('5a ArrowLeft from system -> dark chosen (cycle dark<-system)',
	w.localStorage.getItem('gwill-color-scheme') === 'dark' && w.document.documentElement.dataset.theme === 'dark');

r = await run({ osDark: false, stored: null }); w = r.dom.window;
g0 = w.document.querySelectorAll('[data-theme-group]')[0];
g0.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true, cancelable: true }));
check('5b ArrowRight from system -> light chosen',
	w.localStorage.getItem('gwill-color-scheme') === 'light' && w.document.documentElement.dataset.theme === 'light');

r = await run({ osDark: false, stored: null }); w = r.dom.window;
g0 = w.document.querySelectorAll('[data-theme-group]')[0];
const ev = new w.KeyboardEvent('keydown', { key: 'End', bubbles: true, cancelable: true });
g0.dispatchEvent(ev);
check('5c End -> last segment (light)',
	w.localStorage.getItem('gwill-color-scheme') === 'light');
check('5d keydown preventDefault honored (page does not scroll on handled keys)',
	ev.defaultPrevented === true);

/* ── 6. Private mode: localStorage throws ─────────────────────────────── */

{
	const mq = makeMq(true);
	const dom = new JSDOM(
		'<!DOCTYPE html><html><head><script>' + engine + '</script></head><body>' + PILL + '</body></html>',
		{
			url: 'http://localhost.test/',
			runScripts: 'dangerously',
			beforeParse(window) {
				window.matchMedia = () => mq;
				// Throw on every localStorage access = Safari private mode class.
				Object.defineProperty(window, 'localStorage', {
					get() { throw new DOMException('denied', 'SecurityError'); },
				});
			},
		}
	);
	const w = dom.window;
	while (w.document.readyState === 'loading') {
		await new Promise((res) => setTimeout(res, 10));
	}
	await new Promise((res) => setTimeout(res, 10));
	check('6a localStorage-throws -> still resolves (system/OS-dark -> dark)',
		w.document.documentElement.dataset.theme === 'dark');
	check('6b localStorage-throws -> pill wires, system pressed',
		w.document.querySelector('[data-theme-group]').getAttribute('data-current') === 'system' &&
		w.document.querySelector('[data-theme-set="system"]').getAttribute('aria-pressed') === 'true');
	// Click must not throw even though persistence is denied.
	let clicked = true;
	try {
		w.document.querySelector('[data-theme-set="dark"]').click();
	} catch (e) { clicked = false; }
	check('6c click with throwing storage -> no exception, theme still applied for the visit',
		clicked && w.document.documentElement.dataset.theme === 'dark');
}

console.log('\n' + pass + ' passed, ' + fail + ' failed');
process.exit(fail ? 1 : 0);
})();
