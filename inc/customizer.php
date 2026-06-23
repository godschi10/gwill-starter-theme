<?php
/**
 * Theme Customizer — GWill Starter
 *
 * Registers the "Header Options" section with two controls:
 *   • Display tagline (checkbox)    — show or hide the site description
 *   • Header padding (number, px)   — direct pixel input for top/bottom padding
 *
 * Architecture note: Customizer logic lives here, not in inc/setup.php,
 * because setup.php runs unconditionally on every request. Customizer
 * code only needs to load on the frontend (to read saved theme_mod values)
 * and inside the Customizer preview iframe. Keeping it separate avoids
 * loading Customizer-specific code on every admin and REST request.
 *
 * Transport strategy:
 *   gwill_show_tagline    → 'postMessage' (live hidden/visible toggle; see customizer-preview.js)
 *   gwill_header_padding  → 'postMessage' (live CSS variable update, no reload needed)
 *
 * @author  G-will Chijioke <hello@gwillchijioke.com>
 * @package GWill_Starter
 * @since   1.0.17
 */

defined( 'ABSPATH' ) || exit;

// ── Customizer panel registration ────────────────────────────────────────────

add_action( 'customize_register', function ( WP_Customize_Manager $wp_customize ) {

	/**
	 * "Header Options" section.
	 *
	 * Priority 30 sits between the default "Site Identity" (20) and
	 * "Colors" (40) — natural reading order for header-related controls.
	 */
	$wp_customize->add_section( 'gwill_header', [
		'title'    => __( 'Header Options', 'gwill-starter' ),
		'priority' => 30,
	] );

	// ── Tagline visibility ──────────────────────────────────────────────────

	$wp_customize->add_setting( 'gwill_show_tagline', [
		'default'           => true,
		'sanitize_callback' => 'gwill_sanitize_checkbox',
		'transport'         => 'postMessage',
	] );

	$wp_customize->add_control( 'gwill_show_tagline', [
		'label'       => __( 'Display tagline', 'gwill-starter' ),
		'description' => __( 'The tagline text is set under Settings → General.', 'gwill-starter' ),
		'section'     => 'gwill_header',
		'type'        => 'checkbox',
	] );

	// ── Sticky header ─────────────────────────────────────────────────────
	//
	// Default ON — most sites want this. Adds .gwill-sticky-header to
	// body_class() (gwill_sticky_header_body_class() below), which both
	// the CSS (.gwill-sticky-header .site-header { position: sticky }) and
	// assets/js/sticky-header.js are scoped to. Refresh transport, not
	// postMessage — this changes server-rendered body_class() output, and
	// scroll-triggered behaviour isn't meaningfully previewable live in the
	// Customizer iframe anyway.

	$wp_customize->add_setting( 'gwill_sticky_header', [
		'default'           => true,
		'sanitize_callback' => 'gwill_sanitize_checkbox',
	] );

	$wp_customize->add_control( 'gwill_sticky_header', [
		'label'       => __( 'Enable sticky header', 'gwill-starter' ),
		'description' => __( 'Keeps the header visible at the top of the viewport while scrolling.', 'gwill-starter' ),
		'section'     => 'gwill_header',
		'type'        => 'checkbox',
	] );

	// ── Header padding ──────────────────────────────────────────────────────
	//
	// Default: 24px  (= 1.5rem at a 16px browser base — matches --spacing)
	// Range:   0–200 px
	//
	// Uses postMessage transport so the preview iframe updates live as the
	// user types, without a full page refresh. The JS handler lives in
	// assets/js/customizer-preview.js (loaded by inc/enqueue.php on
	// customize_preview_init).

	$wp_customize->add_setting( 'gwill_header_padding', [
		'default'           => 24,
		'sanitize_callback' => 'gwill_sanitize_header_padding',
		'transport'         => 'postMessage',
	] );

	$wp_customize->add_control( 'gwill_header_padding', [
		'label'       => __( 'Header padding (px)', 'gwill-starter' ),
		'description' => __( 'Top & bottom padding on the site header. Default: 24 px.', 'gwill-starter' ),
		'section'     => 'gwill_header',
		'type'        => 'number',
		'input_attrs' => [
			'min'  => 0,
			'max'  => 200,
			'step' => 1,
		],
	] );

	// ── Logo width — added to Site Identity (title_tagline) ─────────────────
	//
	// Placed in the same Customizer section as the logo/favicon upload so
	// the width control sits directly below the logo field — matching the
	// UX of commercial themes (Astra, GeneratePress, OceanWP).
	//
	// Default: 160px.  Range: 20–400 px.
	//
	// Applied as the CSS custom property --logo-width on :root.
	// style.css reads it via: .custom-logo { max-width: var(--logo-width, 160px) }
	// postMessage handler in customizer-preview.js updates the variable live.

	$wp_customize->add_setting( 'gwill_logo_width', [
		'default'           => 160,
		'sanitize_callback' => 'gwill_sanitize_logo_width',
		'transport'         => 'postMessage',
	] );

	$wp_customize->add_control( 'gwill_logo_width', [
		'label'       => __( 'Logo width (px)', 'gwill-starter' ),
		'description' => __( 'Maximum display width of the custom logo. Height scales automatically. Default: 160 px.', 'gwill-starter' ),
		'section'     => 'title_tagline',
		'type'        => 'number',
		'input_attrs' => [
			'min'  => 20,
			'max'  => 400,
			'step' => 1,
		],
	] );

	// ── Default Social Share Image — added to Site Identity ─────────────────
	//
	// Used by inc/social-meta.php as the og:image / twitter:image fallback
	// for any page that has no featured image of its own — and, on a site
	// running an SEO plugin, simply unused (gwill_output_social_meta() bails
	// before this setting is ever read). Same section as the logo/favicon
	// for the same reason logo width is here: this is the one other "site
	// identity" image a site owner sets once and rarely touches again.
	//
	// Stores an attachment ID (absint), not a URL — consistent with how
	// WordPress core's own custom_logo theme_mod works, and lets
	// inc/social-meta.php request it at whatever registered image size it
	// needs (gwill-hero) rather than being stuck with whatever size was
	// uploaded.

	$wp_customize->add_setting( 'gwill_default_social_image', [
		'default'           => 0,
		'sanitize_callback' => 'absint',
	] );

	$wp_customize->add_control( new WP_Customize_Image_Control(
		$wp_customize,
		'gwill_default_social_image',
		[
			'label'       => __( 'Default Social Share Image', 'gwill-starter' ),
			'description' => __( 'Shown when a page or post is shared on Facebook, X, etc. and has no featured image of its own. Recommended size: 1200×630px or larger.', 'gwill-starter' ),
			'section'     => 'title_tagline',
		]
	) );

} );

// ── body_class hook for the sticky-header setting ────────────────────────────

/**
 * Add .gwill-sticky-header to body_class() when the Customizer toggle is on.
 *
 * Both the CSS (.gwill-sticky-header .site-header { position: sticky })
 * and assets/js/sticky-header.js are scoped to this class — when the
 * toggle is off, neither does anything, regardless of being loaded.
 *
 * @param  string[] $classes
 * @return string[]
 * @since  1.0.50
 */
function gwill_sticky_header_body_class( array $classes ): array {
	if ( get_theme_mod( 'gwill_sticky_header', true ) ) {
		$classes[] = 'gwill-sticky-header';
	}
	return $classes;
}
add_filter( 'body_class', 'gwill_sticky_header_body_class' );

// ── Sanitize helpers ─────────────────────────────────────────────────────────

/**
 * Sanitize a Customizer checkbox value.
 *
 * The Customizer posts '1' when checked and an empty string when unchecked.
 * Cast to bool so get_theme_mod() callers always receive a clean boolean.
 *
 * @param mixed $value Raw value from the Customizer.
 * @return bool
 */
function gwill_sanitize_checkbox( $value ): bool {
	return (bool) $value;
}

/**
 * Sanitize the header padding pixel value.
 *
 * Casts to int and clamps to [0, 200]. Rejects negative values and
 * excessively large values before either reaches inline CSS output.
 *
 * @param mixed $value Raw value from the Customizer.
 * @return int Pixel value in the range 0–200.
 */
function gwill_sanitize_header_padding( $value ): int {
	return max( 0, min( 200, (int) $value ) );
}

/**
 * Sanitize the logo width pixel value.
 *
 * Casts to int and clamps to [20, 400]. The minimum of 20 prevents
 * the logo from being set to an unusably small size.
 *
 * @param mixed $value Raw value from the Customizer.
 * @return int Pixel value in the range 20–400.
 */
function gwill_sanitize_logo_width( $value ): int {
	return max( 20, min( 400, (int) $value ) );
}

// ── Header padding — frontend inline CSS ─────────────────────────────────────

/**
 * Append a header-padding override to the main stylesheet as inline CSS.
 *
 * Only fires when the saved value differs from the default (24 px). At the
 * default, style.css's fallback value var(--header-padding, var(--spacing))
 * resolves to --spacing (1.5rem ≈ 24 px), so no extra bytes are sent.
 *
 * The override is a single CSS custom property on :root, so it cascades
 * correctly to any element that reads --header-padding.
 *
 * Priority 20: runs after inc/enqueue.php (priority 10) has registered
 * the 'gwill-style' handle that wp_add_inline_style() attaches to.
 *
 * @see inc/enqueue.php
 * @see assets/js/customizer-preview.js   (postMessage live-preview handler)
 */
add_action( 'wp_enqueue_scripts', function () {

	$padding_px  = gwill_sanitize_header_padding( get_theme_mod( 'gwill_header_padding', 24 ) );
	$logo_px     = gwill_sanitize_logo_width( get_theme_mod( 'gwill_logo_width', 160 ) );
	$inline_vars = [];

	if ( 24 !== $padding_px ) {
		$inline_vars[] = '--header-padding:' . $padding_px . 'px';
	}
	if ( 160 !== $logo_px ) {
		$inline_vars[] = '--logo-width:' . $logo_px . 'px';
	}

	if ( $inline_vars ) {
		wp_add_inline_style( 'gwill-style', ':root{' . implode( ';', $inline_vars ) . '}' );
	}

}, 20 );
