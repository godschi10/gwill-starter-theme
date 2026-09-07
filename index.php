<?php
/**
 * Fallback Template (index.php)
 *
 * WordPress's last-resort template - used only when no more specific
 * template matches the current query (e.g. home.php/archive.php/single.php
 * etc. all exist in this theme and take priority for their respective
 * contexts; this mainly catches edge cases like a custom post type archive
 * with no dedicated archive-{posttype}.php).
 *
 * Kept in parity with the other listing templates (breadcrumbs + numbered
 * pagination) rather than as a bare unstyled fallback - found missing both
 * during the v1.0.49 audit; every other listing template already had them.
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;
get_header();
gwill_breadcrumbs();
?>

<?php if ( have_posts() ) : ?>

	<header class="archive-header">
		<h1 class="archive-title"><?php echo esc_html( gwill_index_title() ); ?></h1>
		<?php if ( gwill_list_count() ) : ?>
			<p class="archive-header__count"><?php
				printf(
					/* translators: %s: number of posts in this list. */
					esc_html( _n( '%s article', '%s articles', gwill_list_count(), 'gwill-starter' ) ),
					esc_html( number_format_i18n( gwill_list_count() ) )
				);
			?></p>
		<?php endif; ?>
	</header>

	<?php
	/*
	 * v1.12.1 (C17): the .post-list wrapper is the card-chrome hook -
	 * .gwill-cards body class + this container scope all list styling,
	 * so plain mode needs zero overrides (no rules apply).
	 */
	?>
	<div class="post-list">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php gwill_part( 'content' ); ?>
	<?php endwhile; ?>
	</div>

	<?php
	the_posts_pagination( [
		'mid_size'  => 2,
		'prev_text' => __( '&larr; Prev', 'gwill-starter' ),
		'next_text' => __( 'Next &rarr;', 'gwill-starter' ),
	] );
	?>

<?php else : ?>
	<?php gwill_part( 'content-none' ); ?>
<?php endif; ?>

<?php get_footer(); ?>
