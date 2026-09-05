<?php
/**
 * Performance base  -  GWill Starter (v1.3.0, ported from finance v1.0.157).
 *
 * Three generic, dependency-free wins every build inherits:
 *
 *   1. gwill_prime_thumbnail_cache()   -  the_posts filter that batches
 *      thumbnail attachment meta into ONE query per loop. Card grids
 *      call get_the_post_thumbnail() per post; without this, every
 *      card fires its own wp_postmeta query for the attachment's
 *      _wp_attachment_image_alt etc. update_post_thumbnail_cache()
 *      turns N queries into 1.
 *
 *   2. gwill_preload_lcp()             -  <link rel="preload"> for the
 *      Largest Contentful Paint image: the featured image on
 *      singulars, the first post's cover on the home/posts index.
 *      Carries imagesrcset + imagesizes so the browser preloads the
 *      SAME candidate the rendered <img> resolves (no wasted
 *      full-size preload on small viewports). Query-free: the main
 *      query has already run when wp_head fires.
 *
 *   3. Memoized primary category       -  gwill_get_primary_category()
 *      (inc/helpers.php) now caches its result per post ID for the
 *      request; breadcrumbs, single.php, related posts and content
 *      cards all call it for the same post.
 *
 * @package GWill_Starter
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Query performance: batch thumbnail-meta priming
// ---------------------------------------------------------------------------

/**
 * Prime thumbnail attachment meta in one batched query per loop.
 *
 * WP_Query primes meta for the posts in the loop, but NOT for their
 * attachments  -  so every card used to fire its own wp_postmeta query.
 * Guarded to full-object queries only (fields=ids existence checks
 * skip  -  no card markup is rendered from them).
 *
 * @param WP_Post[] $posts Queried posts.
 * @param WP_Query  $query The query that produced them.
 * @return WP_Post[] Unchanged.
 * @since 1.3.0
 */
function gwill_prime_thumbnail_cache( $posts, $query ) {
	if ( $posts && ( ! $query->get( 'fields' ) || 'all' === $query->get( 'fields' ) ) ) {
		update_post_thumbnail_cache( $query );
	}
	return $posts;
}
add_filter( 'the_posts', 'gwill_prime_thumbnail_cache', 10, 2 );

// ---------------------------------------------------------------------------
// LCP image preload
// ---------------------------------------------------------------------------
// The article cover (singulars) and the first card's image (home/posts
// index) are the LCP elements. fetchpriority=high is set on the rendered
// <img>; a preload makes the browser start the image request before
// CSS/JS parsing finishes.
//
// Responsive preload: with imagesrcset + imagesizes the browser preloads
// the exact candidate the <img> resolves  -  no wasted 1200px preload on
// small viewports, no late 1024px fetch. Falls back to a plain href
// preload when the attachment has no srcset (single-candidate images).

add_action( 'wp_head', 'gwill_preload_lcp', 2 );

/**
 * Preload the LCP image: singular cover or first home-index post.
 *
 * Query-free by design: wp_head fires AFTER the main query ran, so the
 * first post of the current query is already in memory.
 *
 * @since 1.3.0
 */
function gwill_preload_lcp(): void {
	if ( is_singular() ) {
		if ( has_post_thumbnail() ) {
			gwill_emit_lcp_preload( (int) get_post_thumbnail_id() );
		}
		return;
	}

	// Home / front posts index / archives: the LCP is the first card's
	// cover. Archives use the same loop card markup, so the first post
	// of ANY post query is a safe target.
	if ( ( is_home() || is_front_page() || is_archive() ) && ! is_search() ) {
		global $wp_query;
		if ( empty( $wp_query->posts ) ) {
			return;
		}
		$first = $wp_query->posts[0];
		if ( $first instanceof WP_Post && has_post_thumbnail( $first ) ) {
			gwill_emit_lcp_preload( (int) get_post_thumbnail_id( $first ) );
		}
	}
}

/**
 * Emit the responsive LCP image preload for an attachment.
 *
 * Uses the same srcset + sizes the rendered <img> gets (via
 * wp_get_attachment_image with 'gwill-hero'), so the browser preloads
 * the exact candidate it will paint. Falls back to a plain href preload
 * when the attachment has no srcset (single-candidate images).
 *
 * @param int $attachment_id Attachment (featured image) ID.
 * @since 1.3.0
 */
function gwill_emit_lcp_preload( int $attachment_id ): void {
	if ( ! $attachment_id ) {
		return;
	}
	$srcset = wp_get_attachment_image_srcset( $attachment_id, 'gwill-hero' );
	$sizes  = wp_get_attachment_image_sizes( $attachment_id, 'gwill-hero' );

	if ( $srcset && $sizes ) {
		echo '<link rel="preload" as="image" imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="' . esc_attr( $sizes ) . '" fetchpriority="high">' . "\n";
		return;
	}

	$src = wp_get_attachment_image_url( $attachment_id, 'gwill-hero' );
	if ( $src ) {
		echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '" fetchpriority="high">' . "\n";
	}
}