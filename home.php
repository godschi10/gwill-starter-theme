<?php
/**
 * Blog Posts Index (home.php)
 *
 * Displays the blog posts index — the page assigned under
 * Settings → Reading → "Posts page" when a static front page is set,
 * or the default front when no static page is configured.
 *
 * Template hierarchy: home.php → index.php
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;

get_header();
gwill_breadcrumbs(); // hidden on front page by the function itself
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
