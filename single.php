<?php
defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) : the_post();

	gwill_breadcrumbs();

	/*
	 * Schema.org BlogPosting microdata.
	 * Applied directly to <article> — no extra wrapper needed.
	 */
	$schema_attrs = 'itemscope itemtype="https://schema.org/BlogPosting"';
	?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php echo $schema_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- developer-controlled literal string ?>>

	<?php gwill_part( 'featured-image' ); ?>

	<h1 class="entry-title" itemprop="headline"><?php echo esc_html( get_the_title() ); ?></h1>

	<div class="entry-meta">
		<?php
		/*
		 * <link> and <meta> for Schema.org microdata inside <article> — valid in
		 * HTML5. dateModified is required by Google's Article structured data spec.
		 */
		?>
		<link itemprop="url" href="<?php echo esc_url( get_permalink() ); ?>">
		<meta itemprop="dateModified" content="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">

		<span class="entry-author">
			<?php esc_html_e( 'By', 'gwill-starter' ); ?>
			<span itemprop="author" itemscope itemtype="https://schema.org/Person">
				<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" itemprop="url">
					<span itemprop="name"><?php echo esc_html( get_the_author() ); ?></span>
				</a>
			</span>
		</span>
		&mdash;
		<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" itemprop="datePublished">
			<?php echo esc_html( get_the_date() ); ?>
		</time>
		<span class="entry-meta__sep" aria-hidden="true"> &middot; </span>
		<span class="entry-reading-time">
			<?php
			printf(
				/* translators: %d: estimated reading time in minutes */
				esc_html__( '%d min read', 'gwill-starter' ),
				gwill_reading_time()
			);
			?>
		</span>

		<?php
		// Categories — inline with meta. Shows ALL assigned categories (unlike
		// content.php's card view, which intentionally shows only the primary
		// one) — sorted so the primary category, if set, appears first.
		$gwill_cats = get_the_category();
		if ( $gwill_cats ) :
			$gwill_primary_cat = gwill_get_primary_category();
			if ( $gwill_primary_cat ) {
				usort( $gwill_cats, static function ( $a, $b ) use ( $gwill_primary_cat ) {
					return ( (int) $a->term_id === (int) $gwill_primary_cat->term_id ) ? -1 : 1;
				} );
			}
		?>
		<span class="entry-meta__sep" aria-hidden="true"> &middot; </span>
		<span class="entry-cats" itemprop="articleSection">
			<?php foreach ( $gwill_cats as $gwill_cat ) : ?>
				<a class="entry-cat" href="<?php echo esc_url( get_category_link( $gwill_cat->term_id ) ); ?>">
					<?php echo esc_html( $gwill_cat->name ); ?>
				</a>
			<?php endforeach; ?>
		</span>
		<?php endif; ?>
	</div>

	<?php gwill_part( 'share-button' ); // top mode — compact pill row ?>

	<div class="entry-content" itemprop="articleBody">
		<?php the_content(); ?>
		<?php wp_link_pages(); ?>
	</div>

</article>

<?php
// Tags — below the article, above the footer share row.
$gwill_tags = get_the_tags();
if ( $gwill_tags ) :
?>
<div class="entry-tags">
	<?php foreach ( $gwill_tags as $gwill_tag ) : ?>
		<a class="entry-tag" href="<?php echo esc_url( get_tag_link( $gwill_tag->term_id ) ); ?>">
			<?php echo esc_html( $gwill_tag->name ); ?>
		</a>
	<?php endforeach; ?>
</div>
<?php endif; ?>

<?php
set_query_var( 'gwill_share_mode', 'footer' );
gwill_part( 'share-button' );
set_query_var( 'gwill_share_mode', '' );
?>

<?php gwill_part( 'author-box' ); ?>

<?php gwill_part( 'related-posts' ); ?>

<?php the_post_navigation(); ?>

<?php if ( comments_open() || get_comments_number() ) : ?>
	<?php comments_template(); ?>
<?php endif; ?>

<?php endwhile;

get_footer();
