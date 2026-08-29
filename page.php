<?php
defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) : the_post();

	gwill_breadcrumbs();

	/*
	 * Schema.org WebPage microdata.
	 * Custom page templates (contact, about, FAQ) can override itemtype by
	 * adding a custom field or template-specific logic:
	 *   ContactPage  → https://schema.org/ContactPage
	 *   AboutPage    → https://schema.org/AboutPage
	 *   FAQPage      → https://schema.org/FAQPage
	 */
	$schema_attrs = 'itemscope itemtype="https://schema.org/WebPage"';
	?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php echo $schema_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- developer-controlled literal string ?>>

	<?php gwill_part( 'featured-image' ); ?>

	<h1 class="entry-title" itemprop="name"><?php echo esc_html( get_the_title() ); ?></h1>

	<?php
	/*
	 * <link itemprop="url"> makes this WebPage entity self-describing.
	 * A <link> element is valid inside <article> for microdata annotation.
	 */
	?>
	<link itemprop="url" href="<?php echo esc_url( get_permalink() ); ?>">

	<div class="entry-content" itemprop="text">
		<?php the_content(); ?>
		<?php wp_link_pages(); ?>
	</div>

</article>

<?php if ( comments_open() || get_comments_number() ) : ?>
	<?php comments_template(); ?>
<?php endif; ?>

<?php endwhile;

get_footer();
