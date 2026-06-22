<?php
/**
 * Dark Mode — head script + critical inline CSS.
 *
 * Called from header.php before wp_head() so it runs synchronously at
 * parse time, before any paint or async CSS.
 *
 * WHY EVERYTHING IS INLINE
 * ─────────────────────────
 * LiteSpeed Cache "Load JS Deferred" = Deferred adds the HTML `defer`
 * attribute to every external script tag. Deferred scripts run only after
 * the page is fully parsed — which is why the system dark preference was
 * showing briefly before the user's localStorage preference applied.
 *
 * Inline <script> blocks are not subject to LiteSpeed's defer processing.
 * All darkmode logic — initial theme detection, toggle click handler, ARIA
 * sync, and OS preference change listener — is therefore inlined here so it
 * executes reliably without any external file dependency.
 *
 * The external gwill-darkmode script handle is kept registered (inc/enqueue.php)
 * but is no longer enqueued anywhere. darkmode-toggle.php previously enqueued
 * it; that call is removed in v1.0.47.
 *
 * @package GWill_Starter
 * @since   1.0.30
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output the inline dark mode <script> + critical <style> block.
 *
 * Script section:
 *   Part A (immediate): reads localStorage / prefers-color-scheme, sets
 *     data-theme on <html>, pre-sets background-color for dark mode.
 *     Runs synchronously at HTML parse time — no async gap.
 *
 *   Part B (DOMContentLoaded): attaches the toggle button click handler,
 *     syncs aria-label / aria-pressed, and listens for OS preference changes.
 *     Uses DOMContentLoaded (not load) so it fires as soon as the DOM is
 *     ready, regardless of async images or CSS.
 *
 * Style section:
 *   Mirrors the two flash-prevention tokens from assets/css/darkmode.css:
 *   color-scheme and background-color. These apply synchronously before
 *   LiteSpeed's async CSS loads, preventing the native-browser dark flash.
 *
 * @since 1.0.30
 * @return void
 */
function gwill_darkmode_head_script(): void {
	?>
	<script>
	(function(){
		var KEY    = 'gwill-color-scheme';
		var DARKBG = '#0f172a';
		var root   = document.documentElement;

		/* ── A. Resolve + apply theme immediately (parse time) ─────────────── */

		function resolve(){
			try{ return localStorage.getItem(KEY) || ''; }catch(e){ return ''; }
		}

		function apply(t){
			root.dataset.theme    = t;
			/* Pre-set background on <html> — eliminates the white-canvas flash
			   for dark-mode users before any CSS file loads. Light mode needs
			   no pre-set (browser canvas default is already white). */
			root.style.background = t === 'dark' ? DARKBG : '';
		}

		var saved   = resolve();
		var sysDark = window.matchMedia('(prefers-color-scheme:dark)').matches;
		var theme   = saved ? saved : ( sysDark ? 'dark' : 'light' );
		apply(theme);

		/* ── B. Button + ARIA (DOMContentLoaded) ───────────────────────────── */
		/* Inline → LiteSpeed Deferred mode cannot delay this block.
		   DOMContentLoaded fires once the DOM is parsed, before images/async CSS. */

		document.addEventListener('DOMContentLoaded', function(){
			var btn = document.getElementById('gwill-darkmode-toggle');
			if(!btn) return;

			function syncBtn(t){
				var dark = t === 'dark';
				btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
				btn.setAttribute('aria-label',
					dark
						? ( btn.getAttribute('data-label-light') || 'Switch to light mode' )
						: ( btn.getAttribute('data-label-dark')  || 'Switch to dark mode'  )
				);
			}

			syncBtn(theme);

			btn.addEventListener('click', function(){
				var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
				try{ localStorage.setItem(KEY, next); }catch(e){}
				apply(next);
				theme = next;
				syncBtn(next);
			});

			/* OS preference change — honoured only when no stored preference. */
			try{
				window.matchMedia('(prefers-color-scheme:dark)').addEventListener('change', function(e){
					if( !resolve() ){
						var t = e.matches ? 'dark' : 'light';
						apply(t);
						theme = t;
						syncBtn(t);
					}
				});
			}catch(e){}
		});

	})();
	</script>
	<style>
		/* Critical dark-mode tokens — synchronous, before LiteSpeed async CSS.
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
