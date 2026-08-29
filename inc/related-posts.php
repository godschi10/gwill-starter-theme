<?php
/**
 * Related Posts
 *
 * Matches by primary category (falling back to any assigned category if no
 * primary term is set), excludes the current post, ordered by date.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get related posts for a given post.
 *
 * @param  int $post_id Post ID. Defaults to the current post in the loop.
 * @param  int $count   Number of related posts to return. Filterable via
 *                       'gwill_related_posts_count'.
 * @return WP_Post[]    Empty array if the post has no categories, or no
 *                       other posts share one.
 * @since  1.0.50
 */
function gwill_get_related_posts( int $post_id = 0, int $count = 3 ): array {

	$post_id = $post_id ?: get_the_ID();
	$count   = (int) apply_filters( 'gwill_related_posts_count', $count );

	$primary_cat = gwill_get_primary_category( $post_id );

	if ( $primary_cat ) {
		// Primary category set — match on that one term specifically. Tighter
		// relevance than "any shared category" when a primary term exists,
		// since that's the author's own explicit signal of what this post is
		// really about.
		$cat_ids = [ $primary_cat->term_id ];
	} else {
		$cat_ids = wp_list_pluck( get_the_category( $post_id ), 'term_id' );
		if ( ! $cat_ids ) {
			return [];
		}
	}

	$args = apply_filters( 'gwill_related_posts_args', [
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'post__not_in'        => [ $post_id ],
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'category__in'        => $cat_ids,
		'no_found_rows'       => true, // pagination never needed here — skip the COUNT query.
	], $post_id );

	$query = new WP_Query( $args );

	return $query->posts;
}
