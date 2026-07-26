<?php
/**
 * Email Sending — GWill Starter
 *
 * Functions for sending contact emails and auto-replies.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// Email sending
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Get the fallback recipient email address.
 *
 * @return string GWILL_TO_EMAIL constant or the WordPress admin email.
 * @since  1.0.20
 */
function gwill_get_to_email(): string {
	return defined( 'GWILL_TO_EMAIL' ) ? GWILL_TO_EMAIL : (string) get_option( 'admin_email' );
}

/**
 * Send the main notification email to the site owner.
 *
 * @param  string $to      Recipient email address.
 * @param  string $form_id Form identifier.
 * @param  array  $fields  Sanitised form fields.
 * @return bool            True on success.
 * @since  1.0.20
 */
function gwill_send_contact_email( string $to, string $form_id, array $fields ): bool {
	return wp_mail(
		$to,
		gwill_build_subject( $form_id, $fields ),
		gwill_build_email_body( $fields ),
		gwill_build_email_headers( $fields )
	);
}

/**
 * Send an auto-reply acknowledgement to the submitter.
 *
 * Only runs when GWILL_AUTOREPLY is true and a valid email is present.
 * The message body is filterable via 'gwill_autoreply_message'.
 *
 * @param  array $fields Sanitised form fields.
 * @return bool          True on success.
 * @since  1.0.20
 */
function gwill_send_autoreply( array $fields ): bool {
	if ( empty( $fields['gwill_email'] ) || ! is_email( $fields['gwill_email'] ) ) {
		return false;
	}

	$site = get_bloginfo( 'name' );
	$name = $fields['gwill_name'] ?? ( $fields['gwill_first_name'] ?? '' );
	$to   = $name ? "{$name} <{$fields['gwill_email']}>" : $fields['gwill_email'];

	$message = apply_filters(
		'gwill_autoreply_message',
		sprintf(
			/* translators: 1: submitter first name or "there", 2: site name */
			__(
				"Hi %1\$s,\n\nThanks for your message. I've received it and will get back to you soon.\n\n\xe2\x80\x94 %2\$s",
				'gwill-starter'
			),
			$name ?: __( 'there', 'gwill-starter' ),
			$site
		),
		$fields
	);

	return wp_mail(
		$to,
		/* translators: %s: site name */
		sprintf( __( 'Thanks for reaching out to %s', 'gwill-starter' ), $site ),
		$message,
		[
			// Detect whether the filtered message contains HTML.
			// The default body is plain text, but the gwill_autoreply_message
			// filter can return HTML (e.g. <p> tags). Sending HTML with
			// Content-Type: text/plain causes email clients to render raw tags
			// rather than the formatted message. Detect and switch accordingly.
			( preg_match( '/<[^>]+>/', $message ) ? 'Content-Type: text/html' : 'Content-Type: text/plain' ) . '; charset=UTF-8',
			'Reply-To: ' . gwill_get_to_email(),
		]
	);
}