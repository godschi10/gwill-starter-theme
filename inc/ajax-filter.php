<?php

/*
Table of Contents
1. gwill_filter_posts_ajax
*/

/**
 * AJAX post filter - GWill Starter (v1.7.0).
 *
 * Ported from gwill-tech-theme inc/ajax-posts.php (live-proven on the
 * tech site), adapted to the starter dialect:
 *   - cards render via gwill_part( 'content' ) - the starter's own
 *     article card partial (tech had two contexts; the starter renders
 *     one canonical card, so the context param narrows to 'blog');
 *   - category resolution keeps the `cat` (term-ID) query so child-
 *     category posts roll up into the parent - the behaviour site
 *     owners expect from the homepage sections (tech pattern);
 *   - CTA renders in the theme's pill/button dialect with esc classes.
 *
 * Public read-only admin-ajax endpoint that renders a grid of cards
 * for a category, so category filter pills can load fresh content in
 * place (with a spinner) instead of show/hiding already-rendered cards.
 *
 * Parameters (GET):
 *   category  - category slug, or 'all' for the latest posts.
 *   per_page  - how many posts (clamped 1–30, default 9).
 *
 * No nonce: the endpoint only ever returns PUBLIC published posts, so
 * it is functionally equivalent to the public REST search the theme
 * already exposes. A nonce baked into page HTML would go stale under
 * the site's FastCGI full-page cache - the same reason the contact
 * form fetches its nonce separately.
 *
 * INTERPLAY - search-index.php exposes REST search under
 * gwill/v1/search-index; this endpoint is admin-ajax by design (the
 * filter JS uses the same relative admin-ajax URL pattern as the
 * contact form - resolves against the browser origin on any domain).
 *
 * @package GWill_Starter
 * @since   1.7.0
 */

defined( 'ABSPATH' ) || exit;

// ── 1. gwill_filter_posts_ajax ────────────────────────────
/**
 * Handle the gwill_filter_posts AJAX request.
 *
 * @return void Responds with wp_send_json_success() and exits.
 */
function gwill_filter_posts_ajax(): void {

	$category = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only endpoint, see file docblock.
	$per_page = isset( $_GET['per_page'] ) ? (int) $_GET['per_page'] : 9; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per_page = min( max( $per_page, 1 ), 30 );

	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $per_page,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	);

	if ( 'all' !== $category ) {
		// Resolve slug → term ID so child-category posts roll up into
		// the parent category (`cat` includes descendants;
		// `category_name` would not).
		$cat = get_category_by_slug( $category );
		if ( $cat ) {
			$args['cat'] = (int) $cat->term_id;
		} else {
			// Unknown category - return an honest empty result.
			wp_send_json_success( array(
				'html'  => '',
				'count' => 0,
			) );
		}
	}

	$q = new WP_Query( $args );

	ob_start();
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			gwill_part( 'content' );
		}
	} else {
		gwill_part( 'content-none' );
	}
	wp_reset_postdata();
	$html = (string) ob_get_clean();

	wp_send_json_success( array(
		'html'  => $html,
		'count' => (int) $q->post_count,
	) );
}
add_action( 'wp_ajax_gwill_filter_posts', 'gwill_filter_posts_ajax' );
add_action( 'wp_ajax_nopriv_gwill_filter_posts', 'gwill_filter_posts_ajax' );
