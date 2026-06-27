<?php
/**
 * WooCommerce compatibility layer.
 *
 * Tier 3, opt-in by nature rather than by a define() — every hook in this
 * file is wrapped in class_exists( 'WooCommerce' ), so on a site that
 * never installs the plugin, this file still loads (cheap: it's just
 * function definitions) but registers nothing at all. Zero runtime cost,
 * not just "small" cost, on a site that doesn't use it.
 *
 * @package GWill_Starter
 * @since   1.0.60
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'gwill_woocommerce_setup' );

/**
 * Declare WooCommerce theme support and remove its default content wrapper.
 *
 * The wrapper removal needs explaining, because the snippet usually shown
 * for this (remove the default wrapper, add your own matching one back)
 * does NOT apply here. header.php in this theme already unconditionally
 * opens <main class="site-main" id="content"><div class="inner"> for
 * every single template, with footer.php closing it — no template in
 * this theme ever opens that wrapper itself. So WooCommerce's own
 * default wrapper just needs removing, with nothing added back; adding
 * a second wrapper here would nest it inside the one header.php already
 * opened.
 *
 * Hooked to after_setup_theme rather than init: WooCommerce's own
 * 'woocommerce_before_main_content' / '_after_' actions aren't fired until
 * a WC template runs, well after this — timing only matters here for the
 * class_exists() check itself, and after_setup_theme runs after plugins
 * are loaded, same as every other plugin-detection check in this theme
 * (gwill_seo_plugin_active(), etc.).
 *
 * @since 1.0.60
 */
function gwill_woocommerce_setup(): void {

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

	add_action( 'wp_enqueue_scripts', 'gwill_woocommerce_enqueue_styles' );
	add_filter( 'woocommerce_add_to_cart_fragments', 'gwill_woocommerce_cart_fragment' );
}

/**
 * Enqueue the WooCommerce-specific stylesheet — design-token overrides
 * for WC's default markup, plus the header cart icon.
 *
 * A separate file rather than appending to the main style.css, on
 * purpose: the whole point of gating this behind class_exists() is that
 * a non-WooCommerce site pays nothing for this feature. Appending these
 * rules to style.css would mean every visitor on every site downloads
 * WooCommerce-specific CSS even when the plugin's never installed.
 * woocommerce.css is only ever enqueued inside this same class_exists()
 * gate, so it only ever loads on a site that actually has the plugin.
 *
 * @since 1.0.60
 */
function gwill_woocommerce_enqueue_styles(): void {
	wp_enqueue_style(
		'gwill-woocommerce',
		get_template_directory_uri() . '/assets/css/woocommerce.css',
		[],
		wp_get_theme( get_template() )->get( 'Version' )
	);
}

/**
 * Render the header cart icon — item count badge included.
 *
 * Called directly from header.php inside its own class_exists() check
 * (not auto-hooked), matching how every other optional header element in
 * this theme (the dark-mode toggle, search) is placed explicitly in the
 * markup rather than hooked in from inc/ — header.php is where the
 * actual visual order of header elements is decided, on purpose.
 *
 * @since 1.0.60
 */
function gwill_render_cart_icon(): void {

	if ( ! class_exists( 'WooCommerce' ) || is_null( WC()->cart ) ) {
		return;
	}

	gwill_part( 'woocommerce/cart-icon' );
}

/**
 * Register the cart icon's count badge for WooCommerce's AJAX cart fragments.
 *
 * WooCommerce's own wc-cart-fragments.js (enqueued automatically by the
 * plugin, not something this theme needs to enqueue itself) listens for
 * the 'added_to_cart' event and swaps any DOM element matching a key
 * returned here for the matching HTML value — that's the whole mechanism
 * an "add to cart updates the header count with no full page reload"
 * interaction runs on, and it's been stable WooCommerce core API for
 * years, not something specific to a particular WC version.
 *
 * @param  array<string,string> $fragments
 * @return array<string,string>
 * @since  1.0.60
 */
function gwill_woocommerce_cart_fragment( array $fragments ): array {
	ob_start();
	gwill_part( 'woocommerce/cart-icon' );
	$fragments['a.gwill-cart-icon'] = ob_get_clean();
	return $fragments;
}
