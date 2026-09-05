<?php
/**
 * Newsletter Signup (Brevo Contacts API)  -  GWill Starter
 *
 * Handles the 'newsletter' form pattern which adds an email to a Brevo
 * contact list via the Contacts API. Does not email anyone.
 *
 * @package GWill_Starter
 * @since   1.0.58
 *
 * ── REQUIRED API KEY SCOPE ───────────────────────────────────────────────────
 *
 * The API key defined in GWILL_BREVO_API_KEY MUST have the "Contacts:Write"
 * scope (or "Full Access" if granular scopes unavailable). Created in:
 *   Settings → SMTP & API → API Keys tab
 * NOT the SMTP tab. The SMTP key (used for GWILL_SMTP_PASS) will NOT work
 * for the Contacts API  -  they are different credentials with different
 * permissions, even though they're generated from the same dashboard section.
 * This is Brevo's design, not a limitation of this integration.
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// Newsletter signup (Brevo Contacts API)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Add an email address to a Brevo contact list via the Contacts API.
 *
 * This is NOT the same credential as GWILL_SMTP_* above. Brevo issues SMTP
 * keys (authenticate sending mail) and API keys (authenticate REST calls)
 * as two separate secrets from the same dashboard section  -  the SMTP
 * password will not work here, on purpose, this is Brevo's own design,
 * not a limitation of this integration.
 *
 * updateEnabled is always sent true: a returning visitor re-submitting an
 * email already on the list should silently succeed, not surface a
 * confusing "already subscribed" error  -  resubmitting should just feel
 * like it worked, whether or not anything actually changed on Brevo's end.
 *
 * @param  string $email Already validated with is_email() by the caller.
 * @return true|string   True on success; a translated, user-facing error
 *                        message string on failure.
 * @since  1.0.58
 */
function gwill_brevo_add_contact( string $email ) {

	if ( ! defined( 'GWILL_BREVO_API_KEY' ) || ! GWILL_BREVO_API_KEY
		|| ! defined( 'GWILL_BREVO_LIST_ID' ) || ! GWILL_BREVO_LIST_ID
	) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[GWill Forms] Newsletter signup attempted but GWILL_BREVO_API_KEY / GWILL_BREVO_LIST_ID are not configured.' );
		return __( 'Newsletter signup is not available right now. Please try again later.', 'gwill-starter' );
	}

	$response = wp_remote_post( 'https://api.brevo.com/v3/contacts', [
		'timeout' => 10,
		'headers' => [
			'api-key'      => GWILL_BREVO_API_KEY,
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		],
		'body'    => wp_json_encode( [
			'email'         => $email,
			'listIds'       => [ (int) GWILL_BREVO_LIST_ID ],
			'updateEnabled' => true,
		] ),
	] );

	if ( is_wp_error( $response ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[GWill Forms] Brevo request failed: ' . $response->get_error_message() );
		return __( 'Could not reach the newsletter service. Please try again shortly.', 'gwill-starter' );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	// 201 = newly created contact. 204 = updateEnabled merged into an
	// already-existing contact  -  Brevo's documented upsert behaviour for
	// this endpoint, a real difference, not an arbitrary alternate
	// success code being treated leniently here.
	if ( in_array( $code, [ 201, 204 ], true ) ) {
		return true;
	}

	$body    = json_decode( wp_remote_retrieve_body( $response ), true );
	$api_msg = is_array( $body ) && ! empty( $body['message'] ) ? $body['message'] : ( 'HTTP ' . $code );
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( '[GWill Forms] Brevo add-contact failed: ' . $api_msg );

	return __( 'Could not complete your subscription. Please try again shortly.', 'gwill-starter' );
}