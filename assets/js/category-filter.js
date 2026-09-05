/**
 * AJAX category-filter  -  GWill Starter (v1.7.0).
 *
 * Ported from gwill-tech-theme assets/js/category-filter.js
 * (live-proven), adapted to the starter dialect:
 *   - pills use .gwill-pill (starter's pill class) with .is-active
 *     state  -  tech used .pill / .on;
 *   - the spinner overlay rides the first card's media slot;
 *   - config via wp_localize_script( GwillCategoryFilter ):
 *     ajaxUrl (relative admin-ajax  -  resolves against the browser
 *     origin, works on any domain), i18n.error.
 *
 * Pressing a pill inside a .filter-pills container loads that
 * category's posts fresh from the server (rendered as cards by
 * inc/ajax-filter.php) and swaps them into the target grid, with a
 * CSS spinner while the request is in flight.
 *
 * The container carries everything the handler needs via data
 * attributes:
 *   <div class="filter-pills gwill-pills"
 *        data-target="#grid"           -  CSS selector of the grid to replace
 *        data-per-page="9"
 *        data-action="gwill_filter_posts">
 *
 * Pills carry data-filter="all|<category-slug>".
 *
 * @package GWill_Starter
 * @since   1.7.0
 */

/*
Table of Contents
1. gwillFetch
2. setActive
3. filterGrid
*/

( function () {
	'use strict';

	// ── 1. gwillFetch ─────────────────────────────────────
	/**
	 * fetch() with an XMLHttpRequest fallback for browsers that lack
	 * the Fetch API (Safari <10.1, Chrome <42, Firefox <39). Resolves
	 * with a fetch-compatible response ({ ok, status, json(), text() }).
	 */
	function gwillFetch( url, options ) {
		options = options || {};
		if ( window.fetch ) {
			return fetch( url, options );
		}
		return new Promise( function ( resolve, reject ) {
			var xhr = new XMLHttpRequest();
			xhr.open( options.method || 'GET', url, true );
			if ( options.headers ) {
				for ( var key in options.headers ) {
					if ( Object.prototype.hasOwnProperty.call( options.headers, key ) ) {
						xhr.setRequestHeader( key, options.headers[ key ] );
					}
				}
			}
			xhr.onload = function () {
				resolve( {
					ok: xhr.status >= 200 && xhr.status < 300,
					status: xhr.status,
					json: function () {
						return Promise.resolve( JSON.parse( xhr.responseText ) );
					},
					text: function () {
						return Promise.resolve( xhr.responseText );
					}
				} );
			};
			xhr.onerror = function () {
				reject( new Error( 'Network request failed' ) );
			};
			xhr.send( options.body || null );
		} );
	}

	var cfg = window.GwillCategoryFilter || { ajaxUrl: '/wp-admin/admin-ajax.php', i18n: {} };

	// ── 2. setActive ──────────────────────────────────────
	function setActive( container, btn ) {
		var pills = container.querySelectorAll( '.gwill-pill' );
		Array.prototype.forEach.call( pills, function ( p ) {
			p.classList.remove( 'is-active' );
			p.removeAttribute( 'aria-pressed' );
		} );
		btn.classList.add( 'is-active' );
		btn.setAttribute( 'aria-pressed', 'true' );
	}

	// ── 3. filterGrid ─────────────────────────────────────
	function filterGrid( container, btn, cat ) {
		var targetSelector = container.getAttribute( 'data-target' ) || '';
		var grid = targetSelector ? document.querySelector( targetSelector ) : null;
		if ( ! grid ) { return; }

		var perPage = parseInt( container.getAttribute( 'data-per-page' ) || '9', 10 ) || 9;
		var action  = container.getAttribute( 'data-action' ) || 'gwill_filter_posts';

		setActive( container, btn );

		// Loading indicator: a spinner overlay goes ON TOP of the
		// featured image of the first card, so it stays visible even
		// on slow connections while the fresh cards arrive.
		var spinHost = grid.querySelector( '.card:first-child .card-media' ) || grid.querySelector( 'article:first-child' );
		var spin = null;
		if ( spinHost ) {
			spin = document.createElement( 'span' );
			spin.className = 'filter-grid__spinner';
			spin.setAttribute( 'aria-hidden', 'true' );
			spinHost.appendChild( spin );
		}

		// Build the URL manually so we can control the exact params.
		var url = cfg.ajaxUrl +
			'?action=' + encodeURIComponent( action ) +
			'&category=' + encodeURIComponent( cat ) +
			'&per_page=' + perPage;

		grid.classList.add( 'is-loading' );
		grid.setAttribute( 'aria-busy', 'true' );

		gwillFetch( url, {
			method: 'GET',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( data ) {
				if ( data && data.success ) {
					grid.innerHTML = data.data.html || '';
				} else {
					grid.innerHTML = '<p class="filter-grid__error">' +
						( cfg.i18n.error || 'Could not load posts. Please try again.' ) +
						'</p>';
				}
			} )
			.catch( function () {
				grid.innerHTML = '<p class="filter-grid__error">' +
					( cfg.i18n.error || 'Could not load posts. Please try again.' ) +
					'</p>';
			} )
			.finally( function () {
				if ( spin && spin.parentNode ) { spin.parentNode.removeChild( spin ); }
				grid.classList.remove( 'is-loading' );
				grid.removeAttribute( 'aria-busy' );
			} );
	}

	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest ) { return; }
		var btn = e.target.closest( '.filter-pills .gwill-pill[data-filter]' );
		if ( ! btn ) { return; }
		e.preventDefault();

		var container = btn.closest( '.filter-pills' );
		if ( ! container ) { return; }

		filterGrid( container, btn, btn.getAttribute( 'data-filter' ) );
	} );

} )();
