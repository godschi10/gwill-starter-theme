<?php
/**
 * Contact Forms — GWill Starter
 *
 * Handles all contact form submissions via a single WordPress AJAX action.
 * Every form type in template-parts/forms/ routes through gwill_handle_contact_form().
 * The 'gwill_form_id' hidden field tells the handler which form was submitted.
 *
 * ── CONFIGURATION (add to wp-config.php — all constants are optional) ─────────
 *
 *   // SMTP relay — replaces wp_mail()'s default PHP mail()
 *   define( 'GWILL_SMTP_HOST',  'smtp-relay.brevo.com' );  // hostname
 *   define( 'GWILL_SMTP_PORT',  587 );                    // 587=TLS, 465=SSL
 *   define( 'GWILL_SMTP_USER',  'your@email.com' );       // username
 *   define( 'GWILL_SMTP_PASS',  'your-app-password' );    // password
 *
 *   // Sender identity shown in inbox
 *   define( 'GWILL_FROM_EMAIL', 'noreply@yoursite.com' );
 *   define( 'GWILL_FROM_NAME',  'Your Site' );
 *
 *   // Recipient (falls back to WP admin email)
 *   define( 'GWILL_TO_EMAIL', 'you@yoursite.com' );
 *
 *   // Optional feature flags (default false)
 *   define( 'GWILL_AUTOREPLY',  true );  // send confirmation to submitter
 *   define( 'GWILL_LOG_FORMS',  true );  // log to {prefix}gwill_form_submissions
 *
 * ── DB TABLE (create manually when GWILL_LOG_FORMS is true) ──────────────────
 *
 *   CREATE TABLE {prefix}gwill_form_submissions (
 *       id         BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *       form_id    VARCHAR(50)  NOT NULL,
 *       email      VARCHAR(200) NOT NULL,
 *       fields     LONGTEXT     NOT NULL,
 *       ip_hash    VARCHAR(64)  NOT NULL,
 *       status     VARCHAR(20)  NOT NULL DEFAULT 'new',
 *       created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *       KEY form_id (form_id),
 *       KEY created_at (created_at)
 *   );
 *
 * @author  G-will Chijioke <hello@gwillchijioke.com>
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// SMTP configuration
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Inject SMTP credentials into PHPMailer before every wp_mail() call.
 *
 * Runs only when GWILL_SMTP_HOST is defined. Without it the hook returns
 * immediately and wp_mail() falls back to server PHP mail() — which is
 * correct behaviour when SMTP isn't configured yet.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer The PHPMailer instance passed by reference.
 * @since 1.0.20
 */
function gwill_configure_smtp( $phpmailer ): void {
	if ( ! defined( 'GWILL_SMTP_HOST' ) ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = GWILL_SMTP_HOST;
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Port       = defined( 'GWILL_SMTP_PORT' ) ? (int) GWILL_SMTP_PORT : 587;
	$phpmailer->Username   = defined( 'GWILL_SMTP_USER' ) ? GWILL_SMTP_USER : '';
	$phpmailer->Password   = defined( 'GWILL_SMTP_PASS' ) ? GWILL_SMTP_PASS : '';
	$phpmailer->SMTPSecure = ( 465 === (int) $phpmailer->Port ) ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

	if ( defined( 'GWILL_FROM_EMAIL' ) && is_email( GWILL_FROM_EMAIL ) ) {
		// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- PHPMailer method
		$phpmailer->setFrom( GWILL_FROM_EMAIL, defined( 'GWILL_FROM_NAME' ) ? GWILL_FROM_NAME : '' );
	}
}
add_action( 'phpmailer_init', 'gwill_configure_smtp' );

// ─────────────────────────────────────────────────────────────────────────────
// From-identity filters (server mail + SMTP)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Replace "WordPress" sender name with the site name (or GWILL_FROM_NAME constant).
 *
 * Fires for every wp_mail() call — SMTP or server mail. Without this, the
 * default WordPress core value of "WordPress" appears in the inbox From field.
 */
add_filter( 'wp_mail_from_name', function ( string $name ): string {
	return defined( 'GWILL_FROM_NAME' ) ? GWILL_FROM_NAME : get_bloginfo( 'name' );
} );

/**
 * Override the From address only when GWILL_FROM_EMAIL is explicitly set.
 *
 * Without the constant, the server default (wordpress@domain.com) is preserved
 * so we do not break any existing delivery path.
 */
add_filter( 'wp_mail_from', function ( string $email ): string {
	if ( defined( 'GWILL_FROM_EMAIL' ) && is_email( GWILL_FROM_EMAIL ) ) {
		return GWILL_FROM_EMAIL;
	}
	return $email;
} );

// ─────────────────────────────────────────────────────────────────────────────
// REST nonce endpoint
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
 * @since 1.0.20
 */
function gwill_handle_contact_form(): void {

	// See gwill_ajax_get_nonce() for why this matters: any stray output
	// before wp_send_json_*() corrupts the JSON body on an otherwise-200
	// response, which forms.js can only report as an opaque parse failure.
	if ( ob_get_level() > 0 ) {
		ob_clean();
	}

	// Nonce — wp_ajax actions receive a nonce from the hidden field set by
	// wp_nonce_field() in each form template. check_ajax_referer() dies/returns
	// false; the third argument (false) makes it return false on failure instead
	// of calling wp_die(), so we can send a proper JSON error.
	if ( ! check_ajax_referer( 'gwill_contact_form', 'gwill_nonce', false ) ) {
		wp_send_json_error(
			[ 'message' => __( 'Security check failed. Please refresh and try again.', 'gwill-starter' ) ],
			403
		);
	}

	// Honeypot — fake success so bots do not know they were caught.
	if ( gwill_form_honeypot_triggered() ) {
		wp_send_json_success( [ 'message' => __( 'Thank you. Your message has been sent.', 'gwill-starter' ) ] );
	}

	// Rate limit — 5 minutes between submissions per IP.
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
			$msg .= ' — SMTP: ' . $smtp_error;
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
		[ 'message' => __( "Thank you — your message has been sent. I'll be in touch soon.", 'gwill-starter' ) ]
	);
}

// ─────────────────────────────────────────────────────────────────────────────
// Sanitisation
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Sanitise all gwill_* fields from a raw POST array.
 *
 * Different sanitiser per semantic type:
 *   textarea  → sanitize_textarea_field  (preserves newlines)
 *   email     → sanitize_email
 *   url       → esc_url_raw              (strips javascript: and invalid schemes)
 *   everything else → sanitize_text_field
 *
 * @param  array $post Raw $_POST data.
 * @return array       Only gwill_* keys, sanitised.
 * @since  1.0.20
 */
function gwill_sanitize_form_fields( array $post ): array {
	$textarea_keys = [
		'gwill_message',
		'gwill_description',
		'gwill_project',
		'gwill_outcome',
		'gwill_feedback',
		'gwill_audience_fit',
		'gwill_campaign_goal',
	];
	$email_keys    = [ 'gwill_email' ];
	$url_keys      = [ 'gwill_site_url', 'gwill_brand_url' ];

	$out = [];
	foreach ( $post as $key => $raw ) {
		if ( ! str_starts_with( $key, 'gwill_' ) ) {
			continue;
		}
		$value = is_array( $raw ) ? '' : (string) $raw;
		$value = wp_unslash( $value );

		if ( in_array( $key, $email_keys, true ) ) {
			$out[ $key ] = sanitize_email( $value );
		} elseif ( in_array( $key, $url_keys, true ) ) {
			$out[ $key ] = esc_url_raw( $value );
		} elseif ( in_array( $key, $textarea_keys, true ) ) {
			$out[ $key ] = sanitize_textarea_field( $value );
		} else {
			$out[ $key ] = sanitize_text_field( $value );
		}
	}
	return $out;
}

// ─────────────────────────────────────────────────────────────────────────────
// Validation
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Required fields per form_id.
 *
 * Filterable so per-project code can adjust without editing this file:
 *
 *   add_filter( 'gwill_required_fields', function( $map ) {
 *       $map['simple'][] = 'gwill_phone';
 *       return $map;
 *   } );
 *
 * @param  string $form_id The form identifier from gwill_form_id POST field.
 * @return string[]        Required field names.
 * @since  1.0.20
 */
function gwill_get_required_fields( string $form_id ): array {
	$map = apply_filters( 'gwill_required_fields', [
		'simple'      => [ 'gwill_name', 'gwill_email', 'gwill_message' ],
		'inquiry'     => [ 'gwill_name', 'gwill_email', 'gwill_service_type', 'gwill_message' ],
		'routed'      => [ 'gwill_inquiry_type', 'gwill_name', 'gwill_email', 'gwill_message' ],
		'multistep'   => [ 'gwill_service_type', 'gwill_budget', 'gwill_name', 'gwill_email', 'gwill_description' ],
		'inline'      => [ 'gwill_email', 'gwill_ask' ],
		'sidebar'     => [ 'gwill_name', 'gwill_email', 'gwill_ask' ],
		'exit_intent' => [ 'gwill_first_name', 'gwill_email' ],
		'application' => [ 'gwill_site_url', 'gwill_project', 'gwill_outcome', 'gwill_email' ],
		'partnership' => [ 'gwill_name', 'gwill_brand', 'gwill_campaign_type', 'gwill_email' ],
		'feedback'    => [ 'gwill_response' ],
	] );
	return $map[ $form_id ] ?? [ 'gwill_email' ];
}

/**
 * Validate that every required field is non-empty.
 *
 * Also rejects submissions where any free-text field begins with a JSON
 * structure character (`{` or `[`). Real users never open a Name, Message,
 * or Description with a JSON object — this pattern exclusively matches
 * automated bots probing for injection vulnerabilities (e.g. bots that
 * paste REST API response payloads into form fields).
 *
 * @param  array    $fields   Sanitised field values.
 * @param  string[] $required Required field keys.
 * @return string[]           Error messages; empty = valid.
 * @since  1.0.20
 */
function gwill_validate_fields( array $fields, array $required ): array {
	$labels = gwill_get_field_labels();
	$errors = [];

	// Required-field check.
	foreach ( $required as $key ) {
		if ( empty( $fields[ $key ] ) ) {
			$label    = $labels[ $key ] ?? ucwords( str_replace( [ 'gwill_', '_' ], [ '', ' ' ], $key ) );
			$errors[] = sprintf(
				/* translators: %s: human-readable field label */
				__( '%s is required.', 'gwill-starter' ),
				$label
			);
		}
	}

	// Bot content check: reject any free-text field that starts with a JSON
	// structure token. Legitimate human input never begins with `{` or `[`.
	$text_keys = [
		'gwill_name',
		'gwill_message',
		'gwill_description',
		'gwill_project',
		'gwill_outcome',
		'gwill_ask',
		'gwill_audience_fit',
		'gwill_campaign_goal',
		'gwill_feedback',
	];
	foreach ( $text_keys as $key ) {
		$val = ltrim( $fields[ $key ] ?? '' );
		if ( '' !== $val && ( str_starts_with( $val, '{' ) || str_starts_with( $val, '[' ) ) ) {
			// Generic message — do not reveal the specific rule to scanners.
			$errors[] = __( 'Invalid input detected.', 'gwill-starter' );
			break;
		}
	}

	return $errors;
}

// ─────────────────────────────────────────────────────────────────────────────
// Email building
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Human-readable labels for gwill_* field names used in email body output.
 *
 * Filterable per project.
 *
 * @return array<string,string>
 * @since  1.0.20
 */
function gwill_get_field_labels(): array {
	return apply_filters( 'gwill_field_labels', [
		'gwill_name'            => __( 'Name',                       'gwill-starter' ),
		'gwill_first_name'      => __( 'First Name',                 'gwill-starter' ),
		'gwill_email'           => __( 'Email',                      'gwill-starter' ),
		'gwill_company'         => __( 'Company / Website',          'gwill-starter' ),
		'gwill_message'         => __( 'Message',                    'gwill-starter' ),
		'gwill_service_type'    => __( 'Service Type',               'gwill-starter' ),
		'gwill_scope'           => __( 'Project Scope',              'gwill-starter' ),
		'gwill_timeline'        => __( 'Timeline',                   'gwill-starter' ),
		'gwill_budget'          => __( 'Budget',                     'gwill-starter' ),
		'gwill_description'     => __( 'Project Description',        'gwill-starter' ),
		'gwill_ask'             => __( 'What do you need help with?', 'gwill-starter' ),
		'gwill_inquiry_type'    => __( 'Inquiry Type',               'gwill-starter' ),
		'gwill_referral'        => __( 'How did you find me?',       'gwill-starter' ),
		'gwill_brand'           => __( 'Brand / Company',            'gwill-starter' ),
		'gwill_brand_url'       => __( 'Brand Website',              'gwill-starter' ),
		'gwill_campaign_type'   => __( 'Campaign Type',              'gwill-starter' ),
		'gwill_campaign_goal'   => __( 'Campaign Goal',              'gwill-starter' ),
		'gwill_audience_fit'    => __( 'Audience Fit',               'gwill-starter' ),
		'gwill_site_url'        => __( 'Current Website',            'gwill-starter' ),
		'gwill_project'         => __( 'What are you working on?',   'gwill-starter' ),
		'gwill_revenue'         => __( 'Current Revenue',            'gwill-starter' ),
		'gwill_outcome'         => __( 'Desired Outcome',            'gwill-starter' ),
		'gwill_why_now'         => __( 'Why Now?',                   'gwill-starter' ),
		'gwill_response'        => __( 'Response',                   'gwill-starter' ),
		'gwill_feedback'        => __( 'Feedback',                   'gwill-starter' ),
		'gwill_source_post'     => __( 'Post',                       'gwill-starter' ),
	] );
}

/**
 * Build a branded HTML email body from sanitised fields.
 *
 * Structure:
 *   - Dark #111111 header: site icon (if set) + site name
 *   - One card per field: label in small-caps, value in left-bordered block
 *   - Submission metadata (timestamp + referring page)
 *   - Light footer: "Sent via [site name] contact form"
 *
 * Site icon is fetched via get_site_icon_url(64) — the image set under
 * Appearance → Customize → Site Identity. Falls back to text-only header
 * silently if no icon is uploaded.
 *
 * Skips internal meta fields (form_id, nonce, honeypot) so they never
 * appear in the notification email. Textarea values use nl2br() to
 * preserve line breaks in HTML output.
 *
 * @param  array  $fields Sanitised form fields.
 * @return string         HTML email body.
 * @since  1.0.41
 */
function gwill_build_email_body( array $fields ): string {
	$labels    = gwill_get_field_labels();
	$skip      = [ 'gwill_form_id', 'gwill_nonce', 'gwill_hp' ];
	$site_name = esc_html( get_bloginfo( 'name' ) );
	$icon_url  = get_site_icon_url( 64 );

	// Inline styles only — email clients strip <style> blocks.
	$html  = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $site_name . '</title></head>';
	$html .= '<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Helvetica,Arial,sans-serif;">';

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:32px 16px;">';
	$html .= '<table width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="max-width:600px;width:100%;">';

	// Header strip
	$html .= '<tr><td style="background:#111111;padding:28px 32px;border-radius:8px 8px 0 0;">';
	if ( $icon_url ) {
		$html .= '<img src="' . esc_url( $icon_url ) . '" width="40" height="40" alt="" style="display:block;margin-bottom:14px;border-radius:6px;">';
	}
	$html .= '<span style="color:#ffffff;font-size:17px;font-weight:700;letter-spacing:-0.01em;">' . $site_name . '</span>';
	$html .= '</td></tr>';

	// Fields
	$html .= '<tr><td style="background:#ffffff;padding:32px;">';

	foreach ( $fields as $key => $value ) {
		if ( empty( $value ) || ! str_starts_with( $key, 'gwill_' ) || in_array( $key, $skip, true ) ) {
			continue;
		}
		$label = $labels[ $key ] ?? ucwords( str_replace( [ 'gwill_', '_' ], [ '', ' ' ], $key ) );
		$value = nl2br( esc_html( (string) $value ) );

		$html .= '<div style="margin-bottom:22px;">';
		$html .= '<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888888;margin-bottom:7px;">' . esc_html( $label ) . '</div>';
		$html .= '<div style="border-left:3px solid #111111;padding-left:14px;font-size:15px;color:#333333;line-height:1.65;">' . $value . '</div>';
		$html .= '</div>';
	}

	// Metadata
	$html .= '<div style="margin-top:28px;padding-top:20px;border-top:1px solid #eeeeee;font-size:12px;color:#aaaaaa;line-height:1.6;">';
	$html .= 'Submitted: ' . esc_html( gmdate( 'Y-m-d H:i:s' ) . ' UTC' );
	if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$html .= '<br>Page: ' . esc_html( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
	}
	$html .= '</div>';

	$html .= '</td></tr>';

	// Footer
	$html .= '<tr><td style="background:#f4f4f5;padding:16px 32px;border-radius:0 0 8px 8px;font-size:12px;color:#aaaaaa;text-align:center;">';
	/* translators: %s: site name */
	$html .= sprintf( esc_html__( 'Sent via %s contact form', 'gwill-starter' ), $site_name );
	$html .= '</td></tr>';

	$html .= '</table></td></tr></table></body></html>';

	return $html;
}

/**
 * Build the email subject line for a given form type.
 *
 * Filterable. Override per project via 'gwill_form_subjects':
 *
 *   add_filter( 'gwill_form_subjects', function( $map ) {
 *       $map['inquiry'] = '[' . get_bloginfo('name') . '] New Inquiry';
 *       return $map;
 *   } );
 *
 * @param  string $form_id Submitted form identifier.
 * @param  array  $fields  Sanitised form fields.
 * @return string          Email subject line.
 * @since  1.0.20
 */
function gwill_build_subject( string $form_id, array $fields ): string {
	$site = get_bloginfo( 'name' );
	$name = $fields['gwill_name'] ?? ( $fields['gwill_first_name'] ?? __( 'Someone', 'gwill-starter' ) );

	$subjects = apply_filters( 'gwill_form_subjects', [
		/* translators: 1: site name, 2: submitter name */
		'simple'      => sprintf( '[%1$s] Contact from %2$s', $site, $name ),
		'inquiry'     => sprintf( '[%s] %s Inquiry: %s', $site, $fields['gwill_service_type'] ?? 'Project', $name ),
		'routed'      => sprintf( '[%s] %s: %s', $site, $fields['gwill_inquiry_type'] ?? 'Contact', $name ),
		'multistep'   => sprintf( '[%s] Quote Request from %s', $site, $name ),
		'inline'      => sprintf( '[%s] Quick Message from %s', $site, $name ),
		'sidebar'     => sprintf( '[%s] Sidebar Inquiry: %s', $site, $name ),
		'exit_intent' => sprintf( '[%s] New Subscriber: %s', $site, $name ),
		'application' => sprintf( '[%s] Application: %s', $site, $name ),
		'partnership' => sprintf( '[%s] Partnership: %s', $site, $fields['gwill_brand'] ?? $name ),
		'feedback'    => sprintf( '[%s] Post Feedback', $site ),
	] );

	return $subjects[ $form_id ] ?? sprintf( '[%s] Form Submission: %s', $site, $name );
}

/**
 * Build headers array for wp_mail() with Reply-To set to the submitter.
 *
 * @param  array    $fields Sanitised form fields.
 * @return string[]         Headers array.
 * @since  1.0.20
 */
function gwill_build_email_headers( array $fields ): array {
	$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

	if ( ! empty( $fields['gwill_email'] ) && is_email( $fields['gwill_email'] ) ) {
		$name      = $fields['gwill_name'] ?? ( $fields['gwill_first_name'] ?? '' );
		$reply_to  = $name ? "{$name} <{$fields['gwill_email']}>" : $fields['gwill_email'];
		$headers[] = 'Reply-To: ' . $reply_to;
	}

	return $headers;
}

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

// ─────────────────────────────────────────────────────────────────────────────
// Routing
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Map an inquiry_type value to a recipient email address.
 *
 * All keys default to GWILL_TO_EMAIL so the routing form works out of the
 * box with a single recipient. Override per project:
 *
 *   add_filter( 'gwill_form_routing_map', function( $map ) {
 *       $map['press']   = 'press@clientsite.com';
 *       $map['support'] = 'support@clientsite.com';
 *       return $map;
 *   } );
 *
 * @param  string $inquiry_type Sanitised value of gwill_inquiry_type.
 * @return string               Validated recipient email address.
 * @since  1.0.20
 */
function gwill_get_routing_email( string $inquiry_type ): string {
	$fallback = gwill_get_to_email();
	$map      = apply_filters( 'gwill_form_routing_map', [
		'press'       => $fallback,
		'partnership' => $fallback,
		'support'     => $fallback,
		'reader'      => $fallback,
		'general'     => $fallback,
	] );
	return sanitize_email( $map[ $inquiry_type ] ?? $fallback );
}

// ─────────────────────────────────────────────────────────────────────────────
// Spam protection
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Check whether the hidden honeypot field was populated (bot indicator).
 *
 * The gwill_hp field is hidden from real users via CSS (.gwill-honey).
 * Bots that scrape and fill all fields will populate it.
 *
 * @return bool True if honeypot was triggered.
 * @since  1.0.20
 */
function gwill_form_honeypot_triggered(): bool {
	return ! empty( $_POST['gwill_hp'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified before this call
}

/**
 * Resolve the real client IP, accounting for CDN and reverse-proxy headers.
 *
 * SECURITY — proxy headers are NOT trusted by default.
 *
 * HTTP_X_FORWARDED_FOR (and, by extension, HTTP_CF_CONNECTING_IP) are only
 * trustworthy when EVERY request genuinely passes through the proxy that
 * sets them — i.e. the origin server is firewalled to reject connections
 * that don't come from Cloudflare. On typical shared cPanel hosting, the
 * origin is usually reachable directly via its own IP unless that firewall
 * rule is explicitly configured. If it isn't, a request straight to the
 * origin lets an attacker set ANY value they want for these headers —
 * including a fresh, unique fake IP on every single request, which
 * completely defeats gwill_form_rate_limited()'s per-IP cooldown and
 * reopens the form to unlimited rapid-fire spam.
 *
 * Default behaviour (GWILL_TRUST_PROXY_HEADERS undefined or false): always
 * use REMOTE_ADDR. It's the actual TCP peer address — enforced by the
 * network stack, not an HTTP header — so it cannot be spoofed by the
 * client. Behind Cloudflare without the origin firewalled, REMOTE_ADDR
 * will be Cloudflare's edge IP rather than the visitor's, which makes
 * rate-limiting coarser (many visitors briefly share an edge IP) but never
 * spoofable — a strictly safer failure mode than trusting an attacker-
 * controlled header.
 *
 * To restore per-visitor accuracy when the origin genuinely IS firewalled
 * to only accept Cloudflare's published IP ranges, opt in explicitly in
 * wp-config.php:
 *
 *   define( 'GWILL_TRUST_PROXY_HEADERS', true );
 *
 * @return string  Raw IP string (not validated — immediately hashed by callers).
 * @since  1.0.21
 * @since  1.0.49  Gated proxy-header trust behind an explicit opt-in constant.
 */
function gwill_get_client_ip(): string {

	if ( defined( 'GWILL_TRUST_PROXY_HEADERS' ) && GWILL_TRUST_PROXY_HEADERS ) {

		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed immediately by callers
			return trim( (string) $_SERVER['HTTP_CF_CONNECTING_IP'] );
		}
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed immediately by callers
			$ips = explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'] );
			return trim( $ips[0] );
		}
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed immediately by callers
	return (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
}

/**
 * Check whether the current IP is within the rate-limit window.
 *
 * Bypassed for users with at least edit_posts capability — the same gate
 * template-contact-demo.php itself uses. Without this, testing multiple
 * form patterns in quick succession (exactly what the demo page is for)
 * trips the same 5-minute cooldown meant for anonymous spam, and — until
 * v1.0.49's forms.js fix — surfaced as an indistinguishable generic error
 * instead of the actual "please wait" message.
 *
 * Rate limit window defaults to 5 minutes; override via filter:
 *   add_filter( 'gwill_rate_limit_seconds', fn() => 2 * MINUTE_IN_SECONDS );
 *
 * @return bool True if rate-limited (too soon since last submission).
 * @since  1.0.20
 * @since  1.0.49  Added the edit_posts bypass.
 */
function gwill_form_rate_limited(): bool {
	if ( current_user_can( 'edit_posts' ) ) {
		return false;
	}
	return (bool) get_transient( 'gwill_rl_' . hash( 'sha256', gwill_get_client_ip() ) );
}

/**
 * Set the rate-limit transient for the current IP.
 *
 * Called after a successful send to start the cooldown window.
 *
 * @since 1.0.20
 */
function gwill_set_rate_limit(): void {
	$seconds = (int) apply_filters( 'gwill_rate_limit_seconds', 5 * MINUTE_IN_SECONDS );
	set_transient( 'gwill_rl_' . hash( 'sha256', gwill_get_client_ip() ), true, $seconds );
}

// ─────────────────────────────────────────────────────────────────────────────
// DB logging (opt-in)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Insert a form submission record into the gwill_form_submissions table.
 *
 * Runs only when GWILL_LOG_FORMS is true. Silently bails if the table
 * does not exist — create it manually using the schema in the file header.
 *
 * GDPR note: email addresses are stored in plaintext. Ensure your privacy
 * policy covers this and configure table retention per local regulations.
 *
 * @param  string $form_id Form identifier.
 * @param  array  $fields  Sanitised form fields.
 * @since  1.0.20
 */
function gwill_log_submission( string $form_id, array $fields ): void {
	global $wpdb;

	$table = $wpdb->prefix . 'gwill_form_submissions';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( $table !== $exists ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->insert(
		$table,
		[
			'form_id'    => $form_id,
			'email'      => $fields['gwill_email'] ?? '',
			'fields'     => (string) wp_json_encode( $fields ),
			'ip_hash'    => hash( 'sha256', gwill_get_client_ip() ),
			'status'     => 'new',
			'created_at' => gmdate( 'Y-m-d H:i:s' ),
		],
		[ '%s', '%s', '%s', '%s', '%s', '%s' ]
	);
}
