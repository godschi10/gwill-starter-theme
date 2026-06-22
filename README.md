# GWill Starter Theme

A clean, custom WordPress starter theme built from scratch. No parent theme. No opinions you didn't write. Every line is yours.

---

## Getting Started — New Project Setup

Everything you need to go from a fresh WordPress install to a working site.

### 1. Install

Upload the deploy zip via **Appearance → Themes → Add New → Upload**, or extract and FTP to `wp-content/themes/gwill-starter-theme/`. Activate.

### 2. wp-config.php constants

Add these above the `/* That's all, stop editing! */` line. Only `GWILL_TO_EMAIL` is required.

```php
// ── GWill Starter ─────────────────────────────────────────────────────────────
// Required: email address that receives all contact form submissions.
define( 'GWILL_TO_EMAIL', 'you@yourdomain.com' );

// Optional: SMTP relay for wp_mail() (only needed when $backend = 'native').
// On free/shared hosting, use $backend = 'formsubmit' instead — SMTP is blocked there.
// Get free credentials from Brevo (300 emails/day): https://app.brevo.com
// ⚠ Use the SMTP key from Account → SMTP & API → SMTP tab.
//   NOT the API key from the API keys tab. They are different.
//
// define( 'GWILL_SMTP_HOST',  'smtp-relay.brevo.com' );
// define( 'GWILL_SMTP_PORT',  587 );
// define( 'GWILL_SMTP_USER',  'xxxxxxxx@smtp-brevo.com' );
// define( 'GWILL_SMTP_PASS',  'xsmtp-xxxxxxxxxxxxxxxx' ); // SMTP key, not API key
// define( 'GWILL_FROM_EMAIL', 'hello@clientdomain.com' );
// define( 'GWILL_FROM_NAME',  'Site Name' );

// Optional feature flags (both default to false):
// define( 'GWILL_AUTOREPLY', true ); // confirmation reply to submitter
// define( 'GWILL_LOG_FORMS', true ); // log submissions to DB (schema in inc/forms.php)
```

### 3. Pick a form delivery backend

In `template-parts/forms/contact-simple.php` around line 18:

```php
$backend = 'formsubmit'; // change to 'native' when on a server with SMTP access
```

| Backend | How it works | Requires |
|---|---|---|
| `formsubmit` | Browser POSTs to FormSubmit.co, they email you. Works on any host. | Nothing. First send triggers a one-time activation email from FormSubmit — click the link. |
| `native` | `wp_mail()` via your SMTP relay. Professional sender address. | Host that allows outbound port 587 + SMTP constants above. |

### 4. Create the contact page

Pages → Add New → Title: "Contact" → Template: **Contact** → Publish.

Defaults to the simple 3-field form. To use a different form type, set the `gwill_form_type`
post meta on the page (`simple` | `inquiry` | `routed` | `multistep` | `application` | `partnership`):

```php
update_post_meta( $page_id, 'gwill_form_type', 'inquiry' );
```

### 5. Test the form

Submit with a real email. For `formsubmit`: check inbox for activation email on first send.
For `native`: if it fails, visit `/wp-admin/admin-ajax.php?action=gwill_test_mail` while logged
in — the JSON tells you exactly what went wrong.

### 6. Footer credit

Defaults to "Built by G-will Chijioke". For client sites:

```php
// Remove:
add_filter( 'gwill_footer_credit', '__return_empty_string' );
// Replace:
add_filter( 'gwill_footer_credit', fn() => ' — Built by Your Studio' );
```

### 7. Optional — Type-router form recipient mapping

```php
add_filter( 'gwill_form_routing_map', function( $map ) {
    $map['press']   = 'press@clientsite.com';
    $map['support'] = 'support@clientsite.com';
    return $map;
} );
```

---

## Table of Contents

1. [Philosophy](#philosophy)
2. [Requirements](#requirements)
3. [File Structure](#file-structure)
4. [File Reference](#file-reference)
   - [.gitignore](#gitignore)
   - [.editorconfig](#editorconfig)
   - [phpcs.xml](#phpcsxml)
   - [style.css](#stylecss)
   - [functions.php](#functionsphp)
   - [theme.json](#themejson)
   - [screenshot.png](#screenshotpng)
   - [header.php](#headerphp)
   - [footer.php](#footerphp)
   - [index.php](#indexphp)
   - [single.php](#singlephp)
   - [page.php](#pagephp)
   - [archive.php](#archivephp)
   - [search.php](#searchphp)
   - [searchform.php](#searchformphp)
   - [404.php](#404php)
   - [comments.php](#commentsphp)
   - [inc/setup.php](#incsetupphp)
   - [inc/enqueue.php](#incenqueuephp)
   - [inc/security.php](#incsecurityphp)
   - [inc/helpers.php](#inchelpersphp)
   - [inc/customizer.php](#inccustomizerphp)
   - [template-parts/content.php](#template-partscontentphp)
5. [How to Use Template Parts](#how-to-use-template-parts)
6. [How to Add Features Per Project](#how-to-add-features-per-project)
7. [CSS Standards](#css-standards)
8. [PHP Standards](#php-standards)
9. [WordPress Standards](#wordpress-standards)
10. [Security Standards](#security-standards)
11. [Accessibility Standards](#accessibility-standards)
12. [What Is Intentionally Absent](#what-is-intentionally-absent)
13. [Changelog](CHANGELOG.md)

---

## Philosophy

This theme is a **starting surface, not a finished product**. It contains only what every project needs, nothing that any one project assumes.

The test for inclusion: *Will every build that starts from this theme need this?* If yes, it belongs. If it depends on the project, it does not.

- Zero CSS opinions beyond reset, variables, and structural baseline
- Zero JS by default — enqueue only what you need
- Every `remove_action` call has a documented reason
- Every file is self-contained — no silent dependencies
- Gutenberg is constrained, not embraced. `theme.json` kills its defaults; your styles replace them

---

## Requirements

- WordPress 6.4 or higher
- PHP 8.1 or higher
- No plugins required to function

---

## File Structure

```
gwill-starter/
├── .editorconfig               Enforces consistent indentation and line endings across editors
├── .gitignore                  Excludes OS files, node_modules, compiled .mo files, build output
├── phpcs.xml                   PHP_CodeSniffer ruleset — WordPress coding standards, text domain, PHP 8.1+
├── composer.json               Dev dependencies: PHPCS, WPCS, PHPCompatibilityWP (run: composer install)
├── style.css                   Theme header + all frontend styles
├── functions.php               Loader only — requires all inc/ files
├── theme.json                  Gutenberg configuration
├── screenshot.png              Theme preview in WP admin
├── comments.php                Comments template
├── header.php                  DOCTYPE, <head>, site header, opens <main>
├── footer.php                  Closes <main>, site footer, wp_footer()
├── index.php                   Blog index / fallback template
├── home.php                    Blog index (when a static front page is set)
├── single.php                  Single post
├── page.php                    Static page
├── archive.php                 Category, tag, date, author archives
├── search.php                  Search results
├── searchform.php              Custom search form markup
├── 404.php                     Not found page
├── attachment.php              301-redirects media attachment URLs to parent post or home
├── sidebar.php                 Template hierarchy stub (no widget areas registered by default)
├── inc/
│   ├── setup.php               Theme supports, nav menus, content width
│   ├── enqueue.php             Style and script registration + Customizer preview enqueue
│   ├── security.php            Head cleanup, XML-RPC, emoji removal
│   ├── helpers.php             gwill_part() helper, excerpt filters
│   └── customizer.php          Customizer section: header padding (px), tagline toggle
├── assets/
│   ├── js/
│   │   ├── main.js             Pre-built nav toggle (source: src/main.js)
│   │   └── customizer-preview.js  postMessage live-preview handler (Customizer only)
│   ├── css/                    Empty — CSS lives in style.css
│   └── images/                 Theme images (empty by default)
├── template-parts/
│   └── content.php             Article card partial (index, archive, search)
└── languages/                  Translation files (.po, .mo, .pot)
    └── gwill-starter.pot       Starter POT — regenerate with WP-CLI before shipping
```

---

## File Reference

### .gitignore

**What it does:** Prevents noise from being committed to version control. Three categories matter most for this theme:

- **OS and IDE files** — `.DS_Store`, `.idea/`, `.vscode/` and similar. These are machine-specific and have no place in a shared repository.
- **`node_modules/`** — if you add a build tool (Vite, webpack, esbuild), the dependency directory must never be committed. It belongs in the developer's local environment, restored via `npm install`.
- **Compiled `.mo` files** — `.pot` and `.po` translation sources are committed because they are version-controlled text. `.mo` binaries are compiled from `.po` at deploy time using `msgfmt` or `wp i18n make-mo`. Committing `.mo` files creates binary diffs that are useless in code review.

---

### .editorconfig

**What it does:** Enforces consistent formatting across editors and developers without requiring anyone to configure their editor manually. Editors that support EditorConfig (VS Code, PhpStorm, Vim, Neovim, Sublime Text, and most others) read this file automatically.

**PHP uses tabs.** This is the WordPress Coding Standard. Non-negotiable for any theme that may be reviewed against WPCS.

**CSS, JS, JSON, and YAML use 2-space indentation.** This matches the existing files in this theme and the conventions used by most frontend tooling.

**Markdown exempts trailing whitespace trimming.** Two trailing spaces at the end of a Markdown line produce a `<br>` in rendered output. Trimming them silently breaks line breaks in documentation.

**Rule: do not override `.editorconfig` settings in your personal editor config for files inside this project.** The point is consistency across the team, not personal preference.

---

### phpcs.xml

**What it does:** Configures [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) with the [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards) ruleset. Run it to catch escaping violations, missing text domains, incorrect hook usage, and style inconsistencies before they reach production.

**Installation:**

`composer.json` declares PHPCS, WPCS, PHPCompatibilityWP, and the Composer installer plugin as dev dependencies. One command sets everything up:

```bash
composer install
```

`dealerdirect/phpcodesniffer-composer-installer` registers the WPCS and PHPCompatibilityWP standard paths automatically — no manual `phpcs --config-set` needed.

**Run a check:**

```bash
vendor/bin/phpcs
```

**Auto-fix what can be fixed:**

```bash
vendor/bin/phpcbf
```

**Three deliberate rule exclusions:**

| Excluded rule | Why |
|---|---|
| `Universal.Arrays.DisallowShortArraySyntax` | Short array syntax `[]` is valid PHP 5.4+ and used throughout this theme. WPCS's default flags it; the exclusion allows it. |
| `Universal.FunctionDeclarations.NoLongClosures` | Arrow functions `fn() =>` are valid PHP 7.4+ and used in `inc/helpers.php` and `inc/security.php`. |
| `WordPress.WhiteSpace.ControlStructureSpacing` | Blank lines between hooked callbacks are kept for readability. The rule flags them as violations. |

**PHP compatibility** is checked against PHP 8.1+ and WordPress 6.4+. To enable this, also install:

```bash
composer require --dev phpcompatibility/phpcompatibility-wp
```

---

### style.css

**What it does:** Two jobs. First: holds the theme header (the comment block at the top that WordPress reads to register the theme). Second: all frontend CSS.

The file is also loaded into the Gutenberg editor via `add_editor_style()` in `inc/setup.php`. Editor-scoped rules live at the bottom of the file under `.editor-styles-wrapper` — they have no effect on the frontend.

**CSS variables** are defined in `:root`. All colours, the type scale, max-width, and spacing live here. Never hardcode a value that appears more than once — use a variable.

**Version bump:** Increment `Version:` in the file header on every release to bust the browser cache. WordPress reads this value and appends it as a query string to the enqueued stylesheet URL.

```css
/* Bump this on every release */
Version: 1.0.12
```

**What to add per project:** Anything that is specific to the build goes here — component styles, layout variants, dark mode, print styles. Do not create a separate CSS pipeline unless you have a build tool. If you do have a build tool (Vite, webpack), compile into `assets/css/` and enqueue from there instead.

---

### functions.php

**What it does:** Nothing except load the four files in `inc/`. It is a loader, not a logic file.

```php
require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/enqueue.php';
require_once get_template_directory() . '/inc/security.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/customizer.php';
```

**Rule: never add logic directly to functions.php.** If a new concern arises — custom post types, REST API modifications, ACF integration — create `inc/post-types.php`, `inc/rest.php`, `inc/acf.php`, and add a `require_once` line. `functions.php` should never grow past its initial 8 lines.

---

### theme.json

**What it does:** Controls Gutenberg at the engine level. This file does three things:

1. **Kills Gutenberg's default colour palette.** Without this file, the block editor ships with a set of default colours that have nothing to do with your design. They show up in the colour picker and generate CSS custom properties in the frontend markup — bloat you didn't ask for.

2. **Kills Gutenberg's default font size controls.** Same problem — WP's defaults override your type scale unless explicitly disabled.

3. **Sets content and wide widths.** The `contentSize` and `wideSize` values tell Gutenberg how wide the editor canvas and wide-aligned blocks should be. `contentSize` matches `--max-width` in `style.css`. `wideSize` must be larger than `contentSize` — if they are equal, the `align-wide` block support registered in `inc/setup.php` has no effect and wide blocks render at the same width as regular content.

**The schema is pinned to a specific WP version** (`wp/6.6`) rather than `trunk`. The `trunk` schema can change between WP releases and break editor validation mid-project. Pin to the minimum WP version you require (6.4+) or the version you ship against.

**The palette defined here** (`primary`, `accent`, `white`, `muted`) is the minimum. Add project colours here — they become available in the block editor's colour picker.

**Rule: do not use theme.json as a style engine.** Do not put `styles` blocks into `theme.json`. CSS belongs in `style.css`. `theme.json` is for Gutenberg configuration only.

---

### screenshot.png

**What it does:** The image WordPress displays in Appearance → Themes. Required dimensions: 1200 × 900 px. Without it, the admin shows a broken image placeholder for the theme.

**Replace it** with an actual screenshot of the finished design before delivering any project. You do not need to ship a screenshot mid-build; the placeholder is only there so WordPress doesn't error.

---

### header.php

**What it does:** Opens every page. Contains:
- `<!DOCTYPE html>` and `<html>` with `language_attributes()`
- `<head>` with charset, viewport, and `wp_head()` (never add manual stylesheet links here)
- `wp_body_open()` — required hook; plugins use it to inject content immediately after `<body>`
- The skip-to-content link (accessibility — must be the first focusable element on the page)
- `<div class="site">` wrapper
- `<header class="site-header">` with the site title and primary nav
- Opens `<main class="site-main">` and `<div class="inner">`

**The `<main>` tag is left open.** `footer.php` closes it. Every template that calls `get_header()` must also call `get_footer()`.

**Nav fallback is intentionally absent.** If no menu is assigned to `primary`, the nav area renders nothing. Do not add a fallback that dumps page links — it creates unpredictable output in production.

---

### footer.php

**What it does:** Closes `<main>` and `<div class="inner">` that `header.php` opened. Contains:
- Footer nav (renders only if a menu is assigned to the `footer` location)
- Copyright line with the current year via `gmdate('Y')` — never hardcode a year
- `wp_footer()` — required hook; scripts enqueued with `in_footer: true` load here, as do plugin scripts. Never remove it.

**Rule: `wp_footer()` must always be the last call inside `<body>`.** Nothing comes after it except the closing tags.

---

### index.php

**What it does:** The fallback template for the blog posts index. WordPress uses this when no more specific template exists in the hierarchy. It loops through posts and renders each one via `gwill_part('content')`.

**Template hierarchy context:** WordPress loads templates in this order for the main blog page: `home.php` → `index.php`. Since `home.php` is absent here, `index.php` handles both the front page (when set to display posts) and the blog index. Add `home.php` if you need different layouts for each case.

---

### single.php

**What it does:** Renders a single post. Outputs:
- Featured image (if set)
- Post title as `<h1>`
- Author and date meta
- Full post content via `the_content()`
- Paginated content links via `wp_link_pages()` (for posts split with `<!--nextpage-->`)
- Previous/next post navigation
- Comments template (only when comments are open or the post has existing comments)

**Rule: always wrap `comments_template()` in the conditional** `if ( comments_open() || get_comments_number() )`. Calling it unconditionally generates the comment form on posts that have comments disabled.

---

### page.php

**What it does:** Renders a static page. Identical to `single.php` but without author meta and post navigation — pages don't have those. Comments are conditionally loaded the same way.

**Custom page templates:** To create a page template that appears in the Page Attributes dropdown, add this to the top of any PHP file in the theme root or in `template-parts/`:

```php
<?php
/**
 * Template Name: Full Width
 * Template Post Type: page
 */
```

WordPress discovers it automatically. Name the file descriptively: `template-full-width.php`.

---

### archive.php

**What it does:** Handles all archive views: category, tag, date, author, and custom taxonomy archives. Outputs an archive header (title + optional description) then loops through posts using `gwill_part('content')`.

**Template hierarchy context:** WordPress checks for more specific templates first: `category.php`, `tag.php`, `taxonomy.php`, `author.php`, `date.php`. Create those when a specific archive type needs a different layout. `archive.php` is the fallback for all of them.

---

### search.php

**What it does:** Displays search results. Outputs a header with the query string, the search form (so users can refine), then loops results via `gwill_part('content')`. Shows a "no results" message when the query returns nothing.

---

### searchform.php

**What it does:** Custom markup for the search form. WordPress loads this automatically whenever `get_search_form()` is called — in `search.php`, `404.php`, and anywhere else you call it.

**Rule: always use `get_search_form()`, never write the form HTML inline.** This ensures your custom markup is used consistently everywhere. If the markup needs to change, change it once here.

---

### 404.php

**What it does:** The not-found page. Outputs a heading, a prompt, the search form, and a link back to the homepage.

**Best practice enforced here:** Always offer a search form on 404. A dead end with only a "back to home" link loses the user. The search form gives them a recovery path without leaving the site.

---

### comments.php

**What it does:** The comment thread template. Called by `comments_template()` in `single.php` and `page.php`. Handles three states:

1. Post is password-protected → exit early, show nothing
2. Comments exist → render the comment list, then the comment form below it
3. No comments, comments open → render only the form
4. Comments closed → render a "comments are closed" notice

**Rule: never call `comments_template()` directly in a loop.** It must be called after `the_post()` within a `while ( have_posts() )` block, once per post, outside the loop body if you've already ended it. The conditional `if ( comments_open() || get_comments_number() )` ensures the template file is not even loaded when it would render nothing.

---

### inc/setup.php

**What it does:** All `add_theme_support()` declarations, `register_nav_menus()`, and `$content_width`. Runs on `after_setup_theme`.

**`$content_width`** — a WordPress global that constrains the width of embedded media (YouTube iframes, oEmbeds). Set it to the same value as `--max-width` in `style.css`. Without it, WordPress uses a default of 500 px, which will make wide embeds clip or overflow.

**`automatic-feed-links`** — causes WordPress to inject `<link rel="alternate">` tags for RSS/Atom feeds into `<head>`. Without this declaration, the links are absent and feed discovery fails.

**Nav menus registered:**

| Slug | Label | Used in |
|---|---|---|
| `primary` | Primary Navigation | `header.php` |
| `footer` | Footer Navigation | `footer.php` |

To add a third location (e.g. a mobile-only menu), add it to the array here and call `wp_nav_menu()` with the matching `theme_location` in the appropriate template.

---

### inc/enqueue.php

**What it does:** Registers and enqueues styles and scripts. Runs on `wp_enqueue_scripts`.

**The `comment-reply` script** is conditionally enqueued here. It is the script WordPress needs for the "Reply" link on comments to scroll the form to the correct position and pre-populate the parent ID. Without it, nested comment replies break. It is only loaded on singular templates where comments are open and threaded comments are enabled in Settings → Discussion.

**To enqueue your main JS:**

```php
wp_enqueue_script(
    'gwill-main',
    get_template_directory_uri() . '/assets/js/main.js',
    [],                              // dependencies — add 'jquery' here if needed
    wp_get_theme()->get( 'Version' ),
    true                             // always load in footer unless there is a specific reason not to
);
```

**Rule: never use `<script>` tags in template files.** All scripts go through `wp_enqueue_script()`. Never use `<link>` tags in templates for styles. All styles go through `wp_enqueue_style()`. This ensures dependency resolution, cache-busting, and plugin compatibility.

**Rule: always pass `wp_get_theme()->get('Version')` as the version argument**, not a hardcoded string. This ties cache-busting to the version in `style.css` automatically.

---

### inc/security.php

**What it does:** Removes WordPress output that leaks information or adds no value, and closes several common attack surfaces.

| Action | Why |
|---|---|
| Remove `rsd_link` | RSD (Really Simple Discovery) is a dead protocol. Leaks theme/plugin info. |
| Remove `wp_generator` from `<head>` | Removes the `<meta name="generator" content="WordPress X.X.X">` tag. Stops version disclosure in HTML. |
| Filter `the_generator` to empty string | Removes the WordPress version from RSS/Atom feed headers. `wp_generator` removal alone only covers `<head>` — the feed still leaks without this filter. |
| Remove `wlwmanifest_link` | Windows Live Writer manifest. No modern use. |
| Remove `wp_shortlink_wp_head` | Shortlink exposes the internal post ID (`?p=123`). No SEO value. |
| Disable XML-RPC | Old remote publishing API. Frequently targeted in brute-force and DDoS amplification attacks. Disable unless a plugin explicitly requires it. |
| Remove emoji scripts | WordPress injects a ~15 KB emoji detection script on every page to polyfill emoji rendering on old browsers. Modern browsers render emoji natively. Use real emoji characters instead of WP's polyfill. |
| Block REST API user endpoints | `/wp-json/wp/v2/users` exposes author login names to unauthenticated requests. Removed for anonymous visitors only — authenticated requests (logged-in users, WooCommerce, ACF, etc.) resolve normally. Unsetting unconditionally breaks plugins that call these endpoints internally. |
| Redirect author archives | `/?author=1` redirects to `/author/loginname/`, disclosing login names independently of the REST API. Requests are redirected to homepage with a 301. **Tradeoff:** this disables author archive pages entirely — remove the filter for multi-author or contributor-facing sites. |
| Generic login error messages | By default WordPress distinguishes "no account found" from "wrong password" in login errors, letting an attacker enumerate valid usernames. Both cases return the same generic message. |

**What is not here and why:**

Security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Strict-Transport-Security`, `Referrer-Policy`, `Content-Security-Policy`) are not set via PHP. Reason: setting them in PHP and at the server/CDN layer causes duplicate headers. Some proxies and browsers reject or mishandle responses with duplicate security headers. Set them once, at the infrastructure layer — Cloudflare `_headers` file, Nginx `add_header`, or Apache `.htaccess`.

---

### inc/helpers.php

**What it does:** Utility functions and filter overrides that do not belong in any specific template.

**`gwill_part( string $slug, array $data = [] )`** — a thin wrapper around `get_template_part()` that prepends `template-parts/` automatically. Use this everywhere instead of calling `get_template_part()` directly.

**Excerpt filters** — `excerpt_length` is set to 25 words. `excerpt_more` returns `…` instead of WordPress's default `[…]`. Override these per project here if needed.

---

### inc/customizer.php

**What it does:** Registers a "Header Options" section in the WordPress Customizer (`Appearance → Customize → Header Options`) with two controls.

**Controls:**

| Control | Type | Default | Transport | Description |
|---|---|---|---|---|
| Display tagline | Checkbox | ✓ checked | `postMessage` | Shows or hides the `site-description` paragraph in `header.php`. The element remains in the DOM (controlled via the HTML `hidden` attribute) so the Customizer preview updates live without a reload. |
| Header padding (px) | Number | 24 | `postMessage` | Top & bottom padding on the site header. Min: 0, Max: 200 px. Updates live in the Customizer preview without a page reload. |

**How the header padding works:** `style.css` declares `.site-header { padding: var(--header-padding, var(--spacing)); }`. When the saved value differs from the default (24 px), `inc/customizer.php` injects `:root{--header-padding:Npx}` via `wp_add_inline_style()` after the main stylesheet. At the default value, no inline CSS is emitted — the CSS fallback handles it.

**Live preview (postMessage):** `assets/js/customizer-preview.js` is loaded exclusively inside the Customizer preview iframe via `customize_preview_init` (registered in `inc/enqueue.php`). It listens for `gwill_header_padding` changes and sets `--header-padding` directly on `document.documentElement.style`, giving instant feedback as you type.

**Sanitization:** `gwill_sanitize_checkbox()` casts the Customizer's raw `'1'`/`''` to a clean bool. `gwill_sanitize_header_padding()` casts to `int` and clamps to `[0, 200]` — no arbitrary string ever reaches inline CSS output.

---

### template-parts/content.php

**What it does:** The article card. A single, canonical piece of markup for displaying a post in list context (blog index, archive, search results). Called from `index.php`, `archive.php`, and `search.php` via `gwill_part('content')`.

**Why this exists:** Without a shared partial, the same markup is duplicated in three files. When the card design changes — and it will change — you update one file instead of three, with no risk of the files drifting out of sync.

**What it outputs:**
- Featured image (if set), linked to the post, with `aria-hidden="true"` and `tabindex="-1"` so the duplicate link does not appear in the tab order or to screen readers (the title link below is the accessible one)
- Post title as `<h2>`, linked to the post
- Post date meta
- Excerpt

---

## How to Use Template Parts

Use `gwill_part()` everywhere. Never call `get_template_part()` directly.

```php
// Basic usage
gwill_part( 'content' );                          // loads template-parts/content.php

// Subdirectory
gwill_part( 'cards/project-card' );               // loads template-parts/cards/project-card.php

// Pass data into the partial
gwill_part( 'hero', [ 'title' => 'Welcome' ] );   // $args available inside the partial
```

**Inside the partial, access passed data via `$args`:**

```php
// template-parts/hero.php
$title    = $args['title']    ?? get_bloginfo( 'name' );
$subtitle = $args['subtitle'] ?? '';

echo '<h1>' . esc_html( $title ) . '</h1>';

if ( $subtitle ) {
    echo '<p>' . esc_html( $subtitle ) . '</p>';
}
```

**Rule: always provide a default for every `$args` key with `??`.** Partials can be called with or without data. Assuming a key exists causes PHP notices on every page where it is absent.

---

## How to Add Features Per Project

### Custom Post Types

Create `inc/post-types.php`:

```php
<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
    register_post_type( 'project', [
        'labels'      => [
            'name'          => __( 'Projects', 'gwill-starter' ),
            'singular_name' => __( 'Project',  'gwill-starter' ),
        ],
        'public'      => true,
        'has_archive' => true,
        'supports'    => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'rewrite'     => [ 'slug' => 'projects' ],
        'show_in_rest' => true,   // required for Gutenberg editor support
    ] );
} );
```

Add to `functions.php`:
```php
require get_template_directory() . '/inc/post-types.php';
```

Then create `single-project.php` and `archive-project.php` in the theme root. WordPress picks them up automatically.

---

### Custom Taxonomies

Create `inc/taxonomies.php`:

```php
<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
    register_taxonomy( 'service', [ 'project' ], [
        'labels'       => [
            'name'          => __( 'Services', 'gwill-starter' ),
            'singular_name' => __( 'Service',  'gwill-starter' ),
        ],
        'hierarchical' => true,
        'public'       => true,
        'rewrite'      => [ 'slug' => 'services' ],
        'show_in_rest' => true,
    ] );
} );
```

Require it in `functions.php`. Create `taxonomy-service.php` for a custom archive layout.

---

### Widget Areas (Sidebars)

Create `inc/sidebars.php`:

```php
<?php
defined( 'ABSPATH' ) || exit;

add_action( 'widgets_init', function () {
    register_sidebar( [
        'name'          => __( 'Primary Sidebar', 'gwill-starter' ),
        'id'            => 'sidebar-primary',
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ] );
} );
```

Output in a template:
```php
<?php if ( is_active_sidebar( 'sidebar-primary' ) ) : ?>
    <aside class="widget-area" role="complementary">
        <?php dynamic_sidebar( 'sidebar-primary' ); ?>
    </aside>
<?php endif; ?>
```

---

### Custom Image Sizes

Add to `inc/setup.php` inside the `after_setup_theme` callback:

```php
add_image_size( 'gwill-card', 600, 400, true );   // hard crop
add_image_size( 'gwill-wide', 1200, 600, true );  // hard crop
```

Use in templates:
```php
the_post_thumbnail( 'gwill-card' );
```

---

### REST API Modifications

Create `inc/rest.php`. Do not put REST modifications in any other file.

---

### ACF Integration

Create `inc/acf.php`. Register field groups programmatically rather than exporting from the UI, so field definitions are version-controlled.

---

### Additional Nav Menus

In `inc/setup.php`, add to `register_nav_menus()`:
```php
'mobile' => __( 'Mobile Navigation', 'gwill-starter' ),
```

Call it in the appropriate template:
```php
wp_nav_menu( [
    'theme_location' => 'mobile',
    'container'      => false,
    'fallback_cb'    => false,
] );
```

---

## CSS Standards

### Variables first, always

Every value that appears more than once becomes a CSS variable in `:root`. This includes colours, spacing values, font stacks, border radii, transition durations, and breakpoints.

```css
/* Correct */
:root {
    --radius: 4px;
}

.card { border-radius: var(--radius); }
.badge { border-radius: var(--radius); }

/* Wrong */
.card { border-radius: 4px; }
.badge { border-radius: 4px; }
```

### Never hardcode a colour

Colours only appear in `:root` as variables. Everywhere else, reference the variable. If a project needs a new colour, add it to `:root` with a semantic name, not a descriptive one.

```css
/* Correct — semantic naming */
:root { --color-danger: #dc2626; }
.error { color: var(--color-danger); }

/* Wrong — descriptive naming */
:root { --red: #dc2626; }
.error { color: var(--red); }
```

### Specificity discipline

Never use `!important`. Never target HTML elements directly unless it is a reset or a body-level default. Specificity should never need to climb above two classes.

```css
/* Correct — two-class max */
.site-header .site-title { }

/* Wrong — specificity creep */
header.site-header div.inner .site-title a { }
```

### Scope nav styles to location

The site has two nav locations. Never use a bare `nav` selector — it will hit both. Always scope to `.site-header nav` or `.site-footer nav`.

```css
/* Correct */
.site-header nav ul { display: flex; gap: 1.5rem; }
.site-footer nav ul { display: flex; flex-wrap: wrap; justify-content: center; }

/* Wrong — both navs inherit the same layout */
nav ul { display: flex; gap: 1.5rem; }
```

### Mobile-first media queries

Write base styles for small screens. Add `@media (min-width: X)` breakpoints to add complexity at larger sizes. Never use `max-width` queries except as overrides for edge cases.

```css
/* Correct — mobile first */
.grid { display: block; }

@media ( min-width: 768px ) {
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); }
}

/* Wrong — desktop first */
.grid { display: grid; grid-template-columns: repeat(3, 1fr); }

@media ( max-width: 768px ) {
    .grid { display: block; }
}
```

### Editor styles sync

Whenever you add a frontend rule that affects how content looks — typography, spacing, list styles, images — mirror it in the `.editor-styles-wrapper` block at the bottom of `style.css`. The editor and the frontend should be visually identical for content-bearing elements.

### No inline styles in templates

CSS does not belong in PHP templates. Not as `style=""` attributes, not as `<style>` tags. If a template needs a dynamic value (e.g. a background image URL from ACF), use a CSS custom property:

```php
<!-- Correct -->
<div class="hero" style="--hero-bg: url('<?php echo esc_url( $image_url ); ?>');">

<!-- Wrong -->
<div class="hero" style="background-image: url('<?php echo esc_url( $image_url ); ?>');">
```

Then in CSS: `.hero { background-image: var(--hero-bg); }`

---

## PHP Standards

### ABSPATH guard on every file

Every PHP file in the theme — templates, partials, includes — starts with:

```php
<?php
defined( 'ABSPATH' ) || exit;
```

This blocks direct access to the file via HTTP. WordPress defines `ABSPATH`. If a file is loaded directly without going through WordPress, this line kills execution immediately.

### Escape all output

Never echo user-supplied or database-sourced data without escaping. Use the correct escaping function for the context:

| Context | Function |
|---|---|
| HTML text | `esc_html()` |
| HTML attributes | `esc_attr()` |
| URLs | `esc_url()` |
| JavaScript strings | `esc_js()` |
| CSS values | `esc_attr()` or `sanitize_hex_color()` |
| Translation strings | `esc_html__()` or `esc_attr__()` |
| `get_bloginfo()` in HTML | `esc_html( get_bloginfo( '...' ) )` |
| `get_bloginfo()` in attributes | `esc_attr( get_bloginfo( '...' ) )` |

**Do not use `bloginfo()` directly for output.** `bloginfo()` does not apply contextual escaping — it echoes raw. Always use `get_bloginfo()` wrapped in the correct escaping function:

```php
// Correct — HTML context
echo esc_html( get_bloginfo( 'name' ) );

// Correct — attribute context
echo esc_attr( get_bloginfo( 'charset' ) );

// Wrong — no escaping
bloginfo( 'name' );
```

```php
// Correct
echo esc_html( get_the_title() );
echo esc_url( get_permalink() );
echo esc_attr( $css_class );

// Wrong — never do this
echo get_the_title();
echo the_permalink();  // the_ functions echo internally, but direct echo is still common mistake
```

**Exception:** `the_content()` is not escaped — it is the user's HTML. WordPress sanitises it through `wp_kses_post()` on save. Trust it in output.

### Sanitize all input

Never use raw `$_GET`, `$_POST`, or `$_REQUEST` values without sanitization:

```php
// Correct
$search = sanitize_text_field( $_GET['s'] ?? '' );
$email  = sanitize_email( $_POST['email'] ?? '' );
$url    = esc_url_raw( $_POST['website'] ?? '' );

// Wrong
$search = $_GET['s'];
```

### Use `wp_` and `get_` functions, not PHP equivalents

WordPress provides wrappers for file operations, HTTP requests, date formatting, and more. Prefer them — they respect WP's configuration and filters.

```php
// Correct
$url   = get_template_directory_uri() . '/assets/img/logo.svg';
$path  = get_template_directory() . '/inc/setup.php';
$date  = get_the_date();                    // respects date format in Settings
$year  = gmdate( 'Y' );                    // use gmdate(), not date() — timezone-safe

// Wrong
$url  = get_bloginfo('template_url') . '/assets/img/logo.svg';  // deprecated
$date = date('F j, Y');                    // ignores WP date format setting, timezone-unsafe
```

### Anonymous functions for hooks

Use anonymous functions for all `add_action` and `add_filter` callbacks unless the function needs to be referenced elsewhere (e.g. for `remove_action`).

```php
// Correct — anonymous, contained
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
} );

// Only use named functions when you need to reference them for removal
function gwill_setup_theme() {
    add_theme_support( 'title-tag' );
}
add_action( 'after_setup_theme', 'gwill_setup_theme' );
remove_action( 'after_setup_theme', 'gwill_setup_theme' );
```

### Prefix everything

Every function, class, and global variable you define must be prefixed with `gwill_` to avoid collisions with WordPress core, plugins, or other themes.

```php
// Correct
function gwill_part( string $slug, array $data = [] ): void { }
function gwill_get_breadcrumbs(): string { }
$gwill_options = get_option( 'gwill_settings' );

// Wrong — could collide with anything
function part( string $slug ): void { }
function get_breadcrumbs(): string { }
```

### No closing `?>` tag

Never end a PHP-only file with `?>`. It invites trailing whitespace after the tag, which causes "headers already sent" errors.

```php
// Correct — file ends here, no closing tag
add_filter( 'excerpt_length', fn() => 25 );
```

### Type hints on all function signatures

PHP 8.1 is the minimum. Use parameter types, return types, and nullables everywhere.

```php
// Correct
function gwill_part( string $slug, array $data = [] ): void { }
function gwill_get_post_meta( int $post_id, string $key ): string { }

// Wrong
function gwill_part( $slug, $data = [] ) { }
```

---

## WordPress Standards

### Template hierarchy — use it, don't fight it

WordPress's template hierarchy resolves templates automatically based on the current request. Lean on it instead of adding conditional logic inside templates.

```
single-{post-type}-{slug}.php  →  single-{post-type}.php  →  single.php  →  index.php
taxonomy-{taxonomy}-{term}.php →  taxonomy-{taxonomy}.php →  taxonomy.php → archive.php
category-{slug}.php            →  category-{id}.php       →  category.php → archive.php
```

If a post type or taxonomy needs a different layout, create the specific file. Do not add `if ( is_singular('project') )` logic inside `single.php`.

### Always use `wp_nav_menu()` — never hardcode nav HTML

```php
// Correct
wp_nav_menu( [
    'theme_location' => 'primary',
    'container'      => false,
    'fallback_cb'    => false,
    'depth'          => 2,
] );

// Wrong
echo '<ul><li><a href="/">Home</a></li></ul>';
```

`fallback_cb` is always `false`. The fallback dumps all pages as a menu when no menu is assigned — unpredictable output in production.

### Always use `home_url()` for internal links

```php
// Correct
echo esc_url( home_url( '/' ) );

// Wrong
echo 'https://yoursite.com/';   // breaks on every other install
echo get_bloginfo( 'url' );     // works but home_url() is more explicit
```

### Loop correctly

The standard loop pattern:

```php
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        // output
    endwhile;
else :
    // no results
endif;
```

On `single.php` and `page.php`, the `if` wrapper is not needed — WordPress guarantees a post exists for those templates. But it never hurts to include it. The `while` is always required because `the_post()` sets up the global `$post` object.

### Use `get_template_part()` through `gwill_part()`

Never include partial files with PHP's `include` or `require`. WordPress's `get_template_part()` (wrapped by `gwill_part()` here) handles child theme overrides correctly and fires hooks that plugins use.

### Never modify the global `$post` object in a template

If you need a secondary query (e.g. related posts), use `WP_Query` and restore `$post` afterward:

```php
$related = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 3 ] );

if ( $related->have_posts() ) :
    while ( $related->have_posts() ) : $related->the_post();
        // output
    endwhile;
endif;

wp_reset_postdata();   // always call this — restores the global $post
```

**`wp_reset_postdata()` is not optional.** Forgetting it corrupts the global state for any code that runs after your query.

### Internationalize all strings

Every user-visible string must be wrapped for translation. Use the `gwill-starter` text domain consistently:

```php
// Single string
esc_html_e( 'Back to Home', 'gwill-starter' );

// String with variable
printf(
    esc_html__( 'By %s', 'gwill-starter' ),
    esc_html( get_the_author() )
);

// Plural
printf(
    esc_html( _n( '%s Comment', '%s Comments', $count, 'gwill-starter' ) ),
    number_format_i18n( $count )
);
```

---

## Security Standards

### Direct access guard — mandatory on every file

```php
<?php
defined( 'ABSPATH' ) || exit;
```

No exceptions. Every `.php` file. Including partials inside `template-parts/`.

### No user input in queries without preparation

If you write a custom `$wpdb` query, use prepared statements:

```php
// Correct
global $wpdb;
$results = $wpdb->get_results(
    $wpdb->prepare( 'SELECT * FROM %i WHERE post_author = %d', $wpdb->posts, $author_id )
);

// Wrong — SQL injection vector
$results = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE post_author = $author_id" );
```

Prefer `WP_Query` over raw `$wpdb` queries wherever possible. `WP_Query` handles sanitization, caching, and filter hooks automatically.

### No eval, no base64-decoded execution, no dynamic includes

```php
// Wrong — never do any of these
eval( $user_input );
eval( base64_decode( $encoded_code ) );
include $dynamic_path;
require $_GET['file'];
```

### Nonces for all forms and AJAX

Any form that modifies data (not search, not filter) must include a nonce:

```php
// In the form
wp_nonce_field( 'gwill_save_settings', 'gwill_nonce' );

// On submission
if ( ! wp_verify_nonce( $_POST['gwill_nonce'] ?? '', 'gwill_save_settings' ) ) {
    wp_die( esc_html__( 'Security check failed.', 'gwill-starter' ) );
}
```

### Capability checks before privileged operations

```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have permission to do this.', 'gwill-starter' ) );
}
```

---

## Accessibility Standards

### Smooth scrolling and motion preferences

`html { scroll-behavior: smooth }` is overridden to `auto` when the user has `prefers-reduced-motion: reduce` set at the OS level. This covers WCAG 2.1 SC 2.3.3. Apply the same pattern to any animation or transition you add:

```css
@media ( prefers-reduced-motion: reduce ) {
  /* disable or reduce animations */
  .card { transition: none; }
}
```

### Skip link — never remove it

The skip-to-content link in `header.php` is the first focusable element on every page. It allows keyboard users to bypass the navigation and jump directly to content. It must remain the first thing in `<body>` after `wp_body_open()`.

### Landmark roles — maintain them

Every page uses the four landmark roles:

| Element | Role | Purpose |
|---|---|---|
| `<header>` | `banner` | Site header — one per page |
| `<main>` | `main` | Primary content — one per page |
| `<footer>` | `contentinfo` | Site footer — one per page |
| `<nav>` | `navigation` + `aria-label` | Navigational landmark |

Never remove these. When adding new landmarks (`<aside>`, additional `<nav>` elements), always include an `aria-label`.

### Heading hierarchy — one `<h1>` per page

`single.php` and `page.php` output the title as `<h1>`. `index.php`, `archive.php`, and `search.php` output titles as `<h2>` (the page-level heading is implicit in the browser tab). Never put two `<h1>` tags on the same page.

### Images always need context

`the_post_thumbnail()` does not automatically generate `alt` text. For featured images where context matters, pass explicit alt text:

```php
the_post_thumbnail( 'large', [ 'alt' => esc_attr( get_the_title() ) ] );
```

For decorative images that should be ignored by screen readers, use an explicit empty string — `alt=""`. Never omit the `alt` attribute entirely. The distinction matters: a missing `alt` attribute causes screen readers to fall back to the filename; an empty `alt=""` correctly signals that the image is presentational.

```php
// Decorative image (duplicate link, ornamental illustration, etc.)
the_post_thumbnail( 'medium_large', [ 'alt' => '' ] );
```

### The duplicate link problem — solved in content.php

The article card links the featured image and the title separately, both pointing to the same post. Screen readers would announce both. The image link is marked `aria-hidden="true"` and `tabindex="-1"` so it is invisible to assistive technology and excluded from the tab order. The title link is the single accessible entry point. Maintain this pattern in any card variant you create.

---

## What Is Intentionally Absent

| Thing | Why absent |
|---|---|
| Parent theme | Not needed for custom builds. Only use a parent theme when extending a theme you do not own. |
| jQuery | Not enqueued unless you add it. It is available via WordPress's built-in registration (`'jquery'` as a dependency), but importing it unconditionally costs ~30 KB. Add it only if a plugin or script genuinely requires it. |
| `home.php` | `index.php` serves as the fallback. Create `home.php` when the blog index needs a different layout from the front page. |
| `front-page.php` | Project-specific. Create it when a static front page needs a distinct template. |
| `sidebar.php` | Present as a template hierarchy stub. Register widget areas in `inc/sidebars.php` per project; the stub prevents WordPress from falling through to `index.php` for sidebar requests. |
| `wp_shortlink_wp_head` | Removed. Shortlinks expose internal post IDs (`?p=123`) and have no SEO benefit. |
| Emoji scripts | Removed. Modern browsers render Unicode emoji natively. The WP polyfill costs ~15 KB and injects a tracking pixel. Use real emoji characters or SVG icons instead. |
| Adjacent posts rel links | Kept intentionally. `<link rel="prev/next">` in `<head>` has SEO value for archives and paginated content. |
| FSE / Full Site Editing | Not included. This is a classic PHP theme. `theme.json` is used here for Gutenberg configuration only, not as a style engine or template system. No `parts/` or `templates/` directories — FSE and classic PHP themes are mutually exclusive architectures. |
| Security headers in PHP | Set at the server/CDN layer. Setting them in both places causes duplicate headers. |
| Button base styles | Too opinionated — every project has different button designs. Add them per project. |
| CSS utilities / helpers | Not a utility framework. Add utility classes only when a pattern repeats enough to justify abstraction. |

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full version history.

---

## License

GNU General Public License v2 or later
https://www.gnu.org/licenses/gpl-2.0.html
