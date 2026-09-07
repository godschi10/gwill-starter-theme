/**
 * GWill Starter - Sticky Header
 *
 * Only runs at all when the body has .gwill-sticky-header - added by
 * gwill_sticky_header_body_class() (inc/customizer.php) when the "Enable
 * sticky header" Customizer toggle is on (default: on). The CSS itself is
 * also scoped to that same class, so this script enqueuing unconditionally
 * is harmless even when the toggle is off - it just no-ops immediately.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

( function () {
	'use strict';

	if ( ! document.body.classList.contains( 'gwill-sticky-header' ) ) return;

	var header = document.querySelector( '.site-header' );
	if ( ! header ) return;

	var STUCK_AFTER  = 4;
	var ticking       = false;
	var compactMode   = document.body.classList.contains( 'gwill-compact-header' );
	var lastCompact   = null;

	function update() {
		ticking = false;
		var stuck = window.scrollY > STUCK_AFTER;
		header.classList.toggle( 'is-stuck', stuck );

		// v1.11.2 (A7): compact-on-scroll. Same threshold as is-stuck;
		// the class carries the visual compaction (padding, tagline) while
		// is-stuck carries the shadow. One shared rAF loop, zero extra
		// listeners; the class only flips on CHANGE (not every frame).
		if ( compactMode ) {
			if ( lastCompact !== stuck ) {
				lastCompact = stuck;
				header.classList.toggle( 'is-compact', stuck );
			}
		}
	}

	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	}, { passive: true } );

	update();

} )();
