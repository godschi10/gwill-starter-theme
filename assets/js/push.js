/* global gwillPush, Notification */
/* GWill Starter - smart notification bell.
 *
 * PORTED from gwill-finance-theme v1.2.3 (proven working on real devices),
 * itself the gwill-tech-theme lineage. Every law from docs/LAWS.md is baked
 * into this file:
 *
 *   L3 - requestPermission() BEFORE pushManager.subscribe(): a bell that
 *        goes straight to subscribe() is dead on any device that never
 *        asked (Chrome rejects with NotAllowedError, no prompt, silently).
 *   L5 - bind ALL instances via querySelectorAll('#gwill-bell'), never
 *        getElementById: any build may render the bell in more than one
 *        footer/column.
 *   L6 - every async action runs through settle(): a hard 12s timeout that
 *        ALWAYS restores buttons and clears `busy` (resolve/reject/timeout/
 *        exception) - a button may never die busy.
 *   L7 - persistent opt-out flag: disable() sets it before anything async;
 *        the init self-heal checks it so "Turn off" survives refresh.
 *
 * v1.2.3 lineage - full browser coverage:
 *   - Correct per-browser device detection (Firefox BEFORE Android so
 *     Firefox for Android is not misreported as Chrome; Edge/Opera/Samsung
 *     Internet detected explicitly).
 *   - NOSUPPORT state: the bell STAYS and opens a panel with a notice +
 *     supported-browser list (no silent removal).
 *   - Error mapping: NotAllowedError → blocked, NotSupportedError →
 *     unsupported, everything else → retryable error.
 *
 *   - Real state on load via pushManager.getSubscription() (no optimistic
 *     guess); permission changes in browser settings are reflected live.
 *   - Tap opens a status panel (never a blind toggle):
 *       off        → pitch + Enable button
 *       subscribed → "You're subscribed" + Turn off notifications
 *       blocked    → device-specific unblock steps + Check again
 *       unsupported→ notice + supported-browser list
 *       error      → Try again
 *   - Esc / X / outside-click close; focus restored to the tapped bell.
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

/*
Table of Contents
1. config + state
2. REST helper
3. capability + device detection
4. state application (bells + panel)
5. panel build
6. panel helpers (steps, loading, render, open, close)
7. outside-click + keyboard close
8. settle - busy-safe async runner (L6)
9. opt-out flag (L7)
10. registration + subscribe helpers
11. enable / disable / refreshPermission
12. init
*/

( function () {
	'use strict';

	/* ── 1. config + state ──────────────────────────────────────────── */

	var PUBLIC_KEY = gwillPush.publicKey;
	var REST_URL   = gwillPush.restUrl.replace( /\/$/, '' );
	var NONCE      = gwillPush.nonce;
	var STR        = gwillPush.strings;
	var bell       = null;
	var bells      = [];

	var UNSET = 0, SUBSCRIBED = 1, BLOCKED = 2, ERRWIN = 3, NOSUPPORT = 4;
	var state = UNSET;
	var panel = null, busy = false;

	/* ── 2. REST helper ─────────────────────────────────────────────── */

	function post( route, body ) {
		return fetch( REST_URL + '/' + route, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': NONCE
			},
			body: JSON.stringify( body )
		} ).then( function ( r ) {
			if ( ! r.ok ) {
				return r.json().catch( function () {
					return Promise.reject( new Error( 'server ' + r.status ) );
				} ).then( function ( j ) {
					return Promise.reject( new Error( ( j && j.message ) || ( 'server ' + r.status ) ) );
				} );
			}
			return r.json();
		} );
	}

	/* ── 3. capability + device detection ───────────────────────────── */

	// Push-capable browsers: Chrome 49+, Firefox 44+, Edge 17+ (Chromium),
	// Opera 39+, Safari 16+ (macOS) / iOS 16.4+ (installed), Samsung
	// Internet 5.0+. Everything else gets the NOSUPPORT notice.
	function canPush() {
		return ( 'Notification' in window ) &&
			( 'serviceWorker' in navigator ) &&
			( 'PushManager' in window ) &&
			window.isSecureContext;
	}

	function detectDevice() {
		var ua  = navigator.userAgent || '';
		var iOS = /iPad|iPhone|iPod/.test( ua ) ||
			( navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1 );
		if ( iOS ) {
			return 'ios-safari';
		}
		// ORDER MATTERS: Firefox for Android contains BOTH "Android" and
		// "Firefox/" - check the Android+Firefox pair before either alone.
		if ( /Android/.test( ua ) && /Firefox\//.test( ua ) ) {
			return 'firefox-android';
		}
		if ( /Android/.test( ua ) && /SamsungBrowser\//.test( ua ) ) {
			return 'samsung-internet';
		}
		if ( /Android/.test( ua ) ) {
			return 'android-chrome';
		}
		if ( /Firefox\//.test( ua ) ) {
			return 'desktop-firefox';
		}
		if ( /Edg\//.test( ua ) ) {
			return 'desktop-edge';
		}
		if ( /OPR\//.test( ua ) || /Opera/.test( ua ) ) {
			return 'desktop-opera';
		}
		if ( /Chrome\//.test( ua ) ) {
			return 'desktop-chrome';
		}
		if ( /Safari\//.test( ua ) ) {
			return 'desktop-safari';
		}
		return 'other';
	}

	/* ── 4. state application ────────────────────────────────────────── */

	function currentState() {
		if ( ! canPush() ) {
			return NOSUPPORT;
		}
		if ( Notification.permission === 'denied' ) {
			return BLOCKED;
		}
		return UNSET; // granted-but-unknown resolved by the init check
	}

	function setState( s ) {
		state = s;
		for ( var b = 0; b < bells.length; b++ ) {
			applyBellState( bells[ b ], s );
		}
		if ( panel && ! panel.hidden ) {
			renderPanel();
		}
	}

	function applyBellState( el, s ) {
		if ( ! el ) { return; }
		var pressed = ( SUBSCRIBED === s );
		el.setAttribute( 'aria-pressed', pressed ? 'true' : 'false' );
		el.setAttribute( 'data-push-state', pressed ? 'on' : 'off' );
		var label = pressed ? el.getAttribute( 'data-label-on' ) :
			( BLOCKED === s ? STR.blocked : el.getAttribute( 'data-label-off' ) );
		el.setAttribute( 'aria-label', label );
		el.setAttribute( 'title', BLOCKED === s
			? STR.blocked + ' - tap for unblock steps'
			: ( STR.title || el.getAttribute( 'data-label-off' ) ) );
		el.classList.toggle( 'is-on', pressed );
		el.classList.toggle( 'is-blocked', BLOCKED === s );
		// Update the visible text node (button markup is <svg>…</svg>TEXT).
		var txt = null;
		for ( var i = el.childNodes.length - 1; i >= 0; i-- ) {
			var n = el.childNodes[ i ];
			if ( n.nodeType === 3 && n.textContent.trim().length > 0 ) {
				txt = n;
				break;
			}
		}
		if ( txt ) {
			txt.textContent = label;
		}
	}

	/* ── 5. panel build ──────────────────────────────────────────────── */

	function buildPanel() {
		panel = document.createElement( 'div' );
		panel.className = 'gwill-bell-panel';
		panel.id = 'gwill-bell-panel';
		panel.setAttribute( 'role', 'dialog' );
		panel.setAttribute( 'aria-modal', 'false' );
		panel.setAttribute( 'aria-labelledby', 'gwill-bell-panel-title' );
		panel.hidden = true;

		var title = document.createElement( 'h2' );
		title.className = 'gbp-title';
		title.id = 'gwill-bell-panel-title';
		title.textContent = STR.title;

		var status = document.createElement( 'p' );
		status.className = 'gbp-status';

		var body = document.createElement( 'p' );
		body.className = 'gbp-body';

		var steps = document.createElement( 'ol' );
		steps.className = 'gbp-steps';

		var other = document.createElement( 'p' );
		other.className = 'gbp-other';

		var actions = document.createElement( 'div' );
		actions.className = 'gbp-actions';
		var primary = document.createElement( 'button' );
		primary.type = 'button';
		primary.className = 'gbp-btn gbp-btn--primary';
		var secondary = document.createElement( 'button' );
		secondary.type = 'button';
		secondary.className = 'gbp-btn gbp-btn--ghost';
		secondary.hidden = true;
		actions.appendChild( primary );
		actions.appendChild( secondary );

		var x = document.createElement( 'button' );
		x.type = 'button';
		x.className = 'gbp-x';
		x.setAttribute( 'aria-label', STR.close );
		x.innerHTML = '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
		x.addEventListener( 'click', function ( e ) { e.stopPropagation(); closePanel(); } );

		panel.appendChild( x );
		panel.appendChild( title );
		panel.appendChild( status );
		panel.appendChild( body );
		panel.appendChild( steps );
		panel.appendChild( other );
		panel.appendChild( actions );

		primary.addEventListener( 'click', function () {
			if ( busy ) { return; }
			if ( BLOCKED === state ) {
				refreshPermission();
			} else if ( ERRWIN === state || UNSET === state ) {
				enable();
			}
			// NOSUPPORT: no action button - notice only.
		} );
		secondary.addEventListener( 'click', function () {
			if ( ! busy ) { disable(); }
		} );

		// Keep a click inside the panel from triggering the document close.
		panel.addEventListener( 'click', function ( e ) { e.stopPropagation(); } );

		document.body.appendChild( panel );
	}

	/* ── 6. panel helpers ────────────────────────────────────────────── */

	function fillSteps( key ) {
		var steps = panel.querySelector( '.gbp-steps' );
		var other = panel.querySelector( '.gbp-other' );
		var list  = ( STR.steps && STR.steps[ key ] ) || STR.steps.other || [];
		steps.hidden = false;
		other.hidden = false;
		steps.innerHTML = '';
		list.forEach( function ( raw ) {
			var li = document.createElement( 'li' );
			li.textContent = String( raw ).replace( '%s', STR.siteName || '' );
			steps.appendChild( li );
		} );
		other.textContent = STR.otherDevices;
	}

	function hideSteps() {
		panel.querySelector( '.gbp-steps' ).hidden = true;
		panel.querySelector( '.gbp-other' ).hidden = true;
	}

	// Swap a button to spinner + progress label, disable it, remember its
	// idle label so renderPanel()/settle() restores it cleanly.
	function setButtonLoading( btn, on, busyLabel ) {
		if ( ! btn ) { return; }
		if ( on ) {
			if ( ! btn.getAttribute( 'data-idle' ) ) {
				btn.setAttribute( 'data-idle', btn.textContent );
			}
			btn.classList.add( 'is-loading' );
			btn.disabled = true;
			btn.setAttribute( 'aria-busy', 'true' );
			btn.innerHTML = '<span class="gbp-spin" aria-hidden="true"></span>' + busyLabel;
		} else {
			btn.classList.remove( 'is-loading' );
			btn.disabled = false;
			btn.removeAttribute( 'aria-busy' );
			var idle = btn.getAttribute( 'data-idle' );
			if ( idle !== null ) {
				btn.textContent = idle;
				btn.removeAttribute( 'data-idle' );
			}
		}
	}

	function renderPanel() {
		if ( ! panel ) { return; }
		var status    = panel.querySelector( '.gbp-status' );
		var body      = panel.querySelector( '.gbp-body' );
		var primary   = panel.querySelector( '.gbp-btn--primary' );
		var secondary = panel.querySelector( '.gbp-btn--ghost' );
		var st, bd;

		if ( SUBSCRIBED === state ) {
			st = '● ' + STR.statusOn;
			bd = STR.bodyOn;
			primary.hidden = true;
			secondary.hidden = false;
			secondary.textContent = STR.unsubscribe;
			hideSteps();
		} else if ( BLOCKED === state ) {
			st = '✕ ' + STR.statusBlocked;
			bd = STR.bodyBlocked;
			primary.hidden = false;
			primary.textContent = STR.checkAgain;
			secondary.hidden = true;
			fillSteps( detectDevice() );
		} else if ( NOSUPPORT === state ) {
			st = '✕ ' + STR.statusUnsupported;
			bd = STR.bodyUnsupported + ' ' + STR.browserList;
			primary.hidden = true;
			secondary.hidden = true;
			hideSteps();
		} else if ( ERRWIN === state ) {
			st = '✕ ' + STR.statusError;
			bd = STR.bodyError;
			primary.hidden = false;
			primary.textContent = STR.tryAgain;
			secondary.hidden = true;
			hideSteps();
		} else {
			st = '○ ' + STR.statusOff;
			bd = STR.bodyOff;
			primary.hidden = false;
			primary.textContent = STR.subscribe;
			secondary.hidden = true;
			hideSteps();
		}

		status.textContent = st;
		status.classList.toggle( 'gbp-status--on',  SUBSCRIBED === state );
		status.classList.toggle( 'gbp-status--bad', BLOCKED === state || ERRWIN === state || NOSUPPORT === state );
		body.textContent = bd;
	}

	function openPanel( sourceBell ) {
		if ( ! panel ) { buildPanel(); }
		renderPanel();
		panel.hidden = false;
		panel.classList.add( 'is-open' );
		if ( sourceBell ) {
			sourceBell.setAttribute( 'aria-expanded', 'true' );
		}
		var focusable = panel.querySelector( '.gbp-btn--primary' );
		if ( focusable && ! focusable.hidden ) {
			focusable.focus();
		} else {
			panel.querySelector( '.gbp-x' ).focus();
		}
	}

	function closePanel() {
		if ( ! panel ) { return; }
		panel.hidden = true;
		panel.classList.remove( 'is-open' );
		for ( var b = 0; b < bells.length; b++ ) {
			bells[ b ].setAttribute( 'aria-expanded', 'false' );
		}
		// preventScroll: the bell may sit in a footer, off-screen when the
		// reader is mid-article - restoring focus must never yank the page.
		if ( bell ) {
			bell.focus( { preventScroll: true } );
		}
	}

	/* ── 7. outside-click + keyboard close ───────────────────────────── */

	function onDocClick( e ) {
		if ( ! panel || panel.hidden ) { return; }
		var inBell = false;
		for ( var b = 0; b < bells.length; b++ ) {
			if ( bells[ b ].contains( e.target ) ) { inBell = true; break; }
		}
		if ( inBell || panel.contains( e.target ) ) { return; }
		closePanel();
	}

	function onKey( e ) {
		if ( 'Escape' === e.key && panel && ! panel.hidden ) {
			closePanel();
		}
	}

	/* ── 8. settle - busy-safe async runner (L6) ─────────────────────── */

	/**
	 * Wraps a promise chain so `busy` can never be stuck true and the panel
	 * always settles. Even if the server POST is slow or the SW is in a
	 * weird state, the user's tap is not silently swallowed.
	 *
	 * @param {Function} start     Returns a promise. Called with a done-callback.
	 * @param {number}   ms        Max ms to wait before settling (default 12s).
	 * @param {Function} fallback  Called on timeout or chain failure.
	 */
	function settle( start, ms, fallback ) {
		ms = ms || 12000;
		busy = true;
		var settled = false;

		function done( fn ) {
			if ( settled ) { return; }
			settled = true;
			busy = false;
			if ( panel && ! panel.hidden ) {
				var p = panel.querySelector( '.gbp-btn--primary' );
				var s = panel.querySelector( '.gbp-btn--ghost' );
				setButtonLoading( p, false );
				setButtonLoading( s, false );
			}
			if ( fn ) { fn(); }
			if ( panel && ! panel.hidden ) { renderPanel(); }
		}

		var timer = setTimeout( function () {
			done( fallback || null );
		}, ms );

		try {
			var p = start( function () {
				clearTimeout( timer );
				done();
			} );
			if ( p && typeof p.then === 'function' ) {
				p.then( function () {
					clearTimeout( timer );
					done();
				}, function () {
					clearTimeout( timer );
					done( fallback || null );
				} );
			} else {
				clearTimeout( timer );
				done();
			}
		} catch ( e ) {
			clearTimeout( timer );
			done( fallback || null );
		}
	}

	/* ── 9. opt-out flag (L7) ────────────────────────────────────────── */

	var OPTOUT_KEY = 'gwill_push_opted_out';

	function optedOut() {
		try {
			return '1' === window.localStorage.getItem( OPTOUT_KEY );
		} catch ( e ) { return false; }
	}

	function markOptOut( on ) {
		try {
			if ( on ) {
				window.localStorage.setItem( OPTOUT_KEY, '1' );
			} else {
				window.localStorage.removeItem( OPTOUT_KEY );
			}
		} catch ( e ) { /* storage unavailable - best effort */ }
	}

	/* ── 10. registration + subscribe helpers ────────────────────────── */

	function getRegistration() {
		if ( ! canPush() ) {
			return Promise.reject( new Error( 'no-sw' ) );
		}
		return navigator.serviceWorker.ready;
	}

	function urlBase64ToUint8Array( b64 ) {
		var pad = b64.length % 4 === 0 ? '' : '='.repeat( 4 - ( b64.length % 4 ) );
		var b64std = ( b64 + pad ).replace( /-/g, '+' ).replace( /_/g, '/' );
		var bin = atob( b64std );
		var arr = new Uint8Array( bin.length );
		for ( var i = 0; i < bin.length; i++ ) {
			arr[ i ] = bin.charCodeAt( i );
		}
		return arr;
	}

	function mapError( err ) {
		var name = ( err && err.name ) || '';
		if ( ! canPush() ) {
			return NOSUPPORT;
		}
		if ( 'NotAllowedError' === name ) {
			return BLOCKED;
		}
		if ( 'NotSupportedError' === name || 'AbortError' === name ) {
			return NOSUPPORT;
		}
		if ( 'Notification' in window && Notification.permission === 'denied' ) {
			return BLOCKED;
		}
		return ERRWIN;
	}

	/* ── 11. enable / disable / refreshPermission ─────────────────────── */

	function enable() {
		settle( function () {
			if ( panel && ! panel.hidden ) {
				setButtonLoading( panel.querySelector( '.gbp-btn--primary' ), true, STR.enabling );
			}
			// L3: permission BEFORE subscribe - the dead-bell law.
			return getRegistration().then( function ( reg ) {
				return Notification.requestPermission().then( function ( p ) {
					if ( 'granted' !== p ) {
						setState( 'denied' === p ? BLOCKED : currentState() );
						return;
					}
					return reg.pushManager.subscribe( {
						userVisibleOnly: true,
						applicationServerKey: urlBase64ToUint8Array( PUBLIC_KEY )
					} );
				} );
			} ).then( function ( sub ) {
				if ( ! sub ) { return; } // permission path already settled state
				var keys = sub.toJSON().keys || {};
				return post( 'subscribe', {
					endpoint: sub.endpoint,
					p256dh: keys.p256dh,
					auth: keys.auth
				} ).then( function () {
					// An explicit subscribe clears the opt-out flag  - 
					// the user has chosen back in (L7).
					markOptOut( false );
					setState( SUBSCRIBED );
				} );
			} ).catch( function ( err ) {
				setState( mapError( err ) );
			} );
		} );
	}

	function disable() {
		// Best-effort server delete + ALWAYS remove the local browser
		// subscription. The browser unsubscribe is what actually stops
		// notifications on this device; the server row is cleanup for the
		// send path. Wrapped in settle() so a hung SW/fetch can never leave
		// the button dead (L6).
		// L7: persist the opt-out flag BEFORE anything async so the init
		// self-heal cannot silently re-subscribe on the next page load.
		markOptOut( true );
		settle( function () {
			if ( panel && ! panel.hidden ) {
				setButtonLoading( panel.querySelector( '.gbp-btn--ghost' ), true, STR.turningOff );
			}
			return getRegistration().then( function ( reg ) {
				return reg.pushManager.getSubscription();
			} ).then( function ( sub ) {
				var endpoint = sub ? sub.endpoint : null;
				var done = endpoint ? post( 'unsubscribe', { endpoint: endpoint } ) : Promise.resolve();
				return Promise.resolve( done ).then( function () {
					if ( sub ) { sub.unsubscribe(); }
					setState( UNSET );
				} );
			} );
		}, 12000, function () {
			// Timeout / failure: still remove the local subscription so the
			// user is unsubscribed even if the server call is stuck.
			return getRegistration().then( function ( reg ) {
				return reg.pushManager.getSubscription();
			} ).then( function ( sub ) {
				if ( sub ) { sub.unsubscribe(); }
				setState( UNSET );
			} ).catch( function () {
				setState( ERRWIN );
			} );
		} );
	}

	function refreshPermission() {
		if ( 'Notification' in window && 'granted' === Notification.permission ) {
			// Unblock detected - self-heal straight back to subscribed.
			enable();
			return;
		}
		setState( currentState() );
	}

	/* ── 12. init ────────────────────────────────────────────────────── */

	function onBell( evt ) {
		if ( evt && evt.currentTarget ) {
			bell = evt.currentTarget;
		}
		if ( panel && ! panel.hidden ) {
			closePanel();
		} else {
			openPanel( bell );
		}
	}

	function init() {
		// L5: bind ALL instances - never getElementById.
		bells = Array.prototype.slice.call(
			document.querySelectorAll( '#gwill-bell' )
		);
		if ( ! bells.length ) {
			return;
		}
		bell = bells[ 0 ];

		if ( ! canPush() ) {
			// Unsupported browser: the bell STAYS and opens the notice
			// panel - no silent removal.
			setState( NOSUPPORT );
			for ( var k = 0; k < bells.length; k++ ) {
				bells[ k ].addEventListener( 'click', onBell );
			}
			document.addEventListener( 'click', onDocClick );
			document.addEventListener( 'keydown', onKey );
			return;
		}

		setState( currentState() );
		for ( var j = 0; j < bells.length; j++ ) {
			bells[ j ].addEventListener( 'click', onBell );
		}
		document.addEventListener( 'click', onDocClick );
		document.addEventListener( 'keydown', onKey );

		// Real subscription state (replaces the old optimistic guess).
		getRegistration().then( function ( reg ) {
			return reg.pushManager.getSubscription();
		} ).then( function ( sub ) {
			if ( sub ) {
				setState( SUBSCRIBED );
				return;
			}
			// L7: NEVER silently re-subscribe after the user turned
			// notifications off.
			if ( optedOut() ) {
				setState( UNSET );
				return;
			}
			// Self-heal: permission granted but no subscription (cleared
			// storage, reinstalled SW). Silent - no prompt appears.
			if ( 'granted' === Notification.permission ) {
				enable();
			}
		} ).catch( function () { /* SW not ready; permission state stands */ } );

		// Reflect permission changes made in browser settings while we're open.
		if ( navigator.permissions && navigator.permissions.query ) {
			navigator.permissions.query( { name: 'notifications' } ).then( function ( status ) {
				status.addEventListener( 'change', function () {
					if ( panel && ! panel.hidden && BLOCKED !== state ) {
						setState( currentState() );
					}
				} );
			} ).catch( function () {} );
		}
	}

	// Run once the DOM is ready. If it's already ready (footer script may
	// execute after DOMContentLoaded), run immediately.
	if ( document.readyState === 'loading' ) {
		window.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
