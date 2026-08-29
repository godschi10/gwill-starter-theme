/**
 * GWill Starter — Contact Forms
 *
 * AJAX submission for all .gwill-form elements.
 *
 * Nonce acquisition (cache-safe, two-tier):
 *   1. If GwillForms.nonce is already present (PHP baked it in for
 *      logged-in users — see inc/enqueue.php), use it directly. Logged-in
 *      pages are never served from LiteSpeed's page cache, so a nonce baked
 *      straight into this page's HTML is always fresh. No network request
 *      needed at all for this case.
 *   2. Otherwise (anonymous visitor on a page LiteSpeed may have cached for
 *      hours), fetch a fresh one from GwillForms.nonceUrl — admin-ajax.php,
 *      excluded from LiteSpeed Cache by default — with a cache-busting
 *      timestamp param appended at request time, so no intermediate caching
 *      layer (CDN, reverse proxy, a misconfigured "cache everything" rule on
 *      a dev tunnel) can serve a stale response regardless of whether it
 *      honours admin-ajax.php's usual cache exclusion.
 *
 * The nonce is never read from form HTML for case 2, so LiteSpeed can cache
 * anonymous-visitor pages indefinitely without risk of stale token failures.
 *
 * @package GWill_Starter
 * @since   1.0.41
 */

( function () {
	'use strict';

	var ajaxUrl  = ( typeof GwillForms !== 'undefined' && GwillForms.ajaxUrl )
		? GwillForms.ajaxUrl
		: '/wp-admin/admin-ajax.php';

	var nonceUrl = ( typeof GwillForms !== 'undefined' && GwillForms.nonceUrl )
		? GwillForms.nonceUrl
		: '/wp-admin/admin-ajax.php?action=gwill_get_nonce';

	var noncePromise = null;

	// ── Attach submit handler to all forms ───────────────────────────────────

	document.querySelectorAll( '.gwill-form' ).forEach( function ( form ) {
		form.addEventListener( 'submit', handleSubmit );
	} );

	// ── Feedback form: Yes / No ───────────────────────────────────────────────

	document.querySelectorAll( '.gwill-feedback__yes' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			var form     = e.currentTarget.closest( '.gwill-form' );
			var response = form.querySelector( '[name="gwill_response"]' );
			if ( response ) response.value = 'yes';
			handleSubmit( { currentTarget: form, preventDefault: function () {} } );
		} );
	} );

	document.querySelectorAll( '.gwill-feedback__no' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			var form     = e.currentTarget.closest( '.gwill-form' );
			var extra    = form.querySelector( '.gwill-feedback__extra' );
			var response = form.querySelector( '[name="gwill_response"]' );
			if ( response ) response.value = 'no';
			if ( extra ) {
				extra.removeAttribute( 'hidden' );
				var first = extra.querySelector( 'textarea, input' );
				if ( first ) first.focus();
			}
		} );
	} );

	// ── Nonce acquisition ────────────────────────────────────────────────────
	//
	// Exposed on window so any other form-specific script (multistep,
	// exit-intent, or a future pattern) can share this exact logic instead
	// of writing its own copy. As of 1.0.47, none currently need to — both
	// delegate their actual submission to this file's own .gwill-form submit
	// listener — but the export costs nothing and avoids the duplicate-logic
	// risk if that ever changes.

	function getNonce() {
		if ( typeof GwillForms !== 'undefined' && GwillForms.nonce ) {
			return Promise.resolve( GwillForms.nonce );
		}

		if ( noncePromise ) {
			return noncePromise;
		}

		var bustUrl = nonceUrl + ( nonceUrl.indexOf( '?' ) > -1 ? '&' : '?' ) + '_=' + Date.now();

		noncePromise = fetch( bustUrl )
			.then( function ( res ) {
				if ( ! res.ok ) throw new Error( 'nonce-http-' + res.status );
				return res.json();
			} )
			.then( function ( data ) {
				if ( typeof GwillForms !== 'undefined' ) {
					GwillForms.nonce = data.nonce; // cache for any later submit on this page
				}
				return data.nonce;
			} )
			.catch( function ( err ) {
				noncePromise = null; // allow a retry on the next submit attempt
				throw err;
			} );

		return noncePromise;
	}

	window.gwillGetNonce = getNonce;

	// ── Submit ────────────────────────────────────────────────────────────────

	function handleSubmit( e ) {
		e.preventDefault();

		var form   = e.currentTarget;
		var submit = form.querySelector( '.gwill-form__submit' );
		var status = form.querySelector( '.gwill-form__status' );

		if ( ! status ) return;

		status.textContent = '';
		status.className   = 'gwill-form__status';

		if ( ! validateRequired( form ) ) return;

		setLoading( form, submit, true );

		getNonce()
			.then( function ( nonce ) {
				var formData = new FormData( form );
				formData.set( 'gwill_nonce', nonce );

				return fetchWithRetry( ajaxUrl, {
					method:      'POST',
					credentials: 'same-origin',
					body:        formData,
				} );
			} )
			.then( function ( res ) {
				// IMPORTANT: do NOT gate on res.ok here. gwill_handle_contact_form()
				// deliberately uses wp_send_json_error( $data, $status_code ) with a
				// non-2xx status (403 for a failed nonce check, 429 for rate-limiting)
				// while STILL sending a valid, specific, already-correct JSON body —
				// e.g. { success: false, data: { message: "Please wait a few minutes
				// before sending another message." } }. An earlier version of this
				// file threw on any non-2xx status before ever reading that body,
				// which meant every nonce-failure and every rate-limit rejection
				// showed the same generic "Server error" text instead of the actual,
				// accurate, user-actionable message the server had already composed.
				// Parsing the JSON unconditionally — and only falling through to the
				// catch() below if THAT parse itself fails — is what lets the real
				// message reach the user regardless of which status code carried it.
				return res.json();
			} )
			.then( function ( data ) {
				if ( data.success ) {
					var msg       = document.createElement( 'p' );
					msg.className = 'gwill-form__success-msg';
					msg.setAttribute( 'role', 'alert' );
					msg.textContent = ( data.data && data.data.message )
						? data.data.message
						: 'Message sent!';
					form.replaceWith( msg );
				} else {
					var err = ( data.data && data.data.message )
						? data.data.message
						: 'Something went wrong. Please try again.';
					status.textContent = err;
					status.classList.add( 'gwill-form__status--error' );
					setLoading( form, submit, false );
				}
			} )
			.catch( function ( err ) {
				// Reaches here only for: a true network failure (fetch() itself
				// rejecting — offline, DNS, CORS), the nonce-fetch step failing
				// (see getNonce()'s own nonce-http- tagging above), or a submit
				// response that returned 200 status but a body that wasn't valid
				// JSON at all (a host-level error page, a PHP fatal that bypassed
				// gwill_handle_contact_form()'s own ob_clean() + wp_send_json_*()
				// entirely). Every ordinary, well-formed rejection from the form
				// handler itself is now handled above, not here.
				if ( window.console && console.error ) {
					console.error( '[GWill Forms] Submission failed:', err );
				}

				var errMsg = ( err && err.message ) || '';
				var text;

				if ( /^nonce-http-/.test( errMsg ) ) {
					text = 'Could not verify the form. Please refresh the page and try again.';
				} else if ( err instanceof SyntaxError ) {
					text = 'Unexpected response from the server. Please try again.';
				} else {
					text = 'Network error. Check your connection and try again.';
				}

				status.textContent = text;
				status.classList.add( 'gwill-form__status--error' );
				setLoading( form, submit, false );
			} );
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	function setLoading( form, submit, on ) {
		if ( on ) {
			form.setAttribute( 'aria-busy', 'true' );
			if ( submit ) { submit.setAttribute( 'data-loading', '' ); submit.disabled = true; }
		} else {
			form.removeAttribute( 'aria-busy' );
			if ( submit ) { submit.removeAttribute( 'data-loading' ); submit.disabled = false; }
		}
	}

	function validateRequired( form ) {
		form.querySelectorAll( '.gwill-form__field-error' ).forEach( function ( el ) { el.remove(); } );
		form.querySelectorAll( '[aria-invalid]' ).forEach( function ( el ) {
			el.removeAttribute( 'aria-invalid' );
			el.removeAttribute( 'aria-describedby' );
		} );

		var valid    = true;
		var firstBad = null;

		form.querySelectorAll( '[required]' ).forEach( function ( field ) {
			if ( ! field.value.trim() ) {
				field.setAttribute( 'aria-invalid', 'true' );
				var errId = ( field.id || field.name ) + '-err';
				var err   = document.createElement( 'span' );
				err.className   = 'gwill-form__field-error';
				err.id          = errId;
				err.setAttribute( 'aria-live', 'assertive' );
				err.textContent = labelFor( form, field ) + ' is required.';
				field.setAttribute( 'aria-describedby', errId );
				field.after( err );
				if ( ! firstBad ) firstBad = field;
				valid = false;
			}
		} );

		if ( firstBad ) firstBad.focus();
		return valid;
	}

	function labelFor( form, field ) {
		var label = form.querySelector( 'label[for="' + field.id + '"]' );
		return label ? label.textContent.replace( /[*\u2009]/g, '' ).trim() : 'This field';
	}

	// ── Network retry ─────────────────────────────────────────────────────────
	//
	// Mobile networks silently drop idle TCP connections. The page loads fine
	// but the first fetch() after a period of inactivity fails at the network
	// layer — not WordPress. One automatic retry after 800ms covers the case
	// without the user ever seeing an error.

	function fetchWithRetry( url, options, retries ) {
		retries = ( retries === undefined ) ? 1 : retries;
		return fetch( url, options ).catch( function ( err ) {
			if ( retries > 0 ) {
				return new Promise( function ( resolve ) {
					setTimeout( function () {
						resolve( fetchWithRetry( url, options, retries - 1 ) );
					}, 800 );
				} );
			}
			throw err;
		} );
	}

}() );
