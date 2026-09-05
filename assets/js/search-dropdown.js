/*
 * TOC - search-dropdown.js (v1.16.80 - smart search)
 *
 * 01. gwillFetch shim ······································· line 30
 * 02. State + config ······································· line 71
 * 03. Normalize / tokens / fuzzy ··························· line 95
 * 04. Index load (once) ···································· line 125
 * 05. smartMatch() scoring ································· line 160
 * 06. render() + highlight ································· line 220
 * 07. search() ············································· line 300
 * 08. open/close/nav/keyboard ······························ line 330
 * 09. escape helpers ······································· line 410
 */
/**
 * GWill Starter - Search Dropdown (Inline Header Search) - SMART (v1.16.80)
 *
 * Smart live search WITHOUT bloat: downloads the theme's compact search
 * index (/wp-json/gwill/v1/search-index - one cached JSON of every post's
 * title/excerpt/category/url) ONCE per session, then matches entirely
 * CLIENT-SIDE: case/diacritic-insensitive, typo-tolerant (edit distance),
 * title-weighted relevance ranking, token AND + phrase bonus, matched-term
 * <mark> highlighting, and a recent-posts fallback on no match. ZERO
 * network per keystroke, ZERO server load per keystroke - no plugin, no
 * search service.
 *
 * Falls back to the per-keystroke REST posts search only if the index
 * fetch itself fails.
 *
 * @package GWill_Starter
 * @since   1.0.0 (smart engine: 1.1.0)
 */

( function () {
	/**
	 * gwillFetch - fetch() with an XMLHttpRequest fallback for browsers that
	 * lack the Fetch API (Safari <10.1, Chrome <42, Firefox <39). Resolves
	 * with a fetch-compatible response ({ ok, status, json(), text() }).
	 * The theme's ES6+ floor already requires Promise (Safari 8+, Chrome 32+),
	 * which every fetch-less browser here also has.
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

	'use strict';

	var GwillDropdown = window.GwillDropdown || {};
	var INDEX_URL     = GwillDropdown.indexUrl || '/wp-json/gwill/v1/search-index';
	var FTS_URL       = GwillDropdown.ftsUrl || '';   // v1.16.81 full-coverage endpoint
	var REST_URL      = GwillDropdown.restUrl || '/wp-json/wp/v2/posts?search=';
	var I18N          = GwillDropdown.i18n || {};
	var T_LOADING     = I18N.loading   || 'Searching…';
	var T_NO_RESULTS  = I18N.noResults || 'No results found.';
	var T_NO_MATCHES  = I18N.noMatches || 'No matches for “%s” - try these recent posts:';
	var T_ERROR       = I18N.error     || 'Search unavailable. Press Enter to search.';
	var T_VIEW_ALL    = I18N.viewAll   || 'View all results →';
	var HOME_URL      = GwillDropdown.homeUrl || window.location.origin + '/';

	var DEBOUNCE_MS   = 120; // matching is local now - respond almost instantly
	var MIN_CHARS     = 2;
	var MAX_RESULTS   = 8;
	var STORAGE_KEY   = 'gwill-si';      // sessionStorage cache of the index
	var STORAGE_TTL   = 60 * 60 * 1000;  // 1 hour

	var toggles  = document.querySelectorAll( '[data-gwill-search-toggle]' );
	var dropdown = document.getElementById( 'search-dropdown' );
	var input    = document.getElementById( 'search-input' );
	var results  = document.getElementById( 'search-results' );
	var clearBtn = document.getElementById( 'search-clear' );
	var closeBtn = document.getElementById( 'search-close' );

	if ( ! toggles.length || ! dropdown || ! input ) return;

	// v1.16.94: enforce the X-visibility contract on page load too - the
	// server renders #search-clear with the hidden attribute, but CSS
	// display:flex defeats it; syncClear() (hidden ⇔ empty field) must run
	// at init so the in-field X is in the right state before any interaction.
	syncClear();

	var debounceTimer = null;
	var activeIndex = -1;
	var currentData = [];
	var indexPromise = null;   // module-level: fetch the index at most once per page

	// ── Normalize / tokens / fuzzy ────────────────────────────────────────
	function norm( s ) {
		return String( s || '' ).toLowerCase()
			.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' ) // strip diacritics
			.replace( /[^a-z0-9\s]+/g, ' ' )                       // punctuation → space
			.replace( /\s+/g, ' ' ).trim();
	}

	function toks( s ) {
		var n = norm( s );
		return n ? n.split( ' ' ) : [];
	}

	// Bounded Levenshtein - typo tolerance (returns 9 when clearly too far).
	function editDist( a, b ) {
		var la = a.length, lb = b.length;
		if ( a === b ) return 0;
		if ( Math.abs( la - lb ) > 2 ) return 9;
		var row = [], i, j;
		for ( j = 0; j <= lb; j++ ) row[ j ] = j;
		for ( i = 1; i <= la; i++ ) {
			var prev = row[ 0 ], cur;
			row[ 0 ] = i;
			for ( j = 1; j <= lb; j++ ) {
				cur = Math.min( row[ j ] + 1, row[ j - 1 ] + 1, prev + ( a[ i - 1 ] === b[ j - 1 ] ? 0 : 1 ) );
				prev = row[ j ];
				row[ j ] = cur;
			}
		}
		return row[ lb ];
	}

	// A query token tolerates a small typo in a title word (len >= 4 only,
	// so 2-char tokens never fuzzy-match into junk). The first-letter anchor
	// blocks unrelated words that happen to sit within edit distance
	// ("battery" vs "matter" = dist 2 - same length, unrelated meaning).
	function fuzzyOk( tok, word ) {
		var l = word.length;
		if ( l >= 6 ) return editDist( tok, word ) <= 2 && tok[ 0 ] === word[ 0 ];
		if ( l >= 4 ) return editDist( tok, word ) <= 1 && tok[ 0 ] === word[ 0 ];
		return tok === word;
	}

	function escRe( s ) {
		return s.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	}

	// ── Index load (once per page, sessionStorage-cached) ─────────────────
	function loadIndexFromStorage() {
		try {
			var raw = sessionStorage.getItem( STORAGE_KEY );
			if ( ! raw ) return null;
			var box = JSON.parse( raw );
			if ( ! box || ! Array.isArray( box.d ) || ! box.t ) return null;
			if ( Date.now() - box.t > STORAGE_TTL ) return null;
			return box.d;
		} catch ( e ) {
			return null;
		}
	}

	function getIndex() {
		if ( indexPromise ) return indexPromise;
		var fromStorage = loadIndexFromStorage();
		if ( fromStorage ) {
			indexPromise = Promise.resolve( fromStorage );
			return indexPromise;
		}
		indexPromise = gwillFetch( INDEX_URL )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( ! Array.isArray( data ) ) throw new Error( 'bad index' );
				try {
					sessionStorage.setItem( STORAGE_KEY, JSON.stringify( { t: Date.now(), d: data } ) );
				} catch ( e ) { /* private mode - cache is a nicety */ }
				return data;
			} );
		return indexPromise;
	}

	// ── smartMatch(): relevance scoring ───────────────────────────────────
	// Score model (title-heavy, Google-ish):
	//   exact title        +150   title prefix       +110
	//   title substring    +70    per token in title word (exact +34, prefix +28, fuzzy +15)
	//   token in title     +20    token in slug      +12
	//   token in excerpt   +8     token in category  +6
	//   ALL tokens in title +40   ALL tokens anywhere +25
	function smartMatch( query, posts ) {
		var q = norm( query );
		if ( ! q ) return [];
		var qtoks = q.split( ' ' );

		var scored = [];
		for ( var pi = 0; pi < posts.length; pi++ ) {
			var p = posts[ pi ];
			var titleN = norm( p.title );
			var tToks = toks( p.title );
			var slugN = norm( ( p.url || '' ).split( '/' ).filter( Boolean ).pop() || '' );
			var excerptN = norm( p.excerpt );
			var catN = norm( p.cat );
			var score = 0;
			var allInTitle = true;
			var allAnywhere = true;

			if ( q === titleN ) score += 150;
			else if ( titleN.indexOf( q ) === 0 ) score += 110;
			else if ( q.length >= 3 && titleN.indexOf( q ) !== -1 ) score += 70;

			for ( var ti = 0; ti < qtoks.length; ti++ ) {
				var t = qtoks[ ti ];
				var best = 0;

				// 1) title word exact / prefix / fuzzy
				for ( var wi = 0; wi < tToks.length; wi++ ) {
					var w = tToks[ wi ];
					if ( w === t ) { best = Math.max( best, 34 ); }
					else if ( w.indexOf( t ) === 0 ) { best = Math.max( best, 28 ); }
					else if ( fuzzyOk( t, w ) ) { best = Math.max( best, 15 ); }
				}
				// 2) looser containment fallbacks
				if ( ! best ) {
					if ( titleN.indexOf( t ) !== -1 ) best = 20;
					else if ( slugN.indexOf( t ) !== -1 ) best = 12;
					else if ( excerptN.indexOf( t ) !== -1 ) best = 8;
					else if ( catN.indexOf( t ) !== -1 ) best = 6;
				}

				if ( ! best ) allAnywhere = false;
				// A token "in the title" = title-word exact/prefix/fuzzy (15+)
				// or title substring (20). Slug/excerpt/category hits don't count.
				if ( best < 15 ) allInTitle = false;
				score += best;
			}

			if ( allInTitle && qtoks.length > 1 ) score += 40;
			if ( allAnywhere && qtoks.length > 1 ) score += 25;
			if ( score > 0 ) scored.push( { p: p, s: score } );
		}

		scored.sort( function ( a, b ) {
			if ( b.s !== a.s ) return b.s - a.s;
			return ( b.p.date || '' ) < ( a.p.date || '' ) ? -1 : 1; // newer first on ties
		} );
		return scored.slice( 0, MAX_RESULTS );
	}

	// ── render + highlight ────────────────────────────────────────────────
	function highlight( text, qtoks ) {
		var out = escapeHtml( text );
		// Longest tokens first so overlapping marks never double-wrap.
		var ordered = qtoks.slice().sort( function ( a, b ) { return b.length - a.length; } );
		for ( var i = 0; i < ordered.length; i++ ) {
			var tok = ordered[ i ];
			if ( tok.length < 2 ) continue;
			out = out.replace( new RegExp( '(' + escRe( tok ) + ')', 'gi' ), '<mark>$1</mark>' );
		}
		return out;
	}

	function catColor( slug ) {
		if ( slug.indexOf( 'android' ) !== -1 ) return 'android';
		if ( slug.indexOf( 'web' ) !== -1 || slug.indexOf( 'dev' ) !== -1 ) return 'webdev';
		return 'software';
	}

	function renderItem( p, index ) {
		var qtoks = toks( input ? input.value : '' );
		var title = highlight( p.title, qtoks );
		var snippet = highlight( p.excerpt || '', qtoks );
		var dateStr = '';
		if ( p.date ) {
			var d = new Date( p.date );
			if ( ! isNaN( d.getTime() ) ) {
				dateStr = '<div class="sr-meta">' + d.toLocaleDateString( 'en-US', { month: 'short', year: 'numeric' } ) + '</div>';
			}
		}
		return '<a href="' + escapeHtml( p.url ) + '" class="search-result-item" id="gwill-sr-' + index + '" data-index="' + index + '">'
			+ '<span class="badge badge-' + catColor( p.cat_slug || '' ) + '">' + escapeHtml( p.cat || 'Article' ) + '</span>'
			+ '<div class="search-result-content">'
			+ '<div class="sr-title">' + title + '</div>'
			+ ( snippet ? '<div class="sr-snippet">' + snippet + '</div>' : '' )
			+ dateStr
			+ '</div></a>';
	}

	function renderFooter( q ) {
		return '<div class="search-dropdown-footer"><a href="' + escapeHtml( HOME_URL ) + '?s=' + encodeURIComponent( q ) + '">' + escapeHtml( T_VIEW_ALL ) + '</a></div>';
	}

	// v1.16.81: render a plain array of post objects (local index items or
	// FTS5 endpoint items - same shape) with highlight + footer.
	function renderPosts( q, posts ) {
		currentData = posts;
		activeIndex = -1;
		var html = '';
		for ( var i = 0; i < posts.length; i++ ) {
			html += renderItem( posts[ i ], i );
		}
		html += renderFooter( q );
		results.innerHTML = html;
		results.classList.add( 'has-results' );
	}

	// Smart fallback: "no matches" message + 3 newest posts instead of a
	// dead end. Used when both the local index AND the FTS endpoint agree
	// there is nothing.
	function fallbackRecent( q ) {
		results.innerHTML = '<div class="search-empty">' + escapeHtml( T_NO_MATCHES.replace( '%s', q ) ) + '</div>';
		results.classList.add( 'has-results' );
		getIndex().then( function ( all ) {
			if ( ( input.value || '' ).trim() !== q ) return; // superseded keystroke
			var recent = all.slice( 0, 3 );
			currentData = recent;
			var fb = '';
			for ( var i = 0; i < recent.length; i++ ) {
				fb += renderItem( recent[ i ], i );
			}
			results.innerHTML = results.innerHTML + fb + renderFooter( q );
		} );
	}

	// v1.16.81: query the FTS5 full-coverage endpoint. Fired only when local
	// results are thin (< 3) - for common queries the zero-network local
	// layer answers alone. Merges server results after local ones (dedup by
	// id); on failure or empty response the local rendering stands.
	function fetchFts( q, localMatches ) {
		if ( ! FTS_URL ) {
			if ( ! localMatches.length ) fallbackRecent( q );
			return;
		}
		gwillFetch( FTS_URL + encodeURIComponent( q ) )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( ( input.value || '' ).trim() !== q ) return;
				if ( Array.isArray( data ) && data.length ) {
					var merged = localMatches.map( function ( m ) { return m.p; } );
					var seen = {};
					merged.forEach( function ( p ) { seen[ p.id ] = true; } );
					data.forEach( function ( p ) {
						// Endpoint shape: { id, title, url, type, excerpt }.
						if ( p.id && ! seen[ p.id ] ) {
							seen[ p.id ] = true;
							merged.push( {
								id: p.id,
								title: p.title,
								url: p.url,
								excerpt: p.excerpt || '',
								cat: p.type || 'Article',
								cat_slug: '',
								date: ''
							} );
						}
					} );
					renderPosts( q, merged.slice( 0, MAX_RESULTS ) );
				} else if ( ! localMatches.length ) {
					fallbackRecent( q );
				}
			} )
			.catch( function () {
				if ( ( input.value || '' ).trim() !== q ) return;
				if ( ! localMatches.length ) fallbackRecent( q );
			} );
	}

	function renderResults( q, matches ) {
		if ( ! matches.length ) {
			// No local hits: the FTS endpoint gets the final say (it may
			// still find matches in older posts). Without FTS, fall back now.
			fetchFts( q, [] );
			return;
		}
		renderPosts( q, matches.map( function ( m ) { return m.p; } ) );
		if ( matches.length < 3 ) {
			fetchFts( q, matches ); // enrich thin local results in the background
		}
	}

	function getResultItems() {
		return results
			? Array.from( results.querySelectorAll( '.search-result-item' ) )
			: [];
	}

	function highlightActive() {
		var items = getResultItems();
		items.forEach( function ( el, i ) {
			el.classList.toggle( 'highlighted', i === activeIndex );
		} );
		if ( items[ activeIndex ] ) {
			input.setAttribute( 'aria-activedescendant', 'gwill-sr-' + activeIndex );
			items[ activeIndex ].scrollIntoView( { block: 'nearest' } );
		} else {
			input.removeAttribute( 'aria-activedescendant' );
		}
	}

	// ── search() - client-side, instant after the first index load ────────
	function search( q ) {
		getIndex()
			.then( function ( all ) {
				// Guard: a newer keystroke may have superseded this one.
				if ( ( input.value || '' ).trim() !== q ) return;
				renderResults( q, smartMatch( q, all ) );
			} )
			.catch( function () {
				if ( ( input.value || '' ).trim() !== q ) return;
				// Index unavailable - fall back to the old per-keystroke REST search.
				results.innerHTML = '<div class="search-loading">' + escapeHtml( T_LOADING ) + '</div>';
				results.classList.add( 'has-results' );
				gwillFetch( REST_URL + encodeURIComponent( q ) )
					.then( function ( r ) { return r.json(); } )
					.then( function ( data ) {
						if ( ! Array.isArray( data ) || ! data.length ) {
							results.innerHTML = '<div class="search-empty">' + escapeHtml( T_NO_RESULTS ) + '</div>';
							currentData = [];
							return;
						}
						renderResults( q, data.map( function ( p ) {
							return { p: { title: ( p.title && p.title.rendered ) || '', url: p.link, excerpt: '', cat: 'Article', cat_slug: '', date: p.date }, s: 1 };
						} ) );
					} )
					.catch( function () {
						results.innerHTML = '<div class="search-empty">' + escapeHtml( T_ERROR ) + '</div>';
					} );
			} );
	}

	// ── open/close ─────────────────────────────────────────────────────────
	function open() {
		dropdown.hidden = false;
		toggles.forEach( function ( t ) { t.setAttribute( 'aria-expanded', 'true' ); } );
		input.setAttribute( 'aria-expanded', 'true' );
		syncClear();
		setTimeout( function () { input.focus(); }, 100 );
	}

	// v1.16.92: the in-field "x" is visible only while there is text.
	function syncClear() {
		if ( clearBtn ) clearBtn.hidden = !( input && input.value.length > 0 );
	}

	function close() {
		dropdown.hidden = true;
		toggles.forEach( function ( t ) { t.setAttribute( 'aria-expanded', 'false' ); } );
		input.setAttribute( 'aria-expanded', 'false' );
		input.removeAttribute( 'aria-activedescendant' );
		results.innerHTML = '';
		results.classList.remove( 'has-results' );
		currentData = [];
		activeIndex = -1;
		// v1.16.91 (King): close() KEEPS the typed text so the user can
		// edit it - closing the form never touches the query; the inner x
		// (#search-clear) is the ONLY text clearer.
	}

	// X (original form button) = close ONLY, typed text preserved.
	// v1.16.93 (King clarification): the OUTER x must NOT clear the text  - 
	// it closes the search form; the inner x is the only text clearer.
	function closeOnly() {
		close();
	}

	// v1.16.92 (King clarification): the in-field "x" clears ALL text
	// WITHOUT closing the search form - the dropdown stays open.
	function clearOnly() {
		input.value = '';
		results.innerHTML = '';
		results.classList.remove( 'has-results' );
		currentData = [];
		activeIndex = -1;
		input.removeAttribute( 'aria-activedescendant' );
		syncClear();
		input.focus();
	}

	// In-field "x": clear all text, form stays open.
	if ( clearBtn ) clearBtn.addEventListener( 'click', clearOnly );

	// The King's outer X (#search-close): closes the form ONLY - the
	// typed text stays in the field (v1.16.93). Esc does the same.
	if ( closeBtn ) closeBtn.addEventListener( 'click', function () {
		closeOnly();
		syncClear();
	} );

	// ── toggles / keyboard nav ─────────────────────────────────────────────
	toggles.forEach( function ( toggle ) {
		toggle.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			if ( dropdown.hidden ) open(); else close();
		} );
	} );

	if ( input ) {
		input.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				e.preventDefault();
				close();
			}
			if ( e.key === 'ArrowDown' ) {
				e.preventDefault();
				activeIndex = Math.min( activeIndex + 1, currentData.length - 1 );
				highlightActive();
			}
			if ( e.key === 'ArrowUp' ) {
				e.preventDefault();
				activeIndex = Math.max( activeIndex - 1, 0 );
				highlightActive();
			}
			if ( e.key === 'Enter' && activeIndex >= 0 && currentData[ activeIndex ] ) {
				e.preventDefault();
				window.location = currentData[ activeIndex ].url;
			}
		} );

		input.addEventListener( 'input', function () {
			syncClear();
			clearTimeout( debounceTimer );
			var v = this.value.trim();
			if ( v.length < MIN_CHARS ) {
				results.innerHTML = '';
				results.classList.remove( 'has-results' );
				return;
			}
			debounceTimer = setTimeout( function () { search( v ); }, DEBOUNCE_MS );
		} );
	}

	// v1.16.91 (King): the dropdown stays open until the user presses
	// X (clears) or Esc (dismisses) - clicking elsewhere does NOT close
	// it anymore; the typed text is always preserved for editing.
	// document.addEventListener( 'click', function ( e ) {
	// 	var isToggle = false;
	// 	toggles.forEach( function ( t ) {
	// 		if ( e.target === t || t.contains( e.target ) ) isToggle = true;
	// 	} );
	// 	if ( ! dropdown.hidden && ! dropdown.contains( e.target ) && ! isToggle ) {
	// 		close();
	// 	}
	// } );

	// ── escape helpers (v1.16.41: real escaping, & first) ─────────────────
	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

} )();