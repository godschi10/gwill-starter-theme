/**
 * GWill Starter — Multi-step Form
 *
 * Manages step visibility, progress bar, and back/next navigation
 * for .gwill-form--multistep. AJAX submission is handled by forms.js.
 *
 * No-JS fallback: all steps are stacked and visible, Next/Back buttons
 * are inert type="button" elements with no effect. The form submits
 * normally on the final step's Submit button.
 *
 * sessionStorage: field values are saved on every input/change so Back
 * does not clear entries. Nonce fields are intentionally excluded from
 * storage (nonces must always come from the server).
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

( function () {
	'use strict';

	var form = document.querySelector( '.gwill-form--multistep' );
	if ( ! form ) return;

	// i18n strings injected via wp_localize_script( 'gwill-forms-multistep', 'GwillMultistep', [...] ).
	// English strings are the fallback so the form stays functional even if
	// the localize call is missed (e.g. a partial/custom enqueue setup).
	var i18n = ( window.GwillMultistep && window.GwillMultistep.i18n ) || {};
	var i18nStep       = i18n.step       || 'Step';
	var i18nOf         = i18n.of         || 'of';
	var i18nIsRequired = i18n.isRequired || 'is required.';

	var steps      = Array.from( form.querySelectorAll( '.gwill-form__step' ) );
	var progBar    = form.querySelector( '.gwill-form__progress-fill' );
	var stepLabel  = form.querySelector( '.gwill-form__step-label' );
	var total      = steps.length;
	var current    = 0;
	var STORE_KEY  = 'gwill_ms_' + ( form.dataset.formId || 'default' );

	// ── Initialise ────────────────────────────────────────────────────────────

	// Hide all steps, then show the first.
	steps.forEach( function ( s ) { s.hidden = true; } );
	showStep( 0 );

	// Restore persisted values.
	restoreFromStorage();

	// ── Event delegation ──────────────────────────────────────────────────────

	form.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-next]' ) ) {
			if ( validateStep( current ) ) showStep( current + 1 );
		}
		if ( e.target.closest( '[data-back]' ) ) {
			showStep( current - 1 );
		}
	} );

	// Persist values on every change.
	form.addEventListener( 'input',  saveToStorage );
	form.addEventListener( 'change', saveToStorage );

	// Clear storage on successful submit (forms.js replaces the form element,
	// so this mostly matters for the edge case where the page is navigated away).
	form.addEventListener( 'submit', function () {
		try { sessionStorage.removeItem( STORE_KEY ); } catch ( e ) { /* ignore */ }
	} );

	// ── Step management ───────────────────────────────────────────────────────

	function showStep( index ) {
		if ( index < 0 || index >= total ) return;

		steps[ current ].hidden = true;
		steps[ current ].removeAttribute( 'data-active' );

		current = index;

		steps[ current ].hidden = false;
		steps[ current ].setAttribute( 'data-active', '' );

		updateProgress();

		// Focus first focusable element in the new step.
		var first = steps[ current ].querySelector( 'input:not([type="hidden"]), select, textarea' );
		if ( first ) first.focus();
	}

	function updateProgress() {
		var pct = total > 1 ? ( current / ( total - 1 ) ) * 100 : 100;
		if ( progBar ) progBar.style.width = Math.max( 4, pct ) + '%';
		if ( stepLabel ) {
			stepLabel.textContent = i18nStep + ' ' + ( current + 1 ) + ' ' + i18nOf + ' ' + total;
		}
	}

	// ── Per-step validation ───────────────────────────────────────────────────

	function validateStep( index ) {
		var step  = steps[ index ];
		var valid = true;
		var first = null;

		// Clear previous errors within this step only.
		step.querySelectorAll( '.gwill-form__field-error' ).forEach( function ( el ) { el.remove(); } );
		step.querySelectorAll( '[aria-invalid]' ).forEach( function ( el ) {
			el.removeAttribute( 'aria-invalid' );
			el.removeAttribute( 'aria-describedby' );
		} );

		step.querySelectorAll( '[required]' ).forEach( function ( field ) {
			if ( ! field.value.trim() ) {
				field.setAttribute( 'aria-invalid', 'true' );
				var errId = ( field.id || field.name ) + '-err';
				var err   = document.createElement( 'span' );
				err.className   = 'gwill-form__field-error';
				err.id          = errId;
				err.textContent = labelFor( step, field ) + ' ' + i18nIsRequired;
				field.setAttribute( 'aria-describedby', errId );
				field.after( err );
				if ( ! first ) first = field;
				valid = false;
			}
		} );

		if ( first ) first.focus();
		return valid;
	}

	// Resolve the visible label text for a field within a given container.
	function labelFor( container, field ) {
		var lbl = container.querySelector( 'label[for="' + field.id + '"]' );
		return lbl ? lbl.textContent.replace( /[*\u2009]/g, '' ).trim() : 'This field';
	}

	// ── sessionStorage persistence ────────────────────────────────────────────

	function saveToStorage() {
		var data = {};
		new FormData( form ).forEach( function ( val, key ) {
			// Never persist nonce or WordPress internal fields.
			if ( key === 'gwill_nonce' || key === '_wpnonce' || key === 'action' ) return;
			data[ key ] = val;
		} );
		try { sessionStorage.setItem( STORE_KEY, JSON.stringify( data ) ); } catch ( e ) { /* quota or private mode */ }
	}

	function restoreFromStorage() {
		var raw;
		try { raw = sessionStorage.getItem( STORE_KEY ); } catch ( e ) { return; }
		if ( ! raw ) return;

		var data;
		try { data = JSON.parse( raw ); } catch ( e ) { return; }

		Object.keys( data ).forEach( function ( name ) {
			// Radio groups: find the specific radio with the stored value and check it.
			// Setting .value on a radio input is a no-op — .checked must be set instead.
			var radio = form.querySelector(
				'[name="' + name + '"][type="radio"][value="' + data[ name ].replace( /"/g, '\\"' ) + '"]'
			);
			if ( radio ) {
				radio.checked = true;
				return;
			}
			// All other inputs: set value directly.
			var field = form.querySelector( '[name="' + name + '"]:not([type="hidden"])' );
			if ( field && data[ name ] ) field.value = data[ name ];
		} );
	}

} )();
