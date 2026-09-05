<?php

/*
Table of Contents
1. gwill_feed_sources
2. gwill_feed_normalize_url
3. gwill_feed_posts
*/

/**
 * Cross-site feed - GWill Starter (v1.8.0).
 *
 * Ported from portfolio inc/feed.php (live-proven on the King's own
 * site), made brand-agnostic:
 *   - the portfolio hardcodes its blog sources; the starter ships ZERO
 *     sources and exposes them via the gwill_feed_sources filter - a
 *     build opts in with one add_filter, nothing to edit in-theme;
 *   - the proven caching strategy carries over EXACTLY:
 *       fresh transient -> serve, no network call
 *       expired         -> fetch (4s hard cap), refresh, serve
 *       fetch fails     -> serve the long-lived stale copy, else hide
 *       every failure path caches its outcome for 10 min so a down
 *         remote never becomes a per-page-load tax
 *   - REST titles/excerpts arrive HTML-encoded: strip tags THEN decode
 *     entities or &#8217; renders literally (portfolio law).
 *
 * Usage (build's functions.php or a small plugin):
 *   add_filter( 'gwill_feed_sources', fn() => array(
 *       array( 'base' => 'https://blog.example.com', 'transient' => 'example_blog_feed', 'count' => 3 ),
 *   ) );
 *   foreach ( gwill_feed_posts( $source ) as $card ) { ... }
 *   // Or let the theme pick them up: gwill_feed_all() returns cards
 *   // from every configured source, interleaved.
 *
 * @package GWill_Starter
 * @since   1.8.0
 */

defined( 'ABSPATH' ) || exit;

// ── 1. gwill_feed_sources ─────────────────────────────────
/**
 * The configured remote feeds. Ships EMPTY - the starter is
 * brand-agnostic; builds opt in via the filter (see docblock above).
 *
 * @param array<int, array{
 *   base:      string,  Remote blog home URL.
 *   transient: string, Cache key (prefix with the build slug).
 *   count:     int,     Posts to fetch, 1-6.
 * }> $sources
 * @return array
 */
function gwill_feed_sources(): array {
	/**
	 * Filter the cross-site feed sources.
	 *
	 * @param array $sources See gwill_feed_sources() docblock.
	 */
	return apply_filters( 'gwill_feed_sources', array() );
}

/**
 * Cards from ALL configured sources (newest first). Empty when nothing
 * is configured - callers hide the section entirely (the portfolio
 * pattern: a hidden section beats a dead one).
 *
 * @param int $count Max cards overall (after interleave, newest first).
 * @return array[]
 */
function gwill_feed_all( int $count = 3 ): array {
	$cards = array();
	foreach ( gwill_feed_sources() as $source ) {
		$fetched = gwill_feed_posts( $source );
		if ( is_array( $fetched ) ) {
			$cards = array_merge( $cards, $fetched );
		}
	}
	if ( count( $cards ) < 2 ) {
		return array_slice( $cards, 0, $count );
	}
	// Newest first across sources (each card carries 'timestamp').
	usort( $cards, function ( $a, $b ) {
		return ( $b['timestamp'] ?? 0 ) <=> ( $a['timestamp'] ?? 0 );
	} );
	return array_slice( $cards, 0, $count );
}

// ── 2. gwill_feed_normalize_url ───────────────────────────
/**
 * Normalize a URL from the remote origin onto its public host.
 *
 * The remote DB may store siteurl on a raw origin host (dev tunnel,
 * internal address), so REST links/media come back on that origin.
 * Rebuild them on the configured public base so visitors never see it.
 *
 * @param string $url  Absolute URL from the REST response.
 * @param string $base Public blog base URL.
 * @return string
 */
function gwill_feed_normalize_url( $url, $base ) {
	if ( ! $url || ! $base ) {
		return $url;
	}

	$base_host = wp_parse_url( $base, PHP_URL_HOST );
	$path      = wp_parse_url( $url, PHP_URL_PATH );

	if ( ! $base_host || ! $path ) {
		return $url;
	}

	return 'https://' . $base_host . $path;
}

// ── 3. gwill_feed_posts ───────────────────────────────────
/**
 * Fetch + cache the latest posts from a remote WordPress blog.
 *
 * @param array $source See gwill_feed_sources() docblock.
 * @return array[] Card arrays; empty when unreachable and no stale copy.
 */
function gwill_feed_posts( $source ) {
	$source = wp_parse_args( (array) $source, array(
		'base'      => '',
		'transient' => '',
		'count'     => 3,
	) );

	$count = max( 1, min( 6, absint( $source['count'] ) ) );

	if ( empty( $source['base'] ) || empty( $source['transient'] ) ) {
		return array();
	}

	// 1. Fresh transient? (A cached EMPTY array is a valid short-TTL
	//    failure marker - "recently unreachable, do not re-hit".)
	$cached = get_transient( $source['transient'] );
	if ( is_array( $cached ) ) {
		return array_slice( $cached, 0, $count );
	}

	// 2. Long-lived stale copy for offline fallback.
	$stale = get_option( $source['transient'] . '_stale', array() );

	// Failure shortcut: never return from a failure path without
	// caching the outcome - otherwise EVERY cache-miss render
	// re-attempts the remote fetch (up to 4s each), turning a down
	// remote into a page-load tax. Cache the fallback for 10 minutes.
	$cache_failure = function () use ( $source, $stale ) {
		$fallback = ( is_array( $stale ) && ! empty( $stale ) ) ? $stale : array();
		set_transient( $source['transient'], $fallback, 10 * MINUTE_IN_SECONDS );
		return $fallback;
	};

	$endpoint = esc_url_raw( trailingslashit( $source['base'] ) . 'wp-json/wp/v2/posts' );
	$response = wp_remote_get( add_query_arg( array(
		'per_page' => $count,
		'_embed'   => '1',
	), $endpoint ), array(
		'timeout' => 4,
		'headers' => array( 'Accept' => 'application/json' ),
	) );

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array_slice( $cache_failure(), 0, $count );
	}

	$posts = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $posts ) || empty( $posts ) ) {
		return array_slice( $cache_failure(), 0, $count );
	}

	// 3. Map REST items -> card arrays.
	$cards = array();

	foreach ( array_slice( $posts, 0, $count ) as $post ) {
		// REST titles/excerpts are HTML: strip tags THEN decode entities
		// (&#8217; etc.) or they render literally through esc_html().
		$title = isset( $post['title']['rendered'] )
			? trim( html_entity_decode( wp_strip_all_tags( $post['title']['rendered'] ), ENT_QUOTES, 'UTF-8' ) )
			: '';

		$url = isset( $post['link'] ) ? esc_url_raw( $post['link'] ) : '';
		$url = gwill_feed_normalize_url( $url, $source['base'] );

		// First category name (terms arrive grouped by taxonomy in wp:term).
		$category = '';
		if ( ! empty( $post['_embedded']['wp:term'] ) && is_array( $post['_embedded']['wp:term'] ) ) {
			foreach ( $post['_embedded']['wp:term'] as $tax_terms ) {
				if ( ! is_array( $tax_terms ) ) {
					continue;
				}
				foreach ( $tax_terms as $term ) {
					if ( ! empty( $term['name'] ) ) {
						$category = sanitize_text_field( $term['name'] );
						break 2;
					}
				}
			}
		}

		// REST date_gmt is timezone-independent; render in the viewer's TZ.
		$date      = '';
		$timestamp = 0;
		if ( ! empty( $post['date_gmt'] ) ) {
			$timestamp = (int) get_date_from_gmt( $post['date_gmt'], 'U' );
			$date      = date_i18n( 'M j, Y', $timestamp );
		}

		$read_time = '';
		if ( ! empty( $post['excerpt']['rendered'] ) ) {
			$excerpt   = html_entity_decode( wp_strip_all_tags( $post['excerpt']['rendered'] ), ENT_QUOTES, 'UTF-8' );
			$words     = str_word_count( $excerpt );
			$read_time = max( 1, (int) ceil( $words / 200 ) ) . ' min read';
		}

		$image     = '';
		$image_alt = $title;
		if ( ! empty( $post['_embedded']['wp:featuredmedia'] ) && is_array( $post['_embedded']['wp:featuredmedia'] ) ) {
			$media = $post['_embedded']['wp:featuredmedia'][0];
			if ( ! empty( $media['source_url'] ) ) {
				$image = gwill_feed_normalize_url( esc_url_raw( $media['source_url'] ), $source['base'] );
			}
			if ( ! empty( $media['alt_text'] ) ) {
				$image_alt = sanitize_text_field( $media['alt_text'] );
			}
		}

		$cards[] = array(
			'title'     => $title,
			'url'       => $url,
			'category'  => $category,
			'date'      => $date,
			'timestamp' => $timestamp,
			'read_time' => $read_time,
			'image'     => $image,
			'image_alt' => $image_alt,
			'external'  => true,
		);
	}

	if ( empty( $cards ) ) {
		return array_slice( $cache_failure(), 0, $count );
	}

	// 4. Cache fresh copy (6h) + long-lived stale copy for offline fallback.
	set_transient( $source['transient'], $cards, 6 * HOUR_IN_SECONDS );
	update_option( $source['transient'] . '_stale', $cards, false );

	return $cards;
}
