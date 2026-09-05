/**
 * Code Copy Buttons + language sniffing  -  GWill Starter (v1.7.0).
 *
 * Ported from gwill-tech-theme assets/js/code-copy.js (live-proven),
 * adapted: the sniffer query targets .entry-content (the starter's
 * content scope  -  tech used .prose).
 *
 * 1. Copy: adds working copy to <pre><code> blocks via their .copy-btn
 *    (injected by inc/code-blocks.php). Shows "Copied!" feedback 1.6s.
 *    Falls back to execCommand for insecure contexts (http://IP) where
 *    navigator.clipboard is unavailable.
 * 2. Language sniffing: if a code block has no language-* class (plain
 *    Gutenberg code block), detect it from the first non-empty lines and
 *    re-trigger Prism highlighting so the block still gets colours.
 *
 * @package GWill_Starter
 * @since   1.7.0
 */

/*
Table of Contents
1. copyText
2. fallbackCopy
3. Copy buttons
4. Language sniffing (only when Prism is loaded)
5. sniffLanguage
6. highlightUnlabeled
*/

( function () {
	'use strict';

	// ── 1. copyText ────────────────────────────────────────────
	function copyText( txt ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			return navigator.clipboard.writeText( txt ).catch( function () {
				fallbackCopy( txt );
			} );
		}
		fallbackCopy( txt );
	}

	// ── 2. fallbackCopy ────────────────────────────────────────
	function fallbackCopy( txt ) {
		var ta = document.createElement( 'textarea' );
		ta.value = txt;
		ta.setAttribute( 'readonly', '' );
		ta.style.position = 'absolute';
		ta.style.left = '-9999px';
		document.body.appendChild( ta );
		ta.select();
		try {
			document.execCommand( 'copy' );
		} catch ( ex ) { /* ignore */ }
		document.body.removeChild( ta );
	}

	// ── 3. Copy buttons ───────────────────────────────────────
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.copy-btn' );
		if ( ! btn ) return;

		var c = btn.parentElement.querySelector( 'code' );
		var txt = c ? c.textContent : '';
		var orig = btn.textContent;

		btn.textContent = 'Copied!';
		setTimeout( function () { btn.textContent = orig; }, 1600 );

		copyText( txt );
	} );

	// ── 4. Language sniffing (only when Prism is loaded) ──────
	// ── 5. sniffLanguage ──────────────────────────────────────
	function sniffLanguage( code ) {
		var text = ( code.textContent || '' ).trim();
		if ( ! text ) return '';

		var lines = text.split( /\r?\n/ ).filter( function ( l ) { return l.trim() !== ''; } ).slice( 0, 3 );
		var first = lines.join( '\n' );

		if ( /^<\?php/.test( first ) ) return 'php';
		// PHP function signatures: "function name(...): Type {"  -  the "):"
		// return-type hint is rare in JS/TS but standard in PHP 7+.
		if ( /^function\s+\w+\s*\([^)]*\)\s*:\s*\w+/.test( first ) ) return 'php';
		if ( /^<(!DOCTYPE|html|div|script|style|svg|body|head)/i.test( first ) ) return 'markup';
		if ( /^(import |export |from |const |let |var |function |async |await |=>|class |interface |type )/.test( first ) ) return 'javascript';
		if ( /^(def |class |import |from |print\(|if __name__)/.test( first ) ) return 'python';
		// Bash: a command in ANY of the first lines (# comments, shebangs,
		// $ prompts, or command prefixes)  -  before YAML since "# foo" alone
		// is ambiguous but a following command makes it shell.
		var bashCmd = /^(#!|[$>]\s|(?:git |npm |yarn |sudo |apt |curl |wget |docker |cd |ls |mkdir |rm |chmod |ssh |cp |mv |cat |echo |export |php |composer |pip |brew ))/;
		if ( lines.some( function ( l ) { return bashCmd.test( l ); } ) ) return 'bash';
		if ( /^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|WITH)\b/i.test( first ) ) return 'sql';
		if ( /^\{[\s\S]*\}$/.test( text.slice( 0, 200 ) ) ) return 'json';
		if ( /^(#|title:|name:|version:)/.test( first ) ) return 'yaml';
		if ( /^(fn |let |mod |use |pub fn)/.test( first ) ) return 'rust';
		if ( /^package /.test( first ) && /import /.test( first ) ) return 'java';
		return '';
	}

	// ── 6. highlightUnlabeled ─────────────────────────────────
	function highlightUnlabeled() {
		if ( ! window.Prism ) return;

		document.querySelectorAll( '.entry-content pre code' ).forEach( function ( code ) {
			if ( code.className && /language-/.test( code.className ) ) return;
			var lang = sniffLanguage( code );
			if ( ! lang ) return;

			code.classList.add( 'language-' + lang );

			// Update the language label (injected by PHP, may be empty).
			var label = code.parentElement.querySelector( '.code-lang' );
			if ( label && ! label.textContent ) {
				label.textContent = lang;
			}

			if ( Prism.highlightElement ) {
				Prism.highlightElement( code );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', highlightUnlabeled );
	} else {
		highlightUnlabeled();
	}
} )();
