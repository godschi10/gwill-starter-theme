<?php
/**
 * 404 template - makeover v1.12.0 (docs/UI-MAKEOVER-LIST.md B12).
 *
 * Ghost "404" numeral (aria-hidden decoration), search card, CTA row,
 * and a latest-articles grid (up to 3) so a dead end becomes a doorway.
 * The grid queries only when posts exist; zero posts = the whole block
 * stays out of the DOM.
 *
 * @package GWill_Starter
 * @since   1.0.0
 * @since   1.12.0 Ghost numeral, card treatment, CTA row, latest grid.
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>

<section class="error-404 not-found" aria-labelledby="error-404-heading">
	<p class="error-404__ghost" aria-hidden="true">404</p>

	<div class="error-404__card">
		<h1 id="error-404-heading"><?php esc_html_e( 'Page not found', 'gwill-starter' ); ?></h1>
		<p class="error-404__text"><?php esc_html_e( 'The page you\'re looking for doesn\'t exist, moved, or never did. Try a search:', 'gwill-starter' ); ?></p>

		<?php get_search_form(); ?>

		<p class="error-404__cta">
			<a class="gwill-btn gwill-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( '← Back to Home', 'gwill-starter' ); ?>
			</a>
		</p>
	</div>

	<?php
	/*
	 * Latest articles (up to 3). A separate query - the 404 context has no
	 * usable main query. Hidden entirely when the site has no posts.
	 */
	$gwill_404_latest = new WP_Query( [
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	] );
	if ( $gwill_404_latest->have_posts() ) :
		?>
		<div class="error-404__latest">
			<h2 class="error-404__latest-title"><?php esc_html_e( 'Latest articles', 'gwill-starter' ); ?></h2>
			<div class="error-404__grid">
				<?php
				while ( $gwill_404_latest->have_posts() ) :
					$gwill_404_latest->the_post();
					?>
					<article <?php post_class(); ?>>
						<h3 class="entry-title">
							<a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a>
						</h3>
						<div class="entry-meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</div>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
		<?php
	endif;
	?>
</section>

<?php
get_footer();
