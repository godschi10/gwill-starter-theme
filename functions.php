<?php
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/enqueue.php';
require_once get_template_directory() . '/inc/security.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/author.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/darkmode.php';
require_once get_template_directory() . '/inc/forms.php';
require_once get_template_directory() . '/inc/search.php';
require_once get_template_directory() . '/inc/related-posts.php';
require_once get_template_directory() . '/inc/social-meta.php';
require_once get_template_directory() . '/inc/faq.php';
require_once get_template_directory() . '/inc/table-of-contents.php';
require_once get_template_directory() . '/inc/testimonials.php';
require_once get_template_directory() . '/inc/pricing-table.php';
require_once get_template_directory() . '/inc/portfolio.php';
require_once get_template_directory() . '/inc/woocommerce.php';
require_once get_template_directory() . '/inc/staging.php';

add_action( 'wp_enqueue_scripts', 'gwill_tech_dequeue_old', 20 );
function gwill_tech_dequeue_old(): void {
    $handles = array( 'gwill-main', 'gwill-cookie-consent', 'gwill-back-to-top', 'gwill-sticky-header' );
    foreach ( $handles as $h ) {
        wp_dequeue_script( $h );
        wp_deregister_script( $h );
    }
    wp_dequeue_style( 'gwill-darkmode' );
    wp_dequeue_style( 'gwill-darkmode-vibe' );
    wp_dequeue_style( 'gwill-search' );
}

// Disable WordPress global styles - core adds at priority 10 on wp_enqueue_scripts
add_action( 'wp_enqueue_scripts', 'gwill_disable_global_styles', 11 );
function gwill_disable_global_styles(): void {
    wp_dequeue_style( 'global-styles' );
    wp_deregister_style( 'global-styles' );
    wp_dequeue_style( 'wp-global-styles-placeholder' );
    wp_deregister_style( 'wp-global-styles-placeholder' );
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
}

// Remove the wp_footer actions that print global styles inline
// wp_enqueue_global_styles runs at wp_footer priority 1
// wp_print_late_styles runs at wp_footer priority 20
add_action( 'init', 'gwill_remove_global_styles_footer', 999 );
function gwill_remove_global_styles_footer(): void {
    remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
    remove_action( 'wp_footer', 'wp_print_late_styles', 20 );
    // Also remove from wp_body_open where they might be added
    remove_action( 'wp_body_open', 'wp_enqueue_global_styles', 1 );
    remove_action( 'wp_body_open', 'wp_print_late_styles', 20 );
}

// Ensure our wireframe CSS loads LAST with highest priority
add_action( 'wp_enqueue_scripts', 'gwill_tech_wireframe_css_last', 9999 );
function gwill_tech_wireframe_css_last(): void {
    wp_dequeue_style( 'gwill-style' );
    wp_enqueue_style(
        'gwill-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme( get_template() )->get( 'Version' )
    );
}

// Also force remove at wp_head just in case
add_action( 'wp_head', 'gwill_force_remove_global_styles', 0 );
function gwill_force_remove_global_styles(): void {
    global $wp_styles;
    if ( isset( $wp_styles->registered['global-styles'] ) ) {
        unset( $wp_styles->registered['global-styles'] );
    }
    if ( isset( $wp_styles->registered['wp-global-styles-placeholder'] ) ) {
        unset( $wp_styles->registered['wp-global-styles-placeholder'] );
    }
}
