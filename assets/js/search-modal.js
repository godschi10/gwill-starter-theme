/**
 * search-modal.js — Combo B
 *
 * Modal overlay with debounced live-search via REST API.
 *
 * Data source:  GET /wp-json/gwill/v1/search?s=<term>&per_page=5
 *               Configured by gwill_execute_search() on the PHP side,
 *               so all backend swap hooks (SearchWP, Algolia, etc.) apply.
 *
 * Keyboard:
 *   Trigger button         → open modal, focus input
 *   ArrowDown (in input)   → focus first result
 *   ArrowDown / ArrowUp    → navigate results
 *   Enter (on result)      → follow result link
 *   Enter (on empty input) → submit form → search.php
 *   Escape                 → close modal, restore focus to trigger
 *   Tab / Shift+Tab        → trapped within modal while open
 *
 * Accessibility:
 *   role="dialog" + aria-modal + aria-hidden toggled
 *   role="listbox" on results container
 *   role="option" + aria-selected on each result row
 *   aria-expanded on the input
 *
 * i18n strings and REST URL injected via GwillSearch (wp_localize_script).
 *
 * @package GWill_Starter
 * @since   1.0.23
 */
( function () {
	'use strict';

	// ── Constants ─────────────────────────────────────────────────────────────

	var REST_URL    = ( window.GwillSearch && GwillSearch.restUrl ) || '/wp-json/gwill/v1/search';
	var I18N        = ( window.GwillSearch && GwillSearch.i18n ) || {};
	var T_LOADING   = I18N.loading  || 'Searching\u2026';
	var T_NONE      = I18N.noResults || 'No results found.';
	var T_ERROR     = I18N.error    || 'Search unavailable. Press Enter to search.';
	var T_VIEW_ALL  = I18N.viewAll  || 'View all results \u2192';

	var DEBOUNCE_MS = 300;
	var MIN_CHARS   = 2;
	var PER_PAGE    = 5;

	// ── DOM references ────────────────────────────────────────────────────────

	var trigger  = document.querySelector( '[data-gwill-search-trigger]' );
	var modal    = document.querySelector( '[data-gwill-search-modal]' );

	if ( ! trigger || ! modal ) return;

	var input    = modal.querySelector( '.gwill-search-modal__input' );
	var results  = modal.querySelector( '.gwill-search-modal__results' );
	var clearBtn = modal.querySelector( '.gwill-search-modal__clear' );
	var closeBtn = modal.querySelector( '[data-gwill-search-close]' );
	var backdrop = modal.querySelector( '[data-gwill-search-backdrop]' );

	// ── State ────────────────────────────────────────────────────────────────

	var xhrTimer     = null;   // debounce timer ID
	var activeIndex  = -1;     // keyboard-selected result index (-1 = none)
	var lastQuery    = '';     // last fetched term (avoids duplicate requests)
	var currentXhr   = null;   // active fetch AbortController

	// ── Open / close ─────────────────────────────────────────────────────────

	function open() {
		modal.removeAttribute( 'hidden' );
		modal.setAttribute( 'aria-hidden', 'false' );
		document.body.style.overflow = 'hidden';
		document.addEventListener( 'keydown', handleKeydown );
		setTimeout( function () { if ( input ) input.focus(); }, 50 );
	}

	function close() {
		modal.setAttribute( 'hidden', '' );
		modal.setAttribute( 'aria-hidden', 'true' );
		document.body.style.overflow = '';
		document.removeEventListener( 'keydown', handleKeydown );
		clearResults();
		if ( input ) input.value = '';
		if ( clearBtn ) clearBtn.hidden = true;
		if ( input ) input.setAttribute( 'aria-expanded', 'false' );
		activeIndex = -1;
		lastQuery   = '';
		trigger.focus();
	}

	// ── Results rendering ────────────────────────────────────────────────────

	function clearResults() {
		if ( results ) results.innerHTML = '';
		activeIndex = -1;
		if ( input ) input.setAttribute( 'aria-expanded', 'false' );
	}

	function showStatus( message ) {
		if ( ! results ) return;
		results.innerHTML = '<p class="gwill-search-modal__status" role="status" aria-live="polite">'
			+ escapeHtml( message ) + '</p>';
		input.setAttribute( 'aria-expanded', 'false' );
	}

	function renderResults( items, term ) {
		if ( ! results ) return;

		if ( ! items.length ) {
			showStatus( T_NONE );
			return;
		}

		var html = '';
		items.forEach( function ( item, i ) {
			html += '<a'
				+ ' class="gwill-search-modal__result"'
				+ ' role="option"'
				+ ' aria-selected="false"'
				+ ' href="' + escapeAttr( item.url ) + '"'
				+ ' data-index="' + i + '"'
				+ '>'
				+ '<span class="gwill-search-modal__result-title">' + escapeHtml( item.title ) + '</span>'
				+ '<span class="gwill-search-modal__result-type">' + escapeHtml( item.type ) + '</span>'
				+ ( item.excerpt
					? '<span class="gwill-search-modal__result-excerpt">' + escapeHtml( item.excerpt ) + '</span>'
					: '' )
				+ '</a>';
		} );

		// "View all results" link falls through to search.php.
		html += '<a'
			+ ' class="gwill-search-modal__view-all"'
			+ ' href="' + escapeAttr( home_url() + '?s=' + encodeURIComponent( term ) ) + '"'
			+ '>' + escapeHtml( T_VIEW_ALL ) + '</a>';

		results.innerHTML = html;
		input.setAttribute( 'aria-expanded', 'true' );
		activeIndex = -1;
	}

	// ── Fetch ────────────────────────────────────────────────────────────────

	function fetchResults( term ) {

		// Abort previous in-flight request.
		if ( currentXhr ) {
			currentXhr.abort();
			currentXhr = null;
		}

		if ( term === lastQuery ) return;
		lastQuery = term;

		var controller = new AbortController();
		currentXhr = controller;

		showStatus( T_LOADING );

		fetch( REST_URL + '?s=' + encodeURIComponent( term ) + '&per_page=' + PER_PAGE, {
			signal:  controller.signal,
			headers: { 'Accept': 'application/json' },
		} )
		.then( function ( res ) {
			if ( ! res.ok ) throw new Error( 'HTTP ' + res.status );
			return res.json();
		} )
		.then( function ( data ) {
			currentXhr = null;
			renderResults( data, term );
		} )
		.catch( function ( err ) {
			currentXhr = null;
			if ( err.name === 'AbortError' ) return; // intentional cancel — do nothing
			showStatus( T_ERROR );
		} );
	}

	// ── Keyboard navigation ──────────────────────────────────────────────────

	function getResultItems() {
		return results
			? Array.from( results.querySelectorAll( '.gwill-search-modal__result' ) )
			: [];
	}

	function setActive( index ) {
		var items = getResultItems();
		if ( ! items.length ) return;

		// Clamp index.
		index = Math.max( -1, Math.min( index, items.length - 1 ) );

		// Clear old selection.
		items.forEach( function ( el ) { el.setAttribute( 'aria-selected', 'false' ); } );

		if ( index >= 0 ) {
			items[ index ].setAttribute( 'aria-selected', 'true' );
			items[ index ].focus();
		} else {
			input.focus();
		}

		activeIndex = index;
	}

	// ── Focus trap ───────────────────────────────────────────────────────────

	function getFocusable() {
		return Array.from(
			modal.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), ' +
				'[tabindex]:not([tabindex="-1"])'
			)
		);
	}

	// ── Global keydown handler (active only while modal is open) ─────────────

	function handleKeydown( e ) {

		switch ( e.key ) {

			case 'Escape':
				e.preventDefault();
				close();
				break;

			case 'ArrowDown':
				e.preventDefault();
				// If focus is in input, move to first result.
				if ( document.activeElement === input ) {
					setActive( 0 );
				} else {
					setActive( activeIndex + 1 );
				}
				break;

			case 'ArrowUp':
				e.preventDefault();
				if ( activeIndex <= 0 ) {
					// Move back up to the input.
					activeIndex = -1;
					input.focus();
				} else {
					setActive( activeIndex - 1 );
				}
				break;

			case 'Tab': {
				// Trap focus within the modal.
				var focusable = getFocusable();
				if ( ! focusable.length ) break;
				var first = focusable[0];
				var last  = focusable[ focusable.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) {
					e.preventDefault();
					last.focus();
				} else if ( ! e.shiftKey && document.activeElement === last ) {
					e.preventDefault();
					first.focus();
				}
				break;
			}
		}
	}

	// ── Input handler ────────────────────────────────────────────────────────

	input.addEventListener( 'input', function () {
		var term = input.value.trim();

		// Show / hide clear button.
		if ( clearBtn ) clearBtn.hidden = ! term;

		if ( term.length < MIN_CHARS ) {
			clearTimeout( xhrTimer );
			clearResults();
			return;
		}

		// Debounce the fetch.
		clearTimeout( xhrTimer );
		xhrTimer = setTimeout( function () { fetchResults( term ); }, DEBOUNCE_MS );
	} );

	// ── Clear button ─────────────────────────────────────────────────────────

	if ( clearBtn ) {
		clearBtn.addEventListener( 'click', function () {
			input.value = '';
			clearBtn.hidden = true;
			clearResults();
			input.focus();
		} );
	}

	// ── Open / close events ──────────────────────────────────────────────────

	trigger.addEventListener( 'click', open );

	if ( closeBtn ) closeBtn.addEventListener( 'click', close );

	if ( backdrop ) {
		backdrop.addEventListener( 'click', close );
	}

	// ── Utilities ────────────────────────────────────────────────────────────

	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function escapeAttr( str ) {
		return String( str ).replace( /"/g, '&quot;' );
	}

	function home_url() {
		// GwillSearch.homeUrl comes from PHP's home_url('/') (inc/enqueue.php) and
		// correctly includes any subdirectory path a WordPress install might be
		// running in (e.g. example.com/blog/). window.location.origin only ever
		// gives protocol + domain — on a subdirectory install that silently
		// dropped the path, sending "View all results" to example.com/?s=term
		// instead of example.com/blog/?s=term. Falls back to location.origin only
		// if GwillSearch wasn't localized at all (e.g. this file loaded in
		// isolation outside the normal WordPress enqueue pipeline).
		if ( typeof GwillSearch !== 'undefined' && GwillSearch.homeUrl ) {
			return GwillSearch.homeUrl;
		}
		return window.location.origin + '/';
	}

}() );
