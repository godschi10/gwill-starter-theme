# PORTABILITY AUDIT - GWill Starter Theme

**Date:** 2026-08-20 · **Theme version:** v1.3.10 (repo at `/var/www/wp-content/themes/gwill-starter-theme/`)
**Method:** Full static analysis - all inc/ modules, all templates, all JS/CSS - against the 7-section protocol (site migration/portability focus). Verification = `php -l`, grep inventories, POT regeneration via `wp i18n make-pot`.
**Prior reports:** 8 reports in `docs/` - this is the NINTH and final audit of the series (CONFLICT v1.3.1, RESPONSIVE v1.3.2+v1.3.3, CROSS-BROWSER v1.3.4, SECURITY v1.3.5, SPEED v1.3.6, CLEANUP v1.3.7, SEO v1.3.8, ACCESSIBILITY v1.3.9).

---

## SECTION 1: HARDCODED URLs

```
[CLEAN] Absolute URLs in PHP - all hits are legitimate and domain-independent:
        • helpers.php:112-115 - YouTube URL FORMAT examples in doc comments
        • helpers.php:517,527 / faq.php:179 - schema.org URLs (structured-data
          vocabulary, not site URLs)
        • spam.php:108 - Cloudflare docs URL in a comment
        • embed-facades.php:280,283 - i.ytimg.com / i.vimeocdn.com thumbnail
          endpoints (public keyless CDN URLs, identical on any domain)
        • footer.php credit default - https://gwillchijioke.com (the King's
          brand link, filterable via gwill_footer_credit - KNOWN-DELIBERATE)
[CLEAN] Asset references - 19× get_template_directory_uri() in enqueue.php;
        zero relative/absolute filesystem paths, zero wp-content hardcoding.
[CLEAN] admin-ajax.php - forms.js uses GwillForms.ajaxUrl (localized via
        wp_make_link_relative(admin_url('admin-ajax.php')) → '/wp-admin/
        admin-ajax.php'), resolved against the current origin - works on any
        domain or subdirectory (the in-file fallback only fires if the
        localize object is absent; documented).
[CLEAN] JS site URLs - search-modal.js home_url() reads GwillSearch.homeUrl
        from PHP home_url('/') (subdirectory-correct, documented); falls back
        to location.origin only in isolation.
[CLEAN] Uploads paths - the FTS SQLite DB lives under
        wp_upload_dir()/gwill-search/ (dynamic, not hardcoded). The only
        literal is the .htaccess check inside that same directory.
```

---

## SECTION 2: DATABASE & SERIALIZED DATA

```
[CLEAN] Serialized data - the theme never calls serialize() (transients use
        arrays which WP stores internally; no domain strings baked into
        serialized payloads). No JSON with domains in options.
[CLEAN] Option names - all gwill_-prefixed (gwill_rewrite_ver only); core
        options read-only. No collision with other themes/plugins (verified
        in the v1.3.1 conflict audit).
[CLEAN] Uninstall cleanup - the theme creates no options at activation
        (gwill_rewrite_ver is written lazily on version change), no CPT
        rewrites flushed until needed. No orphaned wp_options on deactivate.
[CLEAN] Table prefixes - no hardcoded wp_ prefix: the forms log uses
        $wpdb->prefix, the FTS tables live in a dedicated SQLite file under
        uploads (prefix-independent). Zero hardcoded table names with wp_.
[CLEAN] Direct SQL - all PDO prepared statements (search-fts.php) + one
        $wpdb->prepare (forms log, opt-in). No raw SQL with hardcoded
        prefixes.
```

---

## SECTION 3: DEPENDENCIES & ENVIRONMENT

```
[SHOULD] [theme header] - style.css lacked Requires at least: + Requires PHP:
[ISSUE] The theme header declared no minimum WP or PHP version. The code
        requires WP 6.4+ (security.php documents "WP 6.4+ (our minimum)";
        rsd/wlw removal assumption) and PHP 8.0+ (union types, str_starts_with,
        typed properties, match). Without headers, WordPress installs the
        theme on incompatible stacks and fails at runtime.
[FIX] Added `Requires at least: 6.4` and `Requires PHP: 8.0` to style.css.
      (APPLIED - v1.3.10.)
```

```
[CLEAN] Plugin dependencies - none required. ACF/WooCommerce/vibe-comments
        are class_exists()-gated everywhere (woocommerce.php, enqueue.php
        vibe block, acf-helpers absent). Features degrade gracefully without
        plugins.
[CLEAN] Server paths - zero hardcoded absolute includes (all require_once use
        get_template_directory()). No ini_set/error_reporting/mail settings
        in theme code.
[CLEAN] Environment config - the staging banner is the only environment-
        aware feature, and it only renders on recognised staging host
        patterns (qzz.io/.local/staging./dev./test.) - never on live.
```

---

## SECTION 4: MIGRATION SAFETY

```
[CLEAN] Default options - the theme works with a default WordPress install
        (no reliance on custom wp_options). Menus fall back to the theme's
        own fallback renderer when no primary menu is assigned. Customizer
        defaults are sane (tagline on, sticky header on, staging banner on).
[CLEAN] DB writes on page load - none. The only option write is
        gwill_rewrite_ver (version-keyed, on theme switch/upgrade only).
        Transients all expire. No the_content DB writes.
[CLEAN] Core/.htaccess modifications - the theme creates a .htaccess ONLY
        inside its own uploads/gwill-search/ directory (FTS data protection),
        not the site root. No core file edits.
[CLEAN] Dev-mode code - the demo contact template is documented as a
        deliberate starter feature ("Set to Private before deploying"),
        not accidental debug residue. No API keys/test data seeding.
```

---

## SECTION 5: INTERNATIONALIZATION

```
[SHOULD] [i18n] - languages/gwill-starter.pot was STALE
[ISSUE] The POT file predated the v1.3.7 i18n fixes - "Leave this blank"
        (×11 honeypot labels), "Contact Form Demo", "Dev only.", and the
        demo description sentence were absent from the translation template.
[FIX] Regenerated via `wp i18n make-pot` (1503 lines; new strings present).
      (APPLIED - v1.3.10.)
```

```
[CLEAN] Hardcoded strings - zero user-facing hardcoded text after the v1.3.7
        cleanup (all esc_html_e/__). The only raw strings are the footer
        credit brand name (proper noun, filterable default).
[CLEAN] Text domain - 'gwill-starter' everywhere, matches style.css header
        and the POT filename. grep-verified.
[CLEAN] Date/number formatting - get_the_date()/get_the_modified_date()/the
        time functions use WP locale formatting. Reading time returns an int
        (no hardcoded locale strings). No hardcoded currency symbols.
[CLEAN] Locale strings - the only locale-sensitive text is the default
        footer credit "Built by G-will Chijioke" (English proper noun).
```

---

## SECTION 6: CHILD THEME COMPATIBILITY

```
[CLEAN] Extensibility - the theme is extension-through-hooks (the modern
        WordPress pattern used by Twenty Twenty-Four/Storefront): every
        gwill_* behaviour is filterable (gwill_footer_credit, gwill_show_
        breadcrumbs, gwill_hidden_slugs, gwill_front_page_tagline, gwill_
        seo_plugin_active, gwill_related_posts_args, gwill_back_to_top_
        percent, gwill_excerpt_length…) or action-hooked. Child themes
        override via hooks, not function_exists() wrappers - the sanctioned
        base-theme pattern (pluggable functions are the legacy parent-theme
        pattern). KNOWN-DELIBERATE, documented in README.
[CLEAN] get_template_part - used everywhere (gwill_part wrapper); zero
        include/require in templates. Child themes can override any part.
[CLEAN] Enqueue safety - all assets via wp_enqueue_*, handles are
        gwill-prefixed so child themes can dequeue/deregister. style.css uses
        get_template_directory_uri() (parent-safe) - never get_stylesheet_uri()
        for theme assets (documented in enqueue.php).
[CLEAN] Template hierarchy - standard WordPress hierarchy (no custom routing),
        child themes override naturally.
```

---

## SECTION 7: ASSET PORTABILITY

```
[CLEAN] CSS url() - the only url() in any stylesheet is an inline SVG data
        URI (select dropdown arrow, style.css:1485) - self-contained, no
        external path, survives any move.
[CLEAN] External CDN deps - zero. system-ui font stack (no Google Fonts, no
        CDN). The only external requests are the Cloudflare IP range fetch
        (cached 1-day, for rate-limit IP detection) and the Brevo API (form
        submission only) - both optional, graceful-degrading.
[CLEAN] Font files - none bundled, none referenced from other themes.
[CLEAN] Screenshot - screenshot.png exists in the theme root (referenced by
        the standard Theme Name header mechanism, not hardcoded).
```

---

## Summary

### Counts by severity
| Severity | Count |
|---|---|
| CRITICAL | 0 |
| SHOULD | **2 (both fixed - Requires headers + POT regeneration)** |
| NICE-TO-HAVE | 0 (1 observation: embed-facades.php:193 plural-placeholder ordering warning from the POT generator - cosmetic, i18n tooling advisory) |

### The single biggest migration risk
**None material.** The theme's URL handling is exemplary (relative admin-ajax, localized home/rest URLs, subdirectory-aware JS). The two findings were both standards-compliance gaps (missing version headers, stale POT) - fixed.

### Prioritized fix order
1. Requires at least: + Requires PHP: headers - **done** (v1.3.10).
2. POT regeneration - **done** (v1.3.10).
3. Everything else: **CLEAN**.

### Quick wins
- Both fixes under 5 minutes each.

---

## FIXES-APPLIED LEDGER (v1.3.10)

| # | Finding | File | Change | Verification |
|---|---|---|---|---|
| 1 | No Requires headers | style.css:7-8 | Added `Requires at least: 6.4` + `Requires PHP: 8.0` | grep-verified both headers |
| 2 | Stale POT | languages/gwill-starter.pot | Regenerated via wp i18n make-pot (1503 lines) | grep-verified "Leave this blank" ×1, "Contact Form Demo" ×1, "Dev only." ×1, description ×1 |

**Changed-file count: 2** (`style.css`, `languages/gwill-starter.pot`). Version bumped to **1.3.10**.

---
*Audit complete. 2 SHOULD fixed; everything else CLEAN - and the full audit series is now complete.*