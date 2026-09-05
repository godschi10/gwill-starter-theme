# GWill Starter - Feature Roadmap

> The tiered plan this theme's documentation has referenced since
> v1.0.50. Committed at last in v1.5.0 - the five dangling references
> in README.md and CHANGELOG.md now resolve.

**Guiding law** (README, Philosophy): *will every build that starts
from this theme need this?* If yes, it belongs in core (Tier 1/2). If
it depends on the project, it's an opt-in module (Tier 3) - shipped,
but costing a site that never uses it nothing.

---

## Tier 1 - Core (every build) - ✅ SHIPPED v1.0.50

Batched as one release, tested as a whole, per plan:

1. Open Graph / Twitter Card fallback (`inc/social-meta.php`)
2. FAQ accordion + FAQPage schema (`inc/faq.php`)
3. Cookie consent banner
4. Related posts
5. Reading time (card + single views)
6. Back-to-top button
7. Sticky header (Customizer toggle)

## Tier 2 - Near-core (most builds) - ✅ SHIPPED v1.0.62

1. Newsletter signup (Brevo contacts API) - v1.0.58
2. Table of contents (auto from real heading structure) - v1.0.62
3. Testimonials CPT (grid + carousel) - v1.0.62
4. Staging banner (Customizer toggle) - v1.0.57/59/62

## Tier 3 - Opt-in modules (zero cost unused) - ✅ SHIPPED v1.0.60–63

1. WooCommerce compatibility layer - v1.0.60
2. Pricing table component - v1.0.63
3. Portfolio / case-studies CPT (`gwill_portfolio`) - v1.0.63

## Beyond the roadmap - shipped in the v1.4 era

1. **Web push, self-hosted VAPID** (`inc/webpush.php`) - v1.4.0
2. **Installable Chrome app PWA** (`inc/pwa.php`, true icon suite) - v1.4.0–1.4.2
3. **Theme Laws** (`docs/LAWS.md`, 11 laws + launch checklist) - v1.4.0
4. **Custom-apps skeleton** (`inc/apps.php`, `/apps/<slug>/`) - v1.4.0
5. **Smart search** (FTS5 + typo correction + live dropdown/modal) - v1.1.0

## v1.5.0 - The King's five (all five candidates, one batch)

1. **Portfolio single + archive templates** (`single-gwill_portfolio.php`,
   `archive-gwill_portfolio.php`) - the dedicated surfaces v1.0.63's CPT
   always implied: CreativeWork microdata, project-details card,
   type-filter pills, real pagination.
2. **Search modal - verified shipped & documented** (v1.0.23) - the recon
   proved Combo B complete (JS + partial + CSS); README's search
   variants table now lists all three with their partial names.
3. **Newsletter analytics** (`inc/analytics.php`) - Tools → Forms &
   Newsletter: 30-day signup chart (pure SVG, zero JS), totals, recent
   log, CSV export - plus the Law L12 fix: `gwill_log_submission()` is
   finally DEFINED (it was called since v1.0.20 behind GWILL_LOG_FORMS
   but never defined anywhere - a latent fatal).
4. **Push dashboard** (`inc/push-dashboard.php`) - Tools → Push
   Subscribers: counts, subscriber table with per-endpoint delete,
   test-notification send through the publish path's proven loop.
5. **Two new demo apps** - case-converter + unit-converter join
   word-counter in `gwill_apps_registry()`; three reference apps now
   demonstrate the skeleton's range.

## v1.6.0 - Tier A (the five battle-tested ports)

1. **Two-factor login (TOTP)** (`inc/two-factor.php` + the mandatory
   `inc/login-rate-limit.php` companion) - from the tech theme's proven
   662-line module: RFC 6238 codes verified against independently computed
   test vectors, `wp_hash()`ed backup codes, pending→active pairing state
   machine, admin force-disable, Users-list column.
2. **Image CLS pass** (`inc/images.php`) - from the portfolio theme's
   images.php: dimensions enforced, decoding=async (sync on LCP), WebP,
   900px sizes hint, 1920px scaled cap.
3. **Cache purge on save** (`inc/cache-purge.php`) - from the portfolio
   theme's cache.php: dev local wipe / production `home_url()`-derived
   purge fan-out, FILES-only, never fatal.
4. **HTML whitespace minification** (`inc/minify.php`) - from the tech
   theme's performance.php: placeholder-protected sensitive regions,
   manifest-route guard.
5. **Late-styles bloat catch** (`inc/wp-css-off.php`) - the bugfix-grade
   close of the starter's own hole: WP 6.9+/7.x re-enqueues global-styles
   at `wp_footer:1`; the module catches it at `wp_footer:2` before core's
   priority-8 hoist. Supersedes enqueue.php's head-only dequeue. Emoji,
   jQuery Migrate, front-end heartbeat, and logged-out dashicons removed
   alongside.

Plus: `login_errors` obfuscation made 2FA-aware (security.php), the six
loader lines (functions.php), and Law L13 (port-verification discipline).

## v1.7.0 - Tier B (the six UX/feature ports)

1. **Reading progress bar** (tech) - `transform: scaleX()` compositor bar.
2. **Code-block copy button + Prism** (tech) - self-hosted, sniffer-driven.
3. **AJAX category filter** (tech) - endpoint + `.gwill-pill` driver.
4. **Accessible nav walker** (tech, adapted) - split-button mobile
   accordion for the starter's ONE-menu reality + brand-agnostic
   pages-based fallback.
5. **Login page branding** (fresh) - logo/wordmark + accent via
   `login_head`; `gwill_login_accent` filter.
6. **External-link hardening** (fresh) - `_blank` + `noopener noreferrer`,
   rel tokens merged, author targets respected.

Plus the **embed-facade verification**: the King's requested click-to-play
facades were already owned since v1.3.0 - proven functional with 12 battery
tests (both render paths, all three providers) rather than rebuilt.

## v1.8.0 - Tier C (the four situational ports)

1. **Print stylesheet** (fresh) - article-as-document printing: chrome
   stripped, serif 12pt, expanded link URLs, table borders, no breaks
   inside code blocks. Wired `media="print"`.
2. **Unified inline-SVG icon helper** (finance, unified) - `gwill_icon()`
   registry of 11 icons extracted verbatim from live-proven sources by
   `build-svg-icons.py`; `currentColor` + `1em`; `gwill_icons` filter.
3. **Cross-site feed** (portfolio) - transient-cached REST pull with all
   four failure paths (fresh/stale-fallback/fetch/expired-refresh);
   `gwill_feed_sources` filter, zero hardcoded URLs.
4. **Ad slots** (tech, ACF struck) - six Customizer-configured placements
   with per-device variants; `ads.js` instantiates only the current
   device client-side (cache-safe); in-content injection 2nd ¶ + every
   ~5, max 4; PHP-tag sanitizer keeps network scripts.

## v1.9.1 - vibe-comments integration hardening

Plugin guard 3.5.6→3.6.3; darkmode-vibe enqueue now gated on the plugin's
own `should_render()` (kills _doing_it_wrong dep notices + dead CSS);
dark-state coverage ported (pin hover, error/success, counter, banner  - 
both selector systems).

## v1.9.0 - the candidate pool (all seven, per royal order)

1. **Unit-converter expansion** - area/volume/speed/data-size categories
   on the rate-table pattern; `TABLES` map replaces the length/weight
   ternary throughout.
2. **Case-converter counts preview** - live words/characters/sentences/
   paragraphs bar above the buttons; refreshes on input AND after every
   conversion (the counts must reflect the converted text).
3. **Apps registry schema variations** - optional `fields` map renders
   as `data-*` on `#gwill-app-root`; kebab-case keys; scalar-only;
   backward compatible (no fields → markup unchanged).
4. **Push open-rate tracking** - `cid` stamped on every notification
   payload; sw.js pings `gwill/v1/push-click` (keepalive) on click;
   `gwill_push_stats` option (autoload off, 200-campaign trim) stores
   sent/clicked/when; Push dashboard gains a Campaign open-rates table.
5. **Analytics per-form-pattern chart** - `gwill_analytics_pattern_breakdown()`
   (GROUP BY form_id) + pure-SVG horizontal bars.
6. **Portfolio single meta sidebar** - `.gwill-project-layout` grid:
   content + sticky `.gwill-project-aside` (client, live site,
   services, published); single column < 1024px; CreativeWork schema
   untouched.
7. **Dark-mode aware favicon** - `favicon-dark.svg` + media-attr link
   pair in head; `gwill_pwa_dark_favicon()` filter with light-URL
   fallback when the dark asset is absent.

## Candidate pool (unplanned - awaiting royal direction)

- Dark-mode aware app icons
- Portfolio single columns (sidebar with meta)
- Push open-rate tracking (needs a clicked-through counter)
- Apps skeleton: registry-driven schema variations
- Analytics: per-form-pattern breakdown chart
- Unit-converter: area/volume/speed/digital units
- Case-converter: counts preview before apply

---

*Last updated: 2026-08-30 (v1.9.1).*
