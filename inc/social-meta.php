<?php
/**
 * Open Graph / Twitter Card fallback meta tags.
 *
 * Only outputs anything when no major SEO plugin is detected — RankMath,
 * Yoast, AIOSEO, SEOPress, and The SEO Framework all already output their
 * own OG/Twitter tags, and outputting both would create duplicate,
 * conflicting meta tags in <head>. This exists purely for the (common,
 * especially on smaller client sites) case of no SEO plugin at all, where
 * a shared link would otherwise show no preview image and no description.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'gwill_output_social_meta', 1 );

/**
 * Output the actual meta tags.
 *
 * @since 1.0.50
 */
function gwill_output_social_meta(): void {

	if ( gwill_seo_plugin_active() ) {
		return;
	}

	$is_singular  = is_singular();
	$title        = gwill_social_meta_title();
	$description  = gwill_social_meta_description();
	$url          = gwill_social_meta_url();
	$image        = gwill_social_meta_image();

	?>
	<meta property="og:type" content="<?php echo esc_attr( $is_singular ? 'article' : 'website' ); ?>">
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<?php if ( $description ) : ?>
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
	<?php endif; ?>
	<meta property="og:url" content="<?php echo esc_url( $url ); ?>">

	<?php if ( $image ) : ?>
	<meta property="og:image" content="<?php echo esc_url( $image['url'] ); ?>">
	<meta property="og:image:width" content="<?php echo esc_attr( $image['width'] ); ?>">
	<meta property="og:image:height" content="<?php echo esc_attr( $image['height'] ); ?>">
	<meta property="og:image:alt" content="<?php echo esc_attr( $image['alt'] ); ?>">
	<?php endif; ?>

	<?php if ( is_single() ) : ?>
	<meta property="article:published_time" content="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
	<meta property="article:modified_time" content="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
	<meta property="article:author" content="<?php echo esc_attr( get_the_author() ); ?>">
	<?php endif; ?>

	<meta name="twitter:card" content="<?php echo esc_attr( $image ? 'summary_large_image' : 'summary' ); ?>">
	<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
	<?php if ( $description ) : ?>
	<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
	<?php endif; ?>
	<?php if ( $image ) : ?>
	<meta name="twitter:image" content="<?php echo esc_url( $image['url'] ); ?>">
	<?php endif; ?>
	<?php
}

/**
 * Resolve the canonical URL for the current request.
 *
 * Deliberately NOT built from $_SERVER['REQUEST_URI'] passed through
 * home_url() — REQUEST_URI already includes any subdirectory prefix on an
 * install running at example.com/blog/, so wrapping it in home_url() a
 * second time would double that prefix (example.com/blog/blog/...). Uses
 * WordPress's own conditional-tag URL functions instead, which are each
 * already subdirectory-safe.
 *
 * @since 1.0.50
 */
function gwill_social_meta_url(): string {

	if ( is_singular() ) {
		$permalink = get_permalink();
		return $permalink ?: home_url( '/' );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		// get_term_link() can return WP_Error, which has no __toString() —
		// casting it directly would fatal ("Object ... could not be
		// converted to string"), not just produce a wrong URL.
		return is_wp_error( $link ) ? home_url( '/' ) : $link;
	}

	if ( is_author() ) {
		return get_author_posts_url( get_queried_object_id() );
	}

	if ( is_search() ) {
		return get_search_link();
	}

	if ( is_home() || is_front_page() ) {
		return home_url( '/' );
	}

	// Date archives, custom post type archives, 404, anything else not
	// covered above. $wp->request is the rewrite-engine-resolved relative
	// path with no home_url prefix already baked in, so this is safe on
	// subdirectory installs too.
	global $wp;
	return home_url( $wp->request );
}

/**
 * Resolve the title to use. Filterable via 'gwill_og_title'.
 *
 * @since 1.0.50
 */
function gwill_social_meta_title(): string {
	if ( is_singular() ) {
		$title = get_the_title();
	} elseif ( is_home() || is_front_page() ) {
		$title = get_bloginfo( 'name' );
	} else {
		$title = wp_get_document_title();
	}
	return (string) apply_filters( 'gwill_og_title', $title );
}

/**
 * Resolve the description to use — explicit excerpt, then auto-excerpt,
 * then the site tagline. Filterable via 'gwill_og_description'.
 *
 * @since 1.0.50
 */
function gwill_social_meta_description(): string {
	$description = '';

	if ( is_singular() ) {
		$description = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 35 );
	}

	if ( ! $description ) {
		$description = get_bloginfo( 'description' );
	}

	return (string) apply_filters( 'gwill_og_description', wp_strip_all_tags( $description ) );
}

/**
 * Resolve the image to use — the post's own featured image first, falling
 * back to the Customizer's "Default Social Share Image" setting, then to
 * nothing at all (a card with no image is still valid; a broken image URL
 * is not). Filterable via 'gwill_og_image_id'.
 *
 * @return array{url:string,width:int,height:int,alt:string}|null
 * @since  1.0.50
 */
function gwill_social_meta_image(): ?array {

	$image_id = 0;

	if ( is_singular() && has_post_thumbnail() ) {
		$image_id = get_post_thumbnail_id();
	}

	if ( ! $image_id ) {
		$image_id = (int) get_theme_mod( 'gwill_default_social_image' );
	}

	$image_id = (int) apply_filters( 'gwill_og_image_id', $image_id );

	if ( ! $image_id ) {
		return null;
	}

	// 'gwill-hero' (1200×675 — registered in inc/setup.php for the single-post
	// hero treatment) is reused here rather than registering a dedicated
	// social-image size: it's already generated for every post with a
	// featured image, already close to platforms' own ~1200×630 preference,
	// and a brand new size would only add an extra generated file per
	// upload for a marginal aspect-ratio improvement social platforms crop
	// around anyway.
	$src = wp_get_attachment_image_src( $image_id, 'gwill-hero' );

	if ( ! $src ) {
		return null;
	}

	return [
		'url'    => $src[0],
		'width'  => $src[1],
		'height' => $src[2],
		'alt'    => get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ?: gwill_social_meta_title(),
	];
}
