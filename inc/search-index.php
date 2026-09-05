<?php
/**
 * Smart live-search index (v1.16.80)
 *
 * Serves ONE compact JSON payload of every published post (id, title,
 * url, excerpt, category, date) at /wp-json/gwill/v1/search-index.
 * search-dropdown.js downloads it once per session, then does ALL
 * matching CLIENT-SIDE (typo-tolerant, title-weighted, highlighted)  - 
 * zero network per keystroke, zero server load per keystroke, no plugin.
 *
 * The payload is plain TEXT only (no markup, no admin data) - the same
 * public data the REST posts endpoint already exposes.
 * Transient-cached server-side (1 day) and invalidated on any
 * post lifecycle change.
 *
 * @package GWill_Starter
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

// ── Client-side index bound ────────────────────────────────────────────────
const GWILL_SEARCH_INDEX_MAX = 200;

// ── REST endpoint ──────────────────────────────────────────────────────────
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'gwill/v1',
			'/search-index',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true', // Public read-only - same exposure as the public posts endpoint.
				'callback'            => 'gwill_search_index_rest',
			)
		);
	}
);

/**
 * REST callback: serve the cached index with a short browser cache window.
 */
function gwill_search_index_rest() {
	$data = gwill_search_index_data();
	if ( is_wp_error( $data ) ) {
		return new WP_Error( 'gwill_search_index_error', 'Search index unavailable.', array( 'status' => 500 ) );
	}
	$response = new WP_REST_Response( $data );
	$response->header( 'Cache-Control', 'public, max-age=300' );
	return $response;
}

/**
 * Build (and transient-cache) the search index.
 *
 * @return array|WP_Error
 */
function gwill_search_index_data() {
	$cached = get_transient( 'gwill_search_index' );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$ids = get_posts(
		array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'numberposts'   => GWILL_SEARCH_INDEX_MAX, // bounded: full coverage lives in the FTS5 index (inc/search-fts.php)
			'fields'        => 'ids',
			'orderby'       => 'date',
			'order'         => 'DESC',
			'no_found_rows' => true,
		)
	);

	if ( is_wp_error( $ids ) ) {
		return $ids;
	}

	$items = array();
	foreach ( $ids as $post_id ) {
		$cats = get_the_category( $post_id );
		$cat  = ! empty( $cats ) ? $cats[0] : null;

		// Plain-text title (entities decoded - the client re-escapes before
		// innerHTML, so decoding here prevents double-escaped titles).
		$title = html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' );

		// Excerpt: manual excerpt if set, else a trimmed plain-text slice of
		// the content.
		$excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : get_post_field( 'post_content', $post_id );
		$excerpt = wp_strip_all_tags( wp_trim_words( $excerpt, 28 ) );

		$items[] = array(
			'id'       => (int) $post_id,
			'title'    => $title,
			'url'      => get_permalink( $post_id ),
			'excerpt'  => $excerpt,
			'cat'      => $cat ? $cat->name : '',
			'cat_slug' => $cat ? $cat->slug : '',
			'date'     => get_the_date( 'c', $post_id ),
		);
	}

	set_transient( 'gwill_search_index', $items, DAY_IN_SECONDS );
	return $items;
}

// ── Invalidation ───────────────────────────────────────────────────────────
add_action( 'save_post', 'gwill_search_index_bust' );
add_action( 'deleted_post', 'gwill_search_index_bust' );
add_action( 'wp_trash_post', 'gwill_search_index_bust' );

/**
 * Drop the cached index whenever a post changes.
 */
function gwill_search_index_bust() {
	delete_transient( 'gwill_search_index' );
}