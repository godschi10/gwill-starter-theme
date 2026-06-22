<?php
/**
 * Search results page.
 *
 * Used by both Combo A (standard page-reload) and as the Enter-key
 * fallback for the Combo B modal when JS is unavailable.
 *
 * @package GWill_Starter
 * @since   1.0.23
 */

defined( 'ABSPATH' ) || exit;

get_header();
gwill_breadcrumbs();
?>

<article class="search-results" aria-label="<?php esc_attr_e( 'Search results', 'gwill-starter' ); ?>">

	<header class="search-results__header">
		<h1 class="search-results__title">
			<?php echo wp_kses( gwill_search_results_count( $wp_query ), [ 'strong' => [] ] ); ?>
		</h1>
		<?php get_search_form(); ?>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="search-results__list">

			<?php while ( have_posts() ) : the_post();
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
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>

				<?php if ( get_the_excerpt() ) : ?>
					<p class="search-result__excerpt"><?php the_excerpt(); ?></p>
				<?php endif; ?>

			</article>

			<?php endwhile; ?>

		</div>

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
