# Changelog

All notable changes to GWill Starter are documented in this file.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versions follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## Changelog Policy

**Every project built from this starter must maintain its own changelog.**

- Every change — no matter how small — gets a changelog entry before the version ships.
- A one-line CSS tweak, a comment fix, a class rename: all logged.
- No entry = the change did not happen as far as future developers are concerned.
- Format: bump the patch version for fixes and small additions, minor for new features, major for breaking changes.
- The entry goes in before the zip/deploy, not after.

---

## [Unreleased]

---

## [1.0.63] - 2026-06-26

### Added — Tier 3 complete: pricing table component, portfolio/case-studies CPT

**Pricing table** (`inc/pricing-table.php`, `template-parts/pricing-table.php`)
- `gwill_pricing_table( $plans, $args )` — a plain PHP array of plans straight to a template tag. Deliberately not a CPT and deliberately no shortcode wrapper, unlike testimonials/portfolio/newsletter: a pricing lineup is normally hand-built once per client and rarely changes, so it doesn't gain anything from being individually manageable `WP_Post` objects, and per-plan feature lists have no sane flat-string shortcode representation short of JSON crammed into an HTML attribute. The function call with an array genuinely is the whole API here, not a placeholder for a "real" content-editor-facing version that didn't get built.
- Columns track the actual plan count (capped at 4) — three plans show three columns, not three stretched into four. `featured: true` on a plan gets a "Most Popular" badge (overridable per-plan via `badge`) and visual emphasis; CTA buttons reuse the exact same `--color-btn-bg`/`--color-btn-text` tokens `.gwill-form__submit` already uses, not a new button system.
- Currency symbol defaults to `$`, overridable per-call (`$args['currency']`) or sitewide (`gwill_pricing_currency_symbol` filter) for a project running in a different currency throughout.

**Portfolio / case-studies CPT** (`inc/portfolio.php`, `template-parts/portfolio/portfolio.php`)
- `gwill_portfolio` — genuinely public (`has_archive: true`), unlike the testimonials CPT's `publicly_queryable: false`. That's not an inconsistency between the two: a case study is content worth its own page, a testimonial is a snippet pulled into someone else's and nothing more.
- `gwill_portfolio_type` taxonomy, hierarchical, `show_admin_column: true` — filtering an agency/freelancer portfolio by service type (Branding/Web Design/Development, etc.) is close to a baseline expectation for this use case, not a nice-to-have bolted on after the fact.
- "Project Details" meta box: client name + an optional live project URL, following the identical nonce/capability-check ordering already established by the testimonial and video-embed meta boxes. When a project URL is set, the grid card's "View Project" link points there (opening in a new tab) instead of the project's own page.
- Scope held to exactly the roadmap's one-liner — "a registered post type plus a grid template-part" — on purpose. No `single-gwill_portfolio.php` or `archive-gwill_portfolio.php` ships; both fall through to this theme's existing `single.php`/`archive.php`, which already degrade gracefully for a post type with no categories assigned, since `gwill_get_primary_category()` already returns `null` cleanly in that case. A project wanting a more tailored single-project layout can add that template later — a per-client decision, not something to bake into the starter.
- Call `gwill_portfolio_grid( $args )` or `[gwill_portfolio]` (same attribute names).

### Fixed

- Caught during review before packaging, not after: `get_the_post_thumbnail()`'s `$attr` array is escaped internally by `wp_get_attachment_image()` — pre-escaping the `alt` text in the testimonials template part (added in 1.0.62) would have double-escaped any title containing an apostrophe or ampersand. Fixed before it ever shipped; the portfolio grid's own thumbnail call was written correctly from the start as a result.
- `README.md` and this changelog updated in the same pass that built these two features, continuing the practice established in 1.0.62 rather than letting documentation drift again.

---

## [1.0.62] - 2026-06-26

### Added — Tier 2 complete: table of contents, testimonials CPT, staging banner restored with a toggle

**Table of contents** (`inc/table-of-contents.php`)
- One `the_content` filter pass (priority 20, after wpautop/shortcode processing) does double duty: adds an `id` to any `<h2>`/`<h3>` missing one (never overwrites a manually-set anchor), and builds the nav from the exact same loop — the nav's links and the headings' actual ids are generated together, so they can never drift apart.
- Only appears with ≥3 headings (`gwill_toc_min_headings`), only on post types in `gwill_toc_post_types` (`post` only by default).
- `<details>`/`<summary>`, collapsed by default everywhere. Past a 1300px viewport, CSS forces it visually open (`display: block !important` regardless of the `[open]` attribute) and `position: sticky` — sticking relative to `.entry-content`'s own height, which works correctly even though this theme's `single.php` has no separate sidebar column. The collapse interaction is dropped entirely at that width, on purpose: a sticky box a visitor can accidentally collapse and have follow them down the page empty would be worse than not offering the toggle there at all.
- Accounts for both the staging banner's height and the sticky-header toggle's own height in its sticky offset, so neither one visually overlaps the stuck ToC box when both happen to be active at once.

**Testimonials CPT** (`inc/testimonials.php`, `template-parts/testimonials/testimonials.php`, `assets/js/testimonials-carousel.js`)
- `gwill_testimonial` post type — `public: false`, `publicly_queryable: false`, no archive: a testimonial is a card pulled into a grid/carousel wherever a developer places it, not content anyone navigates to at its own URL.
- Field mapping deliberately reuses what WordPress already has rather than inventing meta fields for everything: title = name, content = quote, featured image = photo (falls back to a generic avatar glyph when absent). Two real custom fields where nothing built-in covers them — role/company (text) and a 1–5 star rating — via a "Testimonial Details" meta box following the identical nonce/capability-check ordering already established by the video-embed meta box in `inc/setup.php`.
- Public API: `gwill_testimonials_grid( $args )` called directly from a template, or the `[gwill_testimonials]` shortcode with matching attribute names. `mode: 'grid'` (CSS grid, 2–4 columns via `columns`) or `mode: 'carousel'`.
- Carousel mode needs no JavaScript to function at all — native `overflow-x` + `scroll-snap-type: x mandatory` track, fully swipeable/scrollable without `testimonials-carousel.js`. That script only adds Prev/Next buttons, and *creates* them via JS rather than rendering inert ones in PHP — a button whose only behaviour comes from JS that might not load would be worse than no button, so no JS genuinely means no buttons here, not broken ones.

**Staging-environment banner — restored, with a toggle this time** (`inc/staging.php`, `template-parts/staging-banner.php`)
- Same host-pattern detection as the original (1.0.57, removed 1.0.59) — request host, not `home_url()`, against `.qzz.io` / `.local` / `staging.` / `dev.` / `test.`.
- New: `gwill_show_staging_banner` Customizer checkbox, Developer Options section, **defaulting to ON**. The first version's only failure mode was "always on with no way to turn it off short of removing the whole feature" — which is exactly what happened. Defaulting the toggle to on rather than off is deliberate: a banner that's off until a developer remembers to flip it on defeats its own purpose just as much as never having a toggle at all. The point is that a developer sees the option and makes an active choice either way, not that it starts invisible.

### Fixed

- `README.md` brought current for all of the above in the same pass this was built — File Structure tree, File Reference section, Tier 2 Features section (now fully complete rather than partially), and the new Customizer control documented under a new "Developer Options" heading. Lesson from the 1.0.61 audit applied going forward instead of repeating it: every file this version touches is reflected in the docs in the same response that touches it, not patched in after the fact.

---

## [1.0.61] - 2026-06-26

### Fixed

- **The newsletter pattern (v1.0.58) was never actually reachable on `template-contact-demo.php`** — a real functional gap, not just a doc one. The demo page's pattern list is a hardcoded array independent of `inc/forms.php`'s required-fields map; adding `'newsletter'` to the latter in 1.0.58 didn't add it to the former. Added now — pattern 11 of 11 on the demo page.

### Changed

- **README.md correctness pass.** Caught stale in this pass, all now fixed: `functions.php`'s require count (twelve → thirteen), the contact-form pattern count in four separate places (ten → eleven, including the demo-page docblock), `inc/woocommerce.php` and `assets/css/woocommerce.css` missing from the File Structure tree and File Reference section entirely, the back-to-top description still citing the pre-1.0.59 400px threshold instead of the current 30%-of-scrollable-distance behaviour, `gwill_get_primary_category()`'s description still claiming it honours RankMath/Yoast primary-term meta (removed entirely in 1.0.56 — it never reads that meta anymore), and the "What Is Intentionally Absent" table still listing WooCommerce as unsupported when 1.0.60 added it. Added dedicated **Tier 2 Features** and **Tier 3 Features** sections, matching the existing Tier 1 Features section's format, documenting exactly what shipped vs. what was explicitly skipped from each tier.

---

## [1.0.60] - 2026-06-26

### Added — Tier 3, feature 1 of 3: WooCommerce compatibility layer

- `inc/woocommerce.php`, `template-parts/woocommerce/cart-icon.php`, `assets/css/woocommerce.css`. Every hook in `inc/woocommerce.php` is wrapped in `class_exists( 'WooCommerce' )` — on a site that never installs the plugin, this costs nothing at all, not just "a little": no theme support added, no styles enqueued, no markup output, nothing on the hook table.
- `add_theme_support( 'woocommerce' )` + gallery zoom/lightbox/slider support.
- Removed WC's default content wrapper (`woocommerce_output_content_wrapper` / `_end`) **without adding a replacement** — the usual snippet for this adds the theme's own wrapper back, but header.php in this theme already unconditionally opens `<main class="site-main" id="content"><div class="inner">` for every template, with footer.php closing it. Adding a WC-specific wrapper back would have nested it inside the one already open, not replaced it.
- Header cart icon (`template-parts/woocommerce/cart-icon.php`), wired into `woocommerce_add_to_cart_fragments` so the item count updates via WooCommerce's own AJAX cart-fragments mechanism — no full page reload after add-to-cart, no custom JS needed on this theme's side for that part.
- `assets/css/woocommerce.css` — separate file, only enqueued inside the same `class_exists()` gate, for the same reason `darkmode-vibe-comments.css` is separate from the main stylesheet: a non-WooCommerce site shouldn't download CSS written for a plugin it doesn't have. Scope: buttons, price colour, sale badge, star ratings, and checkout/account form fields mapped onto this theme's existing design tokens — a compatibility layer matching the theme's look, not a full shop redesign.
- Tier 3 remaining: pricing table component, portfolio/case-studies CPT.

---

## [1.0.59] - 2026-06-26

### Changed

- **Back-to-top button** (`assets/js/back-to-top.js`) — switched from a fixed 400px scroll threshold to 30% of actual scrollable distance (`scrollHeight - innerHeight`), filterable via `gwill_back_to_top_percent` (`inc/enqueue.php`). A fixed pixel count meant something different on a short post than a long one; percentage scales with the page. Recomputes on resize too, not just scroll, since scrollable distance depends on viewport height.

### Removed

- **Staging-environment banner** (`inc/staging.php`, `template-parts/staging-banner.php`) — removed entirely, by request. `functions.php`'s require line, the CSS block, and the documented z-index scale comment are all reverted to their pre-1.0.57 state. This is a file-deletion release — `rsync --delete` on deploy, not a plain unzip, or the now-orphaned `inc/staging.php` and `template-parts/staging-banner.php` will keep loading on the live server.

---

## [1.0.58] - 2026-06-25

### Added — Tier 2, feature 2 of 4: Newsletter signup

- `template-parts/forms/contact-newsletter.php` — the 11th form pattern, reusing the existing nonce/AJAX/honeypot/rate-limit architecture in `inc/forms.php` wholesale, exactly as scoped in the roadmap. One field: email.
- The roadmap's original spec assumed reusing "the SMTP infrastructure already established" for this — that's not actually sufficient on its own. Confirmed: Brevo is still the active provider (reverted back from Exim after deliverability problems — wp_mail via Exim landing in spam was the reason for the original switch away from Brevo), but SMTP credentials and the API key needed to add a contact to a marketing list are two different secrets in Brevo, generated separately, neither substitutes for the other. Building this required the API key + a List ID, not the SMTP login already in `wp-config.php`.
- New function `gwill_brevo_add_contact()` in `inc/forms.php`, calling Brevo's `POST /v3/contacts` with `updateEnabled: true` — a returning subscriber resubmitting the same address merges silently rather than erroring. Gated on two new optional `wp-config.php` constants, documented in the file's existing config block: `GWILL_BREVO_API_KEY`, `GWILL_BREVO_LIST_ID`. Fails gracefully with a translated error (not a fatal) if either is undefined.
- `gwill_handle_contact_form()` branches for `form_id === 'newsletter'` immediately after validation, before the email-send path — a list subscription has no message for anyone to receive by email, so it skips recipient resolution, `wp_mail()`, and autoreply entirely. Rate limiting and the optional submissions-log table still apply, identically to every other form pattern.
- Noted, not changed: the existing `exit_intent` form pattern is documented as "subscriber capture" but currently only emails a notification to the site owner — it was never actually wired to a marketing list. Worth a decision on whether it should also call `gwill_brevo_add_contact()`, but that's a deliberate scope choice for a separate pass, not bundled into this one.
- Tier 2 remaining: table of contents, testimonials CPT.

---

## [1.0.57] - 2026-06-25

### Added — Tier 2, feature 1 of 4: Staging-environment banner

- `inc/staging.php`, `template-parts/staging-banner.php`. Automatic, zero-setup red ribbon shown only when the request host matches a known staging pattern (`.qzz.io`, `.local`, `staging.`, `dev.`, `test.` — filterable via `gwill_staging_domain_patterns`, with `gwill_is_staging_environment` as a full override for edge cases). No Customizer toggle — a staging indicator someone has to remember to manually enable on a clone defeats its own purpose.
- Detection is based on the actual request host (`$_SERVER['HTTP_HOST']`), not `home_url()` — some staging setups deliberately leave the configured site URL pointed at the live domain to dodge asset-URL rewriting, even while genuinely being accessed via a staging host. Request host is what actually answers "what is this browser looking at right now."
- Fixed-position, `z-index: 9990` (documented z-index scale in `style.css` updated accordingly). `.gwill-staging-active` body class adds top padding so it doesn't cover the header on first paint; also bumps the sticky header's own `top` offset by the banner's height so both stay visible together once scrolled, rather than the header re-covering the banner the instant it sticks.
- Tier 2 remaining: table of contents, testimonials CPT, newsletter signup (blocked — roadmap spec assumed Brevo SMTP infrastructure that no longer exists since the contact form moved to Exim; needs a decision before that one starts).

---

## [1.0.56] - 2026-06-25

### Changed

- **Breadcrumbs (and related-posts/category-badge matching) no longer consult any SEO plugin's "primary category" meta at all** — explicit decision, not a bug fix. `gwill_get_primary_category()` now always returns the deepest assigned category and nothing else. Previously (1.0.55), a validly-resolving explicit primary could still override depth — which meant a post with its parent category deliberately marked primary stopped at the parent even with a more specific child also checked, exactly what was happening on the "Android Malware" post. The decision: breadcrumbs should always show the full parent → child path for any post that has both checked, regardless of what any plugin thinks is "primary." Matches the West Construction theme's `single.php` exactly now, not just in spirit — no RankMath/Yoast postmeta read anywhere in this function anymore.
- Practical effect: every post that has a parent + a more specific child category checked will now show both levels in its breadcrumb, full stop. The only posts unaffected are ones with a single category checked, where there's nothing deeper to show anyway.

---

## [1.0.55] - 2026-06-25

### Fixed

- **Primary-category fallback was alphabetical, not sensible** (`inc/helpers.php`, `gwill_get_primary_category()`). 1.0.54 fixed the RankMath meta key itself, but the *fallback* — used whenever that meta is missing or doesn't resolve — was still `$cats[0]`, and `get_the_category()` returns categories alphabetically by name. That's an arbitrary tiebreaker with no relationship to which category is actually most relevant to the post. Two real situations on this site exposed it: (1) a batch of posts carrying a pre-migration primary-category ID that no longer matches any current term, and (2) a new post created by duplicating an old one, which carried the old post's primary-category meta forward even after its categories were changed. Both cases used to silently fall back to an alphabetically-arbitrary category with no indication anything was wrong.
- Replaced the fallback with "deepest assigned category" (most ancestors among the post's actually-checked categories) instead of alphabetical order. This is computed fresh from live taxonomy data every time, so it can't go stale the way a stored ID can — there's nothing to orphan. It also matches the post's real, current category checkboxes rather than a plugin's possibly-stale opinion about them.
- This approach is ported from the West Construction theme's `single.php`, which already solves this correctly. Worth saying plainly: that comparison is what surfaced the actual fix here, and it was the right move to go check.
- One behavior is unchanged on purpose: if a post's saved primary meta resolves to one of its own real, currently-assigned categories — including a deliberately-chosen *parent* over a more specific child — that explicit choice still wins over depth. Depth-based fallback only activates once the saved meta fails to resolve to anything the post is actually tagged with.

---

## [1.0.54] - 2026-06-25

### Fixed

- **Breadcrumbs silently dropping a category level on some single posts** (`inc/helpers.php`) — the actual root cause of the issue left open in 1.0.51–1.0.53. `gwill_get_primary_category()` was reading the RankMath primary-category postmeta under the wrong key: `rank_math_primary_term_category`, which RankMath has never actually written. Because that lookup never matched anything, the function silently fell through to its fallback, `$cats[0]` — the first category in the array `get_the_category()` returns, which WordPress core sorts alphabetically by name, not by what's actually marked primary in the editor. This produced inconsistent-looking breadcrumbs depending on alphabetical luck: if the alphabetically-first category happened to be a top-level one, its ancestors list is empty and the breadcrumb trail looked one level short (`Home › Parent › Title`, skipping a child category that genuinely was assigned to the post); if it happened to be a child category, the trail looked complete only by coincidence, for the wrong reason. Confirmed against two real posts with side-by-side screenshots showing exactly this pattern before fixing. Corrected to the real key, `rank_math_primary_category`.
- This also retires the 1.0.53 "flex-wrap" and "RankMath title-filter" hypotheses for the open breadcrumbs item — both were reasonable given the evidence available at the time (the post's own title was never actually the thing going missing; a *category level* was), but neither was the real cause.

### Note — not a bug, flagging for awareness

- **OG/Twitter social share image still showing the site logo/favicon despite the Customizer's "Default Social Share Image" being set.** This is expected, not a regression: `inc/social-meta.php` correctly no-ops whenever `gwill_seo_plugin_active()` detects an active SEO plugin (RankMath, confirmed active on this site) — see 1.0.50/1.14 in the project history. The theme's Customizer setting has zero effect on this site for that reason; RankMath owns OG/Twitter output entirely here. If the logo/favicon is genuinely what's being served, the fix is on RankMath's side (Rank Math SEO → Titles & Meta → Social, or the per-post Social tab in the RankMath meta box) or a stale Facebook/Twitter scraper cache (use Facebook's Sharing Debugger / Twitter Card Validator to force a re-scrape) — not a theme-level change.

---

## [1.0.53] - 2026-06-21

### Fixed

- **Archive titles rendering raw HTML tags as visible text** (`archive.php`, `author.php`) — a bug I introduced myself two versions ago, in 1.0.52, and a direct contradiction of something I'd argued correctly just before making the mistake. `get_the_archive_title()` deliberately returns a string containing real markup: WordPress core wraps the dynamic portion in a `<span>` (e.g. `Category: <span>Guides &amp; How-tos</span>`) for styling purposes. In 1.0.52 I changed the bare `the_archive_title()` call to `echo esc_html( get_the_archive_title() )`, reasoning that the audit claim about `$before`/`$after` arguments "using WP's internal escaping" was wrong (which it was — both forms run the identical filter chain) — but I drew the wrong conclusion from that correct observation and added escaping where none was safe to add, without first checking what the function's return value actually contains. The result was exactly what a screenshot then showed: `Category: <span>Guides & How-tos</span>` displaying as literal text, tags and all. Fixed with `wp_kses_post()` instead — preserves the legitimate `<span>` while still stripping anything genuinely dangerous. A comment is now in both files explaining why, specifically so a future audit pass doesn't flag this as "needs more escaping" and walk straight back into the same mistake.

### Investigated, not yet resolved — breadcrumbs missing the final crumb on some single posts

Reported as "some posts don't show the full breadcrumb path." Re-verified the actual code character-by-character rather than trusting an earlier read of it (warranted, given the archive-title mistake above was exactly the kind of thing that slips past a confident-but-not-rechecked read):

- The crumb-array-building logic in `gwill_breadcrumbs()`'s `is_single()` branch is confirmed correct — the post-title crumb is added unconditionally, genuinely outside the `if ( $cat )` block.
- The render loop is confirmed correct — nothing in it would skip or hide the final item's text.
- Decisive elimination: the *category archive page* uses this exact same render path for its own "current" item and displays it correctly. If the bug were in this theme's render loop or CSS, it would break identically on both page types — it only breaks on long single-post titles. The H1 further down the same single-post page also displays the title correctly via a separate `get_the_title()` call, ruling out the title itself being empty or filtered to nothing.
- Remaining candidates, neither confirmed: a flex-wrap/height interaction specific to a title long enough to wrap across multiple lines (not reproducible without live DOM inspection), or a plugin (RankMath is active on this site) filtering `the_title`/`get_the_title()` differently in this function's calling context than it does for the page's own H1.
- Next step to actually isolate it: view-source (not "Inspect Element," which shows the live, JS-modified DOM) on an affected post and search for the post's own title text inside the `<nav class="gwill-breadcrumbs">` block. If it's present in the raw HTML, this is a CSS/layout issue, not a PHP one, and the fix belongs in `style.css`. If it's absent, something is altering it before PHP ever outputs it.

---

## [1.0.52] - 2026-06-21

A third-party AI audit (25 numbered findings across security, bugs, HTML/standards, performance, and accessibility) was checked claim-by-claim against the actual code — not accepted or dismissed wholesale. Roughly a third of the findings were confirmed real and fixed here; several "Critical"/"Ship-blocker" claims were checked directly and found to be incorrect, with the evidence documented below so the reasoning isn't lost.

### Fixed — confirmed real

- **`usort` comparator antisymmetry bug** (`single.php`): the category-sort comparator returned `1` for any non-primary category regardless of which category it was being compared against — meaning comparing two non-primary categories in either order both returned `1`, a direct contradiction (a valid comparator can't say A>B and B>A simultaneously). Traced through a concrete 3-category example to confirm before fixing, rather than trusting the description. Produced undefined, unstable ordering for any post with 3+ categories. Fixed by explicitly comparing both sides and returning `0` when neither is primary.
- **Unescaped `the_title()`/`the_archive_title()` — four locations, not the three reported**: `template-contact.php`, `search.php`, and `archive.php` were flagged; a fourth, identical instance in `author.php` was missed by the audit itself. All four now use `esc_html( get_the_title() )` / `esc_html( get_the_archive_title() )`, consistent with the escape-at-output-point standard already followed everywhere else in this codebase. (Severity note: this requires Administrator-level — `unfiltered_html` — access to actually exploit, since WordPress doesn't sanitize that role's content on save; it's defense-in-depth against a compromised admin account, not an any-user stored-XSS the way it was initially framed, but fixing it costs nothing and removes the ambiguity entirely.)
- **`gwill_rewrite_ver` option missing `autoload=false`** (`inc/setup.php`): loaded into the options cache on every single request — admin, frontend, REST, CLI — for a version string that's only ever checked once per deployment. Fixed.
- **Rewrite-flush priority** (`inc/setup.php`): moved from priority 1 to 99 — plugins typically register their own rewrite rules at the default priority 10; flushing before that could miss plugin-registered permalinks on the one request that actually matters (right after a version bump).
- **Escape-at-assignment instead of escape-at-output** (`inc/author.php`): `$saved` was pre-escaped at assignment rather than at the point it's actually echoed — safe today, but fragile against a future refactor silently losing the escape. Now stores the raw value and escapes at output, matching the standard convention used everywhere else.
- **Multistep radio group had no semantic group label** (`template-parts/forms/contact-multistep.php`): a bare `<label>` with no `for` attribute, not associated with anything — screen reader users got three radio buttons with zero group context. Wrapped in `<fieldset>`/`<legend>`, the standard WCAG 1.3.1-compliant pattern for exactly this case. Added a scoped CSS reset since browsers apply a default border/padding to `<fieldset>` that would otherwise change `.gwill-form__field`'s established appearance.
- **Redundant author-box links** (`template-parts/author-box.php`): the author's name and the separate "More posts by Name" link both pointed to the identical URL — two consecutive, identical keyboard/screen-reader stops. Kept the more descriptive "More posts by Name" link as the sole destination (better out-of-context link text per WCAG 2.4.4) and made the name plain text. Cleaned up the now-orphaned `.author-box__name a` / `a:hover` CSS rules this left behind rather than leaving dead selectors in the stylesheet.
- **Capability checked after the nonce, not before** (`inc/setup.php`, `gwill_save_video_meta_box()`): reordered so capability — the authoritative access control — is checked first, nonce — CSRF protection — second. Free change, matches the general defense-in-depth convention.

### Documentation added (no behavior change)

- `inc/forms.php`: a security note on SMTP credential exposure risk in `wp-config.php` constants, and an actionable GDPR retention snippet (a real `DELETE ... WHERE created_at < ...` example, not just a mention that retention is needed) for `gwill_log_submission()`.
- `inc/search.php`: corrected the `gwill_search_args` filter's docblock, which had been actively claiming `$term` was sanitized — it isn't; `$args['s']` is the sanitized value any filter callback should actually use for DB operations. This wasn't just an unclear comment, it was a wrong one.
- `inc/helpers.php`: `gwill_part()` now emits a `WP_DEBUG`-gated `E_USER_WARNING` when a slug doesn't resolve to an actual file, instead of failing silently — a typo'd slug was previously invisible until someone noticed a missing section on the page.
- `inc/faq.php`: swapped `get_the_ID()` for `get_queried_object_id()` in the `wp_head`-hooked schema generator — a harmless clarity improvement, not a correctness fix (see below).

### Audit claims checked and found incorrect

Documented here so the reasoning isn't lost if the same claim resurfaces from a future audit pass:

- **"All 10 forms are missing `wp_nonce_field()` — forms are broken by design," labeled `[Certain]`, ship-blocker #1 — false.** Verified directly: zero forms have a static nonce field, which is correct and deliberate — the nonce is injected via `formData.set('gwill_nonce', nonce)` in `assets/js/forms.js` at submit time, specifically to avoid baking a nonce into LiteSpeed-cached static HTML for anonymous visitors (the entire reason this architecture exists, established and reasoned through extensively in earlier versions). The underlying observation (no static field in the HTML) was accurate; the conclusion drawn from it was not.
- **"Translators comment missing" on `cookie-consent.php`'s `printf()` call — false.** The comment was already present, verified by direct grep against the actual file.
- **"`the_archive_title()` with `$before`/`$after` arguments uses WP's internal escaping; the bare call doesn't" — false.** Both forms run through the exact same `get_the_archive_title` filter chain with no difference in escaping either way; the `$before`/`$after` arguments only add static, developer-controlled wrapper markup outside the filtered title text. Fixed the real underlying concern with `esc_html()` directly instead of the suggested (incorrect) restructuring.
- **`BUG-2`'s stated reasoning — partially incorrect, fix applied anyway.** The claim that "nonce verification is more expensive (a database lookup)" is wrong — `wp_verify_nonce()` is a pure in-memory hash comparison, no DB query involved. The claimed attack scenario (a subscriber with a stolen nonce bypassing capability checks) also isn't reachable for this specific callback — WordPress core already verifies `edit_post` capability before `save_post` ever fires for a given post, in `wp-admin/post.php`, well before this function runs. The reorder was made anyway since it's free and matches the general principle, but the audit's specific justification for *why* doesn't hold up.

### Deferred — valid points, left as a judgment call

Not fixed this version; reasonable to revisit if priorities change:

- Deleting the backwards-compatibility REST nonce endpoint (`inc/forms.php`) — kept deliberately; delete only if no external integration is known to depend on it.
- The dead-code WP 5.5 guard in `inc/faq.php`'s block pattern registration — the theme's stated minimum is WP 6.4, making the guard technically unreachable, but it costs nothing and protects against a site running on an older, locked-down hosting config despite the stated minimum.
- Caching `gwill_reading_time()`'s result as post meta — a reasonable optimization for very high-traffic archive pages, but adds save_post invalidation complexity for what's currently a cheap, object-cache-backed string operation.
- RFC 2047 encoding (or simply omitting the display name) in the autoreply `To:` header for non-ASCII names — a genuine edge case, low priority.
- `the_post_navigation()` without `in_same_term` (cross-category prev/next) — this is a product decision (do you want cross-category navigation or not?), not a defect.
- HTML entities (`&larr;`/`&rarr;`) vs. literal Unicode arrows in pagination strings — both are equally safe; switching is cosmetic polish, not a fix.
- Removing the `X-WP-Total` header from the search REST response — the audit's own assessment was "not a real vulnerability... can be removed" — low priority cleanup.

---

## [1.0.51] - 2026-06-20

### Fixed

- **"Default Social Share Image" setting silently reverted to empty after every save** (`inc/customizer.php`): `'sanitize_callback' => 'absint'` was the cause. If `WP_Customize_Image_Control` ever supplies a URL string rather than a numeric attachment ID, `absint()` runs `intval()` internally — and `intval()` on a string starting with `https://` returns `0`. The setting was being silently zeroed out on save, matching exactly the reported symptom ("I set it, refresh, and it's gone"). Replaced with `gwill_sanitize_image_setting()`, which handles either value type correctly rather than betting on which one the control actually sends.
- Clarified (no code change needed): the Open Graph image defaulting to the site logo on posts with no featured image is RankMath's own fallback behavior, not this theme's — `gwill_seo_plugin_active()` correctly detects RankMath and `inc/social-meta.php` exits before outputting anything at all on a site running it. The Customizer field only has any effect on a site with no SEO plugin active.

---

## [1.0.50] - 2026-06-20

Tier 1 of the feature roadmap (see `GWILL-FEATURE-ROADMAP.md`) — seven features shipped as a single batch, per plan: build a complete tier, test it as a whole, then move to Tier 2.

### Added

- **Open Graph / Twitter Card fallback meta tags** (`inc/social-meta.php`): full `og:`/`twitter:` tag set, output only when no major SEO plugin is detected. New "Default Social Share Image" Customizer control (Site Identity section, next to the logo) provides the fallback image for pages with no featured image of their own. Reuses the existing `gwill-hero` (1200×675) image size rather than registering a new one — already generated for every post with a featured image, close enough to platforms' own ~1200×630 preference that a dedicated size would only add storage cost for a marginal aspect-ratio improvement.

- **FAQ accordion + Schema.org `FAQPage` markup** (`inc/faq.php`): built on WordPress core's native `<details>`/`<summary>` block — already a fully accessible, zero-JavaScript accordion, so no custom block or JS library was built to duplicate it. A new "FAQ Section" block pattern gives editors a ready-to-fill structure; a `DOMDocument`-based scan of `.gwill-faq` content (not regex — far more reliable for real HTML) extracts question/answer pairs and emits matching JSON-LD, so the visible accordion and the schema can never drift out of sync with each other.

- **Cookie consent banner** (`template-parts/cookie-consent.php`): a notice + Accept/Reject, stored in `localStorage`, dismissed once chosen. Deliberately scoped to the notice itself plus a `gwill:cookie-consent-given` DOM event — this theme has no tracking scripts of its own to gate, so building full cookie-category management would solve a problem that doesn't exist yet. Visibility is entirely client-side, the same architecture as the dark-mode toggle: a LiteSpeed-cached page shows the same correct thing to every visitor regardless of cache state. Uses `get_privacy_policy_url()` (WordPress core, Settings → Privacy) rather than a duplicate theme-level setting.

- **Related posts** (`inc/related-posts.php`, `template-parts/related-posts.php`): shown after the author box on `single.php`. Matches by primary category, falling back to any shared category if no primary term is set.

- **Reading time estimate**: added to both `single.php`'s entry-meta and `content.php`'s card meta — the same "show it in both places or it looks like an oversight" reasoning already applied to categories earlier in this project.

- **Back-to-top button** (`template-parts/back-to-top.php`): appears past 400px scrolled, respects `prefers-reduced-motion` for instant vs. smooth scroll.

- **Sticky header** (new Customizer toggle, "Enable sticky header" — default on): adds `.gwill-sticky-header` to `body_class()`; both the CSS and `assets/js/sticky-header.js` are scoped to that class, so the script is harmless to load unconditionally when the toggle is off.

### Changed

- **Extracted `gwill_get_primary_category()`** (`inc/helpers.php`): the RankMath/Yoast-primary-term-aware category-picking logic had been independently duplicated three times (`gwill_breadcrumbs()`, `content.php`, `single.php`) as each was built across earlier sessions. Related posts needed the exact same logic a fourth time — the trigger to finally consolidate rather than copy-paste again. All four call sites now share one function; a future fix to the primary-term logic only ever needs to happen once. `single.php`'s genuinely different behaviour (showing *all* categories, sorted with primary first, rather than just the primary one) was preserved — only the "which one is primary" sub-logic was extracted, not forced into a shape it doesn't fit.

### Fixed (caught during this build, before shipping)

- **`gwill_seo_plugin_active()` didn't exist.** Referenced as if it were an established function from earlier project history; a theme-wide search turned up nothing. Built it for real (`inc/helpers.php`) before `inc/social-meta.php` could have called a function that doesn't exist — would have been a fatal "call to undefined function" on every page load.
- **og:url subdirectory-doubling bug.** An early draft built the non-singular fallback URL from `home_url( add_query_arg( null, null ) )` — `add_query_arg()` with no explicit base defaults to `$_SERVER['REQUEST_URI']`, which already includes any subdirectory prefix; wrapping that in `home_url()` a second time would double it (`example.com/blog/blog/...`) on any subdirectory install. The exact same class of bug already fixed in `search-modal.js` two versions ago, caught here before it shipped a second time. Replaced with proper conditional-tag URL resolution (`get_permalink()`, `get_term_link()`, `get_author_posts_url()`, etc.), with an `is_wp_error()` guard on `get_term_link()`'s result — it can return `WP_Error`, which has no `__toString()` and would fatal on a direct string cast.
- **FAQ schema generator ran the entire `the_content` filter pipeline a second time** on every singular page load, for content that — most of the time — has no FAQ section at all. Caught against my own stated reasoning for the reading-time function (raw `post_content`, not filtered) two features earlier in this same build. Gutenberg stores static blocks' rendered HTML directly in `post_content`; the `<!-- wp:details -->` wrapper comments are editor metadata `DOMDocument` simply ignores. Switched to raw content, with the cheap `str_contains()` bail-out moved before the expensive DOM parse instead of after it.
- **Z-index collisions, caught before writing any CSS.** A planned cookie-banner z-index of 9000 would have exactly matched the existing search modal's z-index; a planned sticky-header z-index of 100 would have exactly matched the existing mobile-nav-dropdown's z-index (technically harmless, since they're different stacking contexts, but confusing to read later regardless). Built a complete, documented hierarchy instead: 50 (sticky header) < 100 (nav dropdown) < 8000 (back-to-top) < 8500 (cookie consent) < 9000 (search modal) < 9100 (search expand) < 9999 (skip-link focus / exit-intent overlay).
- **`.site-header` had no explicit background.** Switching it to `position: sticky` without one would have let scrolling content show through underneath it. Added `background: var(--color-bg)`, scoped to the same `.gwill-sticky-header` class as the rest of the feature.

### Documentation

- **`README.md` was severely out of date — verified directly against the live GitHub repository, not assumed.** It described `functions.php` as five `require_once` lines when the actual file (confirmed by fetching it directly) has had nine for a long time, before this version's three more brought it to twelve. It described the contact form system as choosing between a "FormSubmit.co" backend and a native one — that architecture doesn't exist anywhere in the current ten-pattern AJAX system. It described the author-archive redirect as an unconditional 301 with no opt-out — the exact pre-1.0.42 behaviour, fixed two versions ago and hardened again since. It referenced `phpcs.xml`, `composer.json`, and `.editorconfig` as if they existed; none were actually present in the repository. This had been silently copied forward, completely unedited, through every single version bump this entire project — `CHANGELOG.md` was kept rigorously accurate at every release; `README.md` was never once checked against it. Rewritten from scratch against the actual v1.0.50 file tree, with every numeric claim (file counts, field counts, control counts) verified by direct `grep`/`ls` against the real codebase rather than carried forward from memory — including catching and correcting a "nine require_once lines" claim in the very first draft of this rewrite, which was accurate for the GitHub-verified prior state but already stale the moment this version's own three new `inc/` files were added.
- **Added `phpcs.xml`, `composer.json`, `.editorconfig`** — referenced by the old README as if they existed; they didn't. Built for real: a WordPress Coding Standards ruleset with the three rule exclusions this codebase's actual style already requires (short array syntax, arrow functions, blank lines between hooked callbacks), a `composer.json` declaring PHPCS/WPCS/PHPCompatibilityWP as dev dependencies, and a standard `.editorconfig` (tabs for PHP, 2-space for CSS/JS/JSON, trailing-whitespace preserved in Markdown so intentional `<br>` line breaks survive). Not run in this environment — no `composer`/network access in this sandbox — but syntactically valid and ready to run in any environment that has both.

---

## [1.0.49] - 2026-06-18

Final pre-release audit. Every file in the theme reviewed individually; every cross-cutting pattern (escaping, sanitization, nonce/capability checks, comparison operators, indentation, asset versioning, Yoda conditions, debug statements) checked theme-wide rather than file-by-file. Full findings in the chat response this version shipped with; this entry covers what changed.

### Fixed

- **Form submission errors showed a generic message instead of the server's actual, specific one** (`assets/js/forms.js`): `gwill_handle_contact_form()` deliberately uses `wp_send_json_error( $data, $status_code )` with a non-2xx status (403 for a failed nonce check, 429 for rate-limiting) while still sending a valid JSON body carrying an accurate, already-correct message — e.g. *"Please wait a few minutes before sending another message."* The submit handler threw on any non-2xx status before ever reading that body, discarding the real message in favour of a generic fallback. Restructured to read the JSON body unconditionally and only fall through to the network-failure catch block if the parse itself fails — which is the actual, narrower condition that block is for. This was a real-world reproducible bug, not theoretical: confirmed against a live screenshot showing "Server error. Please try again in a moment." on a correctly-filled exit-intent form, traced to a rate-limit rejection whose real message was never being read.

- **`X-Forwarded-For` rate-limit bypass** (`inc/forms.php`, `gwill_get_client_ip()`) — listed as a deferred audit item since earlier in this project's history, resolved here. The function trusted `HTTP_X_FORWARDED_FOR` (and `HTTP_CF_CONNECTING_IP`) unconditionally. Those headers are only trustworthy when every request genuinely passes through the proxy that sets them — i.e. the origin is firewalled to reject direct connections. On typical shared cPanel hosting, the origin is usually reachable directly unless that firewall rule is explicitly configured. If it isn't, a request straight to the origin lets an attacker set any value for these headers, including a fresh fake IP on every request — completely defeating the per-IP rate limit. Default behaviour is now `REMOTE_ADDR` only (the actual TCP peer address, not spoofable by the client); proxy-header trust requires an explicit opt-in: `define( 'GWILL_TRUST_PROXY_HEADERS', true )` in `wp-config.php`, documented with the precondition for safely enabling it.

- **No way to test forms without tripping the rate limiter** (`inc/forms.php`, `gwill_form_rate_limited()`): added a bypass for `current_user_can( 'edit_posts' )` — the same capability gate `template-contact-demo.php` itself uses. Real anonymous traffic is unaffected; the rate limit only ever stopped applying to people who could already reach the gated demo page in the first place. This is what was actually producing the "Server error" in the screenshot — submitting several demo-page forms in quick succession is exactly what trips a cooldown meant for spam, and there was no exemption for legitimate testing.

- **`index.php` missing breadcrumbs and using old-style pagination**: every other listing template (`home.php`, `archive.php`, `author.php`, `search.php`) calls `gwill_breadcrumbs()` and uses `the_posts_pagination()` with the standardised numbered style. This fallback template — WordPress's last-resort template, easy to forget since it's rarely the one actually hit — had neither. Both added for parity.

- **"View all results" link broken on subdirectory WordPress installs** (`assets/js/search-modal.js`, `inc/enqueue.php`): the link was built from `window.location.origin`, which gives only protocol + domain. On any install running in a subdirectory (`example.com/blog/`), this silently dropped the path, sending the link to `example.com/?s=term` instead of `example.com/blog/?s=term`. Added `homeUrl` (from PHP's `home_url('/')`, which correctly includes any subdirectory) to the `GwillSearch` localized data; `search-modal.js` now uses it, falling back to the old behaviour only if `GwillSearch` isn't defined at all.

- **Author archive disable-redirect used the wrong HTTP status for what it actually is** (`inc/security.php`): the `/author/slug/` redirect that fires when a site owner sets `GWILL_ALLOW_AUTHOR_ARCHIVES` to `false` used a 301 (permanent) status. That redirect's existence is conditional on a toggleable site setting — not a genuine permanent URL move — and 301's "cache this forever" semantics work directly against that: a browser (Chrome especially holds 301s in its own redirect cache well past a normal cache-clear) that already cached the redirect would keep landing on the homepage even after the setting changes, or after a version upgrade changes whether the redirect fires at all — a "fixed in code, browser won't let go of the old behaviour" failure mode indistinguishable from the underlying bug never having been fixed. Changed to 302. The unconditional `?author=N` enumeration block immediately above this one is genuinely permanent, unconditional behaviour — it correctly stays 301.

- **Inconsistent LCP priority for the same hero slot** (`template-parts/featured-image.php`): the YouTube video embed used `loading="lazy"` while the image branch immediately below — for the exact same template position — explicitly uses `fetchpriority="high"` + `loading="eager"` + `decoding="sync"`, with documented reasoning for why. A video set on a single post is, by definition, always above the fold; lazy-loading it worked against fast LCP for the one case it's guaranteed to matter, with no comment suggesting this was a deliberate tradeoff rather than an oversight. Changed to `eager`, with the counter-argument (YouTube's embed is genuinely heavy; some teams deliberately defer it even above the fold for CPU-cost reasons) documented in a comment so the choice is informed and reversible rather than silently one way or the other.

### Changed

- **`wp_unslash()` consistency** (`inc/setup.php`, `gwill_save_video_meta_box()`): the video-meta-box nonce and URL were read from `$_POST` without `wp_unslash()` first — functionally low-risk here (nonces are hex-like; `esc_url_raw()` filters aggressively) but inconsistent with the wp_unslash()-then-sanitize pattern correctly used everywhere else in this codebase (e.g. `inc/author.php`). Brought in line.

### Verified clean (no changes needed)

- Zero loose (`==`/`!=`) comparisons anywhere in the theme — strict comparison discipline confirmed throughout.
- Zero Yoda-condition violations.
- Zero space-indented PHP files — tabs used consistently throughout.
- Zero leftover debug statements (`var_dump`, `print_r`, `console.log`, `debugger`) anywhere.
- `error_log()` appears in exactly the one documented, intentional case (`wp_mail` failure logging under `WP_DEBUG`).
- The one raw `$wpdb` query (`gwill_log_submission()`'s table-existence check) correctly uses `$wpdb->prepare()` with a placeholder — no SQL injection surface.
- Every `wp_enqueue_*`/`wp_register_*` call traces its version argument to the actual theme version (`wp_get_theme( get_template() )->get( 'Version' )`) — no hardcoded or missing cache-busting version anywhere.
- `the_post_thumbnail()` calls outside the explicit LCP-priority case (`content.php`'s repeating post-card thumbnail) correctly rely on WordPress core's own automatic lazy-loading threshold rather than needing an explicit override.
- Field-name parity across all 10 contact-form patterns (re-confirmed; originally verified in 1.0.43).
- Nonce action-string consistency between creation and verification (re-confirmed).
- `darkmode.css` / `darkmode-vibe-comments.css` reference only CSS custom properties that are actually defined in `style.css` — no undefined-token usage in either dark-mode file.

---

## [1.0.48] - 2026-06-18

### Fixed

- **Default WordPress comment list — stray "1." before threaded replies** (`style.css`): `comments.php` correctly wraps `wp_list_comments()` in `<ol class="comment-list">`, and the existing `.comment-list { list-style: none }` rule correctly suppressed numbering on that outer list. What it didn't cover: WordPress core's `Walker_Comment::start_lvl()` generates a *separate*, nested `<ol class="children">` for every threaded-reply level — a different class entirely, untouched by the original selector. That nested list fell back to the browser's native decimal numbering and restarted at "1" for every reply thread, while top-level comments correctly showed nothing. Fix: the reset now matches `.comment-list` and any nested `<ol>` at any depth (`.comment-list, .comment-list ol`). Confirmed against the actual markup WordPress generates here — `'comment-list'` and `'comment-form'` are both declared in this theme's `add_theme_support('html5', [...])` call (`inc/setup.php`), which guarantees the well-documented `html5_comment()` output structure rather than an older fallback format.

- **Undefined `--color-text` token** (`style.css`): found while auditing the comment CSS — three existing rules (`.gwill-share__heading`, `.page-numbers`, `.gwill-breadcrumbs__item--current span`) referenced `var(--color-text)`, a custom property that is never defined anywhere in this theme's token system (`:root` defines `--color-primary` for default text colour; `--color-text` doesn't exist). An undefined custom property with no fallback computes as if `unset` — for an inherited property like `color`, that resolves to whatever the parent's computed colour happens to be, which coincidentally looked correct in the current page structure but would silently break the moment any of these elements is ever nested somewhere the inherited colour doesn't match the intended one. All three corrected to `var(--color-primary)`, the token that's actually defined.

### Added

- **Clean visual treatment for WordPress's default comment markup** (`style.css`), so the comment list looks intentional rather than bare browser-default when the Vibe Comments plugin isn't active:
  - `.comment-author.vcard` — flex row (avatar beside name, replacing the unstyled stacked block layout), matching the existing `.author-box` flex convention elsewhere in this file.
  - Avatar: circular (`border-radius: 50%`) with a 1px border in `var(--color-border)`, so WordPress's default "mystery person" placeholder doesn't render as a stray light square against a dark background.
  - Author name (`.fn`): styled on both the bare and linked form, since `get_comment_author_link()` only wraps the name in `<a>` when the commenter has a URL set — styling only `.fn a` would have left unlinked names inheriting the muted `.comment-meta` colour instead of full-weight text colour.
  - Date/edit-link row (`.comment-metadata`): muted by default, accent-coloured on hover; a middle-dot separator added before "Edit" via `::before` rather than relying on WordPress's own (inconsistent across versions) separator output.
  - `.comment-awaiting-moderation`: a left accent-stripe notice, same visual language as `.author-box`'s `border-top: 3px solid var(--color-accent)`, so it reads as a distinct system message rather than part of the comment text.
  - `.comment-reply-link`: accent-coloured, semi-bold, underline on hover/focus — replacing the bare default link styling.
  - Reply threads (`.comment-list .children`): now that the buggy native numbering is gone, a modest left indent + left border gives genuine threaded replies a visual cue distinguishing them from top-level comments — selector specificity confirmed to correctly override the new `padding: 0` reset above it.

  Every new rule uses existing design tokens only (`--color-primary`, `--color-muted`, `--color-accent`, `--color-border`) — no new tokens introduced, and no companion `darkmode.css` changes needed, since `darkmode.css` works purely by redefining these same token values under `[data-theme="dark"]` / `prefers-color-scheme` — confirmed by reading its actual structure before writing a single rule, rather than assuming.

---

## [1.0.47] - 2026-06-17

### Fixed

- **Exit-intent overlay close button — real root cause found** (`assets/js/form-exit-intent.js`): Two earlier hypotheses (a `type="submit"` default, an `e.target.matches()` vs `closest()` mismatch on a wrapped icon) were both checked against the actual markup and ruled out — `contact-exit-intent.php` already used `type="button"` and a bare `&times;` text node with no child element, so neither applied. The real cause: the close-button and Escape-key listeners were registered *after* `if ( alreadySeen() ) return;` — the same early-return that's supposed to gate only the automatic mouseleave/scroll triggers. On any page load within the 7-day respawn window of a previous trigger (the normal case the moment this feature is tested more than once), the whole script exited before either listener was ever attached. The overlay could still be forced open through any code path that doesn't go through `show()` — which is exactly what the Contact Demo page's manual trigger button does, by toggling `hidden`/`aria-hidden` directly — leaving a visible overlay with a structurally non-functional close button. Fix: close-button and Escape listeners now live above the throttle guard and are always attached; only the mouseleave/scroll auto-triggers are gated by `alreadySeen()`. Also hardened the close-button click check from `e.target.matches(...)` to `e.target.closest(...)` so this stays correct if the `&times;` glyph is ever replaced with a wrapped SVG icon (the close-button class on the project already uses SVGs for other icon buttons — `matches()` would silently break the moment that pattern is applied here too).

- **"Network error" on form submission — three contributing causes, all addressed**:
  1. **Cache staleness (most likely actual cause; verify first):** `GwillForms.nonceUrl` is written into the page's inline `<script>` by `wp_localize_script()` — it's part of the cached HTML, not a separately-versioned asset file with its own cache-busting query string. If LiteSpeed's full-page cache wasn't purged after the v1.0.46 deploy, a previously-cached page is still serving the *old* REST-endpoint URL regardless of what's in the new theme files. **Verify:** view-source on the live page and search for `nonceUrl` — if it contains `/wp-json/`, the cache wasn't purged. **Fix:** purge LiteSpeed's full page cache (and object cache) after every deploy that touches `inc/enqueue.php`, not just re-upload the zip.
  2. **No pre-baked nonce for logged-in users** (`inc/enqueue.php`, `assets/js/forms.js`): Every submission — including the most common real-world case of testing your own site while logged in — required a network round-trip before the actual submit could even begin, with no fallback if that round-trip failed for any reason. Logged-in pages are never served from LiteSpeed's page cache (every cache layer skips authenticated requests by default), so a nonce baked directly into the page's own HTML at request time is guaranteed fresh. `GwillForms.nonce` is now populated server-side for logged-in users only (`wp_create_nonce()` at render time); `forms.js`'s new `getNonce()` checks this first and skips the fetch entirely when present. Anonymous visitors — whose pages genuinely can be cached for hours — still use the network fetch, now with a `Date.now()` cache-busting parameter appended at request time so no intermediate caching layer can serve a stale response regardless of whether it honours admin-ajax.php's usual cache exclusion.
  3. **Unprotected JSON output** (`inc/forms.php`): Neither `gwill_ajax_get_nonce()` nor `gwill_handle_contact_form()` cleared the output buffer before calling `wp_send_json()`. Any stray output earlier in the request — a PHP notice, a deprecation warning from an unrelated plugin hook, even accidental leading whitespace — would prepend to the JSON body on an otherwise-200 response. `fetch().json()` then throws a `SyntaxError`, which was previously indistinguishable from a true connectivity failure in the UI. Both handlers now call `ob_clean()` when an output buffer is active, immediately before sending JSON.

  `forms.js`'s error handling also no longer collapses every failure into one generic string: nonce-fetch HTTP errors, submission HTTP errors, JSON parse failures, and true connectivity failures (`fetch()` itself rejecting) now produce distinct, more actionable messages, and the real underlying error is always logged to the console for diagnosis regardless of which user-facing message is shown.

  `gwillGetNonce()` is exposed on `window` so any future form-specific script can share this exact logic rather than risk an independent, divergently-maintained copy — though the audit below confirms neither existing specialized script (multistep, exit-intent) currently has one.

- **Stale code comments**: `forms.js`'s file header still described the REST endpoint as the active nonce source, a year-old holdover from before the v1.0.46 admin-ajax migration that was never updated when the code changed. Same issue in two docblocks in `inc/forms.php`. All three corrected to describe the current (admin-ajax + pre-baked) architecture — stale comments describing already-replaced behaviour are exactly what causes the next debugging pass to start from the wrong assumption.

### Changed

- **Dark mode — fully inlined, no external script dependency** (`inc/darkmode.php`): All toggle behaviour — initial theme resolution, background pre-set (eliminates the white-canvas flash for dark-mode users), click handler, ARIA sync, and OS-preference-change listener — now lives in one inline `<script>` block output directly in `<head>`, plus a small inline `<style>` block carrying the critical `color-scheme`/`background-color` tokens. Root cause of the original flash: LiteSpeed Cache's "Load JS Deferred" setting can delay *external* `<script>` execution until after first user interaction (scroll, tap) on some devices — confirmed against this exact site. Inline blocks aren't subject to that delay. `template-parts/ui/darkmode-toggle.php` no longer calls `wp_enqueue_script('gwill-darkmode')`; the handle stays registered in `inc/enqueue.php` for backwards compatibility (harmless — registering without enqueuing loads nothing) but nothing in the theme uses it. The button template now exposes translated ARIA label text via `data-label-dark`/`data-label-light` attributes so the inline script doesn't hardcode English strings. `assets/js/darkmode.js` is marked `@deprecated` in its docblock and kept for reference only.

### Verified (theme-wide audit)

- **Apostrophe-in-single-quoted-string bug class** (the cause of the `contact-partnership.php` fatal in v1.0.45): grepped every `.php` file in the theme for single-quoted strings containing an unescaped contraction. Zero hits outside the already-fixed file — confirmed isolated, not a pattern.
- **Field-name parity across all 10 form patterns**: cross-checked every `name="gwill_*"` attribute in every `template-parts/forms/contact-*.php` file against the corresponding entry in `gwill_get_required_fields()`. All 10 patterns have complete parity — no template is missing a field its own required-fields entry expects (which would silently fail validation on a correctly-filled form), and no `form_id` hidden-field value is mismatched against its map key.
- **Nonce-fetch architecture**: confirmed `assets/js/form-multistep.js` and `assets/js/form-exit-intent.js` have no independent nonce-acquisition code of their own — both correctly delegate actual submission to `forms.js`'s shared `.gwill-form` submit handler. The "network error" was never caused by duplicated, divergently-patched logic in those two files.
- **Nonce action-string consistency**: `wp_create_nonce('gwill_contact_form')` (both nonce endpoints) and `check_ajax_referer('gwill_contact_form', 'gwill_nonce', false)` (the submission handler) use the same action string — confirmed, no mismatch.
- **Brace/structure sanity pass on every file touched this version**: `inc/forms.php` initially appeared to have 3 unbalanced braces under a naive text-level count; traced to two docblock comments illustrating the literal `{` character in prose and one string-literal `'{'` passed as a `str_starts_with()` comparison argument — all three confirmed benign, no actual unclosed code block.

---

## [1.0.46] - 2026-06-16

### Fixed

- **Dark mode white flash** (`inc/darkmode.php`): The remaining brief white-canvas flash for dark-mode users is eliminated by pre-setting `background-color` directly on `<html>` inside the inline JS: `document.documentElement.style.background='#0f172a'`. This runs synchronously at HTML parse time — no CSS file, no async gap, no dependency on LiteSpeed's async CSS loading. For light-mode users the browser canvas default is already white, so no pre-set is needed. The v1.0.45 inline `<style>` handles the `color-scheme` and explicit `[data-theme]` attribute cases; this JS addition covers the canvas flash that precedes them.

  The remaining < 50 ms of white that some users on very slow connections may still observe is the browser's own window-allocation blank before HTML parsing begins. That gap is inherent to traditional HTML pages (requires a service worker to eliminate) and is imperceptible on any connection above ~500 KB/s.

- **"Network error" on all demo contact forms** (`inc/forms.php`, `inc/enqueue.php`): All demo page forms were returning "Network error" because `forms.js` fetches a fresh nonce from `GwillForms.nonceUrl` before every submission. That URL was `/wp-json/gwill/v1/form-nonce` (REST API). Logged-in users hit `rest_cookie_check_errors` (priority 100 on `rest_authentication_errors`) which requires an `X-WP-Nonce` header for any request made with auth cookies — the fetch had no such header → 403 → `!res.ok` → throw → catch → "Network error."

  The `rest_authentication_errors` filter added in v1.0.43 was supposed to short-circuit this at priority 99, but LiteSpeed may have cached an earlier 403 response from the REST endpoint, causing every subsequent nonce fetch to receive the cached 403 regardless.

  Fix: switched the nonce endpoint from REST to `wp_ajax_nopriv_gwill_get_nonce` / `wp_ajax_gwill_get_nonce` (admin-ajax.php). Two reasons this works: (1) LiteSpeed Cache excludes `admin-ajax.php` from caching by default — nonces are always fresh; (2) admin-ajax.php has no REST API cookie-auth layer. Response shape is `{ "nonce": "..." }` — identical to the old REST endpoint — so `forms.js` needed no changes. The REST endpoint is kept for backwards compatibility but is no longer called by `forms.js`.

---

## [1.0.45] - 2026-06-16

### Fixed

- **Contact Demo critical error — actual root cause** (`template-parts/forms/contact-partnership.php`): PHP parse error (E_PARSE) on line `esc_html_e( 'Custom — let's discuss', ... )`. The em dash string contained a literal apostrophe in `let's` inside a single-quoted string — PHP tokeniser ended the string at the `'` in `let's`, leaving `s discuss', 'gwill-starter'` as unparseable code. Fatal before any output. v1.0.44 fixed the wrong file. This fixes the correct file: changed to a double-quoted string `"Custom \u{2014} let's discuss"` using PHP 7+ Unicode escape for the em dash.

- **Dark mode flash on page reload — complete fix** (`inc/darkmode.php`): The `defer` removal in v1.0.44 addressed the button-handler timing. This addresses the visual flash root cause: LiteSpeed Cache "Load CSS Asynchronously" delays external CSS until after first paint, so the browser's native dark rendering (driven by OS color scheme, not the theme CSS) shows for that window. `gwill_darkmode_head_script()` now also outputs a minimal inline `<style>` block immediately after the JS. The style block mirrors the two tokens that drive the flash — `color-scheme` and `background-color` — for all three theme states (explicit dark, explicit light, system preference). These apply synchronously before any external CSS loads, eliminating the gap that caused the flash. Values match `darkmode.css` exactly: light `#ffffff / #111111`, dark `#0f172a / #f1f5f9`.

- **Pagination alignment** (`style.css`): Changed `.pagination .nav-links` from `justify-content: flex-start` to `justify-content: center`, matching `.search-results__pagination .nav-links` in `search.css`. All paginated views now render identically.

---

## [1.0.44] - 2026-06-16

### Fixed

- **Auto-reply sends raw HTML tags** (`inc/forms.php`): `gwill_send_autoreply()` hardcoded `Content-Type: text/plain` even when the `gwill_autoreply_message` filter returned HTML content. Email clients rendered `<p>` tags literally instead of as formatted text. Fix: `preg_match('/<[^>]+>/', $message)` detects HTML in the filtered message body and switches to `Content-Type: text/html` automatically. Plain text auto-replies (the default) are unchanged.

- **Archive pagination buttons spreading full width** (`style.css`): The base `.nav-links { justify-content: space-between }` rule was not overridden in `.pagination .nav-links`, causing numbered page buttons to spread across the full container width. Fix: `justify-content: flex-start` added explicitly to `.pagination .nav-links`.

- **Contact Demo template critical error** (`template-contact-demo.php`): Two bugs. First, `'id' => 'feedback'` was calling `gwill_part('forms/contact-feedback')` but the file is `contact-post-feedback.php` — in WordPress 5.5+ a missing `get_template_part()` target triggers `E_NOTICE` which on this server config promotes to a fatal. Corrected to `'id' => 'post-feedback'`. Second, the template had no `while(have_posts()): the_post(); ... endwhile;` loop, leaving the global `$post` context only partially initialised (query setup but not loop setup). Template parts calling `is_singular()`, `get_the_title()`, `the_ID()` etc. need the full loop context. Loop added. Inline `onclick` JS also hardened to null-check the overlay element before calling methods on it.

- **Dark mode flash on page reload** (`inc/enqueue.php`): `darkmode.js` was registered with `strategy: 'defer'`. Chrome on Android optimises deferred scripts by delaying execution until after first user interaction (scroll, tap) in certain conditions — power-saving mode, slow parse, low-priority background tab. This caused the device system preference (dark) to show on every page load until the user scrolled, at which point `applyTheme(resolveTheme())` ran and applied the stored `localStorage` preference (light). The flash-prevention inline script in `<head>` was setting `data-theme` correctly, but `darkmode.js` — the only thing that syncs ARIA state and attaches the toggle click handler — was not executing until interaction. Fix: removed `strategy: 'defer'` from the registration. Script now runs synchronously at end of `<body>`, immediately on every page load.

---

## [1.0.43] - 2026-06-15

### Added

- **Breadcrumbs** (`inc/helpers.php`, all templates): `gwill_breadcrumbs()` renders an accessible `<nav aria-label="Breadcrumb">` with Schema.org BreadcrumbList microdata. Handles single posts (with primary-category ancestry walking, honouring RankMath/Yoast `_primary_term` meta), pages (full hierarchy), categories, tags, author archives, search, 404, date archives. Hidden on `is_front_page()`. Filterable via `gwill_show_breadcrumbs` for SEO-plugin swaps. Called in `home.php`, `archive.php`, `author.php`, `search.php`, `single.php`, `page.php`.

- **Unified numbered pagination** (`home.php`, `archive.php`, `author.php`): All list templates now use `the_posts_pagination()` matching the numbered style already on `search.php`. Previous text-link `the_posts_navigation()` removed from those three templates. CSS added for `.page-numbers`, `.page-numbers.current`, `.page-numbers.dots`, prev/next variants.

- **Category meta on cards** (`template-parts/content.php`): Primary category displayed as an uppercase accent-colour badge above the date in every post card. Honours RankMath `rank_math_primary_term_category` and Yoast `_yoast_wpseo_primary_category` post meta; falls back to `$cats[0]`.

- **Category and tags on single posts** (`single.php`): Categories added inline in `.entry-meta` (all assigned categories, comma-separated, primary first). Tags rendered as pill links in `.entry-tags` between `</article>` and the footer share row. Both use the same RankMath/Yoast primary-term meta awareness as cards.

- **Logo width Customizer control** (`inc/customizer.php`, `assets/js/customizer-preview.js`): "Logo width (px)" number input added to Appearance → Customize → **Site Identity** — the same section as the logo and favicon uploads, matching the UX of commercial themes. Range 20–400 px, default 160 px, `postMessage` transport for live preview. Applied via `--logo-width` CSS custom property; sanitised by `gwill_sanitize_logo_width()`.

- **Logo width CSS variable** (`style.css`): `.custom-logo { max-width: var(--logo-width, 160px) }` replaces the old `max-width: 100%`. Inline `:root { --logo-width: Npx }` is written by `wp_enqueue_scripts` at priority 20 only when the saved value differs from the default, keeping the stylesheet clean.

### Fixed

- **Contact form "Network error" for logged-in users** (`inc/forms.php`): The REST nonce endpoint `GET /wp-json/gwill/v1/form-nonce` returned 403 for any logged-in user. Root cause: WordPress's `rest_cookie_check_errors` (hooked to `rest_authentication_errors` at priority 100) fires when auth cookies are present but no `X-WP-Nonce` header is sent. The JS `fetch(nonceUrl)` sends no such header → 403 → `!res.ok` throws → `.catch()` shows "Network error." Incognito has no auth cookies so was unaffected. Fix: a `rest_authentication_errors` filter at priority 99 returns `true` for `REQUEST_URI` matching `/gwill/v1/form-nonce` when no prior auth decision has been made, short-circuiting the cookie check before it fires. No JS changes.

- **Conditional nav rendering** (`header.php`): The `<nav>` element (including hamburger) is now only rendered when a menu is assigned to the `primary` location via `has_nav_menu('primary')`. Previously the empty `<nav>` remained in the DOM on sites without a menu, causing the logo to appear oversized in the flex header with no right-side counterpart. No menu assigned = no `<nav>` = header renders branding + darkmode toggle + search only.

---

## [1.0.42] - 2026-06-15

### Fixed

- **Author archive redirect** (`inc/security.php`): Author archive pages
  (`/author/slug/`) were redirecting to the homepage because
  `GWILL_ALLOW_AUTHOR_ARCHIVES` defaulted to `false` — silently killing the
  full author archive system (`author.php`, `inc/author.php`, social fields)
  that ships with this starter.

  **Root cause:** The `?author=N` enumeration attack and the `/author/slug/`
  archive page were conflated into a single redirect that disabled both. They
  are different threats requiring different responses.

  **Fix:**
  - Default changed to `true` — author archives work out of the box.
  - `?author=N` (numeric GET parameter) is blocked unconditionally at priority 1,
    before `redirect_canonical` (priority 10) can forward it to `/author/loginname/`.
    This is the actual enumeration vector; blocking it is sufficient.
  - `/author/slug/` loads normally via `author.php`. Set
    `define( 'GWILL_ALLOW_AUTHOR_ARCHIVES', false )` in `wp-config.php` to
    disable it on single-author sites that don't want any author URL.

---

## [1.0.41] - 2026-06-14

### Added

- **REST nonce endpoint** (`GET /wp-json/gwill/v1/form-nonce`): Returns a fresh
  `gwill_contact_form` nonce on every request. REST bypasses LiteSpeed HTML
  cache, so the nonce is always valid — unlike one baked into cached page HTML
  which expires silently after 12–24 hours.
- **`wp_mail_from_name` / `wp_mail_from` filters** in `inc/forms.php`: Sender
  name defaults to `get_bloginfo('name')` (overridable via `GWILL_FROM_NAME`
  constant); From address is only overridden when `GWILL_FROM_EMAIL` is
  explicitly set. Applies whether SMTP is active or not.

### Changed

- **Contact forms — cache-safe nonce** (`assets/js/forms.js`): Two-step submit.
  JS fetches a fresh nonce from the REST endpoint immediately before posting,
  then injects it into FormData. Nonce field (`wp_nonce_field`) removed from
  all 10 form templates — no longer baked into HTML at render time.
- **Email format** (`inc/forms.php`): Notifications switched from plain text to
  branded HTML. Dark `#111111` header with site icon + name; one card per field
  (small-caps label, left-bordered value); submission timestamp + referring
  page; light footer. Content-Type changed to `text/html; charset=UTF-8`.
- **`inc/enqueue.php`**: `GwillForms` localised data gains `nonceUrl` pointing
  to the REST endpoint alongside the existing `ajaxUrl`.
- **Share buttons — complete rebuild** (`template-parts/share-button.php`,
  `style.css`, `assets/js/main.js`): Replaced toggle + circle icons with
  filled pill buttons (icon + name text). Platforms: X · Facebook · WhatsApp ·
  LinkedIn · "More". "More" triggers `navigator.share()` (system share sheet on
  mobile) with Clipboard API fallback on desktop. Two sizes: compact top-of-post
  row (`.gwill-share--top`) and large footer row (`.gwill-share--footer`).
  Dark mode: X pill inverts to `#e7e9ea / #0f1419`; "More" uses
  `rgba(255,255,255,0.12)`.
- **`EMAIL-SETUP.md`**: Rewritten. Server mail (Exim via cPanel) positioned as
  the first step — test it before adding SMTP. SMTP documented as optional
  enhancement. Brevo de-emphasised to one entry in a provider comparison table.
  HTML email format and REST nonce strategy documented.

---

## [1.0.40] - 2026-06-14

### Fixed

- **Threads icon**: Previous path rendered as a broken circular arrow. Replaced with the Bootstrap Icons verified path (MIT, `icons.getbootstrap.com/icons/threads/`, 16×16 viewBox). All other icons remain 24×24.

### Changed

- **Share button — two-mode architecture**: Template part now accepts `gwill_share_mode` via `set_query_var`.
  - `header` (default, top of post): compact toggle pill, `margin-block-end: 1.75rem` to breathe before content. Platforms reveal on click.
  - `footer` (bottom of post): no toggle — platforms always visible. Labelled with "SHARE THIS ARTICLE" (small-caps muted heading). Top border divider with `2rem` padding separates it cleanly from the article body. Standard pattern used by HubSpot, Moz, Buffer, and most professional blog themes.
- **Icon size reduced to 30px** (`1.875rem`): 8 icons × 30px + 7 × 6px gap = 282px — fits in one row on a 360px mobile viewport without wrapping. Email no longer drops to a second row alone.
- **SVG fully self-contained per platform**: each icon string now includes its own `<svg>` element with the correct `viewBox`, removing the per-platform width/height attributes in the render loop.

---

## [1.0.39] - 2026-06-14

### Changed

- **Share buttons visual redesign**: Platform icons now show their brand colours by default (not just on hover). Hover state fills each circle with its brand colour + white icon + 2px translateY lift + box-shadow. Toggle pill uses `var(--color-text)` with `font-weight: 500`, slightly larger padding. Circle size increased to 2.25rem. Dark mode overrides for X and Threads (both use near-black on light).
- **Pinterest added** (`gwill-share__link--pinterest`, `#e60023`): Share URL `pinterest.com/pin/create/button/`. SVG path includes its own outer circle; rendered with `fill-rule="evenodd"` so the P mark shows as a transparent cutout against the filled circle.
- **Threads added** (`gwill-share__link--threads`, `#000`/`#e7e9ea` dark): Share URL `threads.net/intent/post`. Path from simple-icons. Dark mode inverts to near-white to match Meta's dark-mode brand presentation.

---

## [1.0.38] - 2026-06-14

### Changed

- **Header element order**: darkmode toggle and search icon now appear between the logo and the hamburger. New order: logo → darkmode → search → nav (hamburger). DOM order matches visual order.
- **Share buttons** (`template-parts/share-button.php`): Replaced Web Share API button with a collapsible row of social sharing links — X, Facebook, LinkedIn, WhatsApp, Reddit, Email. All platforms are plain `<a>` tags, no external scripts. Toggle uses `aria-expanded` + CSS; JS in `main.js` is 8 lines. Placed twice in `single.php`: top of post (collapsed by default, immediately after entry-meta) and bottom of post (open by default, after `</article>` before author box). Bottom instance uses `set_query_var('gwill_share_expanded', true)` to set `aria-expanded="true"` at render time.
- **Share button removed from entry-meta**: `entry-meta__sep` and inline `gwill-share__btn` stripped from `single.php` entry-meta block. Meta is now clean: `By Author — Date`.

---

## [1.0.37] - 2026-06-13

### Changed

- **Share button** moved from standalone block between article and author box to inline inside `entry-meta` (same line as By Author — Date). Restyled: no pill border, no background — plain inline icon + "Share" text matching entry-meta typography. `template-parts/share-button.php` retained for future use but no longer called from `single.php`; button HTML is now directly in the entry-meta block.
- **Author box mobile layout**: removed `@media (max-width: 480px)` block that forced `flex-direction: column; align-items: center; text-align: center` on `.author-box` and `.author-archive-hero`. Side-by-side layout (80px avatar left, content right) is maintained at all screen widths — layout works cleanly at 360px.

---

## [1.0.36] - 2026-06-13

### Added

- **`template-parts/share-button.php`** (new file): Share button using Web Share API with Clipboard API fallback and execCommand last resort. Zero new enqueue calls — handler appended to `assets/js/main.js`. Renders after post content, before author box on single posts. Token-based CSS; no dark mode additions needed.
- **YouTube video embed**: Set `_gwill_video_url` post meta via the new "Video Embed" meta box in the post editor sidebar to replace the featured image with a responsive 16:9 embed (`youtube-nocookie.com`). Suppressed in archive/loop context. `gwill_youtube_id()` helper added to `inc/helpers.php`; meta box registered in `inc/setup.php`.

### Fixed

- **Author archive redirect**: Replaced `after_switch_theme` rewrite flush (never fires on file-upload deployments — only triggers on theme activation via WP admin UI) with a version-based `init` hook (`gwill_maybe_flush_rewrites`) that runs once on the first page load after any version bump. **User action still required after deploy**: LiteSpeed Cache → Purge All + Settings → Permalinks → Save Changes.
- **Social icon alignment**: Added `align-items: center` to `.author-box__socials`; changed `.author-box__social-link` from `display: inline-flex` to `display: flex`. Fixes icon shift when author bio is present.

---

## [1.0.35] - 2026-06-13

### Added

- **`inc/author.php`** (new file, required in `functions.php`): Centralises all author-related helpers.
  - `gwill_author_social_fields()` — canonical array of social platform definitions (Website/globe, Twitter/X, LinkedIn, GitHub, Instagram, Facebook, YouTube). Each entry carries its meta key, admin label, placeholder URL, inline SVG icon, and accessible aria label. Add/remove platforms here; the admin screen and templates update automatically.
  - `gwill_get_author_socials( $user_id )` — template helper; returns filtered array of `[ url, icon, aria ]` for platforms where the user has a non-empty URL. Safe to iterate directly — empty platforms are excluded.
  - `gwill_render_social_profile_fields()` — renders a "Social Links" `<table class="form-table">` section on the user profile admin screen (`show_user_profile` + `edit_user_profile` hooks). Website/user_url is excluded (WP already shows it in Contact Info). Outputs a nonce field scoped to the user ID.
  - `gwill_save_social_profile_fields()` — saves on `personal_options_update` + `edit_user_profile_update`. Three-layer security: `current_user_can('edit_user')`, nonce verify, `esc_url_raw()` sanitization. Deletes meta on empty value rather than storing empty strings.

- **Social icons in author box** (`template-parts/author-box.php`): `.author-box__socials` row of circular icon links appears below the "More posts by" link when the author has any social URLs set. Website reads from `user_url`; all others from custom meta. Icons are `target="_blank" rel="noopener noreferrer"` with `aria-label` per platform.

- **Social icons in author archive hero** (`author.php`): Same `.author-box__socials` row added inside `.author-archive-hero__info`, below the bio.

- **Social icon CSS** (`style.css`): `.author-box__socials` flex container and `.author-box__social-link` circular icon buttons. All colors use existing tokens (`--color-border`, `--color-muted`, `--color-accent`) — no dark mode overrides required. 32px circular border + `color: var(--color-muted)` default; hover lifts to `--color-accent` on both color and border. Focus-visible ring matches theme standard.

### Fixed

- **Search results pagination dark mode** (`assets/css/search.css`): `.page-numbers.current` was using `background: var(--color-primary)` which flips to `#f1f5f9` (near-white) in dark mode, producing a white box with invisible white text. Now uses `var(--color-btn-bg)` / `var(--color-btn-text)` — same token pair used by all other buttons since v1.0.34. `.page-numbers:hover` hardcoded `#f3f4f6` replaced with `var(--color-border)` / `var(--color-border-input)` for correct dark mode hover.

- **Author archive redirect** (`inc/setup.php`): Added `after_switch_theme → flush_rewrite_rules()`. Ensures WordPress re-registers all rewrite rules (including author archive slugs) on theme activation. **If the author archive still redirects to homepage after deploying this version:** the root cause is a cached redirect in LiteSpeed Cache. Fix: LiteSpeed Cache plugin → Manage → Purge All, then Settings → Permalinks → Save Changes.

---

## [1.0.34] - 2026-06-13

### Added

- **`--color-btn-bg` / `--color-btn-text` CSS tokens** (`style.css`, `darkmode.css`): New dedicated button tokens replace the incorrect use of `--color-primary` as a button background. In light mode: `--color-btn-bg: #2563eb` (matches `--color-accent`), `--color-btn-text: #ffffff`. In dark mode (both `[data-theme="dark"]` and `@media prefers-color-scheme:dark`): `--color-btn-bg: #60a5fa`, `--color-btn-text: #0f172a`. All button selectors updated to use `var(--color-btn-bg)` / `var(--color-btn-text)`. Removes reliance on 14 per-selector dark mode overrides — now handled by the token layer alone.

- **Comment form submit button styles** (`style.css`): Added explicit `.comment-form .form-submit input[type="submit"]` rules mirroring `.gwill-form__submit` sizing and token usage. Previously unstyled in `style.css`, relying only on the dark mode patch in `darkmode.css`. Now correctly themed in both light and dark mode with a full hover and focus-visible state.

### Changed

- **`single.php` inline author meta**: Author name now links to the author archive (`get_author_posts_url()`). Added `itemprop="url"` on the link element — valid Schema.org `Person` property that complements the existing `itemprop="name"` on the inner `<span>`.

- **`assets/js/form-multistep.js`**: Step counter ("Step 1 of 3") and required-field validation ("Email is required.") strings are no longer hardcoded English. Both now read from `window.GwillMultistep.i18n` with English fallbacks. Closes bug audit item #5.

- **`inc/enqueue.php`**: Added `wp_localize_script('gwill-forms-multistep', 'GwillMultistep', [...])` with `step`, `of`, and `isRequired` i18n keys. Data attaches to the registered handle and only outputs when the handle is enqueued — no cost on pages without the multistep form.

- **`assets/css/search.css`**: `.gwill-search-expand__submit` and `.search-results__header .search-submit` now use `var(--color-btn-bg)` / `var(--color-btn-text)`.

### Removed

- **Per-selector button overrides in `darkmode.css`**: The 14-line block overriding background/color on `.gwill-form__submit`, `.gwill-form__btn-next`, `.gwill-search-expand__submit`, `.search-results__header .search-submit`, and `.comment-form input[type="submit"]` (both `@media` and `[data-theme]` variants) has been removed. Replaced by the token approach above. Only the `border-left-color` fix for `.gwill-search-expand__submit` remains, as that is a separator issue with no corresponding token.

---

## [1.0.33] - 2026-06-13

### Added

- **Author box** (`template-parts/author-box.php`, `style.css`): New template part rendered at the bottom of every single post, between the entry content and post navigation. Displays author avatar (80px, `border-radius: 50%`, lazy-loaded), a "Written by" label, the author display name linked to their archive, the user bio paragraph (skipped when empty — zero output if no bio set), and a "More posts by {name}" link. Skipped entirely when the author has no display name (guard for incomplete user accounts). No hardcoded color values — all CSS uses existing tokens (`--color-accent`, `--color-primary`, `--color-muted`). Border-top uses `--color-accent` so no dark mode override is required. Avatar link and name link both target the same author archive URL; the avatar link is `tabindex="-1" aria-hidden="true"` (same redundant-link pattern as `content.php` thumbnail links). Section is wrapped in `<section aria-label="About the author">`.

- **Author archive template** (`author.php`): Dedicated template for `/author/{nicename}/` URLs. Shows author hero section at top with 96px circular avatar, display name as `<h1>`, and bio paragraph (conditional on non-empty). Author object retrieved via `get_queried_object()` with `instanceof WP_User` guard. Falls back to `the_archive_title()` / `the_archive_description()` if the queried object is not a WP_User. Post loop uses `gwill_part('content')` (same as `archive.php`). Existing `archive.php` now only handles category, tag, date, and custom taxonomy archives. `h1.archive-title` heading already covered by `darkmode.css` global heading rule — no additional dark mode overrides needed.

### Changed

- **`single.php`**: Added `gwill_part('author-box')` call between `</article>` and `the_post_navigation()`.

---

## [1.0.32] - 2026-06-12

### Fixed

- **`pre`/`code`/Gutenberg code blocks white in dark mode** (`assets/css/darkmode.css`): `.entry-content pre` and `.entry-content code` had `background: #f3f4f6` hardcoded in `style.css` — not tokenized, so the dark mode variable override never reached it. Same issue on `.wp-block-code`, `.wp-block-preformatted`, and `.wp-block-verse`. Added explicit `background: #1e293b; color: var(--color-primary)` overrides in `darkmode.css` for all variants. Inner `pre code` reset to `background: none` preserved so nested code inside pre blocks doesn't double-background.

- **All submit/CTA buttons invisible in dark mode** (`assets/css/darkmode.css`): Six button selectors — `.gwill-form__submit`, `.gwill-form__btn-next`, `.gwill-search-expand__submit`, `.search-results__header .search-submit`, and `.comment-form input[type="submit"]` — all use `background: var(--color-primary)`. In light mode this is `#111111` (dark button, white text — correct). In dark mode `--color-primary` flips to `#f1f5f9` (near white), producing a white button with `color: #fff` text: completely invisible. Fixed by switching to `background: var(--color-accent)` (`#60a5fa`) with `color: #0f172a`. Contrast ratio ≈ 8:1, passes WCAG AA for normal and large text. Also fixed `.gwill-search-expand__submit` `border-left-color` which used `--color-border` (`#1e293b`) that would disappear against the now-blue button — switched to `--color-border-input` (`#475569`) for a visible separator.

---

## [1.0.31] - 2026-06-12

### Fixed

- **Headings black in dark mode** (`assets/css/darkmode.css`): Added explicit `color: var(--color-primary)` declarations on all heading levels (`h1`–`h6`) and named heading classes (`.entry-title`, `.page-title`, `.comments-title`, `.wp-block-heading`) under both `[data-theme="dark"]` and the `prefers-color-scheme: dark` media rule. Headings previously relied on color inheritance from `body` which CSS combining (LiteSpeed) or plugin interference can silently break. Explicit declarations are immune to cascade disruption.

- **Contact form inputs rendering white in dark mode** (`assets/css/darkmode.css`): `.gwill-form__field input`, `textarea`, and `select` had hardcoded `background: #fff` in `style.css` (not tokenized). Added targeted overrides in `darkmode.css` forcing `background: #1e293b` (slightly lighter than body bg for visual depth), `color: var(--color-primary)`, and `border-color: var(--color-border-input)`. Same fix applied to `.gwill-exit-intent__panel`, `.gwill-feedback-wrap`, and `.gwill-feedback-wrap__btn` which also used hardcoded light backgrounds.

- **Default WP comment form inputs unstyled in dark mode** (`assets/css/darkmode.css`): Added explicit overrides for `#respond` text/email/url inputs and textareas to ensure consistent dark backgrounds regardless of browser or environment defaults.

### Added

- **Vibe Comments plugin dark mode support** (`assets/css/darkmode-vibe-comments.css`, `inc/enqueue.php`): New separate stylesheet targeting the Vibe Comments plugin. Strategy: override the plugin's own `--vibe-*` CSS custom properties under `[data-theme="dark"] .vibe-comments-section` — since the entire plugin consumes these tokens, six variable overrides update all components at once. Additional hardcoded hex values inside `vibe-comments.css` that bypass the token system are patched individually: awaiting moderation badge (was `#fef3c7`), liked button state (was `#fee2e2`), reply hover surface (was `#eff6ff`), Google OAuth button (was `white`), and cancel hover (was `#fee2e2`). Token values kept in sync with theme dark palette (`--color-accent: #60a5fa`, `--color-primary: #f1f5f9`, etc.). File is registered always but only auto-enqueued when `vibe-comments/vibe-comments.php` is in `active_plugins` — no extra HTTP request on builds without the plugin. Note: `is_plugin_active()` was intentionally avoided; it requires `wp-admin/includes/plugin.php` which is not loaded on the frontend. Used `get_option('active_plugins')` directly instead.

---

## [1.0.30] - 2026-06-12

### Added

- **Dark mode toggle** (`inc/darkmode.php`, `assets/css/darkmode.css`, `assets/js/darkmode.js`, `template-parts/ui/darkmode-toggle.php`): Full three-state dark/light mode system. Inline flash-prevention script runs in `<head>` before `wp_head()` — reads `gwill-color-scheme` from localStorage and sets `data-theme` on `<html>` before any CSS renders, eliminating FOUC. Falls back to `prefers-color-scheme` media query; works for system-dark-preference users with no JS interaction required. Explicit user choice persists via localStorage. OS-level preference changes (e.g. auto-switch at sunset) are respected when no stored choice exists. Toggle button (`#gwill-darkmode-toggle`) placed after the search icon in the header — sun/moon icon set, ARIA label updates on interaction. CSS dark tokens: `--color-primary` `#f1f5f9`, `--color-accent` `#60a5fa`, `--color-bg` `#0f172a`, `--color-muted` `#94a3b8`, `--color-border` `#1e293b`, `--color-border-input` `#475569`. `color-scheme` browser hint applied so scrollbars and native controls follow the active theme. `GwillDarkmode.i18n` localization object provided for translated aria-labels.

### Fixed

- **Long URLs and unbreakable strings overflowing containers** (`style.css`): `overflow-wrap: break-word` added to the `body` rule. Affects comments, post content, sidebars, cards, and all other containers. This was visible as comment-section URL text bleeding past its card boundary when a third-party comment plugin is active.

- **Debug AJAX action shipped in production** (`inc/forms.php`): `wp_ajax_gwill_test_mail` removed entirely. It was gated behind `current_user_can('manage_options')` but still exposed SMTP host and from-email in its JSON response, and leaked that a custom mailer is configured on the server. SMTP has been confirmed working; the diagnostic endpoint has no ongoing purpose.

- **`DOMContentLoaded` wrapper inside a deferred script** (`assets/js/search-expandable.js`): `DOMContentLoaded` fires *after* all deferred scripts execute — wrapping code in that event inside a `defer`-loaded script caused a redundant callback tick and a brief window where CSS depending on JS-applied state could render incorrectly. Wrapper removed; code now runs inline during script execution, consistent with every other JS file in the theme.

- **Hardcoded English aria-labels in search-expandable.js** (`assets/js/search-expandable.js`, `inc/enqueue.php`): "Open search" and "Close search" strings now read from `GwillExpand.i18n` (via `wp_localize_script`), falling back to English if the object is absent. Strings are now in scope for translation via the POT file.

### Documented

- **`gwill_get_search_term()` returns a pre-escaped value** (`inc/search.php`): Docblock updated with an explicit `IMPORTANT` note warning that the return value is already `esc_attr()`-escaped. Calling `esc_attr()` on it again at the call site will double-encode `&` to `&amp;amp;`. No logic change — documentation only.

---

## [1.0.29] - 2026-06-12

### Fixed

- **Demo template had no programmatic access guard** (`template-contact-demo.php`): The template relied entirely on the WordPress page visibility setting ("Private") to restrict access. A logged-out user who knew the URL, or a new build where the admin step was forgotten, would see the page publicly. Added a hard `current_user_can( 'edit_posts' )` gate with `wp_die( 403 )` immediately after the ABSPATH check. The page is now self-enforcing regardless of WP admin settings. Updated docblock to reflect the change and removed the instruction to set visibility manually.

- **Search results page `.search-field` focus ring not suppressed** (`assets/css/search.css`): The nuclear outline suppression introduced in v1.0.28 only covered `.gwill-search-expand__input`. The `.search-results__header .search-field` generated by `get_search_form()` had no focus state rules at all — browser blue ring was live on focus. Applied the identical `:focus`, `:focus-visible`, and `:active` multi-state suppression block with `!important` overrides to match the expandable input treatment.

---

## [1.0.28] - 2026-06-11

### Changed

- **Search form redesigned to three-zone vertical-divider layout** (`template-parts/search/search-form-expandable.php`, `assets/css/search.css`): Previous design wrapped the icon and input inside a shared `.input-wrap` div, creating a visually disconnected icon floating to the left of a boxed input. Replaced with three explicit zones — `.gwill-search-expand__zone-icon` | `.gwill-search-expand__zone-input` | `.gwill-search-expand__submit` — separated by `border-left: 1px solid var(--color-border)` vertical dividers. The outer container carries the single visible border. No input box border exists at all; the widget reads as one unified bar.

### Fixed

- **Blue focus ring persists on Android Chrome** (`assets/css/search.css`): Android Chrome's UA stylesheet applies a focus ring to `input[type="search"]` that survived `outline: none`. Added explicit `:focus`, `:focus-visible`, and `:active` state rules with `border: none !important; outline: none !important; box-shadow: none !important; -webkit-box-shadow: none !important` to defeat all UA overrides. `outline-width: 0 !important` added as belt-and-suspenders.

---

## [1.0.27] - 2026-06-11

### Fixed

- **Blue focus border on expandable search form** (`assets/css/search.css`): The `:focus-within` rule changed the form border and box-shadow to accent blue when the input was focused. Removed both rules. The border now stays `var(--color-border-input, #6b7280)` at all times — matching the search results page form, which has no focus-state colour change.

---

## [1.0.26] - 2026-06-11

### Fixed

- **Search bar appears inside the header instead of below it** (`assets/css/search.css`): `position: absolute; top: calc(100% + 0.5rem)` measured from the toggle button's bottom edge, which is vertically centred inside a tall header (logo + wrapping tagline). On affected viewports the form rendered mid-header overlapping page content. Switched to `position: fixed; top: var(--gwill-header-height)` on **all** screen sizes. JS already sets `--gwill-header-height` via `getBoundingClientRect().bottom` at open time, so the bar always lands flush below the real header regardless of height, admin bar, or viewport size.

- **Browser blue focus ring bleeds through input** (`assets/css/search.css`): `outline: none` on the input was being overridden by mobile browser UA stylesheets. Strengthened to `outline: none !important; box-shadow: none !important; -webkit-tap-highlight-color: transparent`. Focus state is now expressed on the **form container** via `:focus-within` — accent-coloured border + subtle ring glow — rather than on the naked input element.

- **Search icon visually disconnected from input field** (`assets/css/search.css`): Input padding and gap were creating dead space between the icon and the text cursor. Tightened `padding` and `gap` in `.gwill-search-expand__input-wrap`, nudged icon 1px down for optical alignment, increased input `font-size` to 1rem for better mobile readability.

- **Desktop dropdown shadow too subtle** (`assets/css/search.css`): Replaced the flat `box-shadow` with a two-layer shadow (close soft + far diffuse) and a slightly heavier border (`1.5px`), giving the dropdown clear elevation above page content. `z-index` raised to `9100` (above modal at `9000`).

---

## [1.0.25] - 2026-06-11

### Fixed

- **Expandable search has no visible submit button** (`template-parts/search/search-form-expandable.php`): The submit button was `.screen-reader-text` only — no visible way to trigger a search without pressing Enter. Replaced with a styled `.gwill-search-expand__submit` button that sits flush on the right edge of the form bar, styled to match the results-page Search button.

- **Search bar looks uncontained** (`template-parts/search/search-form-expandable.php`, `assets/css/search.css`): The bare input had no internal structure. Added `.gwill-search-expand__input-wrap` containing a search icon + the input, visually separating the field from the submit button with a left border. The overall form now reads as one contained widget: `[🔍 Type to search… | Search]`. Submit button border-radius matches the form container's right edge. On mobile the button border-radius drops to 0 (full-bleed, flush with viewport edges).

---

## [1.0.24] - 2026-06-11

### Fixed

- **Expandable search form always visible** (`assets/css/search.css`): The base `.gwill-search-expand__form` rule had `display: flex`, which overrides the browser's built-in `[hidden] { display: none }` default stylesheet rule. The form rendered on every page regardless of open/close state. Fixed by setting `display: none` explicitly on the base rule and showing only via `.gwill-search-expand.is-open .gwill-search-expand__form { display: flex }`, which JS controls through the `is-open` class on the parent wrapper.

- **Form expands leftward into the logo on mobile** (`assets/css/search.css`): `position: absolute; right: 100%` placed the form to the left of the toggle button. On narrow viewports this shot directly over the site branding. Changed to `top: calc(100% + 0.5rem); right: 0` so the form drops below the button aligned to its right edge. On screens ≤ 640px the form switches to `position: fixed`, stretches full-width (`left: 0; right: 0`), and pins to `--gwill-header-height` — a CSS custom property set by JS via `header.getBoundingClientRect().bottom` at open time so it always sits flush under the real header.

- **`search-expandable.js`**: `open()` now reads the live header height before making the form visible, ensuring `--gwill-header-height` is accurate on every viewport and after any layout shift (sticky scroll offset, admin bar, etc.).

---

## [1.0.23] - 2026-06-10

### Added — Search (Combo A + B)

New files: `inc/search.php`, `search.php` (replaces stub), `template-parts/search/search-form-expandable.php`, `template-parts/search/search-form-modal.php`, `template-parts/search/search-no-results.php`, `assets/js/search-expandable.js`, `assets/js/search-modal.js`, `assets/css/search.css`.

**Combo A — Expandable search (default, ships active)**

An icon button in the header expands an inline input on click. Submits to `search.php` on Enter — full page reload, no JS dependency for functionality. Close on Escape, click-outside, or the X button. Focus returns to the toggle on close. Activated automatically via `gwill_part('search/search-form-expandable')` added to `header.php`.

**Combo B — Modal + live search (opt-in)**

Full-viewport modal overlay with debounced as-you-type results via `GET /wp-json/gwill/v1/search`. Arrow keys navigate results. Enter on a result follows its link. Enter with no result selected submits to `search.php`. Escape / close button / backdrop click closes the modal and restores focus to the trigger. Focus trapped within modal while open (WCAG 2.4.3). To activate, replace one line in `header.php`: `gwill_part('search/search-form-modal')`.

**Plugin swap stub (`inc/search.php`)**

All search queries (results page + REST endpoint) route through `gwill_execute_search()`. Three filter hooks let any plugin or child theme replace the backend without touching theme files: `gwill_search_post_types` (which post types to search), `gwill_search_args` (modify WP_Query args), `gwill_search_backend` (completely replace execution — return a WP_Query to bypass native WP search). SearchWP, Algolia, or custom meta search can all hook in here.

**REST endpoint (`/wp-json/gwill/v1/search`)**

Registered via `gwill/v1` namespace. Returns `[{id, title, url, type, excerpt}]`. Routes through `gwill_execute_search()` so backend swap hooks apply to live results too. Responses include `Cache-Control: public, max-age=60`. REST URL localized domain-agnostically via `wp_make_link_relative(rest_url())` for staging/tunnel compatibility.

**`search.php`** (replaces minimal stub): results count header (singular/plural/zero, i18n-correct), post type badge per result, date, excerpt, proper `the_posts_pagination()`, no-results state via partial.

**`search-no-results.php`**: spelling/keyword tips, filterable CTA link (`gwill_search_no_results_cta` filter), falls back to the posts page or homepage.

**`assets/css/search.css`**: all search component styles scoped to theme CSS custom properties (`--color-primary`, `--color-accent`, `--color-muted`, etc.) with safe fallbacks. `prefers-reduced-motion` respected for modal animation and expand transition.

### Modified

- `functions.php`: added `require_once` for `inc/search.php`
- `inc/enqueue.php`: registered `gwill-search-expand`, `gwill-search-modal` (with `GwillSearch` localisation), and `gwill-search` CSS — all registered-only, enqueued on-demand by partials
- `header.php`: added `gwill_part('search/search-form-expandable')` after `</nav>` with a comment showing the Combo B swap

---

## [1.0.22] - 2026-06-10

### Fixed

- **AJAX cross-origin failure on staging / dev tunnel domains** (`inc/enqueue.php`): `GwillForms.ajaxUrl` was generated with `admin_url()`, which bakes the WordPress `siteurl` domain into the absolute URL. Accessing the site on any other domain (staging subdomain, ngrok, Cloudflare tunnel, qzz.io) produced a cross-origin fetch that `admin-ajax.php` cannot satisfy — no CORS headers — causing the form to show "Network error" for every visitor. Changed to `wp_make_link_relative( admin_url( 'admin-ajax.php' ) )`, which outputs `/wp-admin/admin-ajax.php`. The browser resolves it against whichever domain is in the address bar, so the request is always same-origin regardless of environment.

- **Bot JSON-payload submissions pass validation** (`inc/forms.php`): Automated scanners probe forms by pasting REST API JSON blobs (e.g. `{"wp_version":"7.0","plugin_version":"1.1.3",...}`) into text fields. The honeypot does not catch bots that leave it empty. Added a content check in `gwill_validate_fields()` that rejects any submission where a free-text field (`gwill_name`, `gwill_message`, `gwill_description`, etc.) begins with `{` or `[`. Real humans never open a contact message with a JSON object. Response is a generic `"Invalid input detected."` that does not reveal the specific rule.

---

## [1.0.21] - 2026-06-10

### Fixed

- **Nested `<main>` element** (`template-contact.php`, `template-contact-demo.php`): Both contact templates opened their own `<main id="main">` inside the `<main id="content">` already opened by `header.php`, producing invalid HTML and two exposed landmark regions to screen readers. Removed the `<main>` wrapper from both templates; they now output content directly inside the existing `header.php` wrapper, matching the pattern used by `page.php` and `single.php`. Skip link now resolves to `#content` correctly on all templates.

- **`gwill_source_post` stripped from feedback emails** (`inc/forms.php`): The field containing the post title was accidentally included in the internal `$skip` list alongside `gwill_form_id`, `gwill_nonce`, and `gwill_hp`. Feedback notification emails arrived with no indication of which post was rated. Removed `gwill_source_post` from `$skip` and added it to `gwill_get_field_labels()` so it renders as `Post: <title>` in the notification body.

- **SMTP hostname wrong in docblock** (`inc/forms.php`): Header comment showed `smtp.brevo.com` (legacy). Corrected to `smtp-relay.brevo.com` to match `EMAIL-SETUP.md` and the confirmed working configuration.

- **Autoreply sent for `feedback` and `exit_intent` forms** (`inc/forms.php`): When `GWILL_AUTOREPLY = true`, a Yes/No post feedback click or exit-intent subscriber capture triggered a "Thanks for your message, I'll be in touch soon" auto-reply — wrong for both interactions. Added `$autoreply_skip = ['feedback', 'exit_intent']` guard before `gwill_send_autoreply()`.

- **Rate limiting broken behind CDN/proxy** (`inc/forms.php`): `$_SERVER['REMOTE_ADDR']` returns the Cloudflare edge IP on CDN-fronted sites, so all visitors shared one rate-limit bucket. Added `gwill_get_client_ip()` helper that resolves via `HTTP_CF_CONNECTING_IP` → `HTTP_X_FORWARDED_FOR` → `REMOTE_ADDR`. Used in `gwill_form_rate_limited()`, `gwill_set_rate_limit()`, and `gwill_log_submission()`.

- **Radio button selection lost on Back navigation** (`assets/js/form-multistep.js`): `restoreFromStorage()` called `field.value = data[name]` on radio inputs, which is a no-op. The scope field (Step 1) lost its selection on Back. Fixed to detect radio groups and set `.checked = true` on the matching option.

- **`current_time('mysql', true)` deprecated** (`inc/forms.php`): Replaced with `gmdate('Y-m-d H:i:s')`. The deprecated call produced the same UTC timestamp but generates notices under strict debug configs and will eventually be removed from WordPress.

- **URL fields sanitized as plain text** (`inc/forms.php`): `gwill_site_url` and `gwill_brand_url` were run through `sanitize_text_field()`. Added `$url_keys` array; these fields now use `esc_url_raw()`, which strips `javascript:` and other invalid schemes.

- **Step validation used generic error text** (`assets/js/form-multistep.js`): `validateStep()` showed `"This field is required."` for every missing field. Added a local `labelFor()` helper matching the one in `forms.js`; errors now read `"Service type is required."` etc.

- **Exit-intent overlay had no focus trap** (`assets/js/form-exit-intent.js`): Keyboard users could Tab through elements behind the open overlay. Added `trapFocus()` — cycles focus within the overlay's focusable elements on Tab/Shift+Tab (WCAG 2.4.3). Focus is also restored to the previously focused element on close. Listener is added in `show()` and removed in `hide()` to avoid leaking.

## [1.0.20] - 2026-06-08

### Added

Custom contact form system — no plugin dependency, free forever. Applies to `gwill-starter-theme` and every project built from it.

**Backend (`inc/forms.php`)**
- Single AJAX action (`gwill_contact_form`) handles all 10 form types — the `gwill_form_id` hidden field identifies which form was submitted.
- `phpmailer_init` SMTP hook: configure outbound email relay via `GWILL_SMTP_HOST/PORT/USER/PASS` constants in `wp-config.php`. Falls back to server `mail()` when constants are absent.
- Three-layer spam protection: WordPress nonce (`check_ajax_referer`), honeypot field (`gwill_hp`), transient rate limiting (5 min / IP, filterable via `gwill_rate_limit_seconds`).
- Per-form required field map (filterable via `gwill_required_fields`).
- Generic `gwill_build_email_body()` — outputs all `gwill_*` fields as Label: Value pairs, appended with submission timestamp, IP hash, and referrer URL.
- Filterable subject lines per form type (`gwill_form_subjects`).
- `Reply-To` header set to submitter email — inbox replies go directly to them.
- Optional auto-reply (`GWILL_AUTOREPLY` constant, message filterable via `gwill_autoreply_message`).
- Routing map for type-router form (`gwill_form_routing_map` filter — map inquiry types to recipient email addresses per project).
- Optional DB logging (`GWILL_LOG_FORMS` constant, schema in `inc/forms.php` file header).
- Field label map (filterable via `gwill_field_labels`) used in email body and validation messages.

**JavaScript (`assets/js/`)**
- `forms.js` — AJAX submission for all `.gwill-form` elements. Client-side required-field validation with accessible error messages (`aria-invalid`, `aria-describedby`). Loading state on submit button. Success: replaces form with confirmation paragraph. Error: inline status message, form restored. Feedback Yes/No interaction.
- `form-multistep.js` — step show/hide, progress bar, per-step validation before advancing, `sessionStorage` field persistence (nonces excluded). No-JS fallback: all steps visible, form submits normally.
- `form-exit-intent.js` — `mouseleave` trigger (desktop, `clientY <= 0`) + 75% scroll-depth trigger (mobile). `localStorage` respawn guard (7 days). Escape key and backdrop click close the overlay.

**Form template parts (`template-parts/forms/`)**
- `contact-simple.php` — Pattern 1: Name / Email / Message.
- `contact-inquiry.php` — Pattern 2: Service type, timeline, budget screener for freelancers.
- `contact-routed.php` — Pattern 3: Inquiry type maps to different recipient emails.
- `contact-multistep.php` — Pattern 4: 4-step quote form (service → budget → contact → description).
- `contact-inline.php` — Pattern 5: 2-field embed for post content; includes source post title in email.
- `contact-sidebar.php` — Pattern 6: Compact sticky sidebar form.
- `contact-exit-intent.php` — Pattern 7: Full-screen overlay; triggered by exit-intent JS.
- `contact-application.php` — Pattern 8: Work-with-me framing; revenue + outcome fields qualify applicants.
- `contact-partnership.php` — Pattern 9: Brand deal / sponsorship intake.
- `contact-post-feedback.php` — Pattern 10: Yes/No micro-interaction; No reveals textarea.

**Page templates**
- `template-contact.php` — Standard `/contact` page. Form type controlled by `gwill_form_type` post meta (defaults to `simple`). Allows switching form per project via `update_post_meta()` or ACF without touching templates.
- `template-contact-demo.php` — Dev-only page rendering all 10 patterns. Set the WordPress page to Private before deploying.

**CSS (`style.css`)**
- Complete form CSS section: field layout, 2-column row at ≥600px, input/textarea/select base styles, focus ring, `aria-invalid` error state, radio card group, submit button + loading state, status and success message styles, multi-step progress bar, inline/sidebar/exit-intent/feedback-specific layouts, demo page layout.
- New root custom properties: `--color-error`, `--color-success`, `--form-radius`, `--form-pad`, `--form-border`, `--form-focus-clr`.

**Enqueue (`inc/enqueue.php`)**
- Registers `gwill-forms`, `gwill-forms-multistep`, `gwill-forms-exit` scripts in `wp_enqueue_scripts`. Template parts enqueue on-demand — scripts load only on pages that contain a form.
- `wp_localize_script` for `gwill-forms` outputs `GwillForms.ajaxUrl` only when the script is actually enqueued.

**wp-config.php constants reference** (none are required — all have safe defaults):

| Constant | Type | Default | Purpose |
|---|---|---|---|
| `GWILL_SMTP_HOST` | string | — | SMTP server hostname |
| `GWILL_SMTP_PORT` | int | 587 | SMTP port |
| `GWILL_SMTP_USER` | string | — | SMTP username |
| `GWILL_SMTP_PASS` | string | — | SMTP password |
| `GWILL_FROM_EMAIL` | string | WP default | Sender address |
| `GWILL_FROM_NAME` | string | WP default | Sender name |
| `GWILL_TO_EMAIL` | string | `admin_email` | Recipient address |
| `GWILL_AUTOREPLY` | bool | false | Send auto-reply to submitter |
| `GWILL_LOG_FORMS` | bool | false | Log submissions to DB |

---

## [1.0.19] - 2026-06-07

### Added

- `composer.json` — dev dependencies: `squizlabs/php_codesniffer` ^3.9, `wp-coding-standards/wpcs` ^3.1, `phpcompatibility/phpcompatibility-wp` ^2.1, `dealerdirect/phpcodesniffer-composer-installer` ^1.0. Run `composer install` to register PHPCS standards automatically.
- `deploy` npm script in `package.json` — generates `../gwill-starter-theme-deploy.zip` excluding all dev/build files. Replaces the manual zip-by-memory workflow. Run `npm run deploy` from the theme root.

### Changed

- `inc/customizer.php` — `gwill_show_tagline` transport changed from `refresh` to `postMessage`. The tagline toggle now updates live in the Customizer preview without a page reload, consistent with the header padding control.
- `header.php` — tagline rendering updated to support `postMessage` transport. `.site-description` is always present in the DOM when tagline text exists; the HTML `hidden` attribute controls visibility instead of PHP conditional removal. This allows the JS handler to toggle it without injecting or removing elements.
- `assets/js/customizer-preview.js` — added `gwill_show_tagline` binding. Toggles `tagline.hidden` when the Display Tagline checkbox changes.
- `footer.php` — footer credit wrapped in `apply_filters( 'gwill_footer_credit', ... )`. Client builds can remove via `add_filter( 'gwill_footer_credit', '__return_empty_string' )` or replace with a custom string. `wp_kses()` sanitises the filtered output.
- `package.json` — version bumped to 1.0.19.

### Removed

- `add_theme_support( 'customize-selective-refresh-widgets' )` removed from `inc/setup.php`. This support has no effect without a registered sidebar. No sidebar is registered in this starter — add the support alongside `register_sidebar()` if widgets are needed per project.
- `add_theme_support( 'wp-block-styles' )` removed from `inc/setup.php`. That support enqueues `wp-block-library-theme.css` (~3 KB of opinionated Gutenberg block styles) on every page load. Incompatible with a blank-slate starter that owns all its own CSS. Enable per project if needed.
- `parts/header.html` and `parts/footer.html` deleted. FSE block template parts in a classic PHP theme are an architectural contradiction — FSE and classic themes are mutually exclusive. Removed to eliminate confusion for anyone reading the theme structure.

---

## [1.0.18] - 2026-06-06

### Added

- `assets/js/customizer-preview.js` — new file. Handles `postMessage` transport for Customizer live preview. Loaded exclusively inside the Customizer preview iframe via `customize_preview_init`. Updates `--header-padding` on `:root` as the user types — no page reload required.
- `attachment.php` — new file. Redirects all WordPress attachment page URLs (301) to the parent post, or to the homepage for standalone uploads. Without this, attachment pages render a bare image with no navigation or context. Delete this file and add a full template if the project needs attachment pages.
- `sidebar.php` — new stub. Satisfies the WordPress template hierarchy so `get_sidebar()` calls from plugins and page builders don't fail silently. No widget areas are registered by default; see file comments for activation steps.
- `style.css` — `.no-comments` rule added to the comments block. `comments.php` outputs `<p class="no-comments">` when comments are closed; the class had no CSS definition.

### Changed

- `inc/customizer.php` — replaced "Header size" select (compact / normal / large) with "Header padding" number input. Accepts any integer 0–200 px. Transport changed from `refresh` to `postMessage` for live preview (no reload). Removed `gwill_sanitize_header_size()`; added `gwill_sanitize_header_padding()` which clamps to [0, 200]. Default: 24 px (≈ 1.5 rem — matches the existing `--spacing` default). Fixed `@package` tag from `GWillStarter` to `GWill_Starter` for consistency with all other files.
- `inc/enqueue.php` — added `customize_preview_init` hook that enqueues `gwill-customizer-preview` with `['customize-preview']` dependency. Fires only inside the Customizer iframe; never loaded on the public frontend.
- `phpcs.xml` — removed the `WordPress.PHP.DevelopmentFunctions` severity-0 suppression. The previous comment claimed it suppressed arrow function warnings; it does not — that sniff governs `var_dump`, `error_log`, `print_r`, and related debug functions. Suppressing it silently hid real debug code leaking into production. Arrow functions (`fn() =>`) require no PHPCS suppression in WPCS 3.x targeting WP 6.4+. Updated the `WordPress.WhiteSpace.ControlStructureSpacing` comment to accurately describe what the sniff covers (blank lines inside brace-delimited structures).
- `style.css` — added `:focus` and `:focus:not(:focus-visible)` rules alongside the existing `:focus-visible` rule. Without the `:focus` fallback, keyboard users in browsers lacking `:focus-visible` support (Safari < 15.4) saw no focus ring — a WCAG 2.4.7 violation. The three-rule pattern is the canonical progressive-enhancement approach.
- All space-indented PHP files converted to tabs (WordPress PHP coding standard): `404.php`, `archive.php`, `comments.php`, `footer.php`, `header.php`, `index.php`, `search.php`, `searchform.php`.
- `package.json` — version bumped to `1.0.18` (was stuck at `1.0.16`).

---

## [1.0.17] - 2026-06-06

### Added

- `inc/customizer.php` — new file. Registers a "Header Options" section in the WordPress Customizer with two controls: a checkbox to show/hide the tagline and a select for header size (compact / normal / large). Kept separate from `inc/setup.php` to avoid loading Customizer-specific code on every admin and REST request. Two sanitize callbacks (`gwill_sanitize_checkbox`, `gwill_sanitize_header_size`) follow the WordPress allowlist pattern.
- `inc/customizer.php` — `wp_enqueue_scripts` hook (priority 20) appends `:root{--header-padding:X}` via `wp_add_inline_style( 'gwill-style', ... )` for non-default header sizes. Compact = `0.75rem`, Large = `2.25rem`. Normal emits no output — the CSS variable fallback in `style.css` handles it. Priority 20 ensures the `gwill-style` handle is already registered (priority 10 in `inc/enqueue.php`) before `wp_add_inline_style()` is called.

### Changed

- `functions.php` — added `require_once get_template_directory() . '/inc/customizer.php'`. Load order: setup → enqueue → security → helpers → customizer.
- `header.php` — tagline conditional now also checks `get_theme_mod( 'gwill_show_tagline', true )`. Previous logic only suppressed the tagline when the site description field was empty in Settings → General; now the Customizer checkbox provides independent control. Default is `true` — no visual change for existing installs.
- `style.css` — `.site-header` padding changed from `var(--spacing)` to `var(--header-padding, var(--spacing))`. The fallback preserves the existing `1.5rem` default so no visual change occurs until the Customizer setting is changed. The `--header-padding` property is intentionally not declared in `:root` — it is injected on demand by `inc/customizer.php`.

---

## [1.0.16] - 2026-06-06

Three regressions caught from live PageSpeed Insights and visual inspection.
Two additional CSS gaps found during the post-fix audit.

### Fixed

- `header.php` — `esc_html_x()` was called without `echo`. `esc_html_x()` returns a string; it does not output it. The skip link rendered with no text content in the DOM — the link existed but was empty. PageSpeed Insights flagged it as "Links do not have a discernible name." This regression was introduced in v1.0.15 when changing from `esc_html_e()` (which echoes) to `esc_html_x()` (which returns) to fix the POT `msgctxt` mismatch. Fix: added `echo` before the call.
- `style.css` — `.custom-logo` was set to `height: 40px; max-width: 200px`. A 1024×185 logo at 40px height has a natural width of 221px; capping it at 200px forced the image to display at 200×40 instead of 221×40, distorting the aspect ratio. PageSpeed Insights flagged it under "Displays images with incorrect aspect ratio." Fixed to `height: auto; max-height: 80px; max-width: 100%` — dimensions are now proportionally constrained with no forced distortion.
- `style.css` — Mobile nav used `flex-direction: column` on `.site-header .inner`, stacking the logo and hamburger button vertically on separate lines. The correct mobile layout is logo left + toggle button right on the same row, with the menu dropping below when open. Fixed by removing the column override. `.site-branding` now gets `flex: 1; min-width: 0` so it fills available space while the toggle stays on the right. The open menu is now `position: absolute; top: 100%; left: 0; right: 0` — it drops below the full header width as an overlay rather than pushing content down. `.site-header` received `position: relative` as the positioning context.

### Added

- `style.css` — `.screen-reader-text` styles. `searchform.php` uses `<label><span class="screen-reader-text">Search for:</span><input></label>` to visually hide the label while keeping it accessible. Without `.screen-reader-text` CSS, the "Search for:" text renders visibly above every search input. WordPress defines this class only in `wp-admin` stylesheets — themes must define it on the frontend themselves. Added the standard clip-based visually-hidden pattern.
- `style.css` — `.entry-thumbnail-wrap`, `.entry-thumbnail-wrap a`, and `.entry-thumbnail-wrap img` rules. The archive/index card template (`template-parts/content.php`) wraps thumbnails in `.entry-thumbnail-wrap` but had no corresponding CSS. The `.entry-thumbnail img` rule only applied to the `<figure class="entry-thumbnail">` context in `featured-image.php` (used by single/page). Card thumbnails were missing `border-radius`, `width: 100%`, and the line-height gap fix applied to all other images. Added matching rules so card and single thumbnails render identically.
- `style.css` — Mobile nav open-state list item separators. `#primary-menu li` gets `border-bottom: 1px solid var(--color-border)` with `last-child` exception, giving each nav link a clear visual separator in the open mobile menu.

---

## [1.0.15] - 2026-06-05

All 16 findings from Audit 2 resolved.

### Added

- `home.php` — blog posts index template. WordPress template hierarchy: `home.php` → `index.php`. Without it, the blog index fell through to the generic catch-all `index.php`, leaving no dedicated file to customise (featured post, intro copy, category filter) without modifying the fallback that serves all other templates. Added as a documented stub — identical in behaviour to `index.php` today, ready to diverge when a project needs it. `index.php` remains untouched as the required WordPress fallback.
- `style.css` — `--color-border-input: #6b7280` CSS custom property. Form input borders were using `--color-border` (`#e5e7eb`, 1.24:1 contrast on white), which fails WCAG 2.1 SC 1.4.11 Non-text Contrast (3.0:1 required for UI component boundaries). `#6b7280` is the existing `--color-muted` value and gives 4.83:1 on white — passes AA comfortably. Decorative separators (site-header border-bottom, post dividers, blockquote, pre) continue to use `--color-border`; only form inputs use the new higher-contrast variable.
- `style.css` — `.content-none` and `.content-none__title` rules. The empty-state section existed in the HTML since v1.0.13 but had no CSS — it rendered with default browser spacing and no visual treatment. Added `padding: 2rem 0` and `text-align: center`.
- `style.css` — `.entry-body` rule. The content card body wrapper existed in `template-parts/content.php` since v1.0.14 but had no CSS. Added `padding: 1.25rem 0 0`, `display: flex`, `flex-direction: column`, `gap: 0.5rem` to give consistent internal spacing.
- `theme.json` — `h4`, `h5`, `h6` added to `styles.elements`. Only `h1`–`h3` were defined; the block editor used WordPress defaults for `h4`/`h5`/`h6` while the frontend used `clamp()` values from `style.css`. A client inserting an `h4` in the editor saw a different size than what published. `h6` uppercase + letter-spacing treatment was completely invisible in the editor. All three now match `style.css` exactly.

### Changed

- `template-parts/content.php` — removed `focusable="false"` from the thumbnail `<a>` tag. This is an SVG attribute (`<use focusable="false">` prevents IE11 from making inline SVGs tab-stops) and is invalid HTML on an anchor element. No browser interprets it. The keyboard exclusion is already handled by `tabindex="-1"` and the accessibility tree exclusion by `aria-hidden="true"`.
- `template-parts/content-none.php` — removed `get_search_form()` from the `is_search()` branch. `search.php` already calls `get_search_form()` unconditionally before the posts check. On a zero-results search page both templates ran, producing two `<form role="search">` landmarks — both unlabelled and indistinguishable to screen readers. The "try again" affordance is the form already visible at the top of the page.
- `searchform.php` — added `aria-label="<?php esc_attr_e( 'Site search', 'gwill-starter' ); ?>"` to `<form role="search">`. WCAG technique ARIA11: when multiple landmarks of the same type appear on a page each must have a unique accessible name. Without a label, screen reader users navigating by landmarks cannot distinguish the search form from any other `role="search"` instance on the page.
- `style.css` — renamed `.entry-excerpt` selector to `.entry-summary`. The CSS declared `.entry-excerpt` but `template-parts/content.php` used `.entry-summary`. The CSS rule was dead code — it styled nothing. `.entry-summary` was unstyled. The class names are now consistent; the styling is unchanged in output.
- `404.php` — added `aria-labelledby="error-404-heading"` to `<section class="error-404 not-found">` and `id="error-404-heading"` to the `<h1>`. An unlabelled `<section>` is not a named landmark — screen readers treat it as a generic `<div>`. This is the same fix applied to `template-parts/content-none.php` in v1.0.14. The `error-404` and `not-found` classes are preserved — Theme Check and plugins target them.
- `footer.php` — wrapped `wp_nav_menu()` in `has_nav_menu( 'footer' )` guard. With `fallback_cb => false`, `wp_nav_menu()` outputs nothing when no footer menu is assigned, leaving an empty `<nav>` element in the DOM. The guard ensures the `<nav>` only renders when a menu is actually set.
- `search.php` — added `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` to the `echo '<span>'` line. WPCS flags any `echo` where not all concatenated parts are wrapped in an escaping function. The `<span>` is developer-controlled markup (not user input); the search query itself is correctly wrapped in `esc_html()`. Without the ignore comment, PHPCS produces an error that obscures real issues elsewhere.
- `inc/security.php` — corrected comment on `remove_action( 'wp_head', 'print_emoji_detection_script', 7 )`. The previous comment said "`print_emoji_styles` action was removed in WP 6.4" — naming the wrong function. `print_emoji_styles` (CSS) was removed in WP 6.4. `print_emoji_detection_script` (JS) still runs in WP 6.4+ and still requires explicit removal. The incorrect comment implied the `remove_action` was dead code, creating a risk of it being deleted.
- `header.php` — changed skip link from `esc_html_e()` to `esc_html_x( 'Skip to content', 'skip link', 'gwill-starter' )`. The POT file had a `msgctxt "skip link"` entry for this string since v1.0.14, but `esc_html_e()` generates no context — WordPress's translation loader would never match the `msgctxt` entry and the string would render in English regardless of what translators provided. `esc_html_x()` generates the matching `msgctxt` in the POT.
- `header.php` — changed `get_bloginfo( 'description', 'display' )` to `get_bloginfo( 'description' )`. The `'display'` filter applies `apply_filters( 'bloginfo', ... )` before returning. Plugins hooking `bloginfo` can inject HTML entities; calling `esc_html()` on the result then double-encodes them. Since the template escapes the output itself, `'display'` is unnecessary and removes the double-encode risk.
- `.gitignore` — added `assets/.vite/`. After `npm run build`, Vite generates `assets/.vite/manifest.json`. Without this entry the file appears as untracked after every build. Policy documented in the comment: the manifest is not committed; `wp_get_theme()->get('Version')` is used for cache-busting in `inc/enqueue.php`.
- `package.json` — added `"engines": { "node": ">=18.0.0" }`. Vite 5 requires Node 18+. Without the field a developer on Node 16 receives a cryptic installation or build error. With it, npm/yarn prints a clear version message before attempting anything.
- `languages/gwill-starter.pot` — added `'Site search'` entry for the new `searchform.php` `aria-label`. Updated `Project-Id-Version` and `X-Generator` to 1.0.15.

---

## [1.0.14] - 2026-06-05

All 27 findings from the v1.0.13 audit resolved. No intentional behaviour changes — all fixes preserve existing output or correct it to match the documented intent.

### Added

- `src/main.js` — Vite source entry that was referenced by `vite.config.js` but missing, causing `npm run build` to fail immediately. Contains the nav toggle logic as a proper ES module (no IIFE, `const`/`let`, arrow functions). `assets/js/main.js` is the committed pre-built output; `src/main.js` is the source. Editing `src/main.js` and running `npm run build` regenerates the output.
- `style.css` — `.nav-toggle`, `.nav-toggle__bar`, `.nav-toggle.is-active`, `.site-header nav #primary-menu`, `.site-header nav #primary-menu.is-open` rules. The hamburger button and mobile nav pattern added in v1.0.13 shipped with zero CSS — the button was invisible, the nav never collapsed on mobile, and the JS class-toggling had no effect. This was a functional regression. The three bars now morph into a × when active via CSS transforms.
- `style.css` — `.site-branding`, `.site-description`, `.custom-logo-link`, `.custom-logo` rules to support the `the_custom_logo()` output added to `header.php` this version.
- `inc/setup.php` — `add_theme_support( 'custom-logo', [...] )`. Required by the WordPress Theme Review Team. Without it, the Customizer has no Site Logo section and `has_custom_logo()` always returns false.
- `src/` — directory created as the Vite source tree.

### Changed

- `header.php` — Site title area replaced with `.site-branding` wrapper implementing `has_custom_logo()` / `the_custom_logo()` fallback chain. When a custom logo is set via the Customizer, `the_custom_logo()` renders it; otherwise, the text site title is shown. Optional site tagline (`.site-description`) renders below the logo/title when set. This is the TRT-required custom logo pattern.
- `inc/enqueue.php` — Changed deprecated boolean `true` (5th arg to `wp_enqueue_script`) to `[ 'in_footer' => true, 'strategy' => 'defer' ]`. The boolean was deprecated in WP 6.3. `strategy => 'defer'` is appropriate for the nav toggle JS — it is non-critical and can load after parsing with no visual impact. Changed `wp_get_theme()->get(...)` to `wp_get_theme( get_template() )->get(...)` in both enqueue calls for consistency with child-theme contexts.
- `inc/security.php` — Removed `remove_action( 'wp_head', 'rsd_link' )` and `remove_action( 'wp_head', 'wlwmanifest_link' )`. Both actions were removed from WordPress core in WP 6.3; both calls have been no-ops on this theme's minimum WP version (6.4) since it was first created. Changed `login_errors` filter from `__()` to `esc_html__()` per WPCS `WordPress.Security.EscapeOutput` requirement.
- `inc/helpers.php` — `gwill_featured_image_alt()` changed from `esc_attr()` to `sanitize_text_field()` for its return value. `esc_attr()` was causing double-escaping: the function pre-escaped, then `wp_get_attachment_image()` (called internally by `the_post_thumbnail()`) applied `esc_attr()` again. Alt text containing `&`, `"`, `<`, or `>` rendered corrupted in the browser — e.g., "Dog & cat" became "Dog &amp; cat". `sanitize_text_field()` strips HTML tags and normalises whitespace without HTML-encoding; WP handles the encoding. Updated docblock to document the escaping contract explicitly.
- `inc/helpers.php` — `excerpt_more` filter changed from `'&hellip;'` to `'&#8230;'`. `&hellip;` is an HTML named entity; it is not a valid XML entity. RSS and Atom feeds are XML documents — only `&amp;`, `&lt;`, `&gt;`, `&quot;`, `&apos;` are valid named entities in XML. Feed validators reported an error on any post with a truncated excerpt. `&#8230;` is the numeric XML entity for U+2026 HORIZONTAL ELLIPSIS and is valid in both HTML and XML.
- `comments.php` — Added `(int)` cast to `get_comments_number()`. The function returns `string`; `_n()` expects `int` for its count argument. PHP coerces silently at runtime but the cast removes a static-analysis warning and makes the type contract explicit.
- `single.php` — Restructured `.entry-meta` to remove Schema.org microdata attributes from translatable strings. The previous `__( 'By <span itemprop="author" itemscope itemtype="...">...' )` pattern embedded `itemprop`, `itemscope`, `itemtype`, and `datePublished` attributes inside the POT string — a translator modifying the "By [author] — [date]" phrase could silently corrupt the structured data. Microdata is now in the PHP/HTML template; the only translatable text is `'By'`. Added `<link itemprop="url">` and `<meta itemprop="dateModified">` inside `<article>`. Google's Article structured data specification requires `dateModified` alongside `datePublished` for rich result eligibility.
- `page.php` — Added `<link itemprop="url">` inside the `<article>` element for `WebPage` Schema self-description.
- `template-parts/content.php` — Wrapped post date in `<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">`. The date was plain text with no machine-readable value — inconsistent with `single.php` which already used `<time datetime="">`, and a missed opportunity for search engine and screen reader date context.
- `template-parts/content-none.php` — Added `aria-labelledby="content-none-heading"` to `<section>` and `id="content-none-heading"` to both `<h2>` elements. An unlabelled `<section>` is not exposed as a named landmark by screen readers — it degrades to a generic container. The labelled version is navigable from the landmarks list in VoiceOver, NVDA, and JAWS.
- `index.php` — Replaced inline `<p><?php esc_html_e( 'No posts found.' ) ?></p>` with `gwill_part( 'content-none' )`. This was the only remaining template using the old inline empty-state pattern; `archive.php` and `search.php` were updated in v1.0.13. Consistent empty-state handling across all list templates.
- `assets/js/main.js` — Converted tab indentation to 2-space (matching `.editorconfig` `[*.js] indent_style = space`). Changed `var` to `const` throughout (no reassignment occurs). Added "PRE-BUILT FILE" header comment clearly marking this as a build artifact with source at `src/main.js`.
- `style.css` — `h3` minimum value corrected from `clamp(1.2rem, ...)` to `clamp(1.15rem, ...)` in both the global rule and `.editor-styles-wrapper h3` to match `theme.json` `styles.elements.h3.typography.fontSize`. On mobile the block editor and frontend were rendering `<h3>` at different sizes.
- `style.css` — Added `-moz-osx-font-smoothing: grayscale` to `body`. `-webkit-font-smoothing: antialiased` only affects WebKit/Blink; without its Firefox companion, text on macOS Firefox rendered at a visibly different weight.
- `style.css` — Expanded `prefers-reduced-motion: reduce` block to cover all transitions and animations via `transition-duration: 0.01ms !important` and `animation-duration: 0.01ms !important` on `*, *::before, *::after`. Previously only `scroll-behavior` was reset. Every `transition:` in the stylesheet (form inputs, nav toggle bars) was unaffected by the motion preference. `0.01ms` rather than `0ms` ensures `transitionend` events still fire so JS listeners do not hang.
- `phpcs.xml` — Removed dead `<config name="testVersion" value="8.1-"/>`. The `testVersion` config key is read by PHPCompatibilityWP sniffs, but the PHPCompatibilityWP rule was never declared in the ruleset — making the config a no-op. Added install instructions as a comment including the composer command and the rule/config lines to uncomment once the package is installed. Added `src/` and `assets/js/` to `<exclude-pattern>` since PHPCS has no business scanning Vite source or built JS.
- `languages/gwill-starter.pot` — Regenerated. Removed 5 obsolete strings (old inline archive/search/index empty-state text, old 404 entity string, old single.php format string with embedded microdata). Added 6 new strings (nav toggle button label, all content-none.php strings, standalone `'By'`). Updated `Project-Id-Version` and `X-Generator` headers to 1.0.14. POT should be regenerated via `wp i18n make-pot` after every release that touches translatable strings.
- `vite.config.js` — Added inline comment documenting the pre-built file contract now that `src/main.js` exists.

### Removed

- `inc/security.php` — `remove_action( 'wp_head', 'rsd_link' )` (dead since WP 6.3)
- `inc/security.php` — `remove_action( 'wp_head', 'wlwmanifest_link' )` (dead since WP 6.3)
- `index.php` — Inline `'No posts found.'` string (replaced by `content-none` template part)

---

## [1.0.13] - 2026-06-04

### Added

- `template-parts/featured-image.php` — extracted from the duplicate `<figure>` block that existed verbatim in both `single.php` and `page.php`. Includes the `has_post_thumbnail()` guard, `gwill_featured_image_caption()` call, `the_post_thumbnail()` with full LCP attributes, and conditional `<figcaption>`. Returns early when no thumbnail exists, so callers need no guard of their own. Any future change to LCP attributes, image size, or microdata now requires one edit instead of two.
- `template-parts/content-none.php` — empty-state handler for queries that return zero results. Wraps the message in `<section class="content-none">`, uses `is_search()` to tailor copy, and surfaces `get_search_form()` in the search context for immediate retry. Replaces the inconsistent inline `<p>` tags that existed in `archive.php` and `search.php`.
- `package.json` — declares `vite@^5.4.0` as a dev dependency with `dev`, `build`, and `preview` scripts. Establishes the CSS authoring pipeline at the starter level rather than deferring it to each project.
- `vite.config.js` — reads `src/main.js` as the entry point and outputs to `assets/js/main.js` and `assets/css/main.css`. `emptyOutDir: false` prevents Vite from deleting `.gitkeep` and other static assets in `assets/`. Manifest generation enabled for production cache-busting. `style.css` is intentionally excluded — WordPress reads it for the theme header and it must remain a static file.
- `parts/header.html` — FSE stub. Not loaded in classic PHP mode; WordPress only reads `/parts/` when block templates are active. Contains migration instructions: add `block-templates` support, replace the stub with block markup, create `/templates/index.html`, retire `header.php` once parity is reached.
- `parts/footer.html` — FSE stub. Same status as `parts/header.html`.

### Changed

- `single.php` — inline featured image block replaced with `<?php gwill_part( 'featured-image' ); ?>`. Output is unchanged.
- `page.php` — same replacement as `single.php`.
- `archive.php` — inline else-branch `<p>Nothing found.</p>` replaced with `<?php gwill_part( 'content-none' ); ?>`.
- `search.php` — inline else-branch paragraph replaced with `<?php gwill_part( 'content-none' ); ?>`.
- `header.php` — added hamburger toggle button inside `<nav>` before the menu. Button carries `aria-expanded="false"`, `aria-controls="primary-menu"`, and a translatable `aria-label`. Added `'menu_id' => 'primary-menu'` to `wp_nav_menu()` args so the `<ul>` has the `id` that `aria-controls` references. Button renders three `<span aria-hidden="true">` bars — styled via `.nav-toggle` and `.nav-toggle__bar` in project CSS.
- `assets/js/main.js` — replaced empty comment placeholder with a self-contained IIFE implementing the full mobile nav interaction: click toggles `aria-expanded` + `is-open` on the menu + `is-active` on the button; Escape closes and returns focus to the toggle; `focusin` delegation closes when focus moves outside the nav entirely. No dependencies. CSS contract documented in an inline comment block.
- `inc/enqueue.php` — uncommented `wp_enqueue_script( 'gwill-main' )`. The JS file is now populated; loading it was previously suppressed because the file was empty.
- `theme.json` — added `styles` block. `styles.typography` sets global base font size (`1rem`), line height (`1.6`), and system-font stack — values flow into the block editor and frontend simultaneously. `styles.color` sets global text and background defaults from the palette. `styles.elements` adds link colour with hover state, fluid heading sizes for `h1`–`h3` via `clamp()`, shared heading colour, and basic button colours. Previously `theme.json` only disabled features; it now establishes a design token system shared by the editor and the frontend.

---

## [1.0.12] - 2026-06-02

### Added

- `inc/helpers.php` — `gwill_featured_image_alt( ?int $post_id = null ): string`. Returns the alt text stored in the media library for the featured image attachment, falling back to the post title when none is set. Both values are `esc_attr()`'d. Replaces the previous pattern of passing `alt => ''` (invisible to screen readers) or `alt => esc_attr( get_the_title() )` (ignores explicit media-library alt text).
- `inc/helpers.php` — `gwill_featured_image_caption( ?int $post_id = null ): string`. Returns the media-library caption for the featured image via `wp_get_attachment_caption()`. Returns an empty string when none exists.
- `style.css` — `.entry-thumbnail-caption` rule. Styles the `<figcaption>` conditionally rendered by `single.php` and `page.php` — `0.875rem`, muted colour, italic, centred. Distinct from `.wp-caption-text`, which covers inline classic-editor captions.

### Changed

- `single.php` — added Schema.org `BlogPosting` microdata to the `<article>` element (`itemscope`, `itemtype`, `itemprop="headline"`, `itemprop="articleBody"`, `itemprop="image"` with nested `ImageObject`, `itemprop="author"` with nested `Person`, `itemprop="datePublished"`). Replaced the plain-text date with a `<time datetime="">` element using `get_the_date( 'c' )` for ISO 8601 machine-readable format. Featured image now conditionally renders a `<figcaption>` and calls `gwill_featured_image_alt()` for screen-reader alt text.
- `page.php` — added Schema.org `WebPage` microdata (`itemprop="name"` on `<h1>`, `itemprop="text"` on `.entry-content`, `itemprop="image"` with nested `ImageObject`). Pages typed as `WebPage` rather than `BlogPosting`. Custom page templates can override `itemtype` for `ContactPage`, `AboutPage`, `FAQPage`, etc. Featured image improvements match `single.php`.
- `inc/security.php` — replaced hardcoded `is_author()` redirect with a `GWILL_ALLOW_AUTHOR_ARCHIVES` constant guard. Defining `define( 'GWILL_ALLOW_AUTHOR_ARCHIVES', true )` in `wp-config.php` disables the redirect without touching the starter. Defaults to `false`; existing behaviour unchanged.
- `inc/helpers.php` — `excerpt_length` filter priority raised from `10` to `999`. Previously any plugin registering at a later priority silently overrode the theme's excerpt length. Priority `999` ensures the theme value wins unless a project-specific override hooks at `1000` or higher. Excerpt length is now also filterable per-project via `gwill_excerpt_length` without touching the starter file.
- `header.php` — added explanatory comment above `wp_body_open()` documenting the hook's purpose (GTM noscript, accessibility overlays). Prevents removal by developers who don't recognise it.

### Fixed

- `style.css` — removed three duplicate bare `h4`, `h5`, `h6` rules that were copy-pasted inside the `/* Gutenberg Editor Styles */` section but placed outside `.editor-styles-wrapper`. These were globally scoped, creating redundant specificity that shadowed the correctly scoped rules above and would silently win over child-theme overrides targeting those elements directly.

---

## [1.0.11] - undocumented

*This version does not appear in the changelog. It may have been a version number reserved or skipped during development.*

---

## [1.0.10]

### Fixed

- `header.php` — removed redundant `role="banner"` from `<header>`, `role="navigation"` from `<nav>`, and `role="main"` from `<main>`. All three are implicit ARIA landmark roles already provided by the HTML5 elements. Explicit roles on semantic elements create unnecessary noise in the accessibility tree.
- `footer.php` — removed redundant `role="contentinfo"` from `<footer>` for the same reason.
- `header.php` — removed dead `screen-reader-text` class from the skip link. WordPress does not inject `.screen-reader-text` CSS on the frontend; that class only receives styles in `wp-admin`. The skip link is fully handled by `.skip-link` and `.skip-link:focus-visible` in `style.css`.
- `comments.php` — fixed the "Comments are closed" notice logic. Previously the notice only rendered when `!comments_open() && !have_comments()` — if a post had existing comments but was now closed, no form and no notice appeared. Changed `elseif ( ! have_comments() )` to a plain `else` so the closed notice always renders when `comments_open()` is false, regardless of prior comment count.

---

## [1.0.9]

### Fixed

- `searchform.php` — replaced `esc_attr_e( 'Search&hellip;', ... )` with `esc_attr_e( 'Search…', ... )`. `esc_attr` encodes `&` to `&amp;`, so `&hellip;` was rendering as the literal string `Search&hellip;` in the input placeholder. HTML entities only work unescaped in HTML text content; attribute values must use actual UTF-8 characters.
- `404.php` — same root cause: `esc_html_e( '&larr; Back to Home', ... )` was rendering the literal string `&larr;` instead of the arrow character `←`. Fixed to the UTF-8 character directly. Both are the same category of double-encoding bug fixed in `1.0.8` (`single.php` entry-meta) and `1.0.7` (`search.php` heading).

---

## [1.0.8]

### Fixed

- `style.css` — changed `.skip-link:focus` to `.skip-link:focus-visible`. The global focus style throughout the stylesheet correctly uses `:focus-visible`, but the skip link was inconsistently using `:focus`, which fires on mouse click as well as keyboard focus — causing the skip link to briefly flash on mouse interaction.
- `comments.php` — added `aria-labelledby="comments-title"` to `<section id="comments">` and `id="comments-title"` to the inner `<h2>`. An unlabelled `<section>` is treated as a generic container by screen readers; the comments region was not reachable as a landmark.

---

## [1.0.7]

### Fixed

- `theme.json` — `wideSize` corrected to `1440px`. The `1.0.1` changelog documented this fix but the value was never actually updated in the file — it remained `1200px`, matching `contentSize`. As a result, `add_theme_support( 'align-wide' )` was a silent no-op: wide-aligned blocks rendered at identical width to regular content.
- `inc/security.php` — removed `remove_action( 'wp_print_styles', 'print_emoji_styles' )`. The `print_emoji_styles` function was removed from WP core in 6.4; this call has been dead code on any currently supported WordPress version.
- `search.php` — replaced `esc_html__()` with `__()` on the format string containing a `<span>` tag. `esc_html__` encodes HTML entities, which is semantically wrong on any string that intentionally outputs markup. It worked in practice only because `%s` contains no escapable characters, but a translator adding angle brackets would have broken output.

---

## [1.0.6]

### Fixed

- `inc/enqueue.php` — replaced `get_stylesheet_uri()` with `get_template_directory_uri() . '/style.css'` and `wp_get_theme( get_template() )->get( 'Version' )` for the version string. `get_stylesheet_uri()` is context-sensitive: when this theme is used as a parent, it resolves to the child theme's `style.css`, meaning the starter's own CSS never loaded and the child stylesheet was double-registered under two handles. `get_template_directory_uri()` always resolves to the parent/standalone theme's directory regardless of child theme activation.

---

## [1.0.5]

### Added

- `.gitignore` — excludes OS files (`.DS_Store`, `Thumbs.db`), IDE directories (`.idea/`, `.vscode/`), `node_modules/`, build output (`dist/`, `build/`), `vendor/`, `.env` files, `*.log`, and compiled `.mo` translation binaries. `.pot` and `.po` source files are kept — `.mo` files should be compiled at deploy time, not committed.
- `.editorconfig` — locks PHP to tab indentation (WordPress Coding Standards), CSS/JS/JSON/YAML to 2-space indentation, LF line endings, UTF-8, final newlines, and trailing whitespace trimming. Markdown is exempt from trailing whitespace trimming since two trailing spaces produce a line break in rendered output.
- `phpcs.xml` — configures PHP_CodeSniffer against the WordPress ruleset with three deliberate exclusions: short array syntax `[]`, arrow functions `fn() =>`, and blank lines between hooked callbacks. Text domain locked to `gwill-starter`. PHP 8.1+ and WordPress 6.4+ compatibility targets set.

---

## [1.0.4]

### Added

- `languages/gwill-starter.pot` — starter POT file bootstrapping all translatable strings in the theme. Regenerate using `wp i18n make-pot . languages/gwill-starter.pot` before distributing; the POT should be generated from source, not maintained by hand.

### Changed

- `inc/security.php` — REST API user endpoint removal (`/wp-json/wp/v2/users`) now applies to unauthenticated requests only, gated behind `! is_user_logged_in()`. The previous unconditional removal broke plugins (WooCommerce, ACF, Elementor) that call those endpoints internally on authenticated requests.
- `inc/security.php` — added explicit tradeoff comment to the author archive redirect. The redirect disables author archive pages, which is a silent default that breaks multi-author sites. The comment now makes the tradeoff visible so developers can make an informed choice when forking.

### Fixed

- `theme.json` — `$schema` corrected from `https://schemas.wp.org/wp/7.0/theme.json` (a version that does not exist) to `wp/6.6`. IDE validators and schema-aware tools were rejecting the file.

### Security

- `style.css` — added `@media ( prefers-reduced-motion: reduce )` override resetting `scroll-behavior` to `auto`. Without this, `scroll-behavior: smooth` overrides the OS-level motion preference set by users with vestibular disorders. WCAG 2.1 SC 2.3.3 finding.

---

## [1.0.3]

### Fixed

- `functions.php` — changed all four `require` calls to `require_once`. `require` throws a fatal error on a second inclusion; in a parent theme context where a child theme or plugin triggers a second load, this is a real failure mode. `require_once` has no downside.
- `single.php` — `the_post_thumbnail()` called without an `alt` argument. Now passes `[ 'alt' => esc_attr( get_the_title() ) ]`.
- `page.php` — same bare `the_post_thumbnail()` fix as `single.php`.
- `template-parts/content.php` — thumbnail wrapped in `aria-hidden="true"` is decorative; the correct alt value is `alt=""`, not the post title, which would be redundant with the `<h2>` link that immediately follows. Fixed to `[ 'alt' => '' ]`.
- `footer.php` — `wp_footer()` was placed inside `<footer class="site-footer">`. Plugin hooks on `wp_footer` inject scripts and markup that have no business inside a `<footer>` element. Moved to after the closing `</footer>` tag, immediately before `</body>`.

### Security

- `inc/security.php` — added `the_generator` filter to remove the WordPress version from RSS/Atom feed headers. `remove_action( 'wp_head', 'wp_generator' )` only covers the HTML `<head>`; the feed generator tag is a separate output requiring `add_filter( 'the_generator', '__return_empty_string' )`.
- `inc/security.php` — added REST API user endpoint removal. `/wp-json/wp/v2/users` and `/wp-json/wp/v2/users/{id}` were publicly accessible by default, exposing author login names as input for credential-stuffing attacks.
- `inc/security.php` — added author archive redirect. `/?author=1` redirects to `/author/loginname/`, disclosing login names independently of the REST API. Author archive requests now redirect to the homepage with a 301.
- `inc/security.php` — added generic login error filter. WordPress's default errors distinguish "no account found" from "wrong password", enabling username enumeration. Both cases now return the same message.

---

## [1.0.2]

### Changed

- `theme.json` — `$schema` bumped to `https://schemas.wp.org/wp/7.0/theme.json`. IDE-only change; no runtime impact. `"version": 2` is intentionally unchanged; the theme targets WP 6.4+ and does not require the version 3 features introduced in WP 6.6.

---

## [1.0.1]

### Fixed

- `header.php` — replaced `bloginfo( 'charset' )` with `echo esc_attr( get_bloginfo( 'charset' ) )`. `bloginfo()` echoes without contextual escaping; the correct pattern is `get_bloginfo()` wrapped in the appropriate escaping function.
- `header.php` / `footer.php` — replaced `bloginfo( 'name' )` with `echo esc_html( get_bloginfo( 'name' ) )`. Same root cause as the charset fix.
- `theme.json` — `wideSize` corrected from `1200px` to `1440px`. It was equal to `contentSize`, making the `align-wide` theme support registered in `inc/setup.php` a silent no-op — wide blocks rendered at identical width to regular content.
- `theme.json` — `$schema` pinned to `https://schemas.wp.org/wp/6.5/theme.json` instead of `trunk`. The trunk schema can change between WP releases and break editor validation mid-project.

---

[Unreleased]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.18...HEAD
[1.0.18]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.17...v1.0.18
[1.0.17]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.16...v1.0.17
[1.0.16]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.15...v1.0.16
[1.0.15]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.14...v1.0.15
[1.0.14]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.13...v1.0.14
[1.0.13]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.12...v1.0.13
[1.0.12]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.11...v1.0.12
[1.0.11]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.10...v1.0.11
[1.0.10]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.9...v1.0.10
[1.0.9]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.8...v1.0.9
[1.0.8]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.7...v1.0.8
[1.0.7]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.6...v1.0.7
[1.0.6]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.5...v1.0.6
[1.0.5]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/gwillchijioke/gwill-starter-theme/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/gwillchijioke/gwill-starter-theme/releases/tag/v1.0.1
