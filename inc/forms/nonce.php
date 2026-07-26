<?php
/**
 * REST Nonce Endpoints — GWill Starter
 *
 * Provides nonce endpoints for contact forms.
 * Primary: admin-ajax.php (excluded from LiteSpeed Cache by default).
 * Legacy: REST endpoint (kept for backwards compatibility).
 *
 * @package GWill_Starter
 * @since   1.0.46
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// REST nonce endpoint (legacy — kept for backwards compatibility)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * REST nonce endpoint — kept for backwards compatibility only.
 *
 * Superseded as forms.js's nonce source by gwill_ajax_get_nonce() /
 * admin-ajax.php in v1.0.46 (REST's rest_cookie_check_errors check was
 * returning 403 for logged-in users). Nothing in this theme calls this
 * route anymore. Left registered in case any external integration on a
 * specific site already depends on it.
 *
 * GET /wp-json/gwill/v1/form-nonce
 * Response: { "nonce": "abc123..." }
 *
 * The nonce action ("gwill_contact_form") matches check_ajax_referer() in
 * gwill_handle_contact_form() — no handler changes needed.
 */
add_action( 'rest_api_init', function () {
	register_rest_route(
		'gwill/v1',
		'/form-nonce',
		[
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => function () {
				return rest_ensure_response( [ 'nonce' => wp_create_nonce( 'gwill_contact_form' ) ] );
			},
			'permission_callback' => '__return_true',
		]
	);
} );

/**
 * Nonce endpoint via admin-ajax.php.
 *
 * Replaces the REST endpoint (/gwill/v1/form-nonce) as the primary nonce
 * source for forms.js. admin-ajax.php is excluded from LiteSpeed Cache by
 * default, so nonces are always fresh. It also sidesteps the REST API
 * cookie-auth check (rest_cookie_check_errors at priority 100) that caused
 * logged-in users to receive a 403 — the error that surfaced as "Network
 * error" in all demo page forms.
 *
 * The REST endpoint is kept for backwards compatibility but is no longer
 * called by forms.js. The admin-ajax endpoint is referenced via
 * GwillForms.nonceUrl in inc/enqueue.php.
 *
 * Response shape: { "nonce": "abc123" } — identical to the REST endpoint,
 * so forms.js needs no changes.
 *
 * @since 1.0.46
 */
add_action( 'wp_ajax_gwill_get_nonce',        'gwill_ajax_get_nonce' );
add_action( 'wp_ajax_nopriv_gwill_get_nonce', 'gwill_ajax_get_nonce' );

function gwill_ajax_get_nonce(): void {
	// Discard any output already buffered for this request (a stray PHP
	// notice, a deprecation warning from an unrelated plugin hooked earlier
	// in the request lifecycle, even accidental leading whitespace from a
	// template file with a stray blank line before "<?php") before sending
	// JSON. Without this, such output prepends to the response body —
	// fetch().json() then throws a SyntaxError on an otherwise-200 response,
	// which previously surfaced to the user as an indistinguishable
	// "Network error" with no way to tell it apart from a real connectivity
	// failure. forms.js's catch() now logs the real error to the console,
	// but preventing the corruption here is the actual fix.
	if ( ob_get_level() > 0 ) {
		ob_clean();
	}
	// wp_send_json() outputs the array directly — no success/data wrapper —
	// matching the REST endpoint's { "nonce": "..." } shape.
	wp_send_json( [ 'nonce' => wp_create_nonce( 'gwill_contact_form' ) ] );
}

/**
 * Allow the /gwill/v1/form-nonce REST endpoint to be accessed by logged-in
 * users without the X-WP-Nonce header.
 *
 * Root cause: WordPress's rest_cookie_check_errors (priority 100 on the
 * rest_authentication_errors filter) returns WP_Error('rest_cookie_invalid_nonce')
 * when auth cookies are present but no X-WP-Nonce header is supplied. This
 * causes a 403 for every logged-in user who hits the nonce endpoint from JS —
 * the fetch() rejects, the catch() fires, and "Network error" is shown.
 *
 * Incognito / anonymous users are unaffected because they have no auth cookies,
 * so rest_cookie_check_errors skips them.
 *
 * Fix: run at priority 99 (before the cookie check at 100). If no prior auth
 * decision was made (null) and the request is for our public nonce route,
 * return true ("authenticated" / no error). The permission callback
 * (__return_true) remains the authoritative access gate — returning true here
 * only prevents the spurious 403, not the endpoint's own access control.
 */
add_filter( 'rest_authentication_errors', function ( $result ) {
	if ( null !== $result ) {
		return $result; // Another plugin already made an auth decision — honour it.
	}
	$uri = (string) ( $_SERVER['REQUEST_URI'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- used for URL path comparison only
	if ( false !== strpos( $uri, '/gwill/v1/form-nonce' ) ) {
		return true; // Short-circuit rest_cookie_check_errors for this public endpoint.
	}
	return $result;
}, 99 );