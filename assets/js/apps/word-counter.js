/**
 * App: word-counter — demo of the starter's custom-apps pattern.
 *
 * Pure client-side: counts words, characters, sentences and paragraphs as
 * the visitor types. Nothing is uploaded. Auto-enqueued ONLY on
 * /apps/word-counter/ by inc/apps.php. Delete this file + the registry
 * entry to remove the demo app.
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

/*
Table of Contents
1. build the UI into #gwill-app-root
2. wire input events
3. count helpers
*/

( function () {
	'use strict';

	var root = document.getElementById( 'gwill-app-root' );
	if ( ! root ) { return; }

	/* ── 1. build the UI ─────────────────────────────────────────── */

	root.innerHTML =
		'<label class="wc-label" for="wc-input">' + 'Your text' + '</label>' +
		'<textarea id="wc-input" class="wc-input" rows="9" placeholder="' +
			'Paste or type text here…' + '" spellcheck="false"></textarea>' +
		'<div class="wc-stats" role="status" aria-live="polite">' +
			'<div class="wc-stat"><strong id="wc-words">0</strong><span>words</span></div>' +
			'<div class="wc-stat"><strong id="wc-chars">0</strong><span>characters</span></div>' +
			'<div class="wc-stat"><strong id="wc-sentences">0</strong><span>sentences</span></div>' +
			'<div class="wc-stat"><strong id="wc-paragraphs">0</strong><span>paragraphs</span></div>' +
		'</div>';

	var input    = document.getElementById( 'wc-input' );
	var words    = document.getElementById( 'wc-words' );
	var chars    = document.getElementById( 'wc-chars' );
	var sentences = document.getElementById( 'wc-sentences' );
	var paragraphs = document.getElementById( 'wc-paragraphs' );

	/* ── 2. wire events ───────────────────────────────────────────── */

	function recount() {
		var v = input.value;
		words.textContent = String( ( v.trim().match( /\S+/g ) || [] ).length );
		chars.textContent = String( v.length );
		sentences.textContent = String( ( v.match( /[^.!?\n]+[.!?]+(\s|$)/g ) || [] ).length +
			( /\S/.test( v ) && !/[.!?\n\s]*$/.test( v.trim() ) && !/[.!?)\]"']$/.test( v.trim() ) ? 1 : 0 ) );
		paragraphs.textContent = String( ( v.split( /\n\s*\n/ ).filter( function ( p ) {
			return /\S/.test( p );
		} ) ).length );
	}

	input.addEventListener( 'input', recount );
	recount();
} )();
