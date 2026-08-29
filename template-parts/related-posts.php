<?php
/**
 * Template Part: Related Posts
 *
 * Compact grid (thumbnail + title only) shown after the article on
 * single.php. Deliberately leaner than template-parts/content.php's card —
 * a related-posts grid wants quick visual scanning, not the full
 * excerpt+meta treatment a main listing page needs.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

defined( 'ABSPATH' ) || exit;

$gwill_related = gwill_get_related_posts( get_the_ID() );

if ( ! $gwill_related ) {
	return;
}
?>

<section class="related-posts" aria-labelledby="related-posts-heading">

	<h2 id="related-posts-heading" class="related-posts__heading">
		<?php esc_html_e( 'Related Posts', 'gwill-starter' ); ?>
	</h2>

	<div class="related-posts__grid">
		<?php foreach ( $gwill_related as $gwill_related_post ) : ?>
			<article class="related-posts__card">
				<a href="<?php echo esc_url( get_permalink( $gwill_related_post ) ); ?>" class="related-posts__link">
					<?php if ( has_post_thumbnail( $gwill_related_post ) ) : ?>
						<div class="related-posts__thumb">
							<?php echo get_the_post_thumbnail( $gwill_related_post, 'medium', [ 'alt' => '', 'loading' => 'lazy' ] ); ?>
						</div>
					<?php endif; ?>
					<h3 class="related-posts__title">
						<?php echo esc_html( get_the_title( $gwill_related_post ) ); ?>
					</h3>
				</a>
			</article>
		<?php endforeach; ?>
	</div>

</section>
