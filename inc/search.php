<?php
/**
 * Search — backend functions and plugin swap stub.
 *
 * ════════════════════════════════════════════════════════════════════
 * PLUGIN SWAP STUB
 * ════════════════════════════════════════════════════════════════════
 * Every search query in the theme (results page + live-search REST
 * endpoint) routes through gwill_execute_search(). Three filter hooks
 * let you replace the backend without touching any theme file:
 *
 * 1. gwill_search_post_types — change which post types are searched.
 *
 *    add_filter( 'gwill_search_post_types', fn() => [ 'post', 'project' ] );
 *
 * 2. gwill_search_args — modify WP_Query args before execution.
 *
 *    add_filter( 'gwill_search_args', function ( $args, $term ) {
 *        $args['meta_query'] = [ ... ]; // add custom field search
 *        return $args;
 *    }, 10, 2 );
 *
 * 3. gwill_search_backend — return a WP_Query to completely bypass
 *    native WordPress search (e.g. hand off to SearchWP or Algolia).
 *    Return null to let native WP run (default).
 *
 *    add_filter( 'gwill_search_backend', function ( $result, $args, $term ) {
 *        return new SomePlugin_Query( [ 's' => $term ] ); // must expose ->posts
 *    }, 10, 3 );
 *
 * ════════════════════════════════════════════════════════════════════
 * SEARCH PATTERNS SHIPPED
 * ════════════════════════════════════════════════════════════════════
 * Combo A — Default (expandable icon + page-reload + search.php)
 *   template-parts/search/search-form-expandable.php
 *   search.php
 *
 * Combo B — Opt-in (modal overlay + live REST autocomplete + search.php fallback)
 *   template-parts/search/search-form-modal.php
 *   assets/js/search-modal.js
 *
 * To switch from A to B, replace one line in header.php:
 *   gwill_part( 'search/search-form-expandable' );  ← A
 *   gwill_part( 'search/search-form-modal' );        ← B
 *
 * @package GWill_Starter
 * @since   1.0.23
 */

defined( 'ABSPATH' ) || exit;


// ─────────────────────────────────────────────────────────────────────────────
// Core search execution
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Execute a search query through the plugin swap filter chain.
 *
 * This is the single entry point for all search queries in the theme.
 * See the file-header docblock for how to hook in and swap the backend.
 *
 * @param string $term  Raw (unsanitised) search term.
 * @param array  $args  Optional WP_Query args merged over the defaults.
 * @return WP_Query
 * @since 1.0.23
 */
function gwill_execute_search( string $term, array $args = [] ): WP_Query {

	$defaults = [
		's'              => sanitize_text_field( $term ),
		'post_type'      => apply_filters( 'gwill_search_post_types', [ 'post', 'page' ] ),
		'post_status'    => 'publish',
		'posts_per_page' => (int) get_option( 'posts_per_page', 10 ),
	];

	/**
	 * Filter WP_Query args before the search executes.
	 *
	 * @param array  $args     Merged query args — $args['s'] is sanitized via
	 *                         sanitize_text_field() and is what any filter
	 *                         callback should use for DB operations.
	 * @param string $term_raw The raw, UNsanitized term as originally passed
	 *                         to gwill_execute_search() — provided for
	 *                         context/logging only. Do not use this for any
	 *                         database query; use $args['s'] instead.
	 */
	$args = apply_filters( 'gwill_search_args', wp_parse_args( $args, $defaults ), $term );

	/**
	 * Completely replace search execution.
	 *
	 * Return a WP_Query instance (or any object exposing ->posts and
	 * ->found_posts) to bypass native WordPress search. Return null
	 * (default) to let WP_Query run normally.
	 *
	 * @param WP_Query|null $result Null by default.
	 * @param array         $args   Final query args.
	 * @param string        $term   Sanitised search term.
	 */
	$custom = apply_filters( 'gwill_search_backend', null, $args, $term );

	return ( $custom instanceof WP_Query ) ? $custom : new WP_Query( $args );
}


// ─────────────────────────────────────────────────────────────────────────────
// REST endpoint — live search (Combo B modal)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Register /wp-json/gwill/v1/search for the modal autocomplete.
 *
 * Routes through gwill_execute_search() so all backend swap hooks
 * (SearchWP, Algolia, custom meta) also affect the live results.
 *
 * @since 1.0.23
 */
add_action( 'rest_api_init', function () {
	register_rest_route(
		'gwill/v1',
		'/search',
		[
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'gwill_rest_search_handler',
			'permission_callback' => '__return_true',
			'args'                => [
				's'        => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'minLength'         => 2,
				],
				'per_page' => [
					'default'           => 5,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]
	);
} );

/**
 * REST callback for GET /gwill/v1/search.
 *
 * Returns a minimal JSON array shaped for the autocomplete UI:
 *   [ { id, title, url, type, excerpt }, ... ]
 *
 * Responses are publicly cacheable for 60 s — this is public search data.
 *
 * @param  WP_REST_Request $request
 * @return WP_REST_Response
 * @since  1.0.23
 */
function gwill_rest_search_handler( WP_REST_Request $request ): WP_REST_Response {

	$term     = (string) $request->get_param( 's' );
	$per_page = min( (int) $request->get_param( 'per_page' ), 10 );

	$query   = gwill_execute_search( $term, [ 'posts_per_page' => $per_page ] );
	$results = [];

	foreach ( $query->posts as $post ) {
		$type_obj  = get_post_type_object( $post->post_type );
		$results[] = [
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'type'    => $type_obj ? $type_obj->labels->singular_name : $post->post_type,
			'excerpt' => has_excerpt( $post )
				? wp_trim_words( get_the_excerpt( $post ), 12 )
				: '',
		];
	}

	$response = new WP_REST_Response( $results );
	$response->header( 'X-WP-Total', (int) $query->found_posts );
	$response->header( 'Cache-Control', 'public, max-age=60, s-maxage=60' );

	return $response;
}


// ─────────────────────────────────────────────────────────────────────────────
// Template helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Return the current search term, sanitised for HTML attribute output.
 *
 * IMPORTANT: The return value is already passed through esc_attr(). Do NOT
 * escape the return value again at the call site — double-encoding will
 * corrupt & → &amp;amp; and produce visible noise in the input field.
 *
 * @return string HTML-attribute-escaped search term.
 * @since 1.0.23
 */
function gwill_get_search_term(): string {
	return esc_attr( get_search_query() );
}

/**
 * Return a localised results-count string for the search results header.
 *
 * Produces:
 *   '12 results for "foo"'   (multiple)
 *   '1 result for "foo"'     (single)
 *   'No results for "foo"'   (zero)
 *
 * @param  WP_Query $query The current search query.
 * @return string          Safe HTML — only <strong> allowed.
 * @since  1.0.23
 */
function gwill_search_results_count( WP_Query $query ): string {

	$count = (int) $query->found_posts;
	$term  = '<strong>' . esc_html( get_search_query() ) . '</strong>';

	if ( 0 === $count ) {
		return sprintf(
			/* translators: %s: search term wrapped in <strong> */
			__( 'No results for %s', 'gwill-starter' ),
			$term
		);
	}

	return sprintf(
		/* translators: 1: formatted integer result count  2: search term in <strong> */
		_n( '%1$s result for %2$s', '%1$s results for %2$s', $count, 'gwill-starter' ),
		number_format_i18n( $count ),
		$term
	);
}
