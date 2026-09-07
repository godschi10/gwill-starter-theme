<?php
/**
 * Blog Posts Index (home.php)
 *
 * Displays the blog posts index - the page assigned under
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
