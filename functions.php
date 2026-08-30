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
require_once get_template_directory() . '/inc/search-index.php';
require_once get_template_directory() . '/inc/search-fts.php';
require_once get_template_directory() . '/inc/performance-base.php';
require_once get_template_directory() . '/inc/embed-facades.php';
require_once get_template_directory() . '/inc/related-posts.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/sitemap.php';
require_once get_template_directory() . '/inc/social-meta.php';
require_once get_template_directory() . '/inc/faq.php';
require_once get_template_directory() . '/inc/table-of-contents.php';
require_once get_template_directory() . '/inc/testimonials.php';
require_once get_template_directory() . '/inc/pricing-table.php';
require_once get_template_directory() . '/inc/portfolio.php';
require_once get_template_directory() . '/inc/woocommerce.php';
require_once get_template_directory() . '/inc/staging.php';
require_once get_template_directory() . '/inc/pwa.php';
require_once get_template_directory() . '/inc/webpush.php';
require_once get_template_directory() . '/inc/apps.php';
require_once get_template_directory() . '/inc/analytics.php';
require_once get_template_directory() . '/inc/push-dashboard.php';

// v1.6.0 — Tier A ports (GWILL-FEATURE-ROADMAP.md):
//   wp-css-off.php       core-CSS removal — closes the LATE-STYLES HOLE
//                        (WP 6.9+/7.x re-enqueues global-styles at wp_footer;
//                        supersedes enqueue.php's old head-only dequeue)
//   images.php           image CLS pass (width/height, decoding=async, WebP)
//   cache-purge.php     purge FastCGI cache on publish/save
//   minify.php          HTML whitespace minification (pre/code/script/style safe)
//   login-rate-limit.php brute-force lockout — companion REQUIRED by 2FA docs
//   two-factor.php      TOTP 2FA (RFC 6238) + backup codes + profile panel
require_once get_template_directory() . '/inc/wp-css-off.php';
require_once get_template_directory() . '/inc/images.php';
require_once get_template_directory() . '/inc/cache-purge.php';
require_once get_template_directory() . '/inc/minify.php';
require_once get_template_directory() . '/inc/login-rate-limit.php';
require_once get_template_directory() . '/inc/two-factor.php';
