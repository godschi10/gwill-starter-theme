<?php
defined( 'ABSPATH' ) || exit;
get_header();
gwill_breadcrumbs();
?>

<?php if ( have_posts() ) : ?>

	<header class="archive-header">
		<h1 class="archive-title"><?php the_archive_title(); ?></h1>
		<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
	</header>

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
