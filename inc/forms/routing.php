<?php
/**
 * Routing — GWill Starter
 *
 * Maps inquiry types to recipient emails for the 'routed' form pattern.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

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