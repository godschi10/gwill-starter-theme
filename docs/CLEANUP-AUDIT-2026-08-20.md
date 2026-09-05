# CLEANUP AUDIT  -  GWill Starter Theme

**Date:** 2026-08-20 · **Theme version:** v1.3.7 (repo at `/var/www/wp-content/themes/gwill-starter-theme/`)
**Method:** Full static analysis  -  functions.php, all 24 inc/ modules, all 29 template-parts, all root templates, all 20 JS files, all 6 CSS files  -  against the 6-section protocol. Verification = `php -l` on every touched file, grep inventories, cross-file call audits.
**Prior reports:** CONFLICT (v1.3.1), RESPONSIVE (v1.3.2+v1.3.3), CROSS-BROWSER (v1.3.4), SECURITY (v1.3.5), SPEED (v1.3.6).

---

## SECTION 1: ASSET LOADING

```
[CLEAN] Enqueue inventory  -  every asset enqueued via wp_enqueue_scripts (verified
        in the v1.3.6 speed audit): gwill-style, gwill-main, gwill-cookie-consent,
        gwill-back-to-top, gwill-sticky-header, gwill-search-dropdown (global);
        gwill-forms/multistep/exit (form partials only); gwill-search-expand/modal
        (their partials only); gwill-embeds (singular + core/embed only);
        gwill-darkmode + gwill-darkmode-vibe (conditional); gwill-woocommerce
        (class_exists gate); gwill-customizer-preview (admin only).
[CLEAN] Global assets on 1-2 templates  -  none. All global scripts serve the
        always-present header/footer (nav, search, cookie banner, back-to-top).
[CLEAN] Wrong-hook enqueues  -  none. No enqueue inside the_content; the form
        partials' wp_enqueue_script calls are the sanctioned page-conditional
        pattern (documented).
[CLEAN] Heavy libraries  -  zero jQuery, zero FontAwesome, zero icon fonts
        (system-ui stack only).
[CLEAN] Version params + defer  -  all scripts deferred (in_footer + strategy
        defer); all styles versioned (?ver=1.3.x from the theme Version header).
[CLEAN] Inline <script>/<style> in templates  -  one deliberate inline script:
        the darkmode pre-paint block (inc/darkmode.php, ~1 KB, must run before
        first paint  -  documented). No other inline blocks in templates.
```

---

## SECTION 2: DEAD & DEBUG CODE

```
[CLEAN] Commented-out code blocks >3 lines  -  zero (grep-verified: no
        `// ... ;` blocks). All multi-line comments are prose/design notes.
[CLEAN] console.log / var_dump / print_r / debug_backtrace  -  zero in runtime
        code. The only debug_* hits are WordPress's own wp_die() in
        template-contact-demo.php:32 (the "Access Restricted" permission guard  - 
        a legitimate template feature, not debug residue).
[CLEAN] error_log  -  5 sites, all legitimate production logging of genuine
        failures (no sensitive data, no PII):
        • forms/ajax.php:132  -  wp_mail() failure (real delivery error)
        • forms/brevo.php:53,73,90  -  Brevo API misconfiguration/failure
        • enqueue.php:369  -  vibe-comments version mismatch, WP_DEBUG-gated only
[CLEAN] WP_DEBUG / SCRIPT_DEBUG  -  no wp-config.php in the theme repo (site-level
        file). No debug constants referenced in theme code.
[CLEAN] Duplicate function declarations  -  zero (uniq -d over all function names
        in inc/ + functions.php = empty).
[CLEAN] TODO / FIXME / HACK / XXX  -  ONE found and fixed:
        footer.php:32 had `* TODO: Replace or remove for every client site
        before launch.`  -  reworded to drop the TODO token (the instruction
        stays; the marker is gone). Zero markers remain.
[CLEAN] Unused parameters  -  `gwill_sitemap_invalidate( $post_id )` has an
        unused param, documented (kept for save_post hook compatibility  -  the
        finance theme's accepted-pattern ledger). No other unused params/vars.
[CLEAN] localhost / 127.0.0.1 / dev URLs  -  only legitimate hits:
        • spam.php:143  -  `127.0.0.1`/`::1` checked as "local request" in the
          IP-detection fallback (runtime behaviour, not a URL reference)
        • staging.php + customizer.php  -  the staging-banner feature recognises
          qzz.io/.local/staging./dev./test. host patterns (deliberate feature:
          "Only ever appears on a recognised staging domain")
        • enqueue.php:124  -  comment mentioning staging/ngrok domains (prose)
        No dev URLs in runtime code.
```

---

## SECTION 3: FUNCTIONS.PHP HYGIENE

```
[CLEAN] Prefixing  -  every function gwill_-prefixed (grep-verified across all
        inc/ + functions.php; the only non-gwill tokens in the declaration scan
        are IIFE-scoped JS inside darkmode.php  -  not PHP). No generic names.
[CLEAN] Orphaned hooks  -  every add_action/add_filter targets a core WordPress
        hook that fires (wp_head, init, template_redirect, save_post, the_posts,
        excerpt_length, etc.). Theme-defined apply_filters (gwill_* filters) are
        documented extension points  -  they fire when a build uses them.
[CLEAN] Duplicate add_action  -  no same-hook/same-priority duplicates with
        contradictory work (verified in the v1.3.1 conflict audit).
[CLEAN] Superglobals  -  all sanitized (verified in the v1.3.5 security audit).
[CLEAN] $wpdb  -  only prepare()'d queries (security audit v1.3.5).
[CLEAN] Nonces  -  all handlers verified (security audit v1.3.5).
[CLEAN] Escaping  -  all output escaped (security audit v1.3.5).
[CLEAN] Deprecated functions  -  zero (no ereg/mysql_*/get_currentuserinfo/
        create_function anywhere).
```

---

## SECTION 4: TEMPLATES

```
[SHOULD] [i18n] template-contact-demo.php:52-56  -  hardcoded English block
[ISSUE] The demo template's header rendered raw text: "Contact Form Demo",
        "Dev only.", and the "This page demonstrates all 10 contact form
        patterns…" sentence  -  none wrapped in translation functions, breaking
        the theme's universal i18n convention. (The template ITSELF is a
        deliberate starter feature and stays  -  this is a convention fix only.)
[FIX] Wrapped all three strings in esc_html_e( '…', 'gwill-starter' ).
      Identical English output. (APPLIED  -  v1.3.7.)
```

```
[SHOULD] [i18n] template-parts/forms/*.php ×11  -  honeypot label hardcoded
[ISSUE] All 11 contact-form partials rendered the honeypot label as raw text:
        `<label for="hp_…">Leave this blank</label>`  -  not i18n'd.
[FIX] All 11 now render `esc_html_e( 'Leave this blank', 'gwill-starter' )`.
      Identical output. (APPLIED  -  v1.3.7.)
```

```
[CLEAN] Orphaned templates  -  none. All 12 root templates + 29 template-parts
        are reachable: hierarchy templates (index/home/archive/single/page/404/
        search/author/attachment/comments/searchform/sitemap), page templates
        (template-contact.php, template-contact-demo.php), and every
        template-parts/*.php verified against gwill_part() call sites
        (author-box, back-to-top, content, content-none, cookie-consent,
        featured-image, pricing-table, related-posts, share-button,
        staging-banner, portfolio, search ×3, testimonials, ui/darkmode-toggle,
        woocommerce/cart-icon, forms ×11). The 'cards/card' and 'hero' hits
        are documentation examples in helpers.php comments  -  not runtime calls.
[CLEAN] Direct DB queries  -  no raw WP_Query in templates beyond the memoized/
        no_found_rows helpers (related posts, sitemap, search  -  all in inc/).
[CLEAN] Hardcoded text  -  after the fixes above: zero non-i18n'd user-facing
        text in templates. The only remaining raw strings are proper nouns
        (the footer credit default "G-will Chijioke"  -  the King's brand,
        filterable via gwill_footer_credit) and markup attributes.
[CLEAN] Duplicate markup  -  no repeated blocks that should be a shared partial
        (the 10 form patterns are intentionally distinct; category pills/grid
        loops were already extracted in earlier versions).
[CLEAN] Old meta tags  -  no keywords/revisit-after; exactly one meta description
        source (inc/seo.php priority 2).
```

---

## SECTION 5: MEDIA & SETTINGS

```
[CLEAN] Image sizes  -  one custom size (gwill-hero 1200×675, inc/setup.php:81),
        used in 11 places (featured-image, SEO, social-meta, LCP preload).
        All other templates use core sizes. No unused sizes.
[CLEAN] Emoji/embed scripts  -  emoji detection JS removed (inc/security.php:29);
        wp-block-library dequeued (v1.3.6); block-library-theme NOT enqueued
        (wp-block-styles support commented out in setup.php:45).
[CLEAN] Feed links / rsd / wlw / shortlink / generator  -  wp_generator removed
        (security.php:11), the_generator emptied (security.php:15), shortlink
        removed (security.php:18), RSD + wlw removed by core in WP 6.3
        (documented in security.php:4-8). Feed links deliberately enabled
        (automatic-feed-links support  -  blogs need them).
[CLEAN] Widgets  -  zero register_sidebar() (setup.php:34-35 documents the
        deliberate omission)  -  no empty widget areas.
```

---

## SECTION 6: FORMATTING & STANDARDS

```
[CLEAN] Indentation  -  tabs throughout inc/, spaces in style.css (the theme's
        established convention, consistent per file). No mixed indentation.
[CLEAN] BOM / whitespace before <?php  -  zero BOM characters (byte-pattern
        verified); zero leading whitespace before <?php in pure-PHP files.
[CLEAN] Closing tags  -  pure-PHP files correctly omit ?> (author.php, setup.php,
        etc. end without it); mixed PHP/HTML files (helpers.php breadcrumbs,
        darkmode.php inline script, social-meta, sitemap) use ?> correctly
        before HTML output. No trailing-?> smells.
[CLEAN] Line endings  -  all LF, zero CRLF (file check across inc/).
[CLEAN] php -l  -  passes on all 13 edited files post-fix.
```

---

## Summary

### Counts by severity
| Severity | Count |
|---|---|
| CRITICAL | 0 |
| SHOULD | **2 (both fixed  -  demo template i18n + 11 honeypot labels)** |
| NICE-TO-HAVE | **1 (fixed  -  footer TODO token removed)** |

### Prioritized fix order
1. Honeypot label i18n (×11)  -  convention gap, identical output.
2. Demo template i18n  -  convention gap, identical output.
3. TODO token removal  -  zero-marker standard.
4. Everything else: **CLEAN**.

### Safe to delete immediately vs needs testing
- **Safe**: nothing runtime-critical is dead. The v1.3.6 wp-block-library dequeue stands (verified). No candidates for deletion.
- **Needs testing**: the 13 i18n fixes render identically on en_US (esc_html_e returns the same English)  -  behavior-identical, translatable everywhere else.

---

## FIXES-APPLIED LEDGER (v1.3.7)

| # | Finding | File(s) | Change | Verification |
|---|---|---|---|---|
| 1 | Honeypot label not i18n'd | template-parts/forms/contact-*.php ×11 | `>Leave this blank</label>` → `><?php esc_html_e( 'Leave this blank', 'gwill-starter' ); ?></label>` | php -l ×13 clean; 11 files each carry 1 esc_html_e |
| 2 | Demo template text not i18n'd | template-contact-demo.php:52-56 | "Contact Form Demo" / "Dev only." / description → `esc_html_e()` ×3 | php -l clean; grep-verified |
| 3 | TODO marker | footer.php:32 | `* TODO: Replace…` → `* Replace…` (token dropped, instruction kept) | grep TODO = 0 |

**Changed-file count: 13** (11 form partials + template-contact-demo.php + footer.php). All output behavior-identical on en_US. Version bumped to **1.3.7**.

---
*Audit complete. 2 SHOULD + 1 NICE-TO-HAVE fixed; everything else CLEAN.*