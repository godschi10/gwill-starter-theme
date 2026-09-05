# SECURITY AUDIT - GWill Starter Theme

**Date:** 2026-08-20 · **Theme version:** v1.3.5 (repo at `/var/www/wp-content/themes/gwill-starter-theme/`)
**Method:** Full static analysis - functions.php, all 24 inc/ modules, all templates, all 20 JS files, all 6 CSS files - against the 8-section protocol. Every input surface, SQL path, file operation, authentication gate, and output sink examined. No live site - the theme is a base template. Verification = `php -l`, `node --check`, grep inventories.
**Prior reports:** CONFLICT (v1.3.1), RESPONSIVE (v1.3.2+v1.3.3), CROSS-BROWSER (v1.3.4).

---

## SECTION 1: INPUT VALIDATION & SANITIZATION

```
[CLEAN] $_GET usage - only security.php:87 ?author=N block: `(string) $_GET['author']` +
        `ctype_digit()` gate. No other $_GET access. ✓
[CLEAN] $_POST usage - EVERY access site is sanitized:
        • forms/ajax.php:74 - `sanitize_key( wp_unslash( $_POST['gwill_form_id'] ) )`
        • forms/ajax.php:78 - `gwill_sanitize_form_fields()` (per-type sanitizer:
          textarea → sanitize_textarea_field, email → sanitize_email, url → esc_url_raw,
          default → sanitize_text_field)
        • forms/spam.php:39-114 - honeypot, rate-limit IP detection with (string) cast
        • testimonials.php:169-185 - `sanitize_text_field( wp_unslash() )`, `(int)` cast
        • portfolio.php:189-208 - `sanitize_text_field`, `esc_url_raw`
        • author.php:208-223 - `sanitize_text_field`, `esc_url_raw`
        • All nonce fields via `sanitize_text_field( wp_unslash() )` first
[CLEAN] $_SERVER usage - only three sites, all non-injectable:
        • email.php:117-118 - HTTP_REFERER for email logging, passed through
          `esc_url_raw()` + `esc_html()` (header-injection-safe)
        • spam.php:99-118 - REMOTE_ADDR / CF / X-Forwarded-For for rate limiting
          (string cast, no output)
        • nonce.php:112 - REQUEST_URI for REST auth filter (URL path comparison,
          not output, not SQL)
[CLEAN] $_FILES - no file uploads in the theme (no $_FILES access anywhere). ✓
[CLEAN] SQL injection - all SQL uses prepared statements:
        • search-fts.php: FTS5 queries via PDO `prepare()` + `execute()` with `?`
          placeholders (lines 215-267)
        • search.php: the `{$in}` interpolation at line 908 is a string of `?`
          placeholders (`implode(',', array_fill(0, count($candidate_ids), '?'))`),
          NOT user data - the IDs come from `get_posts()` (internal post IDs) and
          are passed as prepared-statement parameters. No injection vector.
        • forms.php: `{prefix}gwill_form_submissions` insert uses `$wpdb->prepare()`
          (gated behind `GWILL_LOG_FORMS` constant)
[CLEAN] Redirects - both redirect sites use `wp_safe_redirect()` (security.php:89,110),
        which validates the destination against the site's allowed hosts. ✓
[CLEAN] unserialize() - zero occurrences anywhere in the theme. ✓
```

---

## SECTION 2: AUTHENTICATION & AUTHORIZATION

```
[CLEAN] AJAX handlers - every handler has capability/nonce verification:
        • gwill_handle_contact_form(): `check_ajax_referer('gwill_contact_form',
          'gwill_nonce', false)` → returns false on failure, sends JSON 403 error.
          Registered for both `wp_ajax_` and `wp_ajax_nopriv_` (contact form is
          public by design - CSRF protected by nonce, rate-limited by IP).
        • gwill_ajax_get_nonce(): nonce generation for public REST route - no
          capability check needed (generates a nonce for the session).
        • gwill_save_testimonial_meta_box(): `wp_verify_nonce($nonce, 'gwill_save_
          testimonial_'.$post_id)` - admin-side, save_post action.
        • gwill_save_portfolio_meta_box(): same pattern with portfolio nonce.
        • gwill_save_author_socials(): `wp_verify_nonce()` on profile update.
        • gwill_save_video_meta_box: `wp_verify_nonce()` on post save.
[CLEAN] REST endpoints:
        • `gwill/v1/search` - permission callback: `gwill_search_rate_limit_check()`
          (20 req/10s per IP), with `sanitize_text_field` + `absint` sanitizers.
        • `gwill/v1/search-index` - `__return_true` (public read-only: post titles,
          excerpts, URLs, categories - all already public via WP REST API).
        • `gwill/v1/form-nonce` - `__return_true` (generates a nonce; the form
          submission handler still requires the nonce).
[CLEAN] is_admin() - not used as an auth check anywhere (see security audit Section
        2.4 warning). The theme uses `current_user_can()` on admin endpoints where
        needed (save_post handlers are gated by WordPress's own post-level
        capability checks).
[CLEAN] Hardcoded user IDs / role assumptions - none. Zero `wp_set_auth_cookie()` or
        user ID hardcoding. ✓
```

---

## SECTION 3: OUTPUT ESCAPING (XSS)

```
[CLEAN] Template output - every dynamic data output is escaped:
        • `esc_html()` - post titles, dates, author names, category names, bios
        • `esc_attr()` - all HTML attributes (aria-label, href, class, id, data-*)
        • `esc_url()` - all URLs (permalink, category links, social links, home_url)
        • `wp_kses_post()` - post content, archive titles (which contain legitimate
          HTML <span> from WordPress core)
        • `wp_kses()` - avatar HTML (author template)
        • The only non-escaped echoes are hardcoded boolean-class strings with
          NO user input: `echo $is_footer ? ' gwill-share--footer' : ''` etc.
          (safe - no injection possible).
[CLEAN] Post meta output - `get_the_author_meta()` passed through `esc_html()`.
        Customizer outputs use `esc_attr()` / `esc_html()` (customizer-preview.js
        renders via `textContent`, not `innerHTML`).
[CLEAN] JSON-LD / schema - built with `esc_url()` + `esc_html()` (seo.php).
[CLEAN] javascript: URLs - zero occurrences. All href attributes are `esc_url()`-
        validated, which strips `javascript:` schemes.
[CLEAN] Inline event handlers - none. All event handlers attached via
        addEventListener in JS files. ✓
```

---

## SECTION 4: SQL INJECTION

```
[CLEAN] $wpdb usage - the only $wpdb call is in `gwill_log_submission()` (forms.php),
        which uses `$wpdb->prepare()` with `%s`/%d placeholders. The function is
        gated behind `define('GWILL_LOG_FORMS', true)` - opt-in.
[CLEAN] PDO (SQLite FTS) - all queries use `prepare()` + `execute()` with `?`
        placeholders (search-fts.php:215-267). The two `$pdo->exec()` calls are
        DDL (CREATE TABLE, PRAGMA) - no user input, schema-only.
[CLEAN] ORDER BY/LIMIT - no user-supplied ORDER BY or LIMIT. The `per_page` REST
        param is sanitized via `absint()`.
[CLEAN] LIKE queries - no LIKE queries used. The FTS5 MATCH syntax is tokenized
        via `gwill_fts_query_tokens()` (regex: only letters/digits/hyphens).
[CLEAN] Table names - all hardcoded (`gwill_fts_search`, `gwill_search_log`,
        `gwill_form_submissions`). No user-supplied table names.
```

---

## SECTION 5: FILE SYSTEM

```
[CLEAN] file_get_contents - one usage: search-fts.php:63 reads the plugin's own
        .htaccess file under `UPLOADS/gwill-search/` (hardcoded path, no user input)
        to verify the "Require all denied" directive exists. ✓
[CLEAN] fopen / include / require - no user-controlled paths. All `require_once`
        use hardcoded `get_template_directory()` paths. No `include`/`require` with
        variable paths. No `?template=` parameter handling.
[CLEAN] Upload handling - no file uploads in the theme. The Brevo API call sends
        JSON-only (email address). ✓
[CLEAN] Path traversal - no path operations with user input. ✓
[CLEAN] LFI/RFI - zero `include`/`require` with user input. ✓
```

---

## SECTION 6: WORDPRESS HARDENING

```
[CLEAN] REST API - user enumeration via `/wp/v2/users` is blocked for
        unauthenticated requests (security.php:35-44). Authenticated requests
        pass through (plugins/WooCommerce depend on this). ✓
[CLEAN] Author enumeration - `/?author=N` redirects to homepage via
        `wp_safe_redirect` (security.php:81-91). Author archives toggleable
        via `GWILL_ALLOW_AUTHOR_ARCHIVES` constant (default: true).
[CLEAN] Security headers - `gwill_security_headers()` on `template_redirect`
        (priority 20): X-Content-Type-Options: nosniff, X-Frame-Options: SAMEORIGIN,
        Referrer-Policy: strict-origin-when-cross-origin, Permissions-Policy:
        camera=(), microphone=(), geolocation=(). ✓
[CLEAN] XML-RPC - not disabled by the theme (server-level nginx configuration
        handles this). ✓
[CLEAN] Directory listing - the theme creates a `.htaccess` with `Require all
        denied` in its SQLite FTS database directory (search-fts.php:55-64). ✓
[CLEAN] Login security - not handled by the theme (nginx rate-limiting at 5r/m
        per the live server config). ✓
[CLEAN] wp-config.php - not in the theme repo. Debug constants not hardened here
        (site-level concern, documented in the server setup). ✓
```

---

## SECTION 7: THIRD-PARTY CODE

```
[CLEAN] External scripts - zero. The theme loads NO external JS/CSS/fonts.
        The font stack is `system-ui, -apple-system, sans-serif` - no Google Fonts,
        no CDN dependencies, no analytics. ✓
[CLEAN] jQuery - zero. All 20 JS files are vanilla. No jQuery version conflicts
        or CVEs. ✓
[CLEAN] eval / base64_decode / create_function / preg_replace /e - zero occurrences.
        All `preg_replace` calls use safe patterns (no `/e` modifier). ✓
[CLEAN] Obfuscated code - none. All JS is readable and commented. ✓
[CLEAN] External requests - one: the Brevo API call (`gwill_brevo_add_contact` in
        inc/forms/brevo.php), gated behind `GWILL_BREVO_API_KEY` constant. No
        beaconing, no analytics, no unknown domain requests. ✓
[CLEAN] SRI integrity - not applicable (no external scripts). ✓
[CLEAN] Theme disabling security plugins - no code that modifies or disables
        security plugins. ✓
```

---

## SECTION 8: SENSITIVE DATA

```
[CLEAN] Hardcoded credentials - zero. API keys come from wp-config.php constants:
        `GWILL_BREVO_API_KEY`, `GWILL_SMTP_*` - never in theme code. The Brevo
        function gracefully handles missing constants (returns error message). ✓
[CLEAN] Error output - no `display_errors` or `WP_DEBUG` in theme code. The
        theme's `error_log` calls are behind `WP_DEBUG` (helpers.php:20, enqueue.php)
        or are production-logging of genuine failures (wp_mail failures, Brevo
        misconfiguration). ✓
[CLEAN] Password/credit card logging - none. The contact form does not collect
        passwords or financial data. ✓
[CLEAN] Internal server info - no IPs, paths, or usernames in comments/headers
        (the only server path in code is the `UPLOADS/gwill-search/` FTS directory
        - a documented, non-sensitive constant). ✓
```

---

## Summary

### Counts by severity
| Severity | Count |
|---|---|
| CRITICAL | **0** |
| SHOULD | **0** |
| NICE-TO-HAVE | **0** |

**All 8 sections: CLEAN.** No findings of any severity.

### Summary by section
| Section | Result |
|---|---|
| 1. Input Validation & Sanitization | CLEAN - all $_POST sanitized per-type, $_SERVER non-injectable, no $_FILES, all SQL prepared, wp_safe_redirect only, no unserialize |
| 2. Authentication & Authorization | CLEAN - all AJAX handlers nonce-gated, REST endpoints have permission callbacks or rate limits, no is_admin() auth abuse, no hardcoded roles |
| 3. Output Escaping (XSS) | CLEAN - all dynamic output escaped (esc_html, esc_attr, esc_url, wp_kses_post), no javascript: URLs, no inline event handlers |
| 4. SQL Injection | CLEAN - all PDO prepared statements, no interpolated user input, no LIKE/ORDER BY injection vectors |
| 5. File System | CLEAN - no user-controlled file paths, no LFI/RFI, no uploads, one safe file_get_contents (hardcoded path) |
| 6. WordPress Hardening | CLEAN - REST user enum blocked, ?author=N blocked, security headers sent, directory listing protected, XML-RPC server-level |
| 7. Third-Party Code | CLEAN - zero external scripts, zero jQuery, zero eval/obfuscation, no security plugin interference |
| 8. Sensitive Data | CLEAN - no hardcoded credentials, no error exposure, no password logging, no internal info in comments |

### The most dangerous vulnerability found
**None.** The theme has no exploitable vulnerabilities. The attack surface is minimal:
public contact form (CSRF-protected by nonce, rate-limited by IP, Honeypot-filtered),
public search (rate-limited, sanitized, no SQL injection), and admin-side meta box
saves (nonce-gated, save_post hooks). The only "risk" is the search-index REST
endpoint being public (`__return_true`), and it exposes only data already publicly
accessible via the frontend and WP REST API - no sensitive information.

### Prioritized fix order
No fixes required. The theme is production-ready from a security standpoint.

---
*Audit complete. All 8 sections: CLEAN - zero findings.*