/**
 * GWill Starter — Testimonials Carousel
 *
 * Pure progressive enhancement. The carousel track is a plain CSS
 * scroll-snap container (see the "Testimonials" section in style.css) and
 * is fully usable with zero JavaScript at all — touch swipe, or
 * shift+scroll-wheel on desktop. This script's only job is inserting
 * Prev/Next buttons for visitors without an obvious way to scroll
 * sideways with a plain vertical scroll wheel. Buttons are *created*
 * here, not just wired up — a button rendered in PHP whose only behaviour
 * comes from JS that might not load is worse than no button at all; this
 * way, no JS means no buttons, and the track still works regardless.
 *
 * @package GWill_Starter
 * @since   1.0.62
 */

( function () {
	'use strict';

	document.querySelectorAll( '.gwill-testimonials--carousel' ).forEach( function ( carousel ) {

		var track = carousel.querySelector( '.gwill-testimonials__track' );
		if ( ! track ) return;

		var prevBtn = document.createElement( 'button' );
		prevBtn.type = 'button';
		prevBtn.className = 'gwill-testimonials__nav gwill-testimonials__nav--prev';
		prevBtn.setAttribute( 'aria-label', 'Previous' );
		prevBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg>';

		var nextBtn = document.createElement( 'button' );
		nextBtn.type = 'button';
		nextBtn.className = 'gwill-testimonials__nav gwill-testimonials__nav--next';
		nextBtn.setAttribute( 'aria-label', 'Next' );
		nextBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg>';

		carousel.appendChild( prevBtn );
		carousel.appendChild( nextBtn );

		function cardWidth() {
			var card = track.querySelector( '.gwill-testimonial-card' );
			return card ? card.getBoundingClientRect().width + 20 /* gap, see style.css */ : track.clientWidth;
		}

		prevBtn.addEventListener( 'click', function () {
			track.scrollBy( { left: -cardWidth(), behavior: 'smooth' } );
		} );

		nextBtn.addEventListener( 'click', function () {
			track.scrollBy( { left: cardWidth(), behavior: 'smooth' } );
		} );

		function updateButtonState() {
			var atStart = track.scrollLeft <= 4;
			var atEnd   = track.scrollLeft >= track.scrollWidth - track.clientWidth - 4;
			prevBtn.disabled = atStart;
			nextBtn.disabled = atEnd;
		}

		var ticking = false;
		track.addEventListener( 'scroll', function () {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( function () {
					updateButtonState();
					ticking = false;
				} );
			}
		}, { passive: true } );

		updateButtonState();
	} );

} )();
