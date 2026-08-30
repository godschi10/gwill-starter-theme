/**
 * App: unit-converter — demo of the starter's custom-apps pattern.
 *
 * Pure client-side: converts between common length, weight and
 * temperature units. Nothing is uploaded. Auto-enqueued ONLY on
 * /apps/unit-converter/ by inc/apps.php.
 *
 * @package GWill_Starter
 * @since   1.5.0
 */

/*
Table of Contents
1. unit tables (base-unit rates)
2. build the UI into #gwill-app-root
3. conversion + render
4. wire events
*/

( function () {
	'use strict';

	var root = document.getElementById( 'gwill-app-root' );
	if ( ! root ) { return; }

	/* ── 1. unit tables ──────────────────────────────────────────── */

	// Length base = metre; weight base = gram; temperature handled apart.
	var LENGTH = { mm: 0.001, cm: 0.01, m: 1, km: 1000, in: 0.0254, ft: 0.3048, yd: 0.9144, mi: 1609.344 };
	var WEIGHT = { mg: 0.001, g: 1, kg: 1000, t: 1000000, oz: 28.349523125, lb: 453.59237 };

	var GROUPS = [
		{ key: 'length', label: 'Length', units: LENGTH },
		{ key: 'weight', label: 'Weight', units: WEIGHT },
		{ key: 'temp',   label: 'Temperature', units: null }
	];

	function tempUnits() {
		return { C: 'Celsius', F: 'Fahrenheit', K: 'Kelvin' };
	}

	/* ── 2. build the UI ─────────────────────────────────────────── */

	var groupSelects = '';
	GROUPS.forEach( function ( g ) {
		groupSelects += '<option value="' + g.key + '">' + g.label + '</option>';
	} );

	root.innerHTML =
		'<label class="wc-label" for="uc-group">Category</label>' +
		'<select id="uc-group" class="uc-select">' + groupSelects + '</select>' +
		'<div class="uc-row">' +
			'<div class="uc-field">' +
				'<label class="wc-label" for="uc-value">Value</label>' +
				'<input id="uc-value" class="wc-input uc-input" type="number" inputmode="decimal" step="any" min="" value="1">' +
			'</div>' +
			'<div class="uc-field">' +
				'<label class="wc-label" for="uc-from">From</label>' +
				'<select id="uc-from" class="uc-select"></select>' +
			'</div>' +
			'<div class="uc-field">' +
				'<label class="wc-label" for="uc-to">To</label>' +
				'<select id="uc-to" class="uc-select"></select>' +
			'</div>' +
		'</div>' +
		'<div class="uc-result" id="uc-result" role="status" aria-live="polite"></div>';

	var groupSel = document.getElementById( 'uc-group' );
	var fromSel  = document.getElementById( 'uc-from' );
	var toSel    = document.getElementById( 'uc-to' );
	var valIn    = document.getElementById( 'uc-value' );
	var result   = document.getElementById( 'uc-result' );

	/* ── 3. conversion + render ──────────────────────────────────── */

	function unitOptions( group ) {
		var opts = '';
		if ( 'temp' === group ) {
			var t = tempUnits();
			for ( var k in t ) {
				if ( Object.prototype.hasOwnProperty.call( t, k ) ) {
					opts += '<option value="' + k + '">' + k + ' — ' + t[ k ] + '</option>';
				}
			}
			return opts;
		}
		var table = ( 'length' === group ) ? LENGTH : WEIGHT;
		for ( var u in table ) {
			if ( Object.prototype.hasOwnProperty.call( table, u ) ) {
				opts += '<option value="' + u + '">' + u + '</option>';
			}
		}
		return opts;
	}

	function fillUnits() {
		var group = groupSel.value;
		fromSel.innerHTML = unitOptions( group );
		toSel.innerHTML   = unitOptions( group );
		if ( 'temp' === group ) {
			fromSel.value = 'C';
			toSel.value = 'F';
		} else {
			var keys = Object.keys( 'length' === group ? LENGTH : WEIGHT );
			fromSel.value = keys[ 2 ] || keys[ 0 ];   // m / kg
			toSel.value   = keys[ 5 ] || keys[ 1 ];   // ft / lb
		}
		render();
	}

	function cToK( v, unit ) {
		if ( 'C' === unit ) { return v + 273.15; }
		if ( 'F' === unit ) { return ( v - 32 ) * 5 / 9 + 273.15; }
		return v;
	}

	function kTo( k, unit ) {
		if ( 'C' === unit ) { return k - 273.15; }
		if ( 'F' === unit ) { return ( k - 273.15 ) * 9 / 5 + 32; }
		return k;
	}

	function render() {
		var v = parseFloat( valIn.value );
		if ( isNaN( v ) ) {
			result.textContent = '';
			return;
		}
		var group = groupSel.value;
		var out;

		if ( 'temp' === group ) {
			out = kTo( cToK( v, fromSel.value ), toSel.value );
		} else {
			var table = ( 'length' === group ) ? LENGTH : WEIGHT;
			out = v * table[ fromSel.value ] / table[ toSel.value ];
		}

		// Show sensible precision without float noise (0.1+0.2 syndrome).
		out = Math.round( out * 1e6 ) / 1e6;
		result.textContent = v + ' ' + fromSel.value + ' = ' + out + ' ' + toSel.value;
	}

	/* ── 4. wire events ──────────────────────────────────────────── */

	groupSel.addEventListener( 'change', fillUnits );
	[ fromSel, toSel, valIn ].forEach( function ( el ) {
		el.addEventListener( 'change', render );
		el.addEventListener( 'input', render );
	} );

	fillUnits();
} )();
