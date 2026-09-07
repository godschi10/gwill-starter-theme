/**
 * B14 suggestion chips - behavioral battery (jsdom).
 *
 * Drives the REAL assets/js/search-dropdown.js through both zero-results
 * paths with stubbed fetch:
 *   1. FTS endpoint returns empty + index works -> chips render (3 seeds
 *      from index titles, label from i18n, hrefs = homeUrl?s=title).
 *   2. Index fetch rejects + REST fallback returns []  -> plain empty
 *      state, NO chips (the memoized-rejection trap; renderNoResults
 *      must not re-call getIndex).
 *
 * Run: node tests/test-search-chips.js   (no build step, plain jsdom)
 */
'use strict';

let JSDOM;
try {
	({ JSDOM } = require('jsdom'));
} catch (e) {
	({ JSDOM } = require('/home/ubuntu/.hermes/hermes-agent/node_modules/jsdom'));
}
const fs = require('fs');
const path = require('path');

const ROOT = process.env.SRC_DIR || path.join(__dirname, '..');
const ENGINE = fs.readFileSync(path.join(ROOT, 'assets/js/search-dropdown.js'), 'utf8');

const I18N = {
	loading: 'Searching…',
	noResults: 'No results found.',
	noMatches: 'No matches for “%s” - try these recent posts:',
	error: 'Search unavailable. Press Enter to search.',
	viewAll: 'View all results →',
	trySearching: 'Try searching for',
};

const INDEX = [
	{ id: 1, title: 'WordPress Permalinks Guide', url: '/p1/', excerpt: '', cat: 'Guides', cat_slug: 'guides', date: '2026-01-01' },
	{ id: 2, title: 'SQLite Drop-in Primer',   url: '/p2/', excerpt: '', cat: 'Articles', cat_slug: 'articles', date: '2026-01-02' },
	{ id: 3, title: 'Nginx Cache Purge Law',   url: '/p3/', excerpt: '', cat: 'Guides', cat_slug: 'guides', date: '2026-01-03' },
	{ id: 4, title: 'Fourth Post Ignored',     url: '/p4/', excerpt: '', cat: 'Reviews', cat_slug: 'reviews', date: '2026-01-04' },
];

const DOM = `<!DOCTYPE html><html><head></head><body>
<script>${ENGINE}</script>
<button class="gwill-search-toggle" data-gwill-search-toggle aria-expanded="false">S</button>
<div class="search-dropdown" id="search-dropdown" hidden>
  <div class="search-dropdown-inner">
    <form class="search-dropdown-form">
      <span class="search-input-wrap">
        <input class="search-dropdown-input" type="text" id="search-input" aria-label="Search">
        <button class="search-clear" type="button" id="search-clear" hidden>x</button>
      </span>
      <button class="search-dropdown-close" type="button" id="search-close">X</button>
    </form>
    <div class="search-results" id="search-results" role="status" aria-live="polite"></div>
  </div>
</div>
<script>${ENGINE}</script>
</body></html>`;

function run(fetchImpl) {
	return new Promise((resolve, reject) => {
		const dom = new JSDOM(DOM, {
			url: 'http://localhost.test/',
			runScripts: 'dangerously',
			pretendToBeVisual: true,
			beforeParse(window) {
				window.GwillDropdown = {
					indexUrl: '/wp-json/gwill/v1/search-index',
					ftsUrl: '/wp-json/gwill/v1/search-fts?q=',
					restUrl: '/wp-json/wp/v2/posts?search=',
					homeUrl: 'http://localhost.test/',
					i18n: I18N,
				};
				window.fetch = fetchImpl;
			},
		});
		const w = dom.window;
		const settle = (ms) => new Promise((r) => setTimeout(r, ms));
		(async () => {
			try {
				while (w.document.readyState === 'loading') await settle(10);
				await settle(20);
				w.document.querySelector('[data-gwill-search-toggle]').click();
				const input = w.document.getElementById('search-input');
				input.value = 'zzzz-nothing';
				input.dispatchEvent(new w.Event('input', { bubbles: true }));
				await settle(400); // debounce 120ms + fetch chain
				resolve(w);
			} catch (e) { reject(e); }
		})();
	});
}

let pass = 0, fail = 0;
function check(name, ok, extra) {
	if (ok) { pass++; console.log('PASS - ' + name); }
	else { fail++; console.log('FAIL - ' + name + (extra ? ' | ' + extra : '')); }
}

(async () => {
	// ── 1. FTS empty + index alive -> chips render ──
	{
		const calls = [];
		const w = await run((url) => {
			calls.push(String(url));
			if (url.indexOf('search-index') !== -1) {
				return Promise.resolve({ ok: true, json: () => Promise.resolve(INDEX) });
			}
			// FTS + REST return empty arrays
			return Promise.resolve({ ok: true, json: () => Promise.resolve([]) });
		});
		const doc = w.document;
		const empty = doc.querySelector('.search-empty');
		const chips = doc.querySelectorAll('.search-chip');
		check('1a empty message renders', !!empty, 'missing .search-empty');
		check('1b three chips render', chips.length === 3, 'count=' + chips.length);
		check('1c label text from i18n', (doc.querySelector('.search-chips-label') || {}).textContent === 'Try searching for');
		check('1d chips seed from index titles',
			chips.length && [...chips].every((c, i) => c.textContent === INDEX[i].title),
			chips.length ? [...chips].map((c) => c.textContent).join('|') : '');
		check('1e chip hrefs are search URLs',
			chips.length && [...chips].every((c) => c.getAttribute('href') === 'http://localhost.test/?s=' + encodeURIComponent(c.textContent)),
			chips.length ? chips[0].getAttribute('href') : '');
		check('1f no duplicates among seeds',
			new Set([...chips].map((c) => c.textContent)).size === chips.length);
	}

	// ── 2. index rejected + REST empty -> plain empty state, NO chips ──
	{
		const w = await run((url) => {
			if (url.indexOf('search-index') !== -1) return Promise.reject(new Error('down'));
			return Promise.resolve({ ok: true, json: () => Promise.resolve([]) });
		});
		const doc = w.document;
		check('2a empty message renders (index down)', !!doc.querySelector('.search-empty'));
		check('2b NO chips when the index is down', !doc.querySelector('.search-chips'),
			'the memoized-rejection trap would otherwise hang the render');
	}

	console.log('\n' + pass + ' passed, ' + fail + ' failed');
	process.exit(fail ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
