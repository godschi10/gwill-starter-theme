# GWill Starter Theme

A clean, custom WordPress starter theme built from scratch. No parent theme. No opinions you didn't write. Every line is yours.

> **Note on this document:** an earlier version of this README described the theme as it existed many versions ago — a 5-file `inc/` directory, a FormSubmit.co-based contact form, a 2-control Customizer. None of that has been true for a long time; the code moved forward across 50 versions and this file didn't. It has been rewritten from scratch against the actual v1.0.50 codebase, verified file-by-file rather than carried forward from memory. See `CHANGELOG.md` for the version-by-version history of how it got here.

---

## Getting Started — New Project Setup

### 1. Install

Upload the deploy zip via **Appearance → Themes → Add New → Upload**, or extract and FTP to `wp-content/themes/gwill-starter-theme/`. Activate.

### 2. wp-config.php constants

Add these above the `/* That's all, stop editing! */` line. Only `GWILL_TO_EMAIL` is required — everything else has a safe default.

```php
// ── GWill Starter ────────────────────────────────────────────────────────────

// Required: email address that receives all contact form submissions.
define( 'GWILL_TO_EMAIL', 'you@yourdomain.com' );

// Optional: SMTP relay for wp_mail(). Without these, wp_mail() uses the
// server's own mail() function, which many hosts throttle or block outright.
// Get free credentials from Brevo (300 emails/day): https://app.brevo.com
// ⚠ Use the SMTP key from Account → SMTP & API → SMTP tab — NOT the API key
//   from the API keys tab. They are different credentials.
define( 'GWILL_SMTP_HOST',  'smtp-relay.brevo.com' );
define( 'GWILL_SMTP_PORT',  587 );
define( 'GWILL_SMTP_USER',  'xxxxxxxx@smtp-brevo.com' );
define( 'GWILL_SMTP_PASS',  'xsmtp-xxxxxxxxxxxxxxxx' );
define( 'GWILL_FROM_EMAIL', 'hello@clientdomain.com' );
define( 'GWILL_FROM_NAME',  'Site Name' );

// Optional feature flags (default false unless noted):
define( 'GWILL_AUTOREPLY', true );  // Send a confirmation email back to the form submitter.
define( 'GWILL_LOG_FORMS', true );  // Log every submission to a custom DB table (inc/forms.php).

// Optional, default true: author archive pages (/author/slug/). Set false
// to disable them site-wide and redirect to the homepage instead.
define( 'GWILL_ALLOW_AUTHOR_ARCHIVES', true );

// Optional, default false: trust the CF-Connecting-IP / X-Forwarded-For
// headers for rate-limiting the contact form. Only enable this if your
// origin server is firewalled to accept connections ONLY from Cloudflare's
// published IP ranges — otherwise these headers are attacker-spoofable and
// this setting would let someone bypass the rate limit entirely. Default
// (false) uses REMOTE_ADDR, which can't be spoofed but is coarser behind
// any CDN. See inc/forms.php → gwill_get_client_ip() for the full reasoning.
define( 'GWILL_TRUST_PROXY_HEADERS', false );
```

### 3. Create the contact page

Pages → Add New → Title: "Contact" → Template: **Contact** → Publish.

Defaults to the simple 3-field form. To use a different form pattern, set the `gwill_form_type` post meta on the page:

```php
update_post_meta( $page_id, 'gwill_form_type', 'inquiry' );
```

Accepted values for a standalone Contact page: `simple` | `inquiry` | `routed` | `multistep` | `application` | `partnership`. (Four more patterns — `inline`, `sidebar`, `exit_intent`, `post-feedback` — exist as embeddable/overlay/micro-interaction forms, not standalone pages; see [The Contact Form System](#the-contact-form-system) below.)

Submission goes through `admin-ajax.php` via `wp_mail()` — there is no third-party form-delivery service involved.

### 4. Test every form pattern at once

Visit a page using the **Contact Demo (Dev Only)** template (`template-contact-demo.php`) while logged in with at least `edit_posts` capability — it's hard-gated and returns a 403 for anyone without that capability, regardless of the page's own WordPress visibility setting. It renders all 10 form patterns on one page for testing. Logged-in users (capability ≥ `edit_posts`) are also exempt from the contact-form rate limiter specifically so this doesn't block its own testing — see `gwill_form_rate_limited()` in `inc/forms.php`.

### 5. If a submission fails

Open the browser console — `assets/js/forms.js` logs the real underlying error there regardless of which simplified message it shows the visitor (a nonce failure, a rate-limit rejection, a true network failure, and an unparseable server response all produce different console output). For SMTP specifically, a failed `wp_mail()` call is captured via the `wp_mail_failed` action and logged with `error_log()` when `WP_DEBUG` is on.

### 6. Footer credit

Defaults to "Built by G-will Chijioke". For client sites:

```php
// Remove entirely:
add_filter( 'gwill_footer_credit', '__return_empty_string' );
// Replace:
add_filter( 'gwill_footer_credit', fn() => ' — Built by Your Studio' );
```

### 7. Type-router form recipient mapping

The `routed` form pattern sends to different addresses based on inquiry type:

```php
add_filter( 'gwill_form_routing_map', function ( $map ) {
	$map['press']   = 'press@clientsite.com';
	$map['support'] = 'support@clientsite.com';
	return $map;
} );
```

### 8. Dark mode, sticky header, and other on-by-default features

Dark mode, the sticky header, the cookie consent banner, and the back-to-top button are all on by default and need no setup. Sticky header can be turned off in Appearance → Customize → Header Options. See [Tier 1 Features](#tier-1-features) below for what each one actually does and why it's built the way it is.

---

## Table of Contents

1. [Philosophy](#philosophy)
2. [Requirements](#requirements)
3. [File Structure](#file-structure)
4. [File Reference — inc/](#file-reference--inc)
5. [File Reference — Templates](#file-reference--templates)
6. [The Contact Form System](#the-contact-form-system)
7. [Tier 1 Features](#tier-1-features)
8. [Customizer Controls](#customizer-controls)
9. [CSS Standards](#css-standards)
10. [PHP Standards](#php-standards)
11. [Security Standards](#security-standards)
12. [Accessibility Standards](#accessibility-standards)
13. [What Is Intentionally Absent](#what-is-intentionally-absent)
14. [Changelog](CHANGELOG.md)

---

## Philosophy

This theme is a **starting surface, not a finished product**. It contains what every project needs, not what any one project assumes.

The test for inclusion: *will every build that starts from this theme need this?* If yes, it belongs. If it depends on the project, it doesn't. This is also the exact filter used to decide what's in core (Tier 1/2 of the feature roadmap) versus what should be an opt-in module per project (Tier 3) — see `GWILL-FEATURE-ROADMAP.md`.

- Zero CSS opinions beyond reset, design tokens, and structural baseline
- Every `remove_action` call has a documented reason
- Every file is self-contained — no silent dependencies
- Gutenberg is constrained, not embraced — `theme.json` kills its default palette and font-size controls; this theme's own design tokens replace them

---

## Requirements

- WordPress 6.4 or higher
- PHP 8.1 or higher
- No plugins required to function. Optional integrations (RankMath, Yoast, AIOSEO, SEOPress, The SEO Framework) are auto-detected and deferred to when present — see `gwill_seo_plugin_active()` in `inc/helpers.php`.

---

## File Structure

```
gwill-starter-theme/
├── .editorconfig                Consistent indentation/line-ending rules across editors
├── .gitignore                   Excludes OS files, node_modules, compiled .mo files
├── phpcs.xml                    WordPress Coding Standards ruleset (run: vendor/bin/phpcs)
├── composer.json                Dev dependencies: PHPCS, WPCS, PHPCompatibilityWP
├── style.css                    Theme header + all frontend CSS + design tokens
├── functions.php                Loader only — twelve require_once lines, no logic
├── theme.json                   Gutenberg configuration — kills default palette/font sizes
├── screenshot.png               Theme preview shown in Appearance → Themes
├── README.md / CHANGELOG.md / EMAIL-SETUP.md
├── GWILL-FEATURE-ROADMAP.md     Tiered plan for features beyond what's currently built
│
├── header.php                   <head>, site header, dark-mode flash-prevention script
├── footer.php                   Footer credit, back-to-top, cookie consent, wp_footer()
├── comments.php                 Comment thread template (WP core markup + this theme's CSS)
├── searchform.php                Fallback markup for bare get_search_form() calls (e.g. 404.php)
├── sidebar.php                  Template-hierarchy stub — no widget areas registered by default
│
├── index.php                    Last-resort fallback (breadcrumbs + numbered pagination)
├── home.php                     Blog index — used when a static front page is set
├── archive.php                  Category/tag/date/custom-taxonomy archives
├── author.php                   Author archive — hero block with bio + social links
├── search.php                   Search results
├── single.php                   Single post — categories, tags, reading time, related posts, author box
├── page.php                     Static page
├── 404.php
├── attachment.php               Redirects attachment URLs to the parent post
├── template-contact.php          "Contact" page template — 6 standalone-page-appropriate form patterns
├── template-contact-demo.php     "Contact Demo (Dev Only)" — all 10 patterns, edit_posts-gated
│
├── inc/
│   ├── setup.php                Theme supports, nav menus, content_width, image sizes, video meta box
│   ├── enqueue.php               Every wp_enqueue_style/script call, all version-stamped off style.css
│   ├── security.php              REST/XML-RPC/author-archive/login-error hardening
│   ├── helpers.php               gwill_part(), breadcrumbs, primary-category, reading time, SEO-plugin detection
│   ├── author.php                Social profile fields (admin profile screen + template helpers)
│   ├── customizer.php            Header Options + Site Identity Customizer controls
│   ├── darkmode.php               Fully inline flash-prevention script + critical CSS
│   ├── forms.php                  The 10-pattern contact form system — AJAX, nonces, rate limiting
│   ├── search.php                 REST search endpoint + results-count helper
│   ├── related-posts.php          Related-posts query (Tier 1)
│   ├── social-meta.php            Open Graph / Twitter Card fallback (Tier 1)
│   └── faq.php                    FAQ block pattern + FAQPage schema generator (Tier 1)
│
├── template-parts/
│   ├── content.php                Article card (index/archive/search listings)
│   ├── content-none.php           "No posts found" state
│   ├── featured-image.php         Hero image/video with explicit LCP priority attributes
│   ├── author-box.php             In-post author bio (bottom of single.php)
│   ├── related-posts.php          Compact related-posts grid (Tier 1)
│   ├── share-button.php           Social share links (top + footer modes)
│   ├── cookie-consent.php         Cookie notice banner (Tier 1)
│   ├── back-to-top.php            Back-to-top button (Tier 1)
│   ├── ui/darkmode-toggle.php     Dark mode toggle button
│   ├── search/                    3 search UI variants — expandable icon, modal, no-results state
│   └── forms/                    10 contact form patterns — see The Contact Form System below
│
├── assets/
│   ├── css/
│   │   ├── search.css             Search UI styles (all 3 variants)
│   │   ├── darkmode.css           Dark-mode token overrides — WP default comments included
│   │   └── darkmode-vibe-comments.css  Dark-mode overrides for the Vibe Comments plugin specifically
│   ├── js/
│   │   ├── main.js                 Mobile nav toggle
│   │   ├── forms.js                Shared AJAX submit handler for every .gwill-form
│   │   ├── form-multistep.js        Multi-step form's own step logic (shares forms.js's submit path)
│   │   ├── form-exit-intent.js      Exit-intent trigger + overlay (shares forms.js's submit path)
│   │   ├── search-expandable.js, search-modal.js
│   │   ├── customizer-preview.js    postMessage live-preview handlers (Customizer iframe only)
│   │   ├── cookie-consent.js, back-to-top.js, sticky-header.js   (Tier 1)
│   │   └── darkmode.js              @deprecated — superseded by inc/darkmode.php's inline script
│   └── images/
│
└── languages/
    └── gwill-starter.pot          Regenerate with WP-CLI before shipping a client build
```

---

## File Reference — inc/

### inc/setup.php

`add_theme_support()` declarations (`html5` with `comment-list`/`comment-form` enabled, `post-thumbnails`, `custom-logo`, `align-wide`, `responsive-embeds`, etc.), `register_nav_menus()` (`primary`, `footer`), `$content_width` (1200, matching `theme.json`'s `contentSize`), the `gwill-hero` custom image size (1200×675, soft crop — used for the single-post hero and reused as the Open Graph image to avoid generating a third near-duplicate file size), and the video meta box (lets an editor attach a YouTube URL to a post, rendered in the featured-image slot instead of the image when set).

### inc/enqueue.php

Every `wp_enqueue_style()`/`wp_enqueue_script()` call in the theme. All version arguments are `wp_get_theme( get_template() )->get( 'Version' )` — never hardcoded — so every asset cache-busts automatically on every release. Most scripts use `strategy => 'defer'`; the one deliberate exception is `inc/darkmode.php`'s script, which is inlined directly in `<head>` rather than enqueued at all, because LiteSpeed Cache's "Load JS Deferred" setting can delay *external* scripts until first user interaction on some devices — fine for a back-to-top button, not acceptable for a toggle that needs to work the instant it's clicked.

### inc/security.php

| Behaviour | Detail |
|---|---|
| Generator/version removed | From `<head>` and from RSS/Atom feed headers separately — removing one doesn't remove the other. |
| Emoji detection script removed | ~15 KB per page load; modern browsers render Unicode emoji natively. |
| XML-RPC disabled | Filtered off entirely. |
| `/wp-json/wp/v2/users` blocked | Only for unauthenticated requests — logged-in users, WooCommerce, ACF etc. still resolve normally. |
| `?author=N` blocked | Unconditional 301 — this numeric-enumeration vector is never a valid destination on its own, regardless of any other setting. |
| `/author/slug/` archive | **Enabled by default.** Disable via `GWILL_ALLOW_AUTHOR_ARCHIVES => false`, which 302s (not 301) to the homepage — deliberately not 301, since this is a toggleable setting, not a permanent URL move, and a 301 would have browsers caching the redirect well past any later change to the setting. |
| Login errors genericized | "Invalid username or password" regardless of which part was actually wrong — prevents username enumeration via login form. |

### inc/helpers.php

- **`gwill_part( string $slug, array $args = [] )`** — wrapper around `get_template_part()`, always prefixed with `template-parts/`. Use this, never call `get_template_part()` directly.
- **`gwill_get_primary_category( int $post_id = 0 )`** — the single source of truth for "which category is this post primarily about," honouring RankMath's/Yoast's primary-term meta when set. Used by breadcrumbs, the card view, the single-post view, and related posts — extracted in 1.0.50 after the same logic had been independently duplicated three times across earlier sessions.
- **`gwill_reading_time( int $post_id = 0 )`** — word count ÷ 200wpm (filterable via `gwill_reading_speed_wpm`), minimum 1 minute.
- **`gwill_breadcrumbs()`** — full BreadcrumbList Schema.org markup, every WordPress conditional tag covered, filterable off entirely via `gwill_show_breadcrumbs` for sites preferring an SEO plugin's own breadcrumbs.
- **`gwill_seo_plugin_active()`** — detects RankMath, Yoast, AIOSEO, SEOPress, The SEO Framework via each plugin's own version constant. Used as a guard before `inc/social-meta.php` outputs anything, to avoid duplicate OG/Twitter tags.
- **`gwill_youtube_id()`**, **`gwill_featured_image_alt()`**, **`gwill_featured_image_caption()`** — small, focused utilities for the featured-image/video system.

### inc/author.php

Registers 7 social-profile fields (X/Twitter, LinkedIn, GitHub, Instagram, Facebook, YouTube, plus WordPress core's own built-in Website field — not duplicated) on the user-profile screen, nonce- and capability-protected on save. `gwill_get_author_socials()` returns only the fields a given user actually filled in, for `author-box.php` and `author.php` to render.

### inc/customizer.php

See [Customizer Controls](#customizer-controls) below for the full table.

### inc/darkmode.php

Everything — theme detection, the toggle's click handler, ARIA sync, the OS-preference-change listener, and the critical `color-scheme`/`background-color` CSS — is inlined directly into `<head>`, not loaded from an external file. This isn't stylistic; an external script here previously caused a real, reproducible flash of the wrong theme on some Android/Chrome configurations because of how LiteSpeed Cache's deferred-JS setting interacts with external `<script>` tags. `assets/js/darkmode.js` still exists but is marked `@deprecated` and loaded by nothing — kept only for reference.

### inc/forms.php

See [The Contact Form System](#the-contact-form-system) below.

### inc/search.php

Registers `GET /wp-json/gwill/v1/search` (public, intentionally — it only returns published-post search data, the same as the native `?s=` query). Routes through `gwill_execute_search()`, which is filterable via `gwill_search_backend` for swapping in a third-party search service without touching any template. `gwill_search_results_count()` returns a pre-escaped string with the search term safely wrapped in `<strong>` — don't run it through `esc_html()` again at the call site, that would double-escape the tag into visible text.

### inc/related-posts.php, inc/social-meta.php, inc/faq.php

Tier 1 features — see below.

---

## File Reference — Templates

Templates follow WordPress's template hierarchy exactly as documented in the [Theme Handbook](https://developer.wordpress.org/themes/basics/template-hierarchy/) — there's no custom routing logic layered on top of it anywhere in this theme. A few things worth knowing that aren't obvious from the file alone:

- **`comments.php`** uses WordPress core's own default comment-rendering output (no custom `Walker_Comment` callback) — the CSS in `style.css` is written to match that exact, well-documented core markup (`.comment-author.vcard`, `.fn`, `.comment-metadata`, etc.), not a bespoke template.
- **`template-contact.php`**'s allowed form-type whitelist deliberately excludes `inline`, `sidebar`, `exit_intent`, and `post-feedback` — those four are embed/overlay/micro-interaction patterns that don't make sense as the entire content of a standalone Contact page.
- **`index.php`** is WordPress's genuine last resort — `home.php` handles the blog index when a static front page is set, so `index.php` is rarely the template actually hit in practice. It still gets breadcrumbs and the same numbered pagination as every other listing template, for consistency.

---

## The Contact Form System

Ten patterns under `template-parts/forms/`, each a different shape for a different use case:

| Pattern | `gwill_form_type` value | Use case |
|---|---|---|
| Simple | `simple` | Name / Email / Message. General-purpose default. |
| Service Inquiry | `inquiry` | Adds service type, timeline, budget. |
| Type Router | `routed` | Routes to different recipient addresses by inquiry type (`gwill_form_routing_map` filter). |
| Multi-step Quote | `multistep` | 4 steps, `sessionStorage`-persisted on Back. |
| Inline Post Form | `inline` | Compact 2-field embed inside post content. |
| Sidebar Form | `sidebar` | Compact form for a widget area. |
| Exit-Intent | `exit_intent` | Full-viewport overlay, triggered by cursor-leave or 75% scroll depth, throttled to once per 7 days. |
| Application | `application` | "Work with me" framing — revenue/outcome qualifying questions. |
| Partnership | `partnership` | Sponsorship/brand-deal intake. |
| Post Feedback | `post-feedback` | Yes/No micro-interaction; No reveals a follow-up textarea. |

**Shared architecture, every pattern:**

- All ten submit through one shared handler in `assets/js/forms.js`, attached to any `.gwill-form` element — `form-multistep.js` and `form-exit-intent.js` add their own step/trigger logic on top but delegate the actual AJAX submission to the same shared path, rather than each maintaining an independent copy.
- **Nonce**: pre-baked into the page for logged-in users (their pages are never served from LiteSpeed's cache, so a nonce baked into the HTML is always fresh); fetched on-demand via `admin-ajax.php` for anonymous visitors, with a cache-busting parameter so no intermediate caching layer can serve a stale one.
- **Honeypot** field, invisible to real users, silently "succeeds" for bots that fill it in rather than revealing the trap.
- **Rate limiting**: 5 minutes per detected IP (`gwill_form_rate_limited()`), bypassed for `current_user_can('edit_posts')` so testing isn't blocked by the same protection meant for spam. IP detection defaults to `REMOTE_ADDR` only — see `GWILL_TRUST_PROXY_HEADERS` above for why.
- **Errors**: `gwill_handle_contact_form()` sends a specific, accurate message via `wp_send_json_error()` for every rejection reason (bad nonce, rate-limited, validation failure) — `assets/js/forms.js` reads that message directly rather than substituting a generic one, regardless of which non-2xx HTTP status carried it.
- **Email**: `wp_mail()` with optional SMTP relay (see wp-config constants above). HTML auto-reply to the submitter is optional (`GWILL_AUTOREPLY`); DB logging of every submission to a custom table is optional (`GWILL_LOG_FORMS`).

---

## Tier 1 Features

Shipped as a batch in v1.0.50 — see `GWILL-FEATURE-ROADMAP.md` for the full tiered plan and the reasoning behind what's core vs. opt-in.

- **Open Graph / Twitter Card fallback** (`inc/social-meta.php`) — only outputs when no SEO plugin is active. Image source: the post's own featured image, falling back to the Customizer's "Default Social Share Image" (Site Identity section).
- **FAQ accordion + `FAQPage` schema** (`inc/faq.php`) — built on WordPress core's native `<details>`/`<summary>` block, not a custom JS accordion. Insert the "FAQ Section" block pattern from the inserter; the schema is generated automatically from whatever's actually in the accordion, so the two can never drift apart.
- **Cookie consent banner** — notice + Accept/Reject, `localStorage`-backed. Fires a `gwill:cookie-consent-given` DOM event on Accept for any tracking script a specific build adds later to listen for; this theme ships no tracking scripts of its own to gate.
- **Related posts** — shown after the author box on `single.php`, matched by primary category.
- **Reading time** — shown in both the card view and the single-post view.
- **Back-to-top button** — appears past 400px scrolled; respects `prefers-reduced-motion`.
- **Sticky header** — Customizer toggle, default on (Appearance → Customize → Header Options).

---

## Customizer Controls

**Header Options** (`gwill_header` section):

| Control | Type | Default | Transport |
|---|---|---|---|
| Display tagline | Checkbox | On | `postMessage` |
| Enable sticky header | Checkbox | On | `refresh` |
| Header padding (px) | Number, 0–200 | 24 | `postMessage` |

**Site Identity** (`title_tagline` — WordPress core's own section, extended here):

| Control | Type | Default |
|---|---|---|
| Logo width (px) | Number, 20–400 | 160 |
| Default Social Share Image | Image upload | none |

---

## CSS Standards

**Variables first, always.** Every value that appears more than once is a custom property in `:root` — colours, spacing, type scale, border radii.

**Never hardcode a colour.** Reference the variable everywhere else. If you need a new one, add it to `:root` with a semantic name (`--color-danger`), not a descriptive one (`--red`).

**Dark mode is token-based, not duplicated per component.** `assets/css/darkmode.css` works by redefining the *same* custom property values under `[data-theme="dark"]` / `prefers-color-scheme`. Any new component written using only `var(--color-*)` tokens gets correct dark-mode support automatically — no companion dark-mode CSS file edit needed, ever.

**Specificity discipline.** Never `!important`. Specificity should rarely climb above two classes.

**Mobile-first media queries.** Base styles target small screens; `@media (min-width: …)` adds complexity at larger sizes.

---

## PHP Standards

- **ABSPATH guard on every file, no exceptions** — `defined( 'ABSPATH' ) || exit;` as the first line after `<?php`, in every template, partial, and include.
- **Escape all output at the point of output**, using the function that matches the context (`esc_html()`, `esc_attr()`, `esc_url()` for display; `esc_url_raw()` for storage — these are not interchangeable).
- **Sanitize and unslash all input** before use — `sanitize_text_field( wp_unslash( $_POST['x'] ) )`, not raw superglobal access.
- **Prefix everything** `gwill_` — functions, do-not-collide global state, everything.
- **Type hints on every function signature** — PHP 8.1 is the floor.
- **No closing `?>` tag** on PHP-only files.
- **Strict comparison only** (`===`/`!==`) — never `==`/`!=`.

---

## Security Standards

- Nonces on every form and every AJAX action that changes state.
- Capability checks (`current_user_can()`) before any privileged operation, checked before the nonce in some cases and after in others — order doesn't matter as long as both gates exist before anything happens.
- No raw `$wpdb` string interpolation — `$wpdb->prepare()` with placeholders, every time a custom query is unavoidable. `WP_Query` is preferred wherever it can do the job instead.
- Security headers (`X-Content-Type-Options`, `X-Frame-Options`, CSP, etc.) are deliberately **not** set in PHP — set once at the server/CDN layer (Cloudflare, Nginx, Apache). Setting them in both places causes duplicate headers that some proxies mishandle.

---

## Accessibility Standards

- Skip-to-content link, first focusable element on every page, never removed.
- Four landmark roles maintained on every template: `banner`, `main`, `contentinfo`, `navigation` (with `aria-label`).
- One `<h1>` per page.
- `prefers-reduced-motion: reduce` respected everywhere an animation or smooth-scroll exists — dark mode toggle, back-to-top, sticky header.
- The duplicate-link problem (a card's image and title both linking to the same post) is solved once, consistently: the image link is `aria-hidden="true"` and `tabindex="-1"`; the title link is the single accessible entry point. Every card variant in this theme follows this pattern.

---

## What Is Intentionally Absent

| Thing | Why absent |
|---|---|
| Parent theme | Not needed for custom builds. |
| `front-page.php` | Project-specific — add it when a static front page needs a layout distinct from `home.php`. |
| Widget areas | `sidebar.php` is a template-hierarchy stub only. Register sidebars per project in a new `inc/sidebars.php`. |
| WooCommerce support | Tier 3 in the feature roadmap — opt-in per project, not baked into core. |
| Button base styles | Too opinionated to be universal — every project's buttons look different. Add per project. |
| Full Site Editing | This is a classic PHP theme. `theme.json` is Gutenberg *configuration* only — no `styles` block, no `templates/`/`parts/` directories. |
| Granular cookie-category management | This theme ships no tracking scripts of its own to gate — the consent banner exists, full consent-management-platform behaviour doesn't, on purpose. Add it only when a project's tracking scripts actually need it. |

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the complete version-by-version history.

## License

GNU General Public License v2 or later <https://www.gnu.org/licenses/gpl-2.0.html>
