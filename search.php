<?php
/**
 * Search results page.
 *
 * Used by both Combo A (standard page-reload) and as the Enter-key
 * fallback for the Combo B modal when JS is unavailable.
 *
 * Since v1.1.0 — Google-style smart results (ported from GWill Tech):
 *   — "Showing results for X. Search instead for Y?" correction banner
 *     (confident misspellings, results still exist)
 *   — <mark> term highlighting in result titles
 *   — "People also searched for" related-term chips
 *   — "Did you mean?" suggestions on the empty state (see
 *     template-parts/search/search-no-results.php)
 *
 * @package GWill_Starter
 * @since   1.0.23
 */

defined( 'ABSPATH' ) || exit;

get_header();
gwill_breadcrumbs();

$search_query = get_search_query();
?>

<article class="search-results" aria-label="<?php esc_attr_e( 'Search results', 'gwill-starter' ); ?>">

	<header class="search-results__header">
		<h1 class="search-results__title">
			<?php echo wp_kses( gwill_search_results_count( $wp_query ), [ 'strong' => [] ] ); ?>
		</h1>
		<?php get_search_form(); ?>
	</header>

	<?php if ( have_posts() ) : ?>

		<?php
		// Google-style "Showing results for X. Search instead for Y?" — only
		// when results EXIST but the query is a confident misspelling of a
		// better term (v1.1.0). Same engine as the empty-state suggestion:
		// one cheap get_posts() pass over ≤ 500 titles (~1 ms).
		$gwill_correction = function_exists( 'gwill_search_suggest' )
			? gwill_search_suggest( $search_query, 1 )
			: [];
		if ( $gwill_correction
			&& (float) $gwill_correction[0]['score'] >= 0.6
			&& $gwill_correction[0]['term'] !== gwill_search_normalize( $search_query ) ) :
			?>
			<p class="search-correct">
				<?php
				esc_html_e( 'Showing results for', 'gwill-starter' );
				echo ' &ldquo;' . esc_html( $search_query ) . '&rdquo; — ';
				esc_html_e( 'search instead for', 'gwill-starter' );
				?>
				<a href="<?php echo esc_url( $gwill_correction[0]['url'] ); ?>"><?php echo esc_html( $gwill_correction[0]['term'] ); ?></a>?
			</p>
		<?php endif; ?>

		<div class="search-results__list">

			<?php
			// Google-style term highlighting: <mark> the query words inside
			// result titles (self-contained gwill_highlight_search_terms —
			// core wp_highlight_search_terms doesn't exist in WP 7.x).
			if ( function_exists( 'gwill_highlight_search_terms' ) ) {
				add_filter( 'the_title', 'gwill_highlight_search_terms' );
			}

			while ( have_posts() ) : the_post();
				$type_obj   = get_post_type_object( get_post_type() );
				$type_label = $type_obj ? $type_obj->labels->singular_name : get_post_type();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'search-result' ); ?>>

				<div class="search-result__meta">
					<span class="search-result__type-badge"><?php echo esc_html( $type_label ); ?></span>
					<time
						class="search-result__date"
						datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"
					><?php echo esc_html( get_the_date() ); ?></time>
				</div>

				<h2 class="search-result__title">
					<a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a>
				</h2>

				<?php if ( get_the_excerpt() ) : ?>
					<p class="search-result__excerpt"><?php the_excerpt(); ?></p>
				<?php endif; ?>

			</article>

			<?php endwhile; ?>

			<?php
			if ( function_exists( 'gwill_highlight_search_terms' ) ) {
				remove_filter( 'the_title', 'gwill_highlight_search_terms' );
			}
			?>

		</div>

		<?php
		// Google's "People also searched for" — related terms mined from
		// the result set's own titles. Zero extra database cost: the
		// posts are already loaded in $wp_query->posts (v1.1.0).
		$gwill_related = function_exists( 'gwill_search_related_terms' )
			? gwill_search_related_terms( $wp_query->posts, $search_query )
			: [];
		if ( $gwill_related ) :
			?>
			<div class="search-related">
				<p class="search-suggest-kicker"><?php esc_html_e( 'People also searched for', 'gwill-starter' ); ?></p>
				<div class="search-related-list">
					<?php foreach ( $gwill_related as $gwill_rx ) : ?>
						<a class="search-related-chip" href="<?php echo esc_url( $gwill_rx['url'] ); ?>"><?php echo esc_html( $gwill_rx['term'] ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<nav class="search-results__pagination" aria-label="<?php esc_attr_e( 'Search results pages', 'gwill-starter' ); ?>">
			<?php
			the_posts_pagination( [
				'mid_size'  => 2,
				'prev_text' => __( '&larr; Previous', 'gwill-starter' ),
				'next_text' => __( 'Next &rarr;', 'gwill-starter' ),
			] );
			?>
		</nav>

	<?php else : ?>

		<?php gwill_part( 'search/search-no-results' ); ?>

	<?php endif; ?>

</article>

<?php get_footer();
