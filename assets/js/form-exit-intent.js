/**
 * GWill Starter — Exit-Intent Form
 *
 * Shows the .gwill-exit-intent overlay when the user's cursor leaves the
 * viewport toward the browser chrome. On mobile, triggers at 75% scroll depth.
 *
 * Throttled: fires once per session (sessionStorage flag). Respawns after
 * a configurable number of days (localStorage flag, default 7).
 *
 * The form inside the overlay uses the standard AJAX handler in forms.js.
 * This file handles only the trigger + overlay visibility logic.
 *
 * Usage: wp_enqueue_script('gwill-forms-exit') from the template part.
 *        Both this file and forms.js must be enqueued.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

( function () {
	'use strict';

	var OVERLAY_SEL  = '.gwill-exit-intent';
	var SEEN_KEY     = 'gwill_exit_seen';
	var RESPAWN_DAYS = 7;
	var overlay      = document.querySelector( OVERLAY_SEL );

	if ( ! overlay ) return;

	var triggered = false;

	// ── Close button + Escape key — ALWAYS attached ───────────────────────────
	//
	// These must be wired up unconditionally, even when alreadySeen() is true
	// and the automatic mouseleave/scroll triggers below are skipped. The
	// overlay element is always present in the DOM (just `hidden`) — anything
	// that reveals it directly (the Contact Demo page's manual trigger button
	// toggles hidden/aria-hidden itself, bypassing show() entirely) must still
	// be able to close it.
	//
	// BUG (fixed 1.0.47): this used to live below `if (alreadySeen()) return;`,
	// inside the same early-return as the auto-trigger listeners. On any page
	// load within RESPAWN_DAYS of a previous trigger — the normal case the
	// moment this feature gets tested more than once — the whole script bailed
	// out before this listener was ever registered. The overlay could still be
	// forced open (e.g. by the demo's manual trigger), but its close button did
	// nothing: the click handler that would call hide() had never been attached
	// on that page load. Moving these two listeners above the throttle guard
	// fixes it for every code path that can show the overlay, not just the
	// natural mouseleave/scroll triggers.

	overlay.addEventListener( 'click', function ( e ) {
		// closest() (not matches()) so this keeps working if the close button's
		// glyph is ever changed from a plain &times; text node to a wrapped
		// <svg>/<span> icon — matches() only tests e.target itself and would
		// silently stop matching the moment a child element absorbs the click.
		if (
			e.target.closest( '.gwill-exit-intent__close' ) ||
			e.target === overlay
		) {
			hide();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && overlay.getAttribute( 'aria-hidden' ) === 'false' ) {
			hide();
		}
	} );

	// ── Guard: skip automatic triggers if already shown recently ─────────────
	//
	// Gates ONLY the mouseleave/scroll auto-triggers below. Does not affect
	// the close/Escape listeners above — see BUG note.

	if ( alreadySeen() ) return;

	// ── Desktop: mouseleave on document ──────────────────────────────────────

	document.addEventListener( 'mouseleave', function ( e ) {
		// e.clientY <= 0 means cursor left through the top of the viewport
		// (heading toward browser tabs / address bar).
		if ( ! triggered && e.clientY <= 0 ) {
			show();
		}
	} );

	// ── Mobile: 75% scroll depth ─────────────────────────────────────────────

	var scrollFired = false;
	window.addEventListener( 'scroll', function () {
		if ( triggered || scrollFired ) return;
		var scrolled = window.scrollY + window.innerHeight;
		var total    = document.documentElement.scrollHeight;
		if ( scrolled / total >= 0.75 ) {
			scrollFired = true;
			show();
		}
	}, { passive: true } );

	// ── Show / hide ───────────────────────────────────────────────────────────

	// Track the element focused before the overlay opened so we can restore it.
	var lastFocused = null;

	function show() {
		triggered = true;
		markSeen();

		lastFocused = document.activeElement;

		overlay.setAttribute( 'aria-hidden', 'false' );
		overlay.removeAttribute( 'hidden' );

		// Focus first input in the overlay form.
		var first = overlay.querySelector( 'input:not([type="hidden"]), textarea' );
		if ( first ) setTimeout( function () { first.focus(); }, 50 );

		// Trap scroll and keyboard focus while overlay is open.
		document.body.style.overflow = 'hidden';
		document.addEventListener( 'keydown', trapFocus );
	}

	function hide() {
		overlay.setAttribute( 'aria-hidden', 'true' );
		overlay.setAttribute( 'hidden', '' );
		document.body.style.overflow = '';
		document.removeEventListener( 'keydown', trapFocus );

		// Return focus to wherever it was before the overlay opened.
		if ( lastFocused && typeof lastFocused.focus === 'function' ) {
			lastFocused.focus();
		}
	}

	// ── Focus trap ────────────────────────────────────────────────────────────
	//
	// Cycles focus within the overlay on Tab / Shift+Tab so keyboard users
	// cannot accidentally reach content behind the overlay while it is open.
	// Complies with WCAG 2.4.3 Focus Order and APG Modal Dialog pattern.

	function trapFocus( e ) {
		if ( e.key !== 'Tab' ) return;

		var focusable = Array.from(
			overlay.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), ' +
				'textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
			)
		);

		if ( focusable.length === 0 ) return;

		var first = focusable[0];
		var last  = focusable[ focusable.length - 1 ];

		if ( e.shiftKey ) {
			// Shift+Tab from first focusable → wrap to last.
			if ( document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			}
		} else {
			// Tab from last focusable → wrap to first.
			if ( document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	}

	// ── Storage helpers ───────────────────────────────────────────────────────

	function alreadySeen() {
		try {
			var raw = localStorage.getItem( SEEN_KEY );
			if ( ! raw ) return false;
			var ts = parseInt( raw, 10 );
			return Date.now() - ts < RESPAWN_DAYS * 864e5;
		} catch ( e ) {
			return false;
		}
	}

	function markSeen() {
		try { localStorage.setItem( SEEN_KEY, Date.now().toString() ); } catch ( e ) { /* private mode */ }
	}

} )();
