<?php
defined( 'ABSPATH' ) || exit;

/**
 * WP core front-end CSS removal — GWill Starter.
 *
 * Ported from gwillchijioke-theme inc/bloat.php (portfolio, live-proven)
 * and finance-theme inc/wp-css-off.php, closing the LATE-STYLES HOLE in
 * the starter's own one-line bloat removal:
 *
 * THE HOLE (conflict-audit 2026-08-15 on the source theme, applies
 * identically here): WP 6.9+/7.x classic themes with on-demand block
 * assets re-enqueue 'global-styles' + placeholders at wp_footer priority
 * 1 (late-styles hoist) — a head-only dequeue at wp_enqueue_scripts:100
 * can NEVER catch them, so global-styles-inline-css still prints.
 * Dequeue again at wp_footer priority 2, BEFORE core's
 * print_late_styles (priority 8).
 *
 * SAFE HERE BECAUSE: the starter's theme.json uses default layout
 * settings (contentSize 1200px / wideSize 1440px — same as the
 * live-proven portfolio source), and the starter's style.css styles
 * .alignwide/.alignfull itself. Core's block-layout CSS carries
 * nothing this theme renders.
 *
 * Also removes: emoji styles + detection script (all four print
 * points), classic-theme-styles, dashicons (front end, non-logged-in
 * visitors only), jQuery Migrate (front end only), and front-end
 * heartbeat.
 *
 * INTERPLAY — comment-reply is intentionally NOT deregistered here.
 * enqueue.php loads it only on singular posts with open threaded
 * comments (the WP-native condition). Deregistering here while
 * enqueue.php enqueues it was a deadlock on the portfolio source —
 * threaded reply links never worked. Single path: enqueue.php.
 *
 * INTERPLAY — dashicons is dequeued only for non-logged-in visitors.
 * Logged-in users keep it: the front-end admin bar renders its icons
 * from the dashicons font. The starter's own CSS never uses dashicons
 * (verified — no reference in style.css, assets, or templates).
 *
 * INTERPLAY — heartbeat is deregistered on the front end only. Admin
 * autosave + editor lock notifications depend on it; the front-end
 * script serves nothing this theme uses.
 *
 * @package GWill_Starter
 * @since   1.6.0
 */

/*
* TABLE OF CONTENTS
* ─────────────────────────────────────────────────────────────────────────────
*   1. gwill_remove_core_css  Head dequeue (wp_enqueue_scripts:100)
*   2. dashicons dequeue      Front end, non-logged-in (wp_enqueue_scripts:101)
*   3. Late-styles catch      Footer re-dequeue (wp_footer:2)
*   4. Emoji removal          All four print points
*   5. jQuery Migrate strip   wp_default_scripts
*   6. Heartbeat front-end    init deregister
* ─────────────────────────────────────────────────────────────────────────────
*/

// ── 1. gwill_remove_core_css ──────────────────────────────
add_action( 'wp_enqueue_scripts', 'gwill_remove_core_css', 100 );
function gwill_remove_core_css() {
	// Block-library (~10KB) + global styles + placeholders + classic theme
	// styles. The placeholders are WP 6.9+/7.x inline-CSS carriers.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'wp-global-styles-placeholder' );
	wp_dequeue_style( 'wp-block-styles-placeholder' );
	wp_dequeue_style( 'classic-theme-styles' );
}

// ── 2. dashicons dequeue (front end, non-logged-in) ───────
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}
}, 101 );

// ── 3. Late-styles catch (WP 6.9+/7.x hoist) ──────────────
// WP re-enqueues 'global-styles' + placeholders at wp_footer priority 1;
// dequeue again BEFORE core's print_late_styles (priority 8).
add_action( 'wp_footer', function () {
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'wp-global-styles-placeholder' );
	wp_dequeue_style( 'wp-block-styles-placeholder' );
}, 2 );

// ── 4. Emoji removal ─────────────────────────────────────
// Portfolio's proven four-point removal (bloat.php). The two admin_print_*
// points are removed as well, matching the source exactly; admin-side emoji
// removal does not affect editor functionality (emoji remain insertable
// via the editors' own unicode handling).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

// ── 5. jQuery Migrate strip (front end only) ─────────────
add_action( 'wp_default_scripts', function ( $scripts ) {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) {
			$script->deps = array_values( array_diff( $script->deps, array( 'jquery-migrate' ) ) );
		}
	}
} );

// ── 6. Heartbeat front-end deregister ────────────────────
// Removes the periodic admin-ajax POST every 15–60s that the front end
// never uses. Admin keeps heartbeat (autosave, lock notifications).
add_action( 'init', function () {
	if ( ! is_admin() ) {
		wp_deregister_script( 'heartbeat' );
	}
} );
