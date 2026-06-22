<?php
defined( 'ABSPATH' ) || exit;

/*
 * Note: remove_action( 'wp_head', 'rsd_link' ) and wlwmanifest_link were
 * previously included here. Both were removed from WordPress core in WP 6.3
 * and are no longer output — no removal needed on WP 6.4+ (our minimum).
 */

// Remove WP version from <head>
remove_action( 'wp_head', 'wp_generator' );

// Remove WP version from RSS/Atom feeds — wp_generator removal alone only
// covers the HTML <head>; the feed generator tag requires a separate filter.
add_filter( 'the_generator', '__return_empty_string' );

// Remove WP shortlink from <head> — no SEO value; exposes post IDs.
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

// Disable XML-RPC — not needed for sites not using the WordPress mobile app
// or Jetpack Publicize. Re-enable via a project-specific filter if required.
add_filter( 'xmlrpc_enabled', '__return_false' );

// Remove the emoji JS detection script injected via wp_head. This outputs
// ~15 KB of script to detect Unicode emoji support per page load. Use real
// UTF-8 emoji characters instead; the detection script is unnecessary.
// Note: print_emoji_styles (CSS) was removed by WP core in 6.4, but
// print_emoji_detection_script (JS) still runs and requires explicit removal.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

// Block REST API user enumeration for unauthenticated requests only.
// Authenticated requests (logged-in users, WooCommerce, ACF, etc.) still
// resolve normally. Blocking unconditionally breaks plugins that depend on
// these endpoints for internal authenticated calls.
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	unset(
		$endpoints['/wp/v2/users'],
		$endpoints['/wp/v2/users/(?P<id>[\d]+)']
	);
	return $endpoints;
} );

// ── Author archive & enumeration protection ──────────────────────────────────
//
// Two distinct threats:
//
//   1. GET /?author=1  → WordPress's redirect_canonical() redirects this to
//      /author/loginname/, disclosing the user's login name. This is the actual
//      enumeration attack vector. Blocked unconditionally below.
//
//   2. /author/slug/   → The archive page itself. Exposes the display name
//      (not the login name). This starter ships a full author archive system
//      (author.php template, inc/author.php, social fields) so archives are
//      ENABLED by default.
//
// To disable the /author/slug/ archive entirely for a single-author site that
// doesn't want any author URL exposure, define this constant in wp-config.php
// BEFORE the theme loads:
//
//   define( 'GWILL_ALLOW_AUTHOR_ARCHIVES', false );
//
// If display name exposure at the /author/slug/ level is a concern, block
// /author/* at the infrastructure layer (Cloudflare WAF, Nginx) instead —
// PHP can't stop a determined scraper that already has the slug.
//
// ─────────────────────────────────────────────────────────────────────────────
// Why the old default was wrong: the previous code defaulted this constant to
// false, which blocked ALL author archive pages despite the theme shipping a
// full author.php template. The enumeration threat and the archive page are
// different concerns — conflating them silently killed a core theme feature.
// ─────────────────────────────────────────────────────────────────────────────
if ( ! defined( 'GWILL_ALLOW_AUTHOR_ARCHIVES' ) ) {
	define( 'GWILL_ALLOW_AUTHOR_ARCHIVES', true );
}

// Priority 1 — fires before redirect_canonical (priority 10), which would
// otherwise redirect /?author=1 to /author/loginname/ and expose the login name.
add_action( 'template_redirect', function () {

	// Block numeric ?author= enumeration unconditionally.
	// /?author=1 is never a valid user-facing URL — it only exists as an
	// enumeration attack vector. Redirect it to homepage before redirect_canonical
	// can forward it to the author slug page.
	$raw = isset( $_GET['author'] ) ? (string) $_GET['author'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.NonceVerification.Recommended
	if ( '' !== $raw && ctype_digit( $raw ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}

	// Optionally redirect /author/slug/ pages to homepage for sites that have
	// disabled the author archive entirely via GWILL_ALLOW_AUTHOR_ARCHIVES.
	//
	// 302, not 301: this redirect's existence is conditional on a site-owner
	// toggleable constant, not a genuine permanent URL move. A 301 here would
	// tell browsers to cache the redirect essentially forever — Chrome in
	// particular holds 301s in its own internal redirect cache well past a
	// normal cache-clear. If a site owner ever flips GWILL_ALLOW_AUTHOR_ARCHIVES
	// back to true (or upgrades from a version where this redirect had a bug
	// affecting whether it fired), visitors with an already-cached 301 would
	// keep landing on the homepage regardless of what the server now actually
	// does — a "fixed in code, browser won't let go of the old behaviour"
	// failure mode that's indistinguishable from the bug never having been
	// fixed at all. The unconditional ?author=N enumeration block above stays
	// 301 — that one IS permanent, unconditional behaviour, so 301 is correct
	// there.
	if ( ! GWILL_ALLOW_AUTHOR_ARCHIVES && is_author() ) {
		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}

}, 1 );

// Suppress login error specificity — prevents distinguishing bad username
// from bad password. esc_html__ is used (not __) per WPCS output escaping rules.
add_filter( 'login_errors', fn() => esc_html__( 'Invalid username or password.', 'gwill-starter' ) );

/*
 * Security headers (X-Content-Type-Options, X-Frame-Options, Referrer-Policy,
 * Content-Security-Policy, etc.) are intentionally not set here. Set them at
 * the server or CDN layer (Cloudflare _headers file, Nginx config). Setting
 * security headers in PHP risks duplicate headers, which some proxies and
 * caching layers handle inconsistently.
 */
