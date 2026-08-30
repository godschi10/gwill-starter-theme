# GWill Starter — Feature Roadmap

> The tiered plan this theme's documentation has referenced since
> v1.0.50. Committed at last in v1.5.0 — the five dangling references
> in README.md and CHANGELOG.md now resolve.

**Guiding law** (README, Philosophy): *will every build that starts
from this theme need this?* If yes, it belongs in core (Tier 1/2). If
it depends on the project, it's an opt-in module (Tier 3) — shipped,
but costing a site that never uses it nothing.

---

## Tier 1 — Core (every build) — ✅ SHIPPED v1.0.50

Batched as one release, tested as a whole, per plan:

1. Open Graph / Twitter Card fallback (`inc/social-meta.php`)
2. FAQ accordion + FAQPage schema (`inc/faq.php`)
3. Cookie consent banner
4. Related posts
5. Reading time (card + single views)
6. Back-to-top button
7. Sticky header (Customizer toggle)

## Tier 2 — Near-core (most builds) — ✅ SHIPPED v1.0.62

1. Newsletter signup (Brevo contacts API) — v1.0.58
2. Table of contents (auto from real heading structure) — v1.0.62
3. Testimonials CPT (grid + carousel) — v1.0.62
4. Staging banner (Customizer toggle) — v1.0.57/59/62

## Tier 3 — Opt-in modules (zero cost unused) — ✅ SHIPPED v1.0.60–63

1. WooCommerce compatibility layer — v1.0.60
2. Pricing table component — v1.0.63
3. Portfolio / case-studies CPT (`gwill_portfolio`) — v1.0.63

## Beyond the roadmap — shipped in the v1.4 era

1. **Web push, self-hosted VAPID** (`inc/webpush.php`) — v1.4.0
2. **Installable Chrome app PWA** (`inc/pwa.php`, true icon suite) — v1.4.0–1.4.2
3. **Theme Laws** (`docs/LAWS.md`, 11 laws + launch checklist) — v1.4.0
4. **Custom-apps skeleton** (`inc/apps.php`, `/apps/<slug>/`) — v1.4.0
5. **Smart search** (FTS5 + typo correction + live dropdown/modal) — v1.1.0

## v1.5.0 — The King's five (all five candidates, one batch)

1. **Portfolio single + archive templates** (`single-gwill_portfolio.php`,
   `archive-gwill_portfolio.php`) — the dedicated surfaces v1.0.63's CPT
   always implied: CreativeWork microdata, project-details card,
   type-filter pills, real pagination.
2. **Search modal — verified shipped & documented** (v1.0.23) — the recon
   proved Combo B complete (JS + partial + CSS); README's search
   variants table now lists all three with their partial names.
3. **Newsletter analytics** (`inc/analytics.php`) — Tools → Forms &
   Newsletter: 30-day signup chart (pure SVG, zero JS), totals, recent
   log, CSV export — plus the Law L12 fix: `gwill_log_submission()` is
   finally DEFINED (it was called since v1.0.20 behind GWILL_LOG_FORMS
   but never defined anywhere — a latent fatal).
4. **Push dashboard** (`inc/push-dashboard.php`) — Tools → Push
   Subscribers: counts, subscriber table with per-endpoint delete,
   test-notification send through the publish path's proven loop.
5. **Two new demo apps** — case-converter + unit-converter join
   word-counter in `gwill_apps_registry()`; three reference apps now
   demonstrate the skeleton's range.

## Candidate pool (unplanned — awaiting royal direction)

- Dark-mode aware app icons
- Portfolio single columns (sidebar with meta)
- Push open-rate tracking (needs a clicked-through counter)
- Apps skeleton: registry-driven schema variations
- Analytics: per-form-pattern breakdown chart
- Unit-converter: area/volume/speed/digital units
- Case-converter: counts preview before apply

---

*Last updated: 2026-08-30 (v1.5.0).*
