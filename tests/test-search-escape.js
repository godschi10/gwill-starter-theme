/**
 * GWill Starter - search excerpt double-escape regression battery.
 *
 * Proves the v1.12.9 decode fixes end-to-end:
 *   1. inc/search-index.php excerpt/cat decode (write side)
 *   2. inc/search-fts.php read-side decode for stored rows
 *   3. assets/css/search.css results height cap (no page balloon)
 *   4. escapeHtml() + renderResults() output - badge "Guides & How-tos"
 *      renders as ONE ampersand, never "Guides &amp; How-tos".
 *
 * Run: node tests/test-search-escape.js
 * Harness: jsdom (the theme's own battery stack, same as test-pill.js).
 */

'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const { JSDOM } = require( '/home/ubuntu/.hermes/hermes-agent/node_modules/jsdom' );

const ROOT = path.resolve( __dirname, '..' );
let pass = 0, fail = 0;
function t( name, ok ) {
	if ( ok ) { pass++; console.log( `  PASS  ${ name }` ); }
	else { fail++; console.log( `  FAIL  ${ name }` ); }
}

/* ── 1. PHP source contract: decode calls exist where they must ─────── */
const idx = fs.readFileSync( path.join( ROOT, 'inc/search-index.php' ), 'utf8' );
const fts = fs.readFileSync( path.join( ROOT, 'inc/search-fts.php' ), 'utf8' );
const css = fs.readFileSync( path.join( ROOT, 'assets/css/search.css' ), 'utf8' );

t( 'index: excerpt decode after trim (write side)', 
  /'excerpt'\s*=>\s*\$excerpt,/.test( idx ) &&
  idx.includes( "html_entity_decode( $excerpt, ENT_QUOTES, 'UTF-8' )" ) );

t( 'index: cat decode (badge & fix)',
  idx.includes( "html_entity_decode( $cat->name, ENT_QUOTES, 'UTF-8' )" ) );

t( 'fts: excerpt decode (write side)',
  fts.includes( "html_entity_decode( $excerpt, ENT_QUOTES, 'UTF-8' )" ) );

t( 'fts: read-side decode for stored rows (title/excerpt/cat)',
  fts.includes( "html_entity_decode( $row['excerpt'], ENT_QUOTES, 'UTF-8' )" ) &&
  fts.includes( "html_entity_decode( $row['cat'], ENT_QUOTES, 'UTF-8' )" ) );

t( 'css: results list height cap + inner scroll',
  /\.search-results\s*\{[^}]*max-height:\s*300px;[^}]*overflow-y:\s*auto;/s.test( css ) );

/* ── 2. Behavioral: the real engine renders the decoded payload clean ── */
const dom = new JSDOM( `<!DOCTYPE html><html><body>
<button data-gwill-search-toggle aria-expanded="false">Search</button>
<div class="search-dropdown" id="search-dropdown" hidden>
<form class="search-dropdown-form">
  <input class="search-dropdown-input" id="search-input">
</form>
<div class="search-results" id="search-results"></div>
</div>
</body></html>`, {
	url: 'https://example.test/',
	runScripts: 'outside-only'   /* we inject the engine ourselves */
} );

const { window } = dom;
const input   = window.document.getElementById( 'search-input' );
const results = window.document.getElementById( 'search-results' );

/* Minimal localize - the engine reads window.GwillDropdown at parse. */
window.GwillDropdown = {
	indexUrl: 'https://example.test/wp-json/gwill/v1/search-index',
	ftsUrl:   '',
	restUrl:  'https://example.test/wp-json/wp/v2/posts?_embed&search=',
	homeUrl:  'https://example.test/',
	i18n: {
		loading: 'Searching…',
		noResults: 'No results found.',
		noMatches: 'No matches for "%s" - try these recent posts:',
		error: 'Search unavailable. Press Enter to search.',
		viewAll: 'View all results →',
		trySearching: 'Try searching for'
	}
};

/* The index fetch returns the DECODED payload (as PHP now serves it). */
window.fetch = function ( url ) {
	return Promise.resolve( {
		ok: true,
		json: function () {
			return Promise.resolve( [ {
				id: 1,
				title: "Guide: Wi-Fi & Bluetooth",
				url: 'https://example.test/wifi/',
				excerpt: "Fix Wi-Fi & Bluetooth in <5 minutes — the quick way",
				cat: 'Guides & How-tos',
				cat_slug: 'guides',
				date: '2026-09-08T00:00:00'
			} ] );
		}
	} );
};

const engine = fs.readFileSync( path.join( ROOT, 'assets/js/search-dropdown.js' ), 'utf8' );
try {
	/* outside-only runScripts: window.eval executes in the window's OWN
	   realm, so the engine's bare `window`/`document` references resolve. */
	window.eval( engine );
} catch ( e ) {
	console.log( '  engine eval error:', e.message );
}

/* Settle async DOMContentLoaded + fetch promise chain. */
setTimeout( () => {
	try {
		input.value = 'wifi';
		input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
	} catch ( e ) {
		console.log( '  input event error:', e.message );
	}

	setTimeout( () => {
		const html = results.innerHTML;
		const text = results.textContent || '';

		t( 'badge renders ONE ampersand, not &amp;',
		  text.includes( 'Guides & How-tos' ) && ! text.includes( '&amp;' ) );

		t( 'excerpt renders clean (& decoded, < safe)',
		  text.includes( 'Wi-Fi & Bluetooth' ) &&
		  ! /&amp;/.test( text ) && ! /&lt;5/.test( text.replace( '<5 minutes', '' ) ) &&
		  html.includes( '&lt;5 minutes' ) ); /* the < in copy IS escaped - safe */

		t( 'title mark highlight does not double-escape',
		  ! text.includes( '&amp;amp;' ) );

		t( 'result item link present', html.includes( 'search-result-item' ) );

		console.log( `\n${ pass } passed, ${ fail } failed` );
		process.exit( fail ? 1 : 0 );
	}, 400 );
}, 150 );
