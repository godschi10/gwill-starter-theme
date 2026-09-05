<?php

/*
Table of Contents
1. gwill_ads_enabled
2. gwill_ad_code
3. gwill_ad_slot_has_code
4. gwill_ad_slot
5. gwill_ad_customizer
6. gwill_in_content_ad
*/

/**
 * Ad slots  -  GWill Starter (v1.8.0).
 *
 * Ported from tech inc/ad-slots.php (live-proven on the tech site),
 * adapted:
 *   - ACF STRUCK: tech pulls the "Ad loading…" label via gwill_gs() (an
 *     ACF helper); the starter is ACF-free BY LAW, so the label is a
 *     plain i18n string. All other semantics carry over EXACTLY.
 *   - a Customizer section (Customize → Developer Options → Ad
 *     Placements) replaces tech's ACF field group as the config
 *     surface: master switch + per-placement codes (desktop/tablet/
 *     mobile variants), sanitized as textarea with a kses-exempted
 *     allowlist because ad codes are raw HTML/JS by nature
 *     (manage_options-gated, same trust boundary as tech's ACF).
 *
 * Placements: leaderboard | in-content | sidebar | sticky | menu |
 * before-footer (before-footer inherits leaderboard code when empty  - 
 * the tech fallback).
 *
 * DEVICE-AWARE SLOTS: each placement carries up to 3 codes; the slot
 * renders ALL variants as inert <template> tags and assets/js/ads.js
 * instantiates ONLY the visitor's device variant  -  cache-safe (every
 * cached page carries all variants) and fires ONE ad request per slot.
 *
 * Usage: gwill_ad_slot( 'leaderboard' );  -  or drop nothing: empty
 * placements render NOTHING (no dead-space spinners).
 *
 * @package GWill_Starter
 * @since   1.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'gwill_ads_enabled' ) ) :
// ── 1. gwill_ads_enabled ──────────────────────────────────
/**
 * Master switch. Customize → Developer Options → "Show ad placements".
 * Default ON (the tech default)  -  sites with no codes render nothing
 * anyway (empty placements collapse).
 */
function gwill_ads_enabled(): bool {
	return (bool) get_theme_mod( 'gwill_show_ads', true );
}
endif;

if ( ! function_exists( 'gwill_ad_code' ) ) :
// ── 2. gwill_ad_code ──────────────────────────────────────
/**
 * Fetch the raw ad code (HTML/JS) for a placement + device.
 *
 * Device resolution:
 *   '' (base/desktop) → gwill_ad_code_<placement>
 *   'tablet'          → gwill_ad_code_<placement>_tablet  (falls back to base)
 *   'mobile'          → gwill_ad_code_<placement>_mobile  (falls back to base)
 *
 * @param string $placement leaderboard|in-content|sidebar|sticky|menu|before-footer.
 * @param string $device    ''|tablet|mobile ('' = base/desktop + fallback).
 * @return string Raw code, '' when empty.
 */
function gwill_ad_code( string $placement, string $device = '' ): string {
	if ( $device ) {
		$specific = get_theme_mod( 'gwill_ad_code_' . $placement . '_' . $device, '' );
		if ( is_string( $specific ) && '' !== $specific ) {
			return $specific;
		}
	}
	$code = get_theme_mod( 'gwill_ad_code_' . $placement, '' );
	if ( is_string( $code ) && '' !== $code ) {
		return $code;
	}
	// Fallback: before-footer inherits the leaderboard code (both are
	// 728×90 full-width placements; without this an empty before-footer
	// field renders NO ad at all).
	if ( 'before-footer' === $placement ) {
		return gwill_ad_code( 'leaderboard', $device );
	}
	return '';
}
endif;

if ( ! function_exists( 'gwill_ad_slot_has_code' ) ) :
// ── 3. gwill_ad_slot_has_code ─────────────────────────────
/**
 * True when ANY device variant has ad code for a placement.
 *
 * Empty placements must not render  -  a spinner-only box is dead space
 * the King reads as a gap, not an ad.
 *
 * @param string $placement
 * @return bool
 */
function gwill_ad_slot_has_code( string $placement ): bool {
	return '' !== gwill_ad_code( $placement )
		|| '' !== gwill_ad_code( $placement, 'tablet' )
		|| '' !== gwill_ad_code( $placement, 'mobile' );
}
endif;

if ( ! function_exists( 'gwill_ad_slot' ) ) :
// ── 4. gwill_ad_slot ──────────────────────────────────────
/**
 * Render an ad slot (inert device variants + spinner + label).
 *
 * @param string $placement leaderboard|in-content|sidebar|sticky|menu|before-footer.
 * @return void
 */
function gwill_ad_slot( string $placement = 'leaderboard' ) {
	// Removable: master switch off = no ad output anywhere.
	if ( ! gwill_ads_enabled() ) {
		return;
	}

	// No code configured for ANY device → render nothing (collapses the
	// dead space rather than shipping an empty labeled box).
	if ( ! gwill_ad_slot_has_code( $placement ) ) {
		return;
	}

	// Device variants: base (desktop + fallback), tablet, mobile.
	$devices = array( 'tablet', 'mobile' );
	$base    = gwill_ad_code( $placement );
	?>
	<div class="ad-slot ad-slot--<?php echo esc_attr( $placement ); ?>">
		<div class="ad-slot__spinner" aria-hidden="true">
			<svg aria-hidden="true" focusable="false" class="ad-spinner" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
				<circle cx="12" cy="12" r="9" opacity=".25"/>
				<path d="M21 12a9 9 0 0 0-9-9"/>
			</svg>
			<span class="ad-slot__spinner-text"><?php esc_html_e( 'Ad loading…', 'gwill-starter' ); ?></span>
		</div>
		<div class="ad-slot__content">
			<?php if ( $base ) : ?>
			<template class="ad-variant" data-device="desktop"><?php echo $base; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  -  admin-configured ad code (raw HTML/JS by nature); Customizer is manage_options-gated ?></template>
			<?php endif; ?>
			<?php foreach ( $devices as $device ) :
				$code = gwill_ad_code( $placement, $device );
				if ( $code ) : ?>
			<template class="ad-variant" data-device="<?php echo esc_attr( $device ); ?>"><?php echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  -  admin-configured ad code ?></template>
			<?php endif; endforeach; ?>
		</div>
	</div>
	<?php
}
endif;

// ── 5. gwill_ad_customizer ────────────────────────────────
/**
 * Customizer config surface (replaces tech's ACF field group  -  the
 * starter is ACF-free BY LAW). Customize → Developer Options → Ad
 * Placements: master switch + per-placement codes with device variants.
 *
 * Registered lazily on customize_register so nothing loads unless the
 * Customizer itself is open.
 */
function gwill_ad_customizer( WP_Customize_Manager $wp_customize ) {
	if ( ! isset( $wp_customize->sections['gwill_developer'] ) ) {
		$wp_customize->add_section( 'gwill_ads', array(
			'title'       => __( 'Ad Placements', 'gwill-starter' ),
			'description' => __( 'Paste ad network code (AdSense, Mediavine…) per placement. Device variants let you serve different sizes per viewport; the matching variant fires on the current device  -  one request per slot.', 'gwill-starter' ),
			'priority'    => 55,
		) );
		$section = 'gwill_ads';
	} else {
		// Developer Options exists (starter default)  -  nest there.
		$section = 'gwill_developer';
	}

	// Master switch.
	$wp_customize->add_setting( 'gwill_show_ads', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
		'type'              => 'theme_mod',
	) );
	$wp_customize->add_control( 'gwill_show_ads', array(
		'label'   => __( 'Show ad placements', 'gwill-starter' ),
		'section' => $section,
		'type'    => 'checkbox',
	) );

	// Per-placement codes: base + tablet + mobile.
	$placements = array(
		'leaderboard'   => __( 'Leaderboard (728×90, archives)', 'gwill-starter' ),
		'in-content'    => __( 'In-content (after 2nd paragraph, single posts)', 'gwill-starter' ),
		'sidebar'       => __( 'Sidebar (300×250, single posts)', 'gwill-starter' ),
		'sticky'        => __( 'Sticky (320×100 mobile footer bar)', 'gwill-starter' ),
		'menu'          => __( 'Menu (320×100 mobile menu overlay)', 'gwill-starter' ),
		'before-footer' => __( 'Before footer (728×90, inherits leaderboard when empty)', 'gwill-starter' ),
	);

	foreach ( $placements as $placement => $label ) {
		foreach ( array( '' => $label, '_tablet' => $label . '  -  tablet', '_mobile' => $label . '  -  mobile' ) as $suffix => $ctl_label ) {
			$setting = 'gwill_ad_code_' . $placement . $suffix;
			$wp_customize->add_setting( $setting, array(
				'default'           => '',
				'sanitize_callback' => 'gwill_sanitize_ad_code',
				'type'              => 'theme_mod',
			) );
			$wp_customize->add_control( $setting, array(
				'label'   => $ctl_label,
				'section' => $section,
				'type'    => 'textarea',
			) );
		}
	}
}
add_action( 'customize_register', 'gwill_ad_customizer' );

/**
 * Sanitize ad code: admin-pasted raw HTML/JS. KSES would strip the
 * <script> tags that ad codes require; instead we accept the raw string
 * from a manage_options-gated surface (same trust boundary as tech's
 * ACF field) but strip PHP tags so a compromised admin session cannot
 * execute server-side code through the theme.
 *
 * @param string $code Raw textarea input.
 * @return string
 */
function gwill_sanitize_ad_code( $code ) {
	$code = (string) $code;
	// Strip PHP open/close tags  -  theme_mods are evaluated nowhere, but
	// defense-in-depth against a pasted <?php payload.
	$code = preg_replace( '/<\?(?:php|=)?\s*/i', '', $code );
	$code = str_replace( '?>', '', $code );
	return trim( $code );
}

if ( ! function_exists( 'gwill_in_content_ad' ) ) :
// ── 6. gwill_in_content_ad ────────────────────────────────
/**
 * Insert the in-content ad after the 2nd paragraph on single posts
 * (+ every ~5 paragraphs on long reads, max 4  -  the tech distribution).
 */
function gwill_in_content_ad( $content ) {
	// single.php applies the_content filter outside the main loop,
	// so in_the_loop() can't gate it; is_singular + is_admin guards
	// keep it scoped to front-end single post views only.
	if ( ! is_singular( 'post' ) || is_admin() || ! gwill_ads_enabled() ) {
		return $content;
	}

	// Empty in-content code → nothing to insert; skip the paragraph
	// split entirely (saves the preg_split on every post).
	if ( ! gwill_ad_slot_has_code( 'in-content' ) ) {
		return $content;
	}

	// Split on paragraph boundaries; each paragraph produces 2 array
	// entries (text + delimiter), so N paragraphs = 2N entries.
	$paragraphs = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	if ( ! is_array( $paragraphs ) || count( $paragraphs ) < 6 ) {
		return $content; // Too short  -  skip the ads.
	}

	$para_count = (int) ( count( $paragraphs ) / 2 );

	ob_start();
	gwill_ad_slot( 'in-content' );
	$ad = ob_get_clean();

	// Ad 1  -  after the 2nd paragraph (index 3 = 2nd ¶ + delimiter).
	$paragraphs[3] .= $ad;

	// More ads every ~5 paragraphs on longer posts: 7th, 12th, 17th…
	// (≥12 paragraphs gets a second ad; very long reads get more).
	// Max 4 ads total to avoid stacking.
	$ad_spots = array();
	if ( $para_count >= 12 ) {
		$ad_spots[] = 13; // After 7th ¶ (index 13 = 7th + delimiter).
	}
	if ( $para_count >= 17 ) {
		$ad_spots[] = 23; // After 12th ¶.
	}
	if ( $para_count >= 22 ) {
		$ad_spots[] = 33; // After 17th ¶.
	}
	foreach ( $ad_spots as $spot ) {
		if ( isset( $paragraphs[ $spot ] ) ) {
			$paragraphs[ $spot ] .= $ad;
		}
	}

	return implode( '', $paragraphs );
}
endif;
add_filter( 'the_content', 'gwill_in_content_ad', 20 );
