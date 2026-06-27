/**
 * GWill Starter — Back to Top
 *
 * @package GWill_Starter
 * @since   1.0.50
 * @since   1.0.59 Switched from a fixed 400px threshold to a percentage of
 *                 actual scrollable distance (default 30%, filterable via
 *                 gwill_back_to_top_percent in inc/enqueue.php) — a fixed
 *                 pixel count meant something different on a short post
 *                 than a long one; a percentage scales with the page.
 */

( function () {
	'use strict';

	var btn = document.querySelector( '.gwill-back-to-top' );
	if ( ! btn ) return;

	// GwillBackToTop is only present when this script is actually enqueued
	// (see inc/enqueue.php) — the || 0.3 fallback exists purely for the
	// edge case of the localize data failing to print for any reason, not
	// because it's expected to ever actually be missing in practice.
	var SHOW_AFTER_PERCENT = ( window.GwillBackToTop && window.GwillBackToTop.showAfterPercent ) || 0.3;
	var ticking            = false;

	function scrollableDistance() {
		return document.documentElement.scrollHeight - window.innerHeight;
	}

	function update() {
		ticking = false;
		var distance = scrollableDistance();
		// A page shorter than the viewport has nothing to scroll — never show.
		var visible = distance > 0 && ( window.scrollY / distance ) > SHOW_AFTER_PERCENT;
		btn.classList.toggle( 'is-visible', visible );
	}

	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	}, { passive: true } );

	// Recompute on resize too — scrollableDistance() depends on viewport
	// height, which changes on rotation/resize independently of scrolling.
	window.addEventListener( 'resize', function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	}, { passive: true } );

	update(); // Correct initial state on a page load that's already scrolled (e.g. via a #fragment link).

	btn.addEventListener( 'click', function () {
		var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		window.scrollTo( {
			top:      0,
			behavior: reduceMotion ? 'auto' : 'smooth',
		} );
	} );

} )();
