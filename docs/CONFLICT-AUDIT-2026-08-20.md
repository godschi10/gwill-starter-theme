# PLUGIN & THEME CONFLICT AUDIT — GWill Starter Theme

**Date:** 2026-08-20
**Theme (audited + fixed):** `/var/www/wp-content/themes/gwill-starter-theme` (repo == live, v1.3.0 → v1.3.1)
**Audit basis:** full static file-by-file analysis — functions.php, all 24 inc/ modules, all templates, all 20 JS files, all 6 CSS files — against the protocol's 7 sections, with the **SEO-plugin dimension as the King's explicit focus** (the starter ships its own SEO layer and must not fight RankMath/Yoast/AIOSEO/SEOPress/The SEO Framework on any future build).
**Plugins considered (the known GWill stack + common majors):** advanced-custom-fields, redis-cache, sqlite-database-integration, vibe-comments, WooCommerce, RankMath, Yoast SEO, AIOSEO, SEOPress, The SEO Framework, WP Rocket / LiteSpeed (lazy-load), SMTP plugins (WP Mail SMTP / FluentSMTP).
**Live reference:** no site activates this theme (it is the base for future builds) — the 404 on localhost is expected, not a bug. Verification = `php -l`, `node --check`, static cross-file dependency checks, surgical-diff review.

Every finding uses the required format. Sections with nothing to flag are marked **CLEAN** explicitly.

---

## SECTION 1: PHP CONFLICTS

```
[SHOULD] [Hook] inc/sitemap.php:35 (gwill_sitemap_rewrite)
[ISSUE] The theme's ^sitemap\.xml/?$ rewrite rule registers UNCONDITIONALLY on
        init. AIOSEO and The SEO Framework both serve their main sitemap at
        /sitemap.xml — with either plugin active, the theme's 'top'-priority
        rule hijacks the plugin's sitemap URL before the plugin can route it
        (duplicate sitemap surface, wrong XML served).
[FIX] gwill_seo_plugin_active() guard added at the top of the function — the
      rule is not registered while a major SEO plugin is active. With no SEO
      plugin active the output is byte-identical to before. (APPLIED — v1.3.1.)
      This is the finance v1.0.201 lesson (its #2 most-dangerous latent
      conflict) ported into the base theme.
```

```
[SHOULD] [Hook] inc/sitemap.php:65 (gwill_sitemap_template)
[ISSUE] The template_include swap fires for any request carrying the
        gwill_sitemap query var, unconditionally. With an SEO plugin active,
        a plugin-owned sitemap route must never be swapped out by the theme.
[FIX] gwill_seo_plugin_active() guard added at the top of the function — with
      a plugin active the theme never intercepts templates. (APPLIED — v1.3.1.)
```

```
[SHOULD] [Hook] inc/sitemap.php:90 (gwill_sitemap_canonical)
[ISSUE] redirect_canonical suppression for the sitemap query var was
        unconditional — with an SEO plugin active, the plugin's own canonical
        handling for its sitemap could be suppressed if the var ever leaked.
[FIX] gwill_seo_plugin_active() guard added — with a plugin active the theme
      returns the plugin's canonical redirect untouched. Defense in depth on
      top of the rewrite guard. (APPLIED — v1.3.1.)
```

```
[SHOULD] [Hook] inc/seo.php:40 (gwill_front_page_title, pre_get_document_title @ 11)
[ISSUE] RankMath hooks pre_get_document_title at priority 10; the theme's
        priority-11 filter runs AFTER it and overrides the admin's configured
        homepage title in RankMath ("Homepage Title" setting silently
        discarded). Yoast at 15 happens to win against the theme, but the
        RankMath case is a genuine override — the theme's front-page title
        one-liner is the only SEO output besides the sitemap trio that did not
        defer.
[FIX] gwill_seo_plugin_active() guard added at the top of the function — with
      a plugin active the theme returns the plugin's title untouched.
      (APPLIED — v1.3.1.)
```

```
[CLEAN] PHP — duplicate function names
[FILE] entire theme
[ISSUE] none. Every declaration is gwill_-prefixed (grep-verified across
        functions.php + all inc/). The only non-gwill tokens the prefix scan
        surfaced ("sync", "resolve", "syncBtn") are JavaScript function names
        inside the inline <script> output of inc/darkmode.php — scoped inside
        an IIFE, not PHP, not global, no collision possible. Plugins use
        acf_*/vibe_*/WooCommerce*/rank_math_*/wpseo_* namespaces. Zero
        function_exists collision risk.
```

```
[CLEAN] PHP — class name collisions
[ISSUE] none. The theme declares ZERO classes (plain functions only, verified
        by class-declaration scan). Plugin classes are ACF_*, Vibe_Comments_*,
        WooCommerce, RankMath\*, Yoast\WP\SEO\* — no overlap, no fatal.
```

```
[CLEAN] PHP — hook/filter priority fights
[FILE] inc/seo.php, inc/social-meta.php, inc/security.php, inc/enqueue.php,
       inc/performance-base.php vs plugins
[ISSUE] none. Theme wp_head actions sit at distinct priorities: social-meta 1,
        meta-description 2, LCP preload 2, robots 3, JSON-LD 5, canonical 10,
        FAQ schema 10 (default, distinct function). No two theme callbacks
        share a slot with contradictory work; no plugin shares a slot with a
        theme callback on the same output. the_content: theme owns only
        priority 20 (TOC) — no plugin in the considered stack filters
        the_content at 20 with conflicting logic.
```

```
[CLEAN] PHP — autoloader / namespace overlaps
[ISSUE] none. Theme has no autoloader and no namespace declarations;
        composer.json is dev-only phpcs tooling (never loaded on a site).
        Plugins load their own autoloaders scoped to their own prefixes.
```

```
[CLEAN] PHP — bundled-library duplicates
[ISSUE] none. Theme bundles no third-party runtime library (no TinyMCE,
        jQuery UI, Carbon Fields, CMB2, no vendor/ at runtime). The only
        third-party-coded surface is PHPMailer, which is WordPress core's own
        copy — the theme only configures it, never bundles it.
```

```
[CLEAN] PHP — shutdown/destructor hooks
[ISSUE] none. Zero register_shutdown_function / 'shutdown' actions anywhere in
        the theme (grep-verified). No page-kill risk from a theme callback.
```

---

## SECTION 2: CSS CONFLICTS

```
[CLEAN] CSS — plugin global resets/frameworks
[ISSUE] none. No theme CSS is reset/bootstrap/tailwind; none of the considered
        plugins load global resets on the frontend. ACF/redis/sqlite load no
        frontend CSS; vibe-comments loads only its scoped .vibe-* sheet on
        singular pages with comments; WooCommerce loads its own sheet which the
        theme deliberately styles over (below).
```

```
[CLEAN] CSS — !important wars
[FILE] style.css (5), assets/css/search.css (15)
[ISSUE] none. All 5 style.css !important uses are deliberate and isolated
        (reduced-motion duration overrides at :77-79, a display:none state, a
        display:block state). All 15 search.css !important uses are the search
        input/button chrome resets (border/outline/box-shadow/tap-highlight
        removed) — isolated to .gwill-search-* selectors. No plugin targets
        these selectors; no !important is used to fight another stylesheet.
        woocommerce.css and darkmode-vibe-comments.css carry ZERO !important —
        the theme-vs-plugin override layers use design-token specificity, not
        brute force.
```

```
[CLEAN] CSS — duplicate IDs/classes
[ISSUE] none. Grep found no duplicated ID rule sets; the #search-* ids in
        search.css match exactly one markup owner each (header.php dropdown /
        search partials). The .gwill-faq / .gwill-testimonial / .gwill-portfolio
        class families each belong to one block/partial.
```

```
[CLEAN] CSS — enqueue order
[FILE] inc/enqueue.php vs plugins
[ISSUE] none — correct by design. Theme styles: gwill-style first, then
        gwill-darkmode (dep: gwill-style), then gwill-search (dep: gwill-style),
        then gwill-embeds (dep: gwill-style), gwill-darkmode-vibe (dep:
        gwill-darkmode, only when vibe-comments is active), gwill-woocommerce
        (dep: gwill-style, only when WooCommerce is active). Dependency chains
        guarantee the theme's own sheets load after any plugin sheet they must
        override, without relying on registration order.
```

```
[CLEAN] CSS — unprefixed inline styles
[ISSUE] none. The only inline style injection is the darkmode pre-paint script
        (inc/darkmode.php: root[data-theme] + root.style.background) — scoped
        to the <html> element the theme owns, prefixed KEY 'gwill-color-scheme'.
        No global leakage, no plugin stylesheet targeted.
```

---

## SECTION 3: JAVASCRIPT CONFLICTS

```
[CLEAN] JS — multiple jQuery / noConflict
[ISSUE] none. The theme loads ZERO jQuery — all 20 assets/js scripts are
        vanilla (grep-verified: no jQuery token in any theme JS). No 3.x-vs-1.x
        risk, no $() direct-call risk, no noConflict breakage. WooCommerce's
        bundled jQuery is untouched by the theme.
```

```
[CLEAN] JS — script errors on load
[FILE] assets/js/*.js (20 files)
[ISSUE] none. node --check passes on every theme script (re-run this audit).
```

```
[CLEAN] JS — duplicate script handles
[ISSUE] none. Theme handles: gwill-style, gwill-main, gwill-cookie-consent,
        gwill-back-to-top, gwill-sticky-header, gwill-search-dropdown,
        gwill-search-expand, gwill-search-modal, gwill-forms,
        gwill-forms-multistep, gwill-forms-exit, gwill-darkmode,
        gwill-darkmode-vibe, gwill-embeds, gwill-customizer-preview,
        gwill-woocommerce (plus comment-reply). Plugin handles in the
        considered stack: vibe-comments, woocommerce, rank-math, etc. Zero
        overlap — every theme handle is gwill-prefixed.
```

```
[CLEAN] JS — scripts on every page
[ISSUE] none, with documented notes. Unconditionally enqueued: gwill-main,
        gwill-cookie-consent, gwill-back-to-top, gwill-sticky-header,
        gwill-search-dropdown — all justified (header/footer features present
        on every page). Conditionally enqueued: gwill-forms + multistep + exit
        (only from form partials), gwill-search-expand / gwill-search-modal
        (only from their search partials), gwill-darkmode-vibe (only with
        vibe-comments + singular), gwill-embeds (only is_singular() +
        has_block('core/embed')), gwill-woocommerce (only with WooCommerce).
        gwill-search-dropdown is unconditional because its markup lives inline
        in header.php — and its JS no-ops safely if a build swaps the header
        search (guard at search-dropdown.js:102: `if ( ! toggles.length ||
        ! dropdown || ! input ) return;`). KNOWN-DELIBERATE.
```

```
[CLEAN] JS — window.* collisions
[ISSUE] none. Theme globals/localized objects: GwillBackToTop, GwillDarkmode,
        GwillDropdown, GwillExpand, GwillMultistep, GwillSearch, gwillGetNonce
        — all gwill/Gwill-prefixed. Plugin globals: vibeComments, wc_*, etc.
        No collisions.
```

---

## SECTION 4: DATABASE & OPTIONS CONFLICTS

```
[CLEAN] DB — option-name collisions
[ISSUE] none. Theme options: gwill_rewrite_ver (the only option the theme
        writes; version-keyed rewrite flush) + core options read-only
        (active_plugins, admin_email, date_format, page_for_posts,
        posts_per_page, thread_comments). Plugins: acf_* / vibe_* / _rediscache_*
        / WP_REDIS_* / rank_math_* / wpseo_*. Grep-verified — zero shared
        option names.
```

```
[CLEAN] DB — transient key clashes
[ISSUE] none. Theme transients: gwill_search_index, gwill_sitemap_xml,
        gwill_cloudflare_ip_ranges, gwill_hp_expected_* (homepage-expected
        traffic probes), gwill_rl_* (rate-limit hashes). All gwill_-prefixed;
        no data corruption risk with plugin transients (vibe_*, rank_math_*).
```

```
[CLEAN] DB — CPT/taxonomy slug duplicates
[ISSUE] none. Theme registers gwill_testimonial + gwill_portfolio CPTs and the
        gwill_portfolio_type taxonomy — all gwill_-prefixed, unique slugs,
        namespaced labels. No plugin in the considered stack registers these
        slugs. Shortcode tags (gwill_testimonials, gwill_portfolio) are equally
        unique — last-one-wins is impossible.
```

```
[CLEAN] DB — meta key collisions
[ISSUE] none. Theme meta keys: _gwill_video_url, _gwill_testimonial_rating,
        _gwill_testimonial_role, _gwill_portfolio_client, _gwill_portfolio_url
        — all _gwill_-prefixed, all read/written by the theme's own meta boxes
        only. No overlap with acf_* / _vibe_* / rank_math_* formats.
```

```
[CLEAN] DB — table name collisions
[ISSUE] none. The theme creates NO wp_* MySQL table. Its two tables live in a
        dedicated SQLite database under wp-content/uploads/gwill-search/
        (gwill_fts_search — FTS5 index; gwill_search_log — query log), fully
        separate from the WP database and any plugin's tables. The optional
        {prefix}gwill_form_submissions MySQL table is documented and only
        created manually when GWILL_LOG_FORMS is defined (inc/forms.php:35-57)
        — gwill_-prefixed, zero collision.
```

---

## SECTION 5: HOOK & FILTER CONFLICTS

```
[CLEAN] Hooks — same-filter fights (the_content, excerpt, menus)
[ISSUE] none. excerpt_length: theme at 999 (its own gwill_excerpt_length
        wrapper, filterable) — no plugin in the stack touches it. the_content:
        theme at 20 (TOC) only. wp_nav_menu: theme renders its own fallback
        only when no menu is assigned; no plugin modifies menus in the stack.
        body_class: theme adds one class (sticky-header state) — additive,
        no plugin fights it.
```

```
[CLEAN] Hooks — remove_action conflicts
[ISSUE] none. Theme removes core actions only: wp_generator, wp_shortlink_wp_head,
        print_emoji_detection_script (inc/security.php), and WooCommerce's
        default content wrappers (inc/woocommerce.php:52-53, class_exists-
        gated — the theme's header.php/footer.php open and close the wrapper
        themselves, so the removal is the documented correct adaptation). No
        plugin in the stack depends on any of those outputs.
```

```
[CLEAN] Hooks — template_redirect / init / wp_loaded clashes
[ISSUE] none. Theme template_redirect: priority 20 (security headers) + two
        closures (?author=N enumeration block, search no-query guard) — no
        plugin hijacks template_redirect in the stack. Theme init: sitemap
        rewrite (now plugin-guarded), CPT/FAQ registrations, gwill_maybe_flush_
        rewrites at 99 — distinct priorities, no hijacks. wp_loaded: theme
        registers nothing.
```

```
[CLEAN] Hooks — rewrite-rule clashes (post-fix)
[FILE] inc/sitemap.php:35 + the WP core wp-sitemap.xml route
[ISSUE] none after v1.3.1. The theme's ^sitemap\.xml/?$ rule is now gated
        behind gwill_seo_plugin_active() — without an SEO plugin it is the
        documented deliberate sitemap design (robots.txt advertises
        /sitemap.xml only); with one active, the plugin's own sitemap route
        wins and the theme registers nothing. No plugin registers the bare
        sitemap.xml pattern in the no-plugin case.
```

```
[CLEAN] Hooks — shortcode tag duplicates
[ISSUE] none. Exactly two shortcodes in the theme (gwill_testimonials,
        gwill_portfolio), both uniquely gwill_-prefixed, registered once each
        (grep-verified). No plugin registers these tags.
```

---

## SECTION 6: FUNCTIONAL CONFLICTS

```
[CLEAN] Functional — overlapping features (SEO plugin dimension — the King's focus)
[FILE] inc/seo.php, inc/sitemap.php, inc/social-meta.php
[ISSUE] none after v1.3.1. ALL TEN theme-owned SEO outputs now defer to a major
        SEO plugin via gwill_seo_plugin_active() (detects RankMath, Yoast,
        AIOSEO ×2, SEOPress, The SEO Framework):
         1. front-page <title> (pre_get_document_title)   — v1.3.1 NEW GUARD
         2. document_title_parts (long-singular trim)     — guarded
         3. meta description (wp_head 2)                  — guarded
         4. robots meta (wp_head 3, respects Yoast output) — guarded
         5. JSON-LD WebSite/Org/Article (wp_head 5)       — guarded
         6. canonical (wp_head 10)                        — guarded
         7. robots.txt filter                             — guarded
         8. OG/Twitter social meta (wp_head 1)            — guarded
         9. sitemap rewrite + template + canonical        — v1.3.1 NEW GUARDS
        10. wp_sitemaps_enabled (core wp-sitemap.xml)     — guarded
        Lazy-load: theme preloads LCP only (performance-base); no plugin
        lazy-load fight. Object cache / page cache: theme transients flow
        through Redis/cache plugins fine (gwill_-prefixed keys).
```

```
[CLEAN] Functional — JSON-LD schema duplication
[FILE] inc/seo.php + inc/faq.php vs RankMath/Yoast
[ISSUE] none. Theme emits WebSite/Organization/Article (wp_head 5, plugin-
        guarded). The FAQPage schema (inc/faq.php:20, gwill_output_faq_schema)
        is deliberately NOT gated — documented in-file: it only fires for
        content built from the theme's OWN .gwill-faq block pattern, which
        RankMath's FAQ block never produces (different block, different class,
        different content path) — no duplicate FAQPage entities possible.
```

```
[CLEAN] Functional — double content / page builders
[ISSUE] none. Single post renders the_content() once; the theme's TOC is
        injected via the_content filter, not duplicate markup. No page-builder
        renders its own layout against this theme's templates in the stack.
        Embed facades (embed_oembed_html + render_block) REPLACE the oEmbed
        iframe with a click-to-play button — if a lazy-load plugin also wraps
        embeds, the facade path wins first (priority 10) and the plugin's
        wrapper never sees a live iframe; no double render.
```

```
[CLEAN] Functional — menu/walker conflicts
[ISSUE] none. Theme ships no custom walker; no plugin in the stack touches
        menus with a conflicting walker.
```

```
[CLEAN] Functional — widget ID clashes
[ISSUE] none. The theme registers zero widget areas/sidebars (setup.php:34-35
        documents the deliberate omission); no plugin registers a conflicting
        widget against a theme area.
```

```
[CLEAN] Functional — form double-submission/validation
[ISSUE] none. Theme owns its forms end-to-end: single wp_ajax_gwill_contact_form
        handler (auth + nopriv), one gwill-forms script, one status area per
        form, nonces via /gwill/v1/form-nonce. The rest_authentication_errors
        filter (nonce.php:99) only acts when $result is null (no other plugin
        decided — "honour it") and only for the theme's own route. SMTP hooks
        (phpmailer_init / wp_mail_from / wp_mail_from_name) are no-ops unless
        GWILL_SMTP_HOST is defined — a site using WP Mail SMTP/FluentSMTP never
        defines it, so no double SMTP configuration is possible.
```

```
[CLEAN] Functional — WooCommerce compatibility
[FILE] inc/woocommerce.php
[ISSUE] none. Every hook is wrapped in class_exists('WooCommerce'); on a
        non-WC site the file registers nothing (zero runtime cost, documented).
        add_theme_support declarations match WC's own gallery expectations;
        wrapper removal is the documented correct adaptation (theme's
        header/footer own the wrapper); gwill-woocommerce stylesheet is
        enqueued only when WC is active and depends on gwill-style.
```

---

## SECTION 7: DIAGNOSTIC PROTOCOL

1. **Isolation (binary search):** on any future build from this starter —
   `wp plugin deactivate <candidate>` → test → re-activate, one by one. Isolate
   the theme by activating twentytwentyfour on a STAGING clone, never live.
2. **Theme-vs-plugin vs plugin-vs-plugin direction:** if the fault persists
   with all plugins off, it is theme/core; if it appears only with plugin A+B
   both on, it is A-vs-B; if it needs the theme + one plugin, it is
   theme-vs-plugin. Reproduce per pair to identify.
3. **First-fatal extraction:** enable WP_DEBUG + WP_DEBUG_LOG on staging only
   (`define('WP_DEBUG', true); define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);`), reproduce, then read the FIRST fatal in
   wp-content/debug.log (the halting fatal is the first, not the last). Cross-
   check `tail -50 /var/log/php*-fpm.log` for pre-WP fatals.
4. **Safe testing:** never on live. Clone to staging with the same plugin set,
   or `wp maintenance-mode activate` + a coming-soon page on a test install.
   All fixes here verified: `php -l` (all touched PHP), `node --check` (all JS),
   surgical-diff review (only intended guard lines added).
5. **Logged-in vs logged-out:** the only auth-gated behaviours in this theme
   are the ?author=N enumeration block (all users), the REST nonce route
   (guests fetch one, editors get one), and admin-only outputs none. No
   conflict findings are auth-dependent.

---

## Summary

### Counts by severity
| Severity | Count |
|---|---|
| CRITICAL | 0 |
| SHOULD | **4 (all fixed in v1.3.1)** |
| NICE-TO-HAVE | 0 (3 observations documented as KNOWN-DELIBERATE — see below) |

### Counts by conflict type
| Type | Findings |
|---|---|
| PHP | 4 SHOULD (all fixed — sitemap trio + front-page title) + 5 CLEAN |
| CSS | 5 CLEAN |
| JS | 5 CLEAN |
| DB | 5 CLEAN |
| Hook | 5 CLEAN |
| Functional | 6 CLEAN |

### The single most dangerous conflict
**None active today.** The most dangerous *latent* conflict was the theme-owned
sitemap route: with AIOSEO or The SEO Framework installed, the theme's
`^sitemap\.xml/?$` rewrite would hijack the plugin's main sitemap URL — two
sitemap authors, wrong XML served, crawl confusion. **Fixed in v1.3.1** with
the `gwill_seo_plugin_active()` guard on the rewrite, the template swap, and
the canonical suppression (the finance v1.0.201 lesson, now baked into the
base theme). The second latent conflict — the front-page `<title>` filter
overriding RankMath's homepage title — is also **fixed in v1.3.1**. All ten
theme-owned SEO outputs now defer to SEO plugins.

### Prioritized fix order
1. Sitemap rewrite guard (done) — the hijack risk, first.
2. Sitemap template + canonical guards (done) — same surface, defense in depth.
3. Front-page title guard (done) — RankMath homepage-title override.
4. Everything else: **CLEAN** — no further action.

### Quick wins (<5 minutes each)
- `gwill_seo_plugin_active()` guard on sitemap rewrite — **done** (v1.3.1).
- Guard on sitemap template + canonical — **done** (v1.3.1).
- Guard on front_page_title — **done** (v1.3.1).
- Nothing else required; the remaining items are documented observations, not fixes.

### Known-deliberate observations (no code change)
- **gwill-search-dropdown enqueued unconditionally** — markup lives inline in
  header.php; the JS no-ops safely if a build swaps the header search
  (guard at search-dropdown.js:102). A build replacing the header search
  should `wp_dequeue_script('gwill-search-dropdown')` for zero bytes.
- **FAQPage schema not SEO-plugin-gated** — in-file documented: fires only for
  the theme's own `.gwill-faq` block pattern; RankMath's FAQ block is a
  different class, so no duplicate schema is possible.
- **SMTP hooks not plugin-gated** — opt-in by `GWILL_SMTP_HOST` define;
  a site with an SMTP plugin never defines it, so no double configuration.

---

## FIXES-APPLIED LEDGER (v1.3.1)

| # | Finding | File | Change | Verification |
|---|---|---|---|---|
| 1 | Sitemap rewrite hijack risk | inc/sitemap.php:35 | `if ( gwill_seo_plugin_active() ) return;` at top of `gwill_sitemap_rewrite()` | php -l clean; diff = only intended lines; 3 guards present |
| 2 | Sitemap template swap risk | inc/sitemap.php:65 | Same guard at top of `gwill_sitemap_template()` | php -l clean; diff surgical |
| 3 | Sitemap canonical suppression risk | inc/sitemap.php:90 | Same guard at top of `gwill_sitemap_canonical()` | php -l clean; diff surgical |
| 4 | RankMath homepage-title override | inc/seo.php:40 | `if ( gwill_seo_plugin_active() ) return $title;` at top of `gwill_front_page_title()` | php -l clean; diff surgical; seo.php guard count now 8 |

**Changed-file count: 2** (`inc/sitemap.php`, `inc/seo.php`). With no SEO
plugin active every code path is byte-identical to v1.3.0 — the guards only
engage when a major SEO plugin is installed, aligning with the theme's
documented deferral design. Version bumped to **1.3.1** (cache-buster).

---
*Audit complete. All sections CLEAN except the 4 SHOULD findings, all fixed and verified.*
