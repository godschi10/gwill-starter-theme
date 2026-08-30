/**
 * Nav accordion — GWill Starter (v1.7.0).
 *
 * Ported from gwill-tech-theme assets/js/main.js (the .mno-caret
 * accordion section, live-proven on the tech site), adapted: the
 * starter renders ONE menu (#primary-menu) for both breakpoints, so
 * the toggle is gated to mobile viewports — on desktop the sub-menus
 * are hover/focus dropdowns (CSS) and the caret chip is hidden.
 *
 * The .mno-caret <button> is emitted by inc/nav-walker.php for every
 * parent item (split-button pattern): the parent link navigates, the
 * chip toggles the sub-menu. Its native click fires on Enter/Space
 * too, so the accordion is keyboard operable (WCAG 2.1.1).
 *
 * @package GWill_Starter
 * @since   1.7.0
 */

/*
Table of Contents
1. isMobileViewport
2. setItem
3. Caret accordion toggle
*/

( function () {
	'use strict';

	// ── 1. isMobileViewport ───────────────────────────────
	// Mirrors the CSS breakpoint where the mobile menu takes over
	// (style.css @media (max-width: 767px) shows the toggle and the
	// accordion behaviour — keep the two in sync).
	function isMobileViewport() {
		return window.matchMedia( '(max-width: 767px)' ).matches;
	}

	// ── 2. setItem ────────────────────────────────────────
	// Open/close a single item, closing siblings for a clean accordion.
	// Keeps aria-expanded on the .mno-caret <button> in sync.
	function setItem( li, open ) {
		li.classList.toggle( 'open', open );
		var caret = li.querySelector( '.mno-parent > .mno-caret' );
		if ( caret ) caret.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( li.parentElement ) {
			Array.prototype.forEach.call( li.parentElement.children, function ( s ) {
				if ( s !== li ) {
					s.classList.remove( 'open' );
					var sc = s.querySelector( '.mno-parent > .mno-caret' );
					if ( sc ) sc.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		}
	}

	// ── 3. Caret accordion toggle ─────────────────────────
	document.addEventListener( 'click', function ( e ) {
		// Only the mobile accordion (.mno-caret is a <button>; its native
		// click fires on Enter/Space too, so it is keyboard operable).
		var caret = e.target.closest ? e.target.closest( '.mno-caret' ) : null;
		if ( ! caret ) return;
		if ( ! isMobileViewport() ) return;

		var li = caret.closest( 'li' );
		if ( ! li ) return;
		var wasOpen = li.classList.contains( 'open' );
		setItem( li, ! wasOpen );

		// preventDefault stops any parent link from navigating; clicks on the
		// link TEXT (outside the button) are left untouched and navigate normally.
		e.preventDefault();
		e.stopPropagation();
	} );

} )();
