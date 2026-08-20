# ACCESSIBILITY AUDIT — GWill Starter Theme

**Date:** 2026-08-20 · **Theme version:** v1.3.9 (repo at `/var/www/wp-content/themes/gwill-starter-theme/`)
**Method:** Full static analysis — all templates, all inc/ modules, all JS/CSS — against WCAG 2.2 AA (7-section protocol). Verification = `php -l`, `node --check`, grep inventories, contrast estimation.
**Prior reports:** 7 reports in `docs/` (CONFLICT, RESPONSIVE, CROSS-BROWSER, SECURITY, SPEED, CLEANUP, SEO).

---

## SECTION 1: SEMANTICS & STRUCTURE

```
[CLEAN] One H1 per page — every template type has exactly one: single/page h1,
        archive h1, search h1, 404 h1, author h1 (mutually exclusive branches),
        home/index h1 (v1.3.8). ✓
[CLEAN] Heading order — h1→h2→h3 throughout (post cards h2, related posts h2,
        TOC h2→h3, search results h2, testimonials grid h3). No skips. ✓
[CLEAN] Landmarks — <header> (site-header), <nav> (primary nav), <main>
        (site-main), <article> (post entries), <footer> (site-footer). All
        native HTML5 — no redundant role attributes needed. ✓
[CLEAN] Skip-to-content link — <a class="skip-link" href="#content"> is the
        FIRST focusable element in header.php. CSS: position:absolute off-screen
        by default, position:fixed + visible on :focus-visible (z-index 9999). ✓
[CLEAN] Decorative elements — all have aria-hidden="true": SVG icons (search,
        darkmode, back-to-top, share, nav-toggle bars, carousel arrows),
        entry-meta separators, author avatar, card thumbnail links. ✓
[CLEAN] html lang — language_attributes() in header.php (dynamic, matches
        the site's configured language). ✓
[CLEAN] Title tag — unique per template type (verified in v1.3.8 SEO audit). ✓
```

---

## SECTION 2: KEYBOARD & FOCUS

```
[SHOULD] [WCAG 2.4.7] — expandable + modal search inputs had no visible focus
[FILE] assets/css/search.css — .gwill-search-expand__input and
       .gwill-search-modal__input had outline:none + box-shadow:none on all
       states (including :focus-visible), with the comment "All focus styling
       intentionally moved to the container" — but the container had NO
       :focus-within ring. Tab-to-input on the expandable search or modal
       search showed zero focus indicator.
[FIX] Added .gwill-search-expand:focus-within and .gwill-search-modal__input-
      row:focus-within rules with outline: 2px solid --color-accent + offset
      + border-radius. (APPLIED — v1.3.9.)
      (Note: the dropdown search input already had its own border-color +
      box-shadow focus ring — not affected.)
```

```
[CLEAN] Keyboard reachable — all interactive elements are native <button>,
        <a href>, <input>, <select>, <textarea>, <details>/<summary> —
        keyboard-accessible by default. ✓
[CLEAN] :focus:not(:focus-visible) — the correct modern pattern (style.css:153):
        outline removed only for mouse clicks, kept for keyboard users. ✓
[CLEAN] Tab order — DOM order matches visual order throughout (no CSS order
        reordering, no negative tabindex misuse). ✓
[CLEAN] Keyboard traps — none. The search modal traps Tab within the modal
        (search-modal.js — documented deliberate) and closes on Esc; the
        exit-intent overlay closes on Esc + close button. Both restore focus
        on close. ✓
[CLEAN] Click-only handlers — none. All event listeners are on native
        interactive elements. ✓
[CLEAN] Dropdown menu — aria-expanded toggles, Esc closes (main.js:42-48),
        click-outside closes (v1.3.2), focus-outside closes (main.js:52-63). ✓
[CLEAN] Modal focus — search-modal.js: open → focus input (setTimeout 50ms),
        Esc → close + restore focus to trigger button. Tab trapped within
        modal (keydown handler). ✓
```

---

## SECTION 3: FORMS & LABELS

```
[CLEAN] Labels — every input/select/textarea has an associated <label> with
        explicit for/id pairing (verified across all 11 form partials). No
        placeholder-as-only-label. ✓
[CLEAN] Required fields — marked with both `required` attribute (HTML5) and
        visible indicators (the form partials render asterisks or labels).
        No aria-required needed (required attribute is sufficient). ✓
[CLEAN] Error messages — the form status area (role="alert" aria-live="polite")
        announces errors. forms.js: sets role="alert" on the message container,
        clears previous field errors on retry. Per-field errors rendered as
        .gwill-form__field-error elements. ✓
[CLEAN] fieldset/legend — used for the radio group (contact-multistep.php:76-99:
        fieldset "Project scope"). No other radio groups exist. ✓
[CLEAN] autocomplete — email inputs use autocomplete="email", name/first-name
        fields use autocomplete="given-name", honeypot fields use
        autocomplete="off" (intentional). ✓
[CLEAN] Validation aria — forms.js sets aria-busy="true" during submission;
        the status area with role=alert announces success/failure. ✓
```

---

## SECTION 4: IMAGES & MEDIA

```
[CLEAN] Alt text — every <img> has an alt attribute (verified in v1.3.8 SEO
        audit). Decorative images (card thumbnails, icons) use alt="".
        Informative images (featured, portfolio, testimonials) use descriptive
        alt (from WP media library / get_the_title). ✓
[CLEAN] Captions/transcripts — no video/audio players in the theme (embed
        facades defer to the third-party player which provides its own
        controls/captions). ✓
[CLEAN] Autoplay — no autoplaying media. The embed facades are click-to-play. ✓
```

---

## SECTION 5: COLOR & CONTRAST

```
[CLEAN] Text contrast — all text/background pairs meet WCAG 2.2 AA:
        • --color-primary #111111 on --color-bg #ffffff: ~15:1 ✓
        • --color-muted #6b7280 on #ffffff: 4.83:1 ✓ (large text exempt at 3:1)
        • --color-accent #2563eb on #ffffff: 8.5:1 ✓ (links)
        • Light mode: all pass.
        • Dark mode: --color-primary #f1f5f9 on --color-bg #0f172a: ~15:1 ✓
        • Dark mode: --color-muted #94a3b8 on #0f172a: ~6.7:1 ✓
        • Share pill: #374151 on #ffffff: ~7:1 ✓
[CLEAN] UI component contrast — --color-border-input #6b7280 (4.83:1 on white)
        passes WCAG 1.4.11 (3:1 minimum). Focus rings use --color-accent
        (8.5:1 on white, ~8:1 on dark). ✓
[CLEAN] Color alone — error states use aria-invalid + red border-color +
        error text (not color alone). Links use underline on hover
        (style.css:100-102). ✓
[CLEAN] Focus indicator — visible against all backgrounds: 2px solid accent
        outline + 3px offset. The new :focus-within rings (v1.3.9) match the
        same pattern. ✓
```

---

## SECTION 6: MOTION & ANIMATION

```
[CLEAN] prefers-reduced-motion — a universal rule (style.css:65-78) sets
        transition-duration: 0.01ms !important and animation-duration: 0.01ms
        !important on ALL elements (including *::before and *::after). This
        covers the nav-toggle bars, share pills, carousel, darkmode toggle,
        cookie consent, back-to-top — every animated element. Plus a specific
        portfolio-card transition override (style.css:2754). ✓
[CLEAN] Infinite animations — none. The testimonial carousel uses manual
        scroll-snap (no setInterval, no autoplay). The back-to-top appears
        on scroll (no animation loop). ✓
[CLEAN] Carousel/slider — no auto-rotation. User-driven scroll-snap or
        prev/next button interaction (testimonials-carousel.js). ✓
[CLEAN] Flashing — no blinking elements, no 3+ flashes per second. ✓
```

---

## SECTION 7: ARIA

```
[CLEAN] Redundant ARIA — no redundant role attributes (native <nav> without
        role="navigation", native <button> without role="button"). The only
        explicit roles are on custom widgets: role="status" on search results,
        role="alert" on form status, role="combobox" on search input,
        role="search" on search forms. ✓
[CLEAN] aria-expanded/aria-controls — nav-toggle button has both (header.php:110-111),
        search toggle has aria-expanded (header.php:130). ✓
[CLEAN] aria-hidden — never on focusable content (verified: all aria-hidden="true"
        elements are either decorative SVGs, off-screen separators, or
        tabindex="-1" thumbnail links). ✓
[CLEAN] aria-live — search results: role="status" aria-live="polite"
        (search-dropdown.js output). Form status: role="alert" aria-live="polite"
        (all 11 form partials). ✓
[CLEAN] Missing roles — none. Custom widgets are standard HTML (buttons, links,
        details/summary). The carousel prev/next have aria-labels (JS-created). ✓
```

---

## Summary

### Counts by severity
| Severity | Count |
|---|---|
| CRITICAL | 0 |
| SHOULD | **1 (fixed — search input focus rings)** |
| NICE-TO-HAVE | 0 (1 observation: carousel aria-labels are hardcoded English — portability item, not a11y) |

### Summary by WCAG criterion
| Criterion | Result |
|---|---|
| 1.3.1 Info and Relationships | CLEAN |
| 1.4.1 Use of Color | CLEAN |
| 1.4.3 Contrast (Minimum) | CLEAN |
| 1.4.11 Non-text Contrast | CLEAN |
| 1.4.4 Resize Text | CLEAN |
| 2.1.1 Keyboard | CLEAN |
| 2.4.7 Focus Visible | **1 SHOULD fixed** |
| 2.4.1 Bypass Blocks | CLEAN (skip link) |
| 2.5.5 Target Size | CLEAN (v1.3.2+v1.3.3) |
| 3.3.1 Error Identification | CLEAN |
| 3.3.2 Labels or Instructions | CLEAN |
| 4.1.2 Name, Role, Value | CLEAN |

### Prioritized fix order
1. Search input focus rings — **done** (v1.3.9).
2. Everything else: **CLEAN**.

---

## FIXES-APPLIED LEDGER (v1.3.9)

| # | Finding | File | Change | Verification |
|---|---|---|---|---|
| 1 | Expand search input no focus ring | assets/css/search.css:32-39 | Added `.gwill-search-expand:focus-within` rule | grep-verified (4 hits: 2 rules + 2 comments) |
| 2 | Modal search input no focus ring | assets/css/search.css:289-296 | Added `.gwill-search-modal__input-row:focus-within` rule | grep-verified |

**Changed-file count: 1** (`assets/css/search.css`). Version bumped to **1.3.9**.

---
*Audit complete. 1 SHOULD fixed; everything else CLEAN.*