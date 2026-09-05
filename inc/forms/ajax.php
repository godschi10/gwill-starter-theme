<?php
/**
 * Main AJAX Handler - GWill Starter
 *
 * Processes all contact form submissions via a single WordPress AJAX action.
 * Every form type in template-parts/forms/ routes through gwill_handle_contact_form().
 * The 'gwill_form_id' hidden field tells the handler which form was submitted.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// AJAX registration
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gwill_contact_form',        'gwill_handle_contact_form' );
add_action( 'wp_ajax_nopriv_gwill_contact_form', 'gwill_handle_contact_form' );

// ─────────────────────────────────────────────────────────────────────────────
// Main AJAX handler
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Process a contact form submission.
 *
 * Execution order: nonce → honeypot → rate limit → sanitise → validate → send.
 * Returns JSON consumed by assets/js/forms.js.
 *
 * One exception to "→ send": form_id 'newsletter' branches to
 * gwill_brevo_add_contact() instead of the email-send path below - a list
 * subscription has no message for anyone to receive by email. Rate
 * limiting and the optional DB log still apply to it identically.
 *
 * @since 1.0.20
 * @since 1.0.58 Added the 'newsletter' branch.
 */
function gwill_handle_contact_form(): void {

	// See gwill_ajax_get_nonce() for why this matters: any stray output
	// before wp_send_json_*() corrupts the JSON body on an otherwise-200
	// response, which forms.js can only report as an opaque parse failure.
	if ( ob_get_level() > 0 ) {
		ob_clean();
	}

	// Nonce - wp_ajax actions receive a nonce from the hidden field set by
	// wp_nonce_field() in each form template. check_ajax_referer() dies/returns
	// false; the third argument (false) makes it return false on failure instead
	// of calling wp_die(), so we can send a proper JSON error.
	if ( ! check_ajax_referer( 'gwill_contact_form', 'gwill_nonce', false ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Security check failed. Please refresh and try again.', 'gwill-starter' ) ],
			403
		);
	}

	// Honeypot - fake success so bots do not know they were caught.
	if ( gwill_form_honeypot_triggered() ) {
		wp_send_json_success( [ 'message' => __( 'Thank you. Your message has been sent.', 'gwill-starter' ) ] );
	}

	// Rate limit - 5 minutes between submissions per IP.
	if ( gwill_form_rate_limited() ) {
		wp_send_json_error(
			[ 'message' => __( 'Please wait a few minutes before sending another message.', 'gwill-starter' ) ],
			429
		);
	}

	// Identify form type.
	$form_id = sanitize_key( wp_unslash( $_POST['gwill_form_id'] ?? 'simple' ) );

	// Sanitise all gwill_* fields from POST.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified above
	$fields = gwill_sanitize_form_fields( (array) $_POST );

	// Validate required fields.
	$errors = gwill_validate_fields( $fields, gwill_get_required_fields( $form_id ) );
	if ( ! empty( $errors ) ) {
		wp_send_json_error( [ 'message' => implode( ' ', $errors ) ] );
	}

	// Validate email format.
	if ( ! empty( $fields['gwill_email'] ) && ! is_email( $fields['gwill_email'] ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Please enter a valid email address.', 'gwill-starter' ) ]
		);
	}

	// Newsletter signup branches here, entirely - it doesn't email anyone
	// (there's no "message" for G-will to receive about a list subscribe),
	// it adds the address to a Brevo list via the REST API. Rate limit and
	// optional DB log still apply, same as every other form; only the
	// recipient/send/autoreply block below is skipped.
	if ( 'newsletter' === $form_id ) {

		$brevo_result = gwill_brevo_add_contact( $fields['gwill_email'] );

		if ( true !== $brevo_result ) {
			wp_send_json_error( [ 'message' => $brevo_result ] );
		}

		gwill_set_rate_limit();

		if ( defined( 'GWILL_LOG_FORMS' ) && GWILL_LOG_FORMS ) {
			gwill_log_submission( $form_id, $fields );
		}

		wp_send_json_success(
			[ 'message' => __( "Thanks - you're subscribed.", 'gwill-starter' ) ]
		);
	}

	// Determine recipient (routing form maps type to address).
	$to = ( 'routed' === $form_id )
		? gwill_get_routing_email( sanitize_key( $fields['gwill_inquiry_type'] ?? 'general' ) )
		: gwill_get_to_email();

	// Capture the exact PHPMailer error if wp_mail() fails.
	// The wp_mail_failed action fires synchronously inside wp_mail() so
	// $smtp_error is populated before gwill_send_contact_email() returns.
	$smtp_error = '';
	add_action(
		'wp_mail_failed',
		function ( \WP_Error $wp_error ) use ( &$smtp_error ) {
			$smtp_error = $wp_error->get_error_message();
			// Always write to PHP error log so it appears in WP_DEBUG_LOG.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[GWill Forms] wp_mail() failed: ' . $smtp_error );
		}
	);

	// Send.
	if ( ! gwill_send_contact_email( $to, $form_id, $fields ) ) {
		$msg = __( 'Your message could not be sent. Please try again or reach out directly.', 'gwill-starter' );
		// Surface the raw SMTP error in the browser when WP_DEBUG is on
		// so you can diagnose without needing server log access.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $smtp_error ) {
			$msg .= ' - SMTP: ' . $smtp_error;
		}
		wp_send_json_error( [ 'message' => $msg ] );
	}

	// Start rate-limit window only after a successful send.
	gwill_set_rate_limit();

	// Optional auto-reply.
	// Skip for micro-interaction forms where a "will be in touch soon" message
	// makes no sense: feedback (Yes/No post reaction) and exit_intent (subscriber capture).
	$autoreply_skip = [ 'feedback', 'exit_intent' ];
	if ( defined( 'GWILL_AUTOREPLY' ) && GWILL_AUTOREPLY && ! in_array( $form_id, $autoreply_skip, true ) ) {
		gwill_send_autoreply( $fields );
	}

	// Optional DB log.
	if ( defined( 'GWILL_LOG_FORMS' ) && GWILL_LOG_FORMS ) {
		gwill_log_submission( $form_id, $fields );
	}

	wp_send_json_success(
		[ 'message' => __( "Thank you - your message has been sent. I'll be in touch soon.", 'gwill-starter' ) ]
	);
}