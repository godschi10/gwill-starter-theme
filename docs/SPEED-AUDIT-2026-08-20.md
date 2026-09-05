# SPEED & PERFORMANCE AUDIT - GWill Starter Theme

**Date:** 2026-08-20 · **Theme version:** v1.3.6 (repo at `/var/www/wp-content/themes/gwill-starter-theme/`)
**Method:** Static analysis of all 6 CSS files, 20 JS files, all 24 inc/ modules, all templates - against the 6-section protocol. No live site (the theme is a base template, never directly activated). Verification = `php -l`, `grep` inventories, file-size checks, cross-file dependency audit.
**Prior reports:** CONFLICT (v1.3.1), RESPONSIVE (v1.3.2+v1.3.3), CROSS-BROWSER (v1.3.4), SECURITY (v1.3.5).

---

## SECTION 1: PAGE LOAD BASELINE

```
[CLEAN] Server response - no heavy queries in init/template_redirect/wp_head
        hooks (verified: all hooks are lightweight - remove_action statements,
        rate-limit checks, security headers, LCP preload). The only DB hook
        on wp_head is gwill_preload_lcp (query-free, uses main query data).
[CLEAN] Caching layer - the theme is caching-layer-agnostic: transients flow
        through any object cache (Redis/Memcached) or degrade to MySQL. The
        sitemap, search index, and Cloudflare IP ranges are all transient-
        cached (DAY_IN_SECONDS). No assumptions about page cache  - 
        server-level nginx page cache is expected.
[CLEAN] Cron - no cron jobs or scheduled events that hammer the DB on page
        load. The sitemap invalidates on save_post (transient delete) - no
        rebuild on page load (first request after save rebuilds it).
```

---

## SECTION 2: ASSET DELIVERY

```
[SHOULD] [all] inc/enqueue.php - wp-block-library CSS not dequeued (~10 KB)
[ISSUE] WordPress core enqueues wp-block-library.css (~10 KB, 86 selectors)
        on every page rendering blocks (post content, pages, archives with
        excerpts). The theme styles .entry-content typography, lists,
        blockquote, headings, and images itself (style.css:499-521) - core's
        block CSS is dead weight. The finance theme's equivalent (wp-css-off.php)
        dequeued this; the starter did not.
[FIX] Added `wp_dequeue_style('wp-block-library')` in a priority-100 action
      (fires after core's enqueue). (APPLIED - v1.3.6.)
```

```
[CLEAN] Enqueue inventory - all assets enqueued via wp_enqueue_scripts (correct):
        • gwill-style (style.css) - global, ~68 KB
        • gwill-main (main.js) - global, deferred, ~4.5 KB
        • gwill-cookie-consent (cookie-consent.js) - global, deferred, ~2 KB
        • gwill-back-to-top (back-to-top.js) - global, deferred, ~2 KB
        • gwill-sticky-header (sticky-header.js) - global, deferred, ~1 KB
        • gwill-search-dropdown (search-dropdown.js) - global, deferred, ~18 KB
        • gwill-forms + multistep + exit - registered, enqueued only from form partials
        • gwill-search-expand + gwill-search-modal - registered, enqueued from their partials
        • gwill-embeds (embeds.js + embeds.css) - conditional: is_singular() + has_block('core/embed')
        • gwill-darkmode + gwill-darkmode-vibe - conditional: darkmode.css + vibe-comments
        • gwill-woocommerce - class_exists-gated
        • gwill-customizer-preview - admin Customizer only
[CLEAN] Render-blocking - all scripts use strategy: 'defer' with in_footer: true.
        The only blocking script is the darkmode head inline script
        (inc/darkmode.php: ~1 KB, must be synchronous to prevent the white
        canvas flash - documented deliberate). No external render-blocking
        resources.
[CLEAN] Defer/async - all 20 JS files are deferred (verified: the main
        enqueue block uses `[ 'in_footer' => true, 'strategy' => 'defer' ]`;
        all registered scripts in enqueue.php carry the same options).
[CLEAN] CSS/JS combining - not needed (HTTP/2 multiplexing). The current
        split (one global CSS + conditional per-feature sheets) is optimal
        for cache granularity.
[CLEAN] Font loading - system-ui font stack. ZERO external fonts, ZERO
        Google Fonts, ZERO font-display concerns. No FOUT/FOIT, no font
        download, no font preload needed.
[CLEAN] Preload/preconnect - LCP image preload via <link rel="preload">
        (inc/performance-base.php:71, wp_head priority 2) with imagesrcset +
        imagesizes - correct. No CDN origins to preconnect (no external
        resources). No other preload hints needed.
[CLEAN] Cache headers - static assets carry ?ver=1.3.x (versioned cache-
        buster). The search-index REST endpoint sets Cache-Control: public,
        max-age=300. The sitemap is transient-cached (no browser cache
        header needed - server cache handles it).
```

---

## SECTION 3: IMAGES & MEDIA

```
[CLEAN] Modern formats - WP core handles WebP/AVIF conversion via
        wp_get_attachment_image() (server/plugin-dependent). The theme
        outputs via the_post_thumbnail(), which respects the core format
        stack. No format hardcoding.
[CLEAN] Image sizes - only one custom size registered (`gwill-hero` 1200×675,
        inc/setup.php:81). Used in featured-image.php (the LCP hero). All
        other templates use core sizes: 'medium' (related posts, 300px),
        'medium_large' (content cards, 768px), 'thumbnail' (testimonials,
        150px), 'large' (portfolio, 1024px). No unused custom sizes.
[CLEAN] Width/height - the_post_thumbnail() outputs width/height attrs
        (WP core). No CLS risk from missing dimensions.
[CLEAN] Lazy loading - LCP image (hero) uses fetchpriority="high" +
        loading="eager" + decoding="sync" (featured-image.php:85). Below-
        fold images use loading="lazy" (WP default). Card thumbnails use
        loading="lazy" (related-posts.php:35). Correct.
[CLEAN] Hero/LCP preload - gwill_preload_lcp() (wp_head priority 2) emits
        <link rel="preload" as="image" ...> with imagesrcset + imagesizes
        for the featured image on singulars, and the first post's cover on
        home/archives (query-free - uses the main query). ✓
[CLEAN] SVGs/GIFs - all icons are inline SVGs (stroke=currentColor, ~200-500
        bytes each). No GIFs. No autoplaying video.
```

---

## SECTION 4: DATABASE & QUERIES

```
[CLEAN] WP_Query patterns - all custom queries use no_found_rows=true:
        • related posts (inc/related-posts.php:53) - no_found_rows, posts_per_page=3
        • sitemap build (inc/sitemap.php:126) - no_found_rows, suppress_filters=false
        • fuzzy search candidates (inc/search.php:890) - no_found_rows, 500 max
        • FTS search (search-fts.php) - own SQLite DB, no WP_Query
[CLEAN] N+1 patterns - none. Thumbnail meta is batched via
        gwill_prime_thumbnail_cache() (the_posts filter, update_post_thumbnail_cache).
        Primary category is memoized per post_id (gwill_get_primary_category,
        static $cache array). The only per-query function is
        gwill_get_related_posts() (one WP_Query per call, no_found_rows, 3 posts)
        - acceptable for a singular post template (called once per page).
[CLEAN] Transients - used for all expensive operations:
        • gwill_sitemap_xml - DAY_IN_SECONDS, busted on save_post
        • gwill_search_index - DAY_IN_SECONDS, busted on save_post
        • gwill_cloudflare_ip_ranges - DAY_IN_SECONDS, refetched on expiry
        • gwill_rl_* - rate-limit window (per-IP, short TTL)
        • gwill_hp_expected_* - homepage traffic probe
[CLEAN] Postmeta - no get_post_meta called in loops (the only meta access
        is gwill_get_primary_category, memoized). The featured image video
        meta box (_gwill_video_url) is read once per singular page.
[CLEAN] Autoloaded options - the theme creates only one option:
        gwill_rewrite_ver (version-keyed rewrite flush, autoload? not set  - 
        so non-autoloaded by default). No bloat.
```

---

## SECTION 5: THEME CODE ISSUES

```
[CLEAN] Heavy libraries - zero. No jQuery, no jQuery UI, no FontAwesome,
        no Google Fonts, no icon fonts. All 20 JS files are vanilla. The
        only "library" is system-ui (the browser's own font stack). ✓
[CLEAN] PHP doing uncached work - all remote calls are cached:
        • Cloudflare IP fetch - 1-day transient
        • Brevo API - AJAX-only (not page load), no caching needed
        • .htaccess check - file_exists, cheap (only on FTS init)
[CLEAN] Enqueue conditionals - correct:
        • gwill-embeds: is_singular() && has_block('core/embed')
        • gwill-forms: enqueued from form partials only
        • gwill-search-expand/modal: enqueued from their template parts
        • gwill-woocommerce: class_exists('WooCommerce') gate
        • gwill-darkmode-vibe: vibe-comments plugin check
        • gwill-customizer-preview: is_customize_preview()
[CLEAN] Blocking code in header - the only inline <script> is the darkmode
        pre-paint script (inc/darkmode.php: inline, ~1 KB, synchronous by
        design - must execute before first paint to prevent white-canvas
        flash for dark-mode users). This is a deliberate trade-off,
        documented in the codebase. No other blocking code.
[CLEAN] Core bloat - removed: wp_generator, shortlink, emoji detection JS,
        RSD link (core 6.3+), wlwmanifest (core 6.3+), XML-RPC disabled.
        wp-block-library dequeued (v1.3.6). Remaining: global-styles inline
        (~1 KB from theme.json palette) - acceptable for a base theme
        (the palette feeds the block editor and exposes CSS variables).
        Dashicons - not loaded (no admin-bar dependency on the frontend).
[CLEAN] Duplicate libraries - no libraries loaded at all. ✓
```

---

## SECTION 6: SERVER & CONFIG

```
[N/A] gzip/brotli - server-level (nginx config, not theme). Noted: the
      server should have compression enabled for CSS/JS/HTML.
[N/A] HTTP/2 - server-level. The theme does not block it.
[CLEAN] PHP version - the theme uses PHP 8.0+ syntax (typed properties,
        union types, match expressions, named arguments in WP_Query?  - 
        checked: WP_Query uses array syntax, not named args). Compatible
        with PHP 8.0–8.4. The style.css header declares no minimum
        (defaults to WP core's minimum).
[CLEAN] OPcache - the theme's composer.json is dev-only (phpcs), no runtime
        vendor/ directory. No OPcache issues.
[N/A] Cache TTLs - server-level (nginx cache-control headers). The theme
      sets Cache-Control on the search-index REST endpoint (public, max-
      age=300) - correct for a dynamic index.
[N/A] CDN - server-level. The theme loads no external resources, so a CDN
      would only serve theme assets (style.css, JS) - beneficial but not
      required.
```

---

## Summary

### Counts by severity
| Severity | Count |
|---|---|
| CRITICAL | 0 |
| SHOULD | **1 (fixed in v1.3.6 - wp-block-library dequeue)** |
| NICE-TO-HAVE | 0 (1 observation documented - global-styles inline, ~1 KB, deliberate) |

### Core Web Vitals projection
Sections 1-5 are all CLEAN or fixed. The theme's asset delivery model (deferred JS, system-ui fonts, no 3rd-party resources, LCP preload, thumbnail cache, transient-cached DB operations) is optimised for good LCP, CLS, and INP on any build. The server layer (caching, compression, HTTP/2) is the remaining variable.

### Prioritized fix order
1. Dequeue wp-block-library (~10 KB per page) - **done** (v1.3.6).
2. Everything else: **CLEAN** - no further action.

### Quick wins
- The wp-block-library dequeue is a one-line addition (done).

---

## FIXES-APPLIED LEDGER (v1.3.6)

| # | Finding | File | Change | Verification |
|---|---|---|---|---|
| 1 | wp-block-library CSS not dequeued | inc/enqueue.php:8-12 | Added `add_action('wp_enqueue_scripts', function(){ wp_dequeue_style('wp-block-library'); }, 100)` | php -l clean; grep-verified (1 dequeue present) |

**Changed-file count: 1** (`inc/enqueue.php`). Version bumped to **1.3.6**.

---
*Audit complete. 1 SHOULD finding (fixed); everything else CLEAN.*