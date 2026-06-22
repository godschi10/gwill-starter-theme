/**
 * search-expandable.js — Combo A
 *
 * Toggles the inline header search input open/closed.
 *
 * Close triggers:
 *   - X / toggle button click (when open)
 *   - Escape key
 *   - Click anywhere outside the component
 *
 * Accessibility:
 *   - aria-expanded on the toggle button
 *   - aria-hidden + hidden attribute on the form
 *   - Focus moves to the input on open; returns to toggle on close
 *
 * i18n: aria-label strings come from GwillExpand.i18n (wp_localize_script).
 * Falls back to English if the object is absent.
 *
 * Note: DOMContentLoaded wrapper removed — deferred scripts execute after
 * DOM parsing is complete; the extra listener was redundant.
 *
 * @package GWill_Starter
 * @since   1.0.23
 */
( function () {
	'use strict';

	var i18n = ( window.GwillExpand && window.GwillExpand.i18n ) || {};

	document.querySelectorAll( '[data-gwill-search-expand]' ).forEach( function ( wrap ) {

		var toggle = wrap.querySelector( '.gwill-search-expand__toggle' );
		var form   = wrap.querySelector( '.gwill-search-expand__form' );
		var input  = wrap.querySelector( '.gwill-search-expand__input' );

		if ( ! toggle || ! form ) return;

		// ── State ────────────────────────────────────────────────────────

		function isOpen() {
			return toggle.getAttribute( 'aria-expanded' ) === 'true';
		}

		// ── Open ─────────────────────────────────────────────────────────

		function open() {
			// Measure real header bottom so --gwill-header-height is accurate
			// on every viewport size before the form becomes visible.
			var header = document.querySelector( '.site-header' );
			if ( header ) {
				var bottom = header.getBoundingClientRect().bottom;
				document.documentElement.style.setProperty( '--gwill-header-height', bottom + 'px' );
			}

			form.removeAttribute( 'hidden' );
			form.setAttribute( 'aria-hidden', 'false' );
			toggle.setAttribute( 'aria-expanded', 'true' );
			toggle.setAttribute( 'aria-label', i18n.closeSearch || 'Close search' );
			wrap.classList.add( 'is-open' );
			// Defer focus so the browser has painted the visible input.
			setTimeout( function () { if ( input ) input.focus(); }, 50 );
		}

		// ── Close ────────────────────────────────────────────────────────

		function close() {
			form.setAttribute( 'hidden', '' );
			form.setAttribute( 'aria-hidden', 'true' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			toggle.setAttribute( 'aria-label', i18n.openSearch || 'Open search' );
			wrap.classList.remove( 'is-open' );
			toggle.focus();
		}

		// ── Event listeners ──────────────────────────────────────────────

		toggle.addEventListener( 'click', function () {
			isOpen() ? close() : open();
		} );

		// Escape key closes from anywhere on the document.
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && isOpen() ) {
				e.preventDefault();
				close();
			}
		} );

		// Click outside closes the expand.
		document.addEventListener( 'click', function ( e ) {
			if ( isOpen() && ! wrap.contains( e.target ) ) close();
		} );

	} );

}() );
