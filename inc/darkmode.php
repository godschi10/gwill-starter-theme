<?php
/**
 * Dark Mode - tri-state theme engine (Dark / System / Light).
 *
 * Ported from the finance theme's segmented pill (its v1.12.70 engine) and
 * adapted to the starter's token + selector system (v1.11.0).
 *
 * Three explicit user options (the pro pattern; same as GitHub/Notion/Vercel):
 *   1. 'dark'   - user override: always dark, OS changes ignored while set.
 *   2. 'light'  - user override: always light, OS changes ignored while set.
 *   3. system   - no stored key: follow the device via prefers-color-scheme,
 *                and KEEP following it live via a matchMedia listener. If
 *                the OS flips while the page is open, the site flips too.
 *
 * localStorage key: 'gwill-color-scheme' (unchanged since v1.0.30 - existing
 * visitor choices carry over untouched). Stored values: 'dark' | 'light';
 * NO stored key means 'system'. Finance persists all three values including
 * a literal 'system'; the starter keeps the older, equally valid
 * "absence = system" contract so no migration code is needed.
 *
 * STARTER ADAPTATION (vs finance): finance removes data-theme for light
 * mode. The starter's stylesheet system uses a no-JS prefers-color-scheme
 * fallback scoped to :root:not([data-theme="light"]), so an explicit
 * data-theme="light" MUST be painted to beat a dark OS preference. This
 * engine therefore always paints the RESOLVED theme ('dark' or 'light')
 * explicitly; the media-query path remains only as the no-JS fallback.
 *
 * THE v1.12.70 FIX (ported): the persisted choice is read ONCE at startup,
 * written into each pill group's data-current, and aria-pressed + the
 * live-follow listener are driven from THAT real state, never from the
 * server-rendered data-current="system" default. Without this, every
 * returning visitor is wrongly treated as "on system": the pill shows
 * System on refresh even when Dark/Light was chosen, and any media-query
 * change silently reverts the page to the OS theme.
 *
 * WHY EVERYTHING IS INLINE
 * -------------------------
 * LiteSpeed Cache "Load JS Deferred" adds the HTML `defer` attribute to
 * every external script tag. Deferred scripts run only after the page is
 * fully parsed - which is why the system dark preference showed briefly
 * before the user's stored preference applied.
 *
 * Inline <script> blocks are not subject to LiteSpeed's defer processing.
 * All darkmode logic - initial resolution, pill sync, click handler,
 * keyboard support, ARIA sync, and the OS preference listener - is
 * therefore inlined here so it executes without any external dependency.
 *
 * The external gwill-darkmode script handle stays registered
 * (inc/enqueue.php) for backward compatibility but nothing enqueues it;
 * assets/js/darkmode.js is deprecated since v1.0.47 and kept for
 * reference only.
 *
 * @package GWill_Starter
 * @since   1.0.30
 * @since   1.11.0 Tri-state pill ported from the finance theme.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output the inline dark mode <script> + critical <style> block.
 *
 * Script section:
 *   Part A (immediate): resolves the stored choice (dark / light / system),
 *     paints the resolved theme on <html> before first paint - no flash.
 *   Part B (DOMContentLoaded): syncs every pill group to the REAL stored
 *     state (data-current + aria-pressed), wires click + Arrow/Home/End
 *     keyboard support, and attaches the live OS-follow listener (active
 *     only while the current choice is 'system').
 *
 * Style section:
 *   Mirrors the two flash-prevention tokens from assets/css/darkmode.css:
 *   color-scheme and background-color. These apply synchronously before
 *   LiteSpeed's async CSS loads, preventing the native-browser dark flash.
 *
 * @since 1.0.30
 * @since 1.11.0 Tri-state engine + multi-group pill sync.
 * @return void
 */
function gwill_darkmode_head_script(): void {
	?>
	<script>
	(function(){
		var KEY    = 'gwill-color-scheme';
		var DARKBG = '#0f172a';
		var root   = document.documentElement;
		var mq     = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
		var mqMobile = window.matchMedia ? window.matchMedia('(max-width: 767px)') : null;

		/* ── A. Resolve + apply theme immediately (parse time) ─────────── */

		function resolve(){
			try{ return localStorage.getItem(KEY) || ''; }catch(e){ return ''; }
		}

		/* Choice: 'dark' | 'light' | '' (= system, follows the device).
		   Anything else is garbage - normalize to system. */
		var choice = resolve();
		if (choice !== 'dark' && choice !== 'light') choice = '';

		function apply(t){
			/* Always paint EXPLICITLY - the STARTER ADAPTATION: an explicit
			   data-theme="light" must beat the prefers-color-scheme media
			   query in darkmode.css (finance removes the attribute instead;
			   that would break the starter's no-JS fallback selector). */
			root.dataset.theme = t;
			/* Pre-set background on <html> - eliminates the white-canvas
			   flash for dark users before any CSS file loads. Light mode
			   needs no pre-set (browser canvas default is white). */
			root.style.background = t === 'dark' ? DARKBG : '';
		}

		function resolveSystem(){
			return (mq && mq.matches) ? 'dark' : 'light';
		}

		apply(choice ? choice : resolveSystem());

		/* ── B. Pills: sync real state + wire interaction ──────────────── */
		/* Inline → LiteSpeed Deferred mode cannot delay this block.
		   DOMContentLoaded fires once the DOM is parsed, before images/async CSS. */
		document.addEventListener('DOMContentLoaded', function(){
			var groups = document.querySelectorAll('[data-theme-group]');
			if (!groups.length) return;

			function current(g){
				return g.getAttribute('data-current') || 'system';
			}

			function syncPressed(g, pick){
				g.querySelectorAll('[data-theme-set]').forEach(function(btn){
					btn.setAttribute('aria-pressed',
						btn.getAttribute('data-theme-set') === pick ? 'true' : 'false');
				});
			}

			function paint(t){ apply(t); }

			function persist(pick){
				/* 'system' = NO stored key (starter contract since v1.0.30). */
				if (pick === 'system') {
					try{ localStorage.removeItem(KEY); }catch(e){}
				} else {
					try{ localStorage.setItem(KEY, pick); }catch(e){}
				}
			}

			/* Sync every group to the REAL stored choice - never trust the
			   server-rendered data-current="system" default (v1.12.70 fix). */
			var startPick = (choice === 'dark' || choice === 'light') ? choice : 'system';
			groups.forEach(function(g){
				g.setAttribute('data-current', startPick);
				syncPressed(g, startPick);
			});

			/* Click: the choice is global - every pill (desktop + mobile)
			   shows the same pressed state, never conflicting. */
			groups.forEach(function(g){
				g.querySelectorAll('[data-theme-set]').forEach(function(btn){
					if (!btn.getAttribute('aria-label')) { btn.setAttribute('aria-label', 'Theme'); }
					btn.addEventListener('click', function(){
						var pick = btn.getAttribute('data-theme-set'); /* dark|system|light */
						/* Collapsed-mobile cycle (v1.12.9, Section A v2): below 768px
						   the pill shows ONLY the active segment (darkmode.css), so a
						   tap on it would re-select the same state forever. When the
						   tap lands on the CURRENT choice in that mode, advance to the
						   next state instead: dark -> system -> light -> dark. Desktop
						   behavior is untouched (all three segments visible, taps are
						   explicit). */
						if (pick === current(g) && mqMobile && mqMobile.matches) {
							var ord = ['dark','system','light'];
							pick = ord[(ord.indexOf(pick) + 1) % 3];
						}
						groups.forEach(function(g2){
							g2.setAttribute('data-current', pick);
							syncPressed(g2, pick);
						});
						persist(pick);
						paint(pick === 'system' ? resolveSystem() : pick);
					});
				});

				/* Keyboard: ArrowLeft/Right + Up/Down cycle, Home/End jump. */
				g.addEventListener('keydown', function(ev){
					var order = ['dark','system','light'];
					var segs  = g.querySelectorAll('[data-theme-set]');
					var i     = order.indexOf(current(g));
					var next  = -1;
					if (ev.key === 'ArrowLeft' || ev.key === 'ArrowUp') {
						next = (i - 1 + order.length) % order.length;
					} else if (ev.key === 'ArrowRight' || ev.key === 'ArrowDown') {
						next = (i + 1) % order.length;
					} else if (ev.key === 'Home') {
						next = 0;
					} else if (ev.key === 'End') {
						next = segs.length - 1;
					}
					if (next < 0) return;
					ev.preventDefault();
					segs[next].click();
					segs[next].focus();
				});
			});

			/* Live-follow the OS ONLY while a group's CURRENT choice is
			   'system'. Reads the live data-current (clicks update it),
			   never the startup snapshot - the v1.12.70 fix. */
			function onSys(e){
				groups.forEach(function(g){
					if (current(g) === 'system') paint(e.matches ? 'dark' : 'light');
				});
			}
			if (mq) {
				if (mq.addEventListener) mq.addEventListener('change', onSys);
				else if (mq.addListener) mq.addListener(onSys); /* Safari 13- */
			}
		});
	})();
	</script>
	<style>
		/* Critical dark-mode tokens - synchronous, before LiteSpeed async CSS.
		   Mirrors color-scheme + background from assets/css/darkmode.css. */
		:root{color-scheme:light;background-color:#fff}
		body{color:#111}
		[data-theme="dark"]{color-scheme:dark;background-color:#0f172a}
		[data-theme="dark"] body{color:#f1f5f9}
		[data-theme="light"]{color-scheme:light;background-color:#fff}
		[data-theme="light"] body{color:#111}
		@media(prefers-color-scheme:dark){
			:root:not([data-theme="light"]){color-scheme:dark;background-color:#0f172a}
			:root:not([data-theme="light"]) body{color:#f1f5f9}
		}
	</style>
	<?php
}
