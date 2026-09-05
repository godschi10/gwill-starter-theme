<?php
defined( 'ABSPATH' ) || exit;

/**
 * Login page branding - GWill Starter.
 *
 * FRESH MODULE (v1.7.0 - no elder theme owns this; verified by recon
 * Aug 30 2026: only tech's 2FA touches login hooks, and only to inject
 * the code field). Brands wp-login.php with what the site already owns:
 *
 *   - The custom logo (custom-logo support declared in inc/setup.php)
 *     replaces the WordPress logo; sites without a logo get a clean
 *     wordmark of the site title instead.
 *   - The logo links to home_url() (not wordpress.org) and announces
 *     the site name (login_headerurl / login_headertitle filters  - 
 *     the two documented, stable core hooks).
 *   - The submit button and focus ring take the theme's accent token,
 *     so the login page reads as the brand without shipping a full
 *     stylesheet. Filterable via 'gwill_login_accent' for brand skins.
 *
 * Scope is deliberately the login page's OWN chrome: the form markup,
 * error boxes and the 2FA code field (inc/two-factor.php, v1.6.0) are
 * never restyled - WP core's login CSS already sizes and lays them out,
 * and fighting it from a theme is a maintenance trap.
 *
 * @package GWill_Starter
 * @since   1.7.0
 */

/*
* TABLE OF CONTENTS
* ─────────────────────────────────────────────────────────────────────────────
*   1. gwill_login_header_link  Logo → site home + site name
*   2. gwill_login_branding_css Accent + logo paint (login_head)
* ─────────────────────────────────────────────────────────────────────────────
*/

// ── 1. gwill_login_header_link ─────────────────────────────
add_filter( 'login_headerurl', fn() => home_url( '/' ) );
add_filter( 'login_headertitle', fn() => get_bloginfo( 'name' ) );

// ── 2. gwill_login_branding_css ────────────────────────────
/**
 * Paint the login page: accent button/focus, logo or site-title wordmark.
 *
 * The logo, when present, is painted as the h1 link's background - the
 * core markup stays untouched (an <img> there inherits core's box rules
 * and fights the max-width). 100% height inside core's 84px slot keeps
 * every logo shape crisp.
 *
 * @since 1.7.0
 */
function gwill_login_branding_css(): void {
	$accent = '#2563eb'; // the starter's default --color-accent
	$accent = (string) apply_filters( 'gwill_login_accent', $accent );

	$logo_css = '';
	if ( has_custom_logo() ) {
		$logo = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );
		if ( $logo ) {
			$logo = esc_url( $logo );
			// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStyleSheet -- inline style fragment below
			$logo_css = '.login h1 a{background-image:url(' . $logo . ');background-size:contain;background-position:center;width:100%;max-width:320px}';
			// phpcs:enable
		}
	} else {
		// No logo: a clean wordmark of the site name replaces the WP logo.
		$logo_css = '.login h1 a{background:none!important;text-indent:0;width:auto;max-width:320px;font-size:1.45rem;font-weight:700;color:#111;text-decoration:none}';
	}

	echo '<style id="gwill-login-branding">'
		. $logo_css
		. 'body.login{border-color:' . $accent . '}'
		. 'body.login form .button-primary{background:' . $accent . ';border-color:' . $accent . ';box-shadow:0 1px 0 ' . $accent . '}'
		. 'body.login form .button-primary:hover,'
		. 'body.login form .button-primary:focus{background:' . $accent . ';border-color:' . $accent . ';filter:brightness(.92)}'
		. 'body.login form input:focus,'
		. 'body.login form textarea:focus{border-color:' . $accent . ';box-shadow:0 0 0 1px ' . $accent . '}'
		. 'a:focus{box-shadow:0 0 0 1px ' . $accent . ' inset}'
		. '</style>' . "\n";
}
add_action( 'login_head', 'gwill_login_branding_css' );
