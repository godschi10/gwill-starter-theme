/**
 * App: case-converter  -  demo of the starter's custom-apps pattern.
 *
 * Pure client-side: converts pasted text between UPPERCASE, lowercase,
 * Title Case, Sentence case, camelCase, snake_case and kebab-case.
 * Nothing is uploaded. Auto-enqueued ONLY on /apps/case-converter/ by
 * inc/apps.php.
 *
 * @package GWill_Starter
 * @since   1.5.0
 */

/*
Table of Contents
1. build the UI into #gwill-app-root
2. conversion helpers
3. wire events
*/

( function () {
	'use strict';

	var root = document.getElementById( 'gwill-app-root' );
	if ( ! root ) { return; }

	/* ── 1. build the UI ─────────────────────────────────────────── */

	root.innerHTML =
		'<label class="wc-label" for="cc-input">' + 'Your text' + '</label>' +
		'<textarea id="cc-input" class="wc-input" rows="6" placeholder="' +
			'Paste or type text here…' + '" spellcheck="false"></textarea>' +
		'<div class="cc-preview" id="cc-preview" role="status" aria-live="polite">' +
			'<span class="cc-count" id="cc-count-words">0 words</span>' +
			'<span class="cc-count" id="cc-count-chars">0 characters</span>' +
			'<span class="cc-count" id="cc-count-sentences">0 sentences</span>' +
			'<span class="cc-count" id="cc-count-paras">0 paragraphs</span>' +
		'</div>' +
		'<div class="cc-actions" role="group" aria-label="Case actions">' +
			'<button type="button" class="cc-btn" data-cc="upper">UPPERCASE</button>' +
			'<button type="button" class="cc-btn" data-cc="lower">lowercase</button>' +
			'<button type="button" class="cc-btn" data-cc="title">Title Case</button>' +
			'<button type="button" class="cc-btn" data-cc="sentence">Sentence case</button>' +
			'<button type="button" class="cc-btn" data-cc="camel">camelCase</button>' +
			'<button type="button" class="cc-btn" data-cc="snake">snake_case</button>' +
			'<button type="button" class="cc-btn" data-cc="kebab">kebab-case</button>' +
			'<button type="button" class="cc-btn cc-btn--copy" data-cc="copy">Copy</button>' +
		'</div>' +
		'<div class="cc-status" id="cc-status" role="status" aria-live="polite"></div>';

	var input   = document.getElementById( 'cc-input' );
	var status  = document.getElementById( 'cc-status' );
	var counts = {
		words:     document.getElementById( 'cc-count-words' ),
		chars:     document.getElementById( 'cc-count-chars' ),
		sentences: document.getElementById( 'cc-count-sentences' ),
		paras:     document.getElementById( 'cc-count-paras' )
	};

	/* ── 2. conversion helpers ──────────────────────────────────── */

	function words( str ) {
		return str.trim().match( /[A-Za-z0-9']+/g ) || [];
	}

	/* Live counts preview (v1.9.0): what the visitor has BEFORE
	   converting  -  words / characters / sentences / paragraphs. */
	function updateCounts() {
		var v = input.value;
		var w = words( v ).length;
		var c = v.length;
		var s = ( v.match( /[.!?](\s|$)/g ) || [] ).length;
		var p = v.trim() ? v.trim().split( /\n\s*\n/ ).length : 0;
		counts.words.textContent     = w + ( 1 === w ? ' word' : ' words' );
		counts.chars.textContent     = c + ( 1 === c ? ' character' : ' characters' );
		counts.sentences.textContent = s + ( 1 === s ? ' sentence' : ' sentences' );
		counts.paras.textContent     = p + ( 1 === p ? ' paragraph' : ' paragraphs' );
	}

	function toTitle( str ) {
		var small = [ 'a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'nor', 'of', 'on', 'or', 'the', 'to', 'up', 'via' ];
		return str.replace( /\w\S*/g, function ( word, offset ) {
			var lower = word.toLowerCase();
			if ( offset > 0 && small.indexOf( lower ) !== -1 ) {
				return lower;
			}
			return lower.charAt( 0 ).toUpperCase() + lower.slice( 1 );
		} );
	}

	function toSentence( str ) {
		return str.toLowerCase().replace( /(^\s*\w|[.!?]\s+\w)/g, function ( c ) {
			return c.toUpperCase();
		} );
	}

	function toCamel( str ) {
		var parts = words( str );
		if ( ! parts.length ) { return ''; }
		return parts[ 0 ].toLowerCase() + parts.slice( 1 ).map( function ( w ) {
			return w.charAt( 0 ).toUpperCase() + w.slice( 1 ).toLowerCase();
		} ).join( '' );
	}

	function toSnake( str ) {
		return words( str ).map( function ( w ) { return w.toLowerCase(); } ).join( '_' );
	}

	function toKebab( str ) {
		return words( str ).map( function ( w ) { return w.toLowerCase(); } ).join( '-' );
	}

	var converters = {
		upper:    function ( v ) { return v.toUpperCase(); },
		lower:    function ( v ) { return v.toLowerCase(); },
		title:    toTitle,
		sentence: toSentence,
		camel:    toCamel,
		snake:    toSnake,
		kebab:    toKebab
	};

	/* ── 3. wire events ─────────────────────────────────────────── */

	root.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '.cc-btn' ) : null;
		if ( ! btn ) { return; }

		var mode = btn.getAttribute( 'data-cc' );

		if ( 'copy' === mode ) {
			if ( ! input.value.trim() ) {
				status.textContent = 'Nothing to copy yet.';
				return;
			}
			// Three-tier copy, same pattern as main.js share buttons.
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( input.value ).then( ok, fail );
			} else {
				try {
					input.select();
					document.execCommand( 'copy' );
					ok();
				} catch ( err ) {
					fail();
				}
			}
			return;
		}

		if ( ! converters[ mode ] ) { return; }
		input.value = converters[ mode ]( input.value );
		updateCounts();
		status.textContent = '';
	} );

	// ── v1.9.0: refresh the preview on every keystroke/paste ──
	input.addEventListener( 'input', updateCounts );
	updateCounts();

	function ok() { status.textContent = 'Copied.'; }
	function fail() { status.textContent = 'Copy failed  -  select the text and copy manually.'; }
} )();
