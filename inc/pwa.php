<?php

/*
Table of Contents
1. register pwa script + i18n strings
2. manifest rewrite + query var + canonical guard
3. manifest route — render /manifest.webmanifest on template_include
4. maybe_publish_sw — bake @PUBLISH@ token + publish sw.js to ABSPATH
5. publish_check — hooks to (re)bake on theme switch / admin visits
6. head links — manifest + PWA metas + icons
7. brand filters — icon URLs + colors, overridable per build
*/

/**
 * PWA — service worker publish + web app manifest + install-prompt script.
 *
 * PORTED from gwill-finance-theme v1.2.3 (which ported it from the tech
 * theme — the proven lineage; finance's Chrome-app install is verified
 * working on real devices). All three pieces of the manifest route are
 * present (rewrite + query var + canonical guard) per docs/LAWS.md L4:
 * a template_include sniff alone serves a 404 STATUS with the correct body.
 *
 *   - sw.js is NOT a static file: it carries a `gwill-starter-@PUBLISH@`
 *     token baked to `gwill-starter-<version>-<mtime>` at publish time and
 *     copied to ABSPATH/sw.js (root scope). The versioned swUrl defeats any
 *     CDN/proxy cache of the SW script (L2).
 *   - The manifest is a REAL rewrite — browsers hard-fail a 404 manifest.
 *   - Full head set (finance parity): theme-color, mobile-web-app-capable,
 *     apple-mobile-web-app-capable + status-bar-style, apple-touch-icon,
 *     SVG favicon, application-name, manifest link.
 *
 * @package GWill_Starter
 * @since   1.4.0 (PWA head-set + true icons + brand filters in 1.4.1)
 */

defined( 'ABSPATH' ) || exit;

// ══════ 7. brand filters (defined first — sections 1/3/6 consume them) ══════

/**
 * Full icon suite, filterable per build. Defaults ship neutral brand-agnostic
 * art (dark slate square + white bell) — a build replaces the files in
 * assets/brand/ or overrides these URLs.
 *
 * @return array{192:string,512:string,apple:string,badge:string,favicon:string}
 */
function gwill_pwa_icons() {
	$base = get_template_directory_uri() . '/assets/brand';
	return apply_filters(
		'gwill_pwa_icons',
		array(
			'192'     => $base . '/appicon-192.png',
			'512'     => $base . '/appicon-512.png',
			'apple'   => $base . '/apple-touch-icon.png',
			'badge'   => $base . '/push-badge.png',
			'favicon' => $base . '/favicon.svg',
		)
	);
}

/**
 * Chrome-app theme colors, filterable per build. Defaults match the theme's
 * light tokens; dark-mode builds swap via the filter or CSS.
 *
 * @return array{theme:string,background:string}
 */
function gwill_pwa_colors() {
	return apply_filters(
		'gwill_pwa_colors',
		array(
			'theme'      => '#111111',
			'background' => '#ffffff',
		)
	);
}

// ══════ 1. register pwa script + i18n strings ══════
add_action(
	'wp_enqueue_scripts',
	function () {
		$ver  = wp_get_theme()->get( 'Version' );
		$icon = gwill_pwa_icons();

		wp_enqueue_script(
			'gwill-pwa',
			get_template_directory_uri() . '/assets/js/gwill-pwa.js',
			array(),
			$ver,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			'gwill-pwa',
			'GwillPwa',
			array(
				// Versioned service-worker URL — defeats ANY CDN/proxy/server
				// cache of the SW script across releases. Each deploy forces
				// a fresh SW install via a unique URL.
				'swUrl'   => home_url( '/sw.js' ) . '?v=' . rawurlencode( $ver ),
				'icon'    => $icon['192'],
				'i18n'    => array(
					'installTitle' => __( 'Install', 'gwill-starter' ) . ' ' . get_bloginfo( 'name' ),
					'installCopy'  => __( 'Add this site to your home screen — read offline, open in one tap.', 'gwill-starter' ),
					'installBtn'   => __( 'Install app', 'gwill-starter' ),
					'laterBtn'     => __( 'Not now', 'gwill-starter' ),
				),
			)
		);
	}
);

// ══════ 2. manifest rewrite + query var + canonical guard ══════
add_action( 'init', 'gwill_pwa_rewrite' );
function gwill_pwa_rewrite() {
	add_rewrite_rule( '^manifest\.webmanifest/?$', 'index.php?gwill_manifest=1', 'top' );
}

/**
 * Stop redirect_canonical from 301-ing /manifest.webmanifest to
 * /manifest.webmanifest/ (the extra hop breaks some manifest fetchers).
 *
 * @param string $redirect_url Proposed redirect target.
 * @return string|false
 */
function gwill_pwa_no_canonical_redirect( $redirect_url ) {
	if ( get_query_var( 'gwill_manifest' ) ) {
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'gwill_pwa_no_canonical_redirect', 10, 2 );

/**
 * Register the manifest query var.
 *
 * @param string[] $vars Registered public query vars.
 * @return string[]
 */
function gwill_pwa_query_var( array $vars ): array {
	$vars[] = 'gwill_manifest';
	return $vars;
}
add_filter( 'query_vars', 'gwill_pwa_query_var' );

// ══════ 3. manifest route ══════
add_action( 'template_include', 'gwill_pwa_maybe_render_manifest', 99 );
function gwill_pwa_maybe_render_manifest( $template ) {
	if ( ! get_query_var( 'gwill_manifest' ) ) {
		return $template;
	}

	$icons = gwill_pwa_icons();

	$manifest = array(
		'name'             => get_bloginfo( 'name' ),
		'short_name'       => wp_html_excerpt( get_bloginfo( 'name' ), 12, '…' ),
		'description'      => get_bloginfo( 'description' ),
		'lang'             => get_bloginfo( 'language' ),
		'dir'              => is_rtl() ? 'rtl' : 'ltr',
		'id'               => home_url( '/' ),
		'start_url'        => home_url( '/' ),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'orientation'      => 'any',
		'background_color' => gwill_pwa_colors()['background'],
		'theme_color'      => gwill_pwa_colors()['theme'],
		'categories'       => apply_filters( 'gwill_pwa_categories', array( 'news', 'productivity' ) ),
		'icons'            => array(
			array(
				'src'   => $icons['192'],
				'sizes' => '192x192',
				'type'  => 'image/png',
			),
			array(
				'src'   => $icons['512'],
				'sizes' => '512x512',
				'type'  => 'image/png',
			),
			array(
				'src'     => $icons['512'],
				'sizes'   => '512x512',
				'type'    => 'image/png',
				'purpose' => 'maskable',
			),
		),
	);

	/**
	 * Last-chance per-build override for the whole manifest.
	 */
	$manifest = apply_filters( 'gwill_pwa_manifest', $manifest );

	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES );
	exit;
}

// ══════ 4. maybe_publish_sw — bake @PUBLISH@ + publish to ABSPATH ══════
function gwill_pwa_maybe_publish_sw() {
	$source = get_template_directory() . '/assets/js/sw.js';
	$target = trailingslashit( ABSPATH ) . 'sw.js';

	if ( ! file_exists( $source ) ) {
		return false;
	}

	$stamp   = get_template() . '-' . (string) filemtime( $source );
	$version = wp_get_theme( get_template() )->get( 'Version' );
	$token   = 'gwill-starter-' . $version . '-' . $stamp;

	// Republish when the source moved OR the target no longer carries THIS
	// release's baked token.
	if ( get_option( 'gwill_sw_published', '' ) === $stamp && file_exists( $target )
		&& false !== strpos( (string) file_get_contents( $target ), $token ) ) {
		return false;
	}

	// Replace the @PUBLISH@ token so the served worker knows its generation.
	$raw = (string) file_get_contents( $source );
	$js  = str_replace( 'gwill-starter-@PUBLISH@', $token, $raw );

	$copied = false !== file_put_contents( $target, $js );
	if ( $copied ) {
		update_option( 'gwill_sw_published', $stamp, false );
	}
	return $copied;
}

// ══════ 5. publish_check — hooks ══════
function gwill_pwa_publish_check() {
	gwill_pwa_maybe_publish_sw();
}
add_action( 'after_switch_theme', 'gwill_pwa_publish_check' );
add_action( 'admin_init', 'gwill_pwa_publish_check' );

// ══════ 6. head links — manifest + PWA metas + icons ══════
function gwill_pwa_head_links() {
	$icons  = gwill_pwa_icons();
	$colors = gwill_pwa_colors();

	echo '<meta name="theme-color" content="' . esc_attr( $colors['theme'] ) . '">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	// apple-mobile-web-app-capable is deprecated (harmless console warning);
	// mobile-web-app-capable is its modern replacement — keep both for older
	// iOS Safari (finance v1.0.175 lesson).
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
	echo '<link rel="manifest" href="' . esc_url( home_url( '/manifest.webmanifest' ) ) . '">' . "\n";
	echo '<meta name="application-name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( $icons['favicon'] ) . '">' . "\n";
	// v1.9.0 — dark-mode aware favicon: the browser picks the matching
	// variant via the media attribute; both are plain SVG (no <picture>).
	echo '<link rel="icon" type="image/svg+xml" media="(prefers-color-scheme: dark)" href="' . esc_url( gwill_pwa_dark_favicon() ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $icons['apple'] ) . '">' . "\n";
}

/**
 * v1.9.0 — dark-variant favicon URL, filterable per build alongside
 * gwill_pwa_icons(). Ships favicon-dark.svg next to the light one; a
 * build without a dark variant returns the light URL (no dead link).
 *
 * @return string
 */
function gwill_pwa_dark_favicon() {
	$base = get_template_directory_uri() . '/assets/brand';
	if ( file_exists( get_template_directory() . '/assets/brand/favicon-dark.svg' ) ) {
		$dark = $base . '/favicon-dark.svg';
	} else {
		$dark = gwill_pwa_icons()['favicon'];
	}
	return apply_filters( 'gwill_pwa_dark_favicon', $dark );
}
add_action( 'wp_head', 'gwill_pwa_head_links', 2 );
