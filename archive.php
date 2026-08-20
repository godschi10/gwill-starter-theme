<?php
defined( 'ABSPATH' ) || exit;
get_header();
gwill_breadcrumbs();
?>

<?php if ( have_posts() ) : ?>

	<header class="archive-header">
		<?php
		/*
		 * wp_kses_post(), NOT esc_html() — found the hard way in 1.0.53.
		 * get_the_archive_title() deliberately returns a string containing
		 * real HTML: WordPress core wraps the dynamic portion in a <span>
		 * (e.g. "Category: <span>Name</span>") for styling purposes. esc_html()
		 * converts those tags to literal visible text ("Category: <span>Name
		 * </span>" rendering as-is on the page) instead of letting them work
		 * as actual markup. wp_kses_post() preserves the legitimate <span>
		 * while still stripping anything genuinely dangerous.
		 */
		?>
		<h1 class="archive-title"><?php echo wp_kses_post( get_the_archive_title() ); ?></h1>
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
