<?php
/**
 * Template Part: Content Card
 *
 * Used by index.php, archive.php, home.php, and author.php to render each
 * post in a list. Not used on singular views (single.php, page.php handle
 * those directly).
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="entry-thumbnail-wrap">
			<?php
			/*
			 * Thumbnail link is aria-hidden + tabindex="-1": the post title link
			 * immediately below gives keyboard and screen reader users the same
			 * navigation target. The image link would be a redundant duplicate stop.
			 */
			?>
			<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php the_post_thumbnail( 'medium_large', [ 'alt' => '' ] ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="entry-body">

		<div class="entry-meta">
			<?php
			// Primary category — first match; honours RankMath / Yoast primary term meta.
			$gwill_cats = get_the_category();
			if ( $gwill_cats ) :
				$gwill_primary_id = (int) get_post_meta( get_the_ID(), 'rank_math_primary_term_category', true );
				if ( ! $gwill_primary_id ) {
					$gwill_primary_id = (int) get_post_meta( get_the_ID(), '_yoast_wpseo_primary_category', true );
				}
				$gwill_cat = null;
				if ( $gwill_primary_id ) {
					foreach ( $gwill_cats as $c ) {
						if ( (int) $c->term_id === $gwill_primary_id ) {
							$gwill_cat = $c;
							break;
						}
					}
				}
				$gwill_cat = $gwill_cat ?? $gwill_cats[0];
			?>
				<a class="entry-cat" href="<?php echo esc_url( get_category_link( $gwill_cat->term_id ) ); ?>">
					<?php echo esc_html( $gwill_cat->name ); ?>
				</a>
				<span class="entry-meta__sep" aria-hidden="true"> &middot; </span>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</div>

		<h2 class="entry-title">
			<a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a>
		</h2>

		<div class="entry-summary">
			<?php the_excerpt(); ?>
		</div>

	</div>

</article>
