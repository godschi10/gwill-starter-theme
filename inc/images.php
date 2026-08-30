<?php
/**
 * inc/images.php — Image optimisation
 *
 * 1. WebP upload support (WP 6.1+)
 * 2. Ensures width + height on all attachment images (prevents CLS)
 * 3. Decoding=async on non-eager images (sync on the LCP image)
 * 4. Responsive sizes hint for content images (starter's 900px column)
 * 5. Cap scaled-image threshold at 1920px
 *
 * Ported from gwillchijioke-theme inc/images.php (portfolio, live-proven),
 * adapted: content sizes hint matches the starter's --max-width: 1200px
 * with the .entry-content column at 900px (style.css).
 *
 * @package GWill_Starter
 * @since   1.6.0
 */
/*
* TABLE OF CONTENTS
* ─────────────────────────────────────────────────────────────────────────────
*   1. gwill_enforce_image_dimensions  Force width/height attrs
*   2. gwill_image_decoding_async  decoding=async everywhere (except LCP)
*   3. gwill_content_image_sizes  Content images sizes hint
* ─────────────────────────────────────────────────────────────────────────────
*/

defined( 'ABSPATH' ) || exit;

// ── 1. WEBP UPLOAD SUPPORT ───────────────────────────────────────────────────
// WP 6.1+ generates WebP versions of uploaded images automatically.
add_theme_support( 'webp-uploads' );

// ── 2. FORCE WIDTH + HEIGHT ON ALL ATTACHMENT IMAGES ────────────────────────
// Missing dimensions are the #1 cause of layout shift (CLS score hit).
// WP should add these automatically but this filter catches any that slip through.
add_filter( 'wp_get_attachment_image_attributes', 'gwill_enforce_image_dimensions', 10, 3 );

// ── 1. gwill_enforce_image_dimensions ─────────────────────
function gwill_enforce_image_dimensions( $attr, $attachment, $size ) {
	if ( ! empty( $attr['width'] ) && ! empty( $attr['height'] ) ) {
		return $attr; // already set
	}

	$meta = wp_get_attachment_metadata( $attachment->ID );
	if ( empty( $meta ) ) return $attr;

	// Named size (string)
	if ( is_string( $size ) && isset( $meta['sizes'][ $size ] ) ) {
		$attr['width']  = $meta['sizes'][ $size ]['width'];
		$attr['height'] = $meta['sizes'][ $size ]['height'];
	// Full / original
	} elseif ( isset( $meta['width'], $meta['height'] ) ) {
		$attr['width']  = $meta['width'];
		$attr['height'] = $meta['height'];
	}

	return $attr;
}

// ── 3. ADD decoding=async ON NON-EAGER IMAGES ────────────────────────────────
// Tells the browser it can decode the image off the main thread.
// Skip images that already have fetchpriority=high (the LCP image).
add_filter( 'wp_get_attachment_image_attributes', 'gwill_image_decoding_async', 11, 1 );

// ── 2. gwill_image_decoding_async ─────────────────────────
function gwill_image_decoding_async( $attr ) {
	if ( isset( $attr['fetchpriority'] ) && 'high' === $attr['fetchpriority'] ) {
		// LCP image — synchronous decode is faster
		$attr['decoding'] = 'sync';
	} else {
		$attr['decoding'] = 'async';
	}
	return $attr;
}

// ── 4. RESPONSIVE CONTENT IMAGES — sizes hint ───────────────────────────────
// WP auto-generates srcset on post content images. This filter ensures
// the sizes attribute gives the browser correct viewport-width hints
// matching the theme's actual content column width (900px — style.css
// .entry-content, 1200px --max-width minus gutters).
add_filter( 'wp_calculate_image_sizes', 'gwill_content_image_sizes', 10, 5 );

// ── 3. gwill_content_image_sizes ──────────────────────────
function gwill_content_image_sizes( $sizes, $size, $image_src, $image_meta, $attachment_id ) {
	// Only apply to content images (no explicit size arg means auto)
	if ( ! doing_filter( 'the_content' ) ) return $sizes;

	// Content column is 900px wide (the starter's .entry-content measure)
	return '(max-width: 900px) calc(100vw - 32px), 900px';
}

// ── 5. DISABLE SCALED IMAGE GENERATION OVER 1920px ──────────────────────────
// WP creates a -scaled version for uploads over 2560px wide by default.
// We cap at 1920px so no oversized originals are stored.
add_filter( 'big_image_size_threshold', function() { return 1920; } );
