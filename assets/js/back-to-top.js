/**
 * GWill Starter — Back to Top
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

( function () {
	'use strict';

	var btn = document.querySelector( '.gwill-back-to-top' );
	if ( ! btn ) return;

	var SHOW_AFTER = 400;
	var ticking    = false;

	function update() {
		ticking = false;
		btn.classList.toggle( 'is-visible', window.scrollY > SHOW_AFTER );
	}

	window.addEventListener( 'scroll', function () {
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
