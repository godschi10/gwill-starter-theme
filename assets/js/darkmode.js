/**
 * Dark mode toggle — DEPRECATED, no longer enqueued anywhere.
 *
 * As of v1.0.47 this entire file's logic lives inline in
 * gwill_darkmode_head_script() (inc/darkmode.php), output directly in
 * <head> by header.php. Inline blocks cannot be delayed by LiteSpeed
 * Cache's "Load JS Deferred" setting; this external file could be, which
 * caused the toggle's click handler to not attach until first user
 * interaction on some mobile devices.
 *
 * inc/enqueue.php still registers the 'gwill-darkmode' handle (harmless —
 * registering without enqueuing loads nothing) in case any site-specific
 * customisation calls wp_enqueue_script('gwill-darkmode') directly. If you
 * have such code, switch it to rely on the inline head script instead;
 * this file is kept only for reference and is not maintained going forward.
 *
 * Original docblock preserved below for history.
 */

/**
 * Dark mode toggle
 *
 * The flash-prevention inline script in header.php has already set
 * data-theme on <html> before first paint. This script handles:
 *   – Button click → toggle and persist to localStorage
 *   – ARIA state sync (aria-label, aria-pressed) on load and on toggle
 *   – OS-level preference changes (e.g. auto-switch at sunset) when the
 *     user has not made an explicit stored choice
 *
 * Storage key : 'gwill-color-scheme'
 * Values      : 'dark' | 'light'
 *
 * @package GWill_Starter
 * @since   1.0.30
 * @deprecated 1.0.47 Use inc/darkmode.php's inline script instead.
 */

( function () {
	'use strict';

	var STORAGE_KEY = 'gwill-color-scheme';
	var btn = document.getElementById( 'gwill-darkmode-toggle' );

	if ( ! btn ) return;

	/**
	 * Apply a theme: write data-theme and update ARIA attributes.
	 *
	 * @param {string} theme  'dark' | 'light'
	 */
	function applyTheme( theme ) {
		document.documentElement.dataset.theme = theme;

		btn.setAttribute( 'aria-pressed', theme === 'dark' ? 'true' : 'false' );

		// i18n strings are provided via wp_localize_script (GwillDarkmode.i18n).
		// Fall back to English if the object is somehow absent.
		var i18n = ( window.GwillDarkmode && window.GwillDarkmode.i18n ) || {};
		btn.setAttribute(
			'aria-label',
			theme === 'dark'
				? ( i18n.switchToLight || 'Switch to light mode' )
				: ( i18n.switchToDark  || 'Switch to dark mode'  )
		);
	}

	/** Resolve the active theme: stored preference or system fallback. */
	function resolveTheme() {
		var stored = localStorage.getItem( STORAGE_KEY );
		if ( stored ) return stored;
		return window.matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light';
	}

	// Sync ARIA to the theme the head script already applied.
	applyTheme( resolveTheme() );

	// Toggle on click.
	btn.addEventListener( 'click', function () {
		var next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
		localStorage.setItem( STORAGE_KEY, next );
		applyTheme( next );
	} );

	// Respond to OS preference changes when no explicit choice is stored.
	window.matchMedia( '(prefers-color-scheme: dark)' ).addEventListener( 'change', function ( e ) {
		if ( ! localStorage.getItem( STORAGE_KEY ) ) {
			applyTheme( e.matches ? 'dark' : 'light' );
		}
	} );

} )();
