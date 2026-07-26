<?php
/**
 * Sanitisation & Validation — GWill Starter
 *
 * Handles sanitisation and validation of all form fields.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

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
		'newsletter'  => [ 'gwill_email' ],
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