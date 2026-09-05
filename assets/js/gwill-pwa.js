/**
 * GWill Starter - PWA install prompt + service worker registration.
 *
 * PORTED from gwill-finance-theme (tech-theme lineage). Expert pattern
 * (not the generic Chrome mini-infobar): capture the
 * `beforeinstallprompt` event, suppress Chrome's default prompt, and show
 * our OWN token-mapped card (.gpwa) so the install CTA matches the theme.
 * Choice is remembered in localStorage so we never nag: "Not now"
 * suppresses for 7 days; a successful install (or the `appinstalled`
 * event) suppresses permanently. Yields to the cookie-consent banner so
 * the two bottom sheets never stack.
 *
 * ES5 by house law (theme JS must run on old engines).
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

/*
Table of Contents
1. Register the service worker (installability + offline)
2. localStorage helpers (private-mode safe)
3. read / write
4. Build + wire the install card
5. buildCard
6. showCard
7. hideCard
8. Capture the install prompt (suppress Chrome's default infobar)
9. maybeShow
10. Installed via any path (our prompt, or the browser's own)
*/

( function () {
	'use strict';

	var I18N = ( typeof window.GwillPwa !== 'undefined' && window.GwillPwa.i18n ) || {};
	var SW_URL = ( typeof window.GwillPwa !== 'undefined' && window.GwillPwa.swUrl ) || '/sw.js';

	// ── 1. Register the service worker ─────────────────────────────
	// Only on secure origins and where supported. Failures are silent  - 
	// the site works fully without the SW; it only adds offline/PWA.
	if ( 'serviceWorker' in navigator && window.isSecureContext ) {
		window.addEventListener( 'load', function () {
			navigator.serviceWorker.register( SW_URL ).catch( function () { /* no-op */ } );
		} );
	}

	// ── 2. localStorage helpers ────────────────────────────────────
	var KEY = 'gwill_pwa';
	var DISMISS_MS = 7 * 24 * 60 * 60 * 1000; // 7 days

	// ── 3. read / write ────────────────────────────────────────────
	function read() {
		try {
			var raw = window.localStorage.getItem( KEY );
			return raw ? JSON.parse( raw ) : {};
		} catch ( e ) { return {}; }
	}
	function write( obj ) {
		try { window.localStorage.setItem( KEY, JSON.stringify( obj ) ); } catch ( e ) {}
	}

	// ── 4. Build + wire the install card ───────────────────────────
	var deferredPrompt = null;

	// ── 5. buildCard ──────────────────────────────────────────────
	function buildCard() {
		var wrap = document.createElement( 'div' );
		wrap.className = 'gpwa';
		wrap.setAttribute( 'role', 'dialog' );
		wrap.setAttribute( 'aria-label', I18N.installTitle || 'Install' );
		wrap.innerHTML =
			'<div class="gpwa-card">' +
				'<div class="gpwa-mark" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm6-6V11a6 6 0 0 0-4.5-5.8V4a1.5 1.5 0 0 0-3 0v1.2A6 6 0 0 0 6 11v5l-1.7 1.7a1 1 0 0 0 .7 1.7h14a1 1 0 0 0 .7-1.7L18 16Z"/></svg></div>' +
				'<div class="gpwa-body">' +
					'<p class="gpwa-title">' + ( I18N.installTitle || 'Install' ) + '</p>' +
					'<p class="gpwa-copy">' + ( I18N.installCopy || 'Add this site to your home screen - read offline, open in one tap.' ) + '</p>' +
				'</div>' +
				'<div class="gpwa-acts">' +
					'<button type="button" class="gpwa-install" data-gpwa="install">' + ( I18N.installBtn || 'Install app' ) + '</button>' +
					'<button type="button" class="gpwa-later" data-gpwa="later">' + ( I18N.laterBtn || 'Not now' ) + '</button>' +
				'</div>' +
			'</div>';
		return wrap;
	}

	// ── 6. showCard ────────────────────────────────────────────────
	function showCard() {
		if ( document.querySelector( '.gpwa' ) ) return;
		if ( document.body.classList.contains( 'gpwa-installed' ) ) return;
		var state = read();
		if ( state.installed ) return;
		if ( state.dismissed && ( Date.now() - state.dismissed ) < DISMISS_MS ) return;
		var card = buildCard();
		document.body.appendChild( card );
		// Next frame so the slide-up transition runs.
		requestAnimationFrame( function () {
			document.body.classList.add( 'gpwa-visible' );
		} );

		var installBtn = card.querySelector( '[data-gpwa="install"]' );
		var laterBtn = card.querySelector( '[data-gpwa="later"]' );
		if ( installBtn ) {
			installBtn.addEventListener( 'click', function () {
				if ( ! deferredPrompt ) { hideCard(); return; }
				deferredPrompt.prompt();
				var p = deferredPrompt.userChoice;
				if ( p && typeof p.then === 'function' ) {
					p.then( function ( choice ) {
						if ( choice && choice.outcome === 'accepted' ) {
							write( { installed: true } );
							document.body.classList.add( 'gpwa-installed' );
						}
					} ).catch( function () {} );
				}
				deferredPrompt = null;
				hideCard();
			} );
		}
		if ( laterBtn ) {
			laterBtn.addEventListener( 'click', function () {
				write( { dismissed: Date.now() } );
				hideCard();
			} );
		}
	}

	// ── 7. hideCard ───────────────────────────────────────────────
	function hideCard() {
		var card = document.querySelector( '.gpwa' );
		document.body.classList.remove( 'gpwa-visible' );
		if ( card ) {
			// Remove after the slide-down transition.
			setTimeout( function () {
				if ( card.parentNode ) card.parentNode.removeChild( card );
			}, 260 );
		}
	}

	// ── 8. Capture the install prompt ──────────────────────────────
	window.addEventListener( 'beforeinstallprompt', function ( e ) {
		e.preventDefault();
		deferredPrompt = e;
		// Show our card a beat after load (after reading has settled). If
		// the consent banner is open (first visit, no choice stored), wait
		// for it to be resolved so the two bottom sheets never stack.
		setTimeout( maybeShow, 2500 );
	} );

	// ── 9. maybeShow ───────────────────────────────────────────────
	// The starter's consent banner (template-parts/cookie-consent.php +
	// assets/js/cookie-consent.js) hides its banner element with the
	// `hidden` attribute; there is no gconsent-open body class here (that
	// was a finance-theme mechanism). The banner and install card are both
	// bottom sheets, so check for an open consent banner before showing.
	function maybeShow() {
		var consent = document.querySelector( '.gwill-cookie-consent:not([hidden])' );
		if ( consent ) {
			// Watch for the consent banner resolving (its hidden attribute
			// returns once a choice is stored), then show.
			var mo = new MutationObserver( function () {
				if ( ! document.querySelector( '.gwill-cookie-consent:not([hidden])' ) ) {
					mo.disconnect();
					showCard();
				}
			} );
			mo.observe( consent, { attributes: true, attributeFilter: [ 'hidden' ] } );
			return;
		}
		showCard();
	}

	// ── 10. Installed via any path ─────────────────────────────────
	window.addEventListener( 'appinstalled', function () {
		write( { installed: true } );
		document.body.classList.add( 'gpwa-installed' );
		hideCard();
	} );
} )();
