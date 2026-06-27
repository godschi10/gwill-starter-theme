<?php
/**
 * Pricing table component.
 *
 * Deliberately NOT a custom post type, unlike testimonials. A pricing
 * lineup (Starter/Pro/Enterprise) is a small, tightly-coupled set that
 * changes rarely and is normally hand-built once per client project —
 * it doesn't gain anything from being individually manageable WP_Post
 * objects in wp-admin the way testimonials (naturally many, independently
 * added over time by different people) genuinely do. The API here is a
 * plain PHP array passed straight to a template tag, matching how a
 * developer would naturally hardcode three or four plans directly in
 * whatever page template they're building for a given client.
 *
 * No shortcode wrapper either, also deliberately — unlike the testimonials
 * and newsletter features. A shortcode's flat string attributes have no
 * sane way to carry nested per-plan feature lists without resorting to
 * JSON crammed into an HTML attribute, which is more awkward to hand-write
 * than it's worth. This is a developer-placed component, not a
 * content-editor one, and the function call itself is the whole API.
 *
 * @package GWill_Starter
 * @since   1.0.63
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a pricing table.
 *
 * Every key in a plan is optional and nothing here throws on a missing
 * one — a blank heading or an absent price just renders blank. This is
 * developer-supplied data hardcoded into a template, not visitor input
 * that needs strict validation; the burden here is output escaping
 * (handled in the template part), not input validation.
 *
 * @param array<int,array{
 *     name?: string,
 *     price?: string,
 *     period?: string,
 *     description?: string,
 *     features?: string[],
 *     cta_text?: string,
 *     cta_url?: string,
 *     featured?: bool,
 *     badge?: string,
 * }> $plans
 * @param array{currency?:string} $args
 * @since 1.0.63
 */
function gwill_pricing_table( array $plans, array $args = [] ): void {

	/**
	 * The currency symbol prepended to every plan's price.
	 *
	 * Per-call $args['currency'] always wins when set; this filter exists
	 * for the common case of one project running entirely in a non-$
	 * currency, so every gwill_pricing_table() call across the site
	 * doesn't need its own explicit override repeated at each call site.
	 *
	 * @param string $currency
	 * @since 1.0.63
	 */
	$currency = $args['currency'] ?? apply_filters( 'gwill_pricing_currency_symbol', '$' );

	gwill_part( 'pricing-table', [
		'plans'    => $plans,
		'currency' => $currency,
	] );
}
