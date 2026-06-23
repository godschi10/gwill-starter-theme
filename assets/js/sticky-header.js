/**
 * GWill Starter — Sticky Header
 *
 * Only runs at all when the body has .gwill-sticky-header — added by
 * gwill_sticky_header_body_class() (inc/customizer.php) when the "Enable
 * sticky header" Customizer toggle is on (default: on). The CSS itself is
 * also scoped to that same class, so this script enqueuing unconditionally
 * is harmless even when the toggle is off — it just no-ops immediately.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

( function () {
	'use strict';

	if ( ! document.body.classList.contains( 'gwill-sticky-header' ) ) return;

	var header = document.querySelector( '.site-header' );
	if ( ! header ) return;

	var STUCK_AFTER = 4;
	var ticking      = false;

	function update() {
		ticking = false;
		header.classList.toggle( 'is-stuck', window.scrollY > STUCK_AFTER );
	}

	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	}, { passive: true } );

	update();

} )();
