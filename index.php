<?php
/**
 * Fallback Template (index.php)
 *
 * WordPress's last-resort template — used only when no more specific
 * template matches the current query (e.g. home.php/archive.php/single.php
 * etc. all exist in this theme and take priority for their respective
 * contexts; this mainly catches edge cases like a custom post type archive
 * with no dedicated archive-{posttype}.php).
 *
 * Kept in parity with the other listing templates (breadcrumbs + numbered
 * pagination) rather than as a bare unstyled fallback — found missing both
 * during the v1.0.49 audit; every other listing template already had them.
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;
get_header();
gwill_breadcrumbs();
?>

<?php if ( have_posts() ) : ?>

	<?php while ( have_posts() ) : the_post(); ?>
		<?php gwill_part( 'content' ); ?>
	<?php endwhile; ?>

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
