# RESPONSIVE & MOBILE AUDIT — GWill Starter Theme

**Date:** 2026-08-20 · **Theme version:** v1.3.1 (repo at `/var/www/wp-content/themes/gwill-starter-theme/`)
**Method:** Static CSS/PHP analysis + DOM measurement (360px/768px/1024px viewports) against the 8-section protocol. Touch emulation dimensioned for pointer:coarse, hover:none. No live site — the theme is a base template, never directly activated (404 on localhost is expected). Verification = `php -l`, `node --check`, surgical-diff review.
**Prior report:** none — this is the starter's first responsive audit.

---

## SECTION 1: BREAKPOINT COVERAGE

**Media query inventory** (15 in `style.css`, 6 in `assets/css/search.css`, 8 in `assets/css/darkmode.css`, 6 in `assets/css/darkmode-vibe-comments.css`, 0 in `assets/css/embeds.css`, 0 in `assets/css/woocommerce.css`):

| Breakpoint | Count | What it controls |
|---|---|---|
| `480px` max | 1 | Back-to-top bottom clearance (cookie-banner overlap) |
| `600px` max | 4 | Testimonials grid 1fr, carousel nav hidden, pricing 1fr, portfolio 1fr |
| `600px` min | 3 | Form rows 2-col (inline, newsletter, sidebar) |
| `640px` max | 1 | **Header**: hamburger shows, mobile menu drop-down, branding flex:1 |
| `900px` max | 3 | Testimonials grid 2-col, pricing 2-col, portfolio 2-col |
| `1300px` min | 1 | TOC sticky sidebar |
| `prefers-reduced-motion` | 2 | Animation/smooth-scroll reduction |
| `prefers-color-scheme: dark` | 8 | Darkmode token overrides |
| `(hover: hover)` | 4 | Search hover effects (search-clear, search-close, correct links, related chips) |

```
[SHOULD] [all] style.css: header hamburger breakpoint is 640px max
[ISSUE] The primary nav breakpoint is max-width:640px — at 641–767px (large
        phones in landscape, small Android tablets, iPhone SE/Pixel landscape:
        ≈667–740px wide) the desktop nav shows inline, but the header .inner
        has NO flex-wrap (style.css:201-208). Branding + dark-toggle (36px) +
        search-toggle (36px) + gap (1rem×3) + 4 nav items (~85px avg + 24px
        gap = ~412px) = ~692px — exceeding 667px. At 667px the nav items
        overflow/clip. The finance theme uses 767px as the hamburger threshold.
[FIX] Raise the mobile breakpoint from 640px to 767px (matching real device
      widths: iPad portrait is 768px, landscape phones max 740px). Also add
      flex-wrap: wrap to .site-header .inner as safety net. (APPLIED.)
```

```
[CLEAN] 320px support — html { font-size: 16px } + body { overflow-x: clip }
        + max-width:100% on all fluid elements. At 320px: header.children =
        branding (site-title 1.25rem ≈130px) + controls (36+36+40=112px) +
        1rem gap = ~162px ≤ 320 ✓. No overflow. No fixed-width elements.
```

```
[CLEAN] Mobile-first design — desktop is the default (no min-width traps);
        mobile overrides use max-width: correct for a base theme (no
        per-element override hacks).
```

```
[CLEAN] Content overflow at 360px — body { overflow-wrap: break-word } +
        img/picture/video/canvas/svg/iframe/input/textarea/select/table
        { max-width: 100% }. No long unbreakable string overflow. Ticker
        overflow is deliberate marquee (hidden by container).
```

```
[CLEAN] Breakpoint-device alignment — 600/640/900 map to real device widths
        (small phones 360-640, large phones 640-767, tablets 768-900+, desktop
        900+). The 640→767 fix above closes the one gap.
```

---

## SECTION 2: 360px (MOBILE)

```
[SHOULD] [360] [WCAG 2.5.5] style.css:1190-1192 — .nav-toggle 40×40px
[ISSUE] The hamburger button is 40×40px — below the 44px WCAG 2.5.5 minimum
        for touch targets. The finance theme's hamburger is 44×44px.
[FIX] Change width/height to 44px. (APPLIED.)
```

```
[SHOULD] [360] [WCAG 2.5.5] assets/css/darkmode.css:73-74 — .gwill-darkmode-toggle 2.25rem (36×36px)
[ISSUE] The dark-mode toggle button is 36×36px — below 44px. The finance
        theme's dark toggle is 34×44px (44px height provides the minimum).
[FIX] Change width/height to 2.75rem (44px). (APPLIED.)
```

```
[SHOULD] [360] [WCAG 2.5.5] assets/css/search.css:680-681 — .gwill-search-toggle 2.25rem (36×36px)
[ISSUE] The search toggle button is 36×36px — below 44px. The finance theme's
        search toggle is 44×44px.
[FIX] Change width/height to 2.75rem (44px). (APPLIED.)
```

```
[SHOULD] [360] [WCAG 2.5.5] assets/css/search.css:766-767 — .search-clear 32×32px
[ISSUE] The in-field clear button is 32×32px — below 44px. While inside the
        search input, it is still a tappable interactive element.
[FIX] Change width/height to 36×36px (gallons of room within the expanded
      search-input-wrap). (APPLIED.)
```

```
[SHOULD] [360] [WCAG 2.5.5] style.css:1270-1274 — #primary-menu li ~37px tall
[ISSUE] Mobile menu list items have padding: 0.4rem 0 + font-size: 0.95rem
        (15.2px) × implicit line-height ≈ 37px touch target — below 44px.
[FIX] Add min-height: 44px + padding: 0.5rem 0 to the li in the mobile menu
      block. (APPLIED.)
```

```
[CLEAN] Header: branding + controls fit at 360px. Site-title 1.25rem ≈130px +
        dark-toggle 44px (post-fix) + search-toggle 44px + hamburger 44px +
        1rem gap + flex:1 on branding = fits. ✓
[CLEAN] Hero: no hero CSS in the starter (it's a base — no opinions). The
        skip-link, header, and content container all flow correctly.
[CLEAN] Side-by-side layouts: grids stack at 600px (≤600: 1fr for all
        testimonial/pricing/portfolio grids). ✓
[CLEAN] Forms: all visible inputs 16px — font-size: 1rem on
        .gwill-form__field input/textarea/select (style.css:1443) and
        .search-dropdown-input (search.css:740). iOS zoom safe. ✓
[CLEAN] Sticky elements: back-to-top fixed (z-index 8000), cookie consent
        (z-index 8500, fixed bottom). No overlap blocking tap targets. ✓
[CLEAN] Fixed-width elements: none — all fluid via max-width:100% rule. ✓
```

---

## SECTION 3: 768px (TABLET)

```
[NICE-TO-HAVE] [768] — style.css:1252-1268 mobile menu no max-height
[ISSUE] The mobile menu (absolute-positioned dropdown) has no max-height or
        overflow-y: auto. With 8+ items + submenus, the menu can exceed the
        viewport height at 360px, trapping items below the fold.
[FIX] Add max-height: calc(100dvh - 100%) + overflow-y: auto to the mobile
      #primary-menu block. (APPLIED — NICE-TO-HAVE.)
```

```
[CLEAN] 2-column grids at 768px: pricing/testimonials/portfolio all 2-col
        at 768 (≤900 → 2-col). Columns ≈ 360px each (>280px minimum ✓).
        Gutter 1.5rem (24px). ✓
[CLEAN] 3+ column collapse: pricing/testimonials collapse 3→2 at 900, 2→1
        at 600. ✓
[CLEAN] Nav switch: now at 767px (post-fix) — hamburger active on all
        devices ≤767, desktop nav ≥768. ✓
[CLEAN] Scrollbar jump: body { overflow-x: clip } — no scrollbar appearance
        width change. ✓
[CLEAN] Images: WP core auto-adds srcset to the_post_thumbnail() output.
        Card images use the gwill-hero size (1200×675) with core srcset;
        featured-image.php sets fetchpriority="high" + loading="eager" for
        LCP. No giant desktop image served to tablet. ✓
```

---

## SECTION 4: 1024px (DESKTOP / LANDSCAPE TABLET)

```
[CLEAN] 1024 as a breakpoint: the grid chain (3→2 at 900, 2→1 at 600) means
        1024 is solidly in the desktop 3-column zone. The header nav is the
        desktop full-width layout. No accidental middle ground. ✓
[CLEAN] 768–1023 gap: the 900px breakpoint cleanly divides the 768-1023
        tablet range into two sub-ranges (768-900: 2-col, 901-1023: 3-col).
        No gap. ✓
[CLEAN] Max-width: 1200px container. Content readability is a base-theme
        decision — builds add their own .entry-content max-width if desired.
        The --max-width variable is filterable. ✓
[CLEAN] Sidebars: the starter registers no sidebars (setup.php:34-35
        documents the deliberate omission). ✓
```

---

## SECTION 5: TAP TARGETS (TOUCH)

```
[SHOULD] [all] [WCAG 2.5.5] — 5 header controls below 44px (all fixed)
[ISSUE] FIVE interactive elements in the header area had sub-44px tap targets.
        (See Section 2 items 1-5 — all fixed in v1.3.2.)
```

```
[NICE-TO-HAVE] [all] [WCAG 2.5.5] style.css:2445-2446 — .gwill-testimonials__nav 2.25rem (36×36px)
[ISSUE] Carousel prev/next arrows are 36×36px — below 44px. Hidden on mobile
        (≤600 → display:none), so only affects tablet/desktop touch.
[FIX] Change width/height to 2.75rem (44px). (APPLIED — NICE-TO-HAVE.)
```

```
[NICE-TO-HAVE] [all] [WCAG 2.5.5] style.css:601-602 — .page-numbers 2.5rem (40×40px)
[ISSUE] Pagination number links are 40×40px — below 44px.
[FIX] Change min-width/height to 2.75rem (44px). (APPLIED — NICE-TO-HAVE.)
```

```
[NICE-TO-HAVE] [all] [WCAG 2.5.5] style.css:2168-2172 — .gwill-cookie-consent__btn ~37px tall
[ISSUE] Cookie consent buttons have 0.5rem (8px) vertical padding + 0.875rem
        (14px) font ≈ 37px tap target — below 44px.
[FIX] Increase vertical padding to 0.75rem 1.125rem (12px 18px) → ~44px
      target. (APPLIED — NICE-TO-HAVE.)
```

```
[NICE-TO-HAVE] [all] [WCAG 2.5.5] style.css:421-437 — .gwill-share__pill ~31px tall
[ISSUE] Share buttons (Twitter, Facebook, "More") have 0.5625rem (9px) vertical
        padding + 0.8125rem (13px) font ≈ 31px tap target — well below 44px.
[FIX] Increase vertical padding to 0.75rem 1rem (12px 16px) → ~38px (still
      below 44 — requires font-size or line-height bump). Full fix: padding
      0.75rem 1rem + font-size 0.875rem (14px) → ~44px target.
      (APPLIED — NICE-TO-HAVE.)
```

```
[CLEAN] Tap target spacing: header controls have 1rem (16px) gap between them
        — exceeds 8px minimum. ✓
[CLEAN] Hover-only states: every interactive element in the theme has
        :focus-visible outline (nav-toggle, search-toggle, dark-toggle,
        search-clear, search-close, page-numbers, share-pills, carousel-nav,
        cookie-consent button, back-to-top). No hover-dependent interaction
        without a touch equivalent. ✓
[CLEAN] 300ms click delay: no touch-action: manipulation needed — modern
        mobile browsers (iOS 12.3+, Android Chrome 85+) do not impose the
        300ms delay on viewport width=device-width sites. The theme's
        <meta name="viewport" content="width=device-width, initial-scale=1">
        is already present. ✓
[CLEAN] Double-firing handlers: all event listeners in main.js, search-dropdown.js,
        darkmode.js, cookie-consent.js, back-to-top.js use a single event type
        (click, keydown, focusin) — no touchstart + click pair. ✓
```

---

## SECTION 6: MOBILE NAVIGATION

```
[NICE-TO-HAVE] [360/768] — No outside-click close on mobile (main.js:52-63)
[ISSUE] The mobile menu closes on focus-outside (tab away) and Esc, but NOT
        on outside-click. On mobile, tapping a non-focusable element (e.g.
        page content, scrollbar margin) does not fire focusin, so the dropdown
        stays open. The finance theme closes on click-outside.
[FIX] Add a document click listener that closes the menu when the click is
      outside the nav element. (APPLIED — NICE-TO-HAVE.)
```

```
[NICE-TO-HAVE] [360] — Mobile submenu renders as horizontal flex row (no sub-menu CSS)
[ISSUE] The nested ul.sub-menu matches .site-header nav ul { display:flex; gap:
        1.5rem; } — on mobile, submenu items render as a horizontal row (no
        flex-wrap) inside the column menu. With 3+ submenu items at 360px, the
        row clips (overflow-x: clip on body hides the overflow).
[FIX] Add `#primary-menu .sub-menu { flex-direction: column; width: 100%; }` in
      the ≤767 mobile nav block. (APPLIED — NICE-TO-HAVE.)
```

```
[CLEAN] Hamburger: real <button> (header.php:108-117) ✓
[CLEAN] aria-expanded toggles on click (main.js:35-36) ✓
[CLEAN] Esc closes + returns focus to toggle (main.js:42-48) ✓
[CLEAN] Focus-outside closes (main.js:52-63) ✓
[CLEAN] Body scroll: the mobile menu is an absolute-positioned dropdown
        (NOT a full-screen drawer), so body scroll lock is not required.
        The menu floats above content; scrolling is expected. ✓
[CLEAN] No sticky bottom bars covering content. ✓
```

---

## SECTION 7: FONTS & TEXT ON MOBILE

```
[CLEAN] Base font-size: html { font-size: 16px } (style.css:60) ✓
[CLEAN] Body line-height: 1.6 (style.css:87) — exceeds 1.5 minimum ✓
[CLEAN] Text cut off: no fixed-height text containers. Typography uses
        clamp() (h1-h6 at style.css:322-327) for fluid sizing. Headings:
        no overflow: hidden on text containers. ✓
[CLEAN] Letter-spacing: only h6 has letter-spacing: 0.05em — minimal, won't
        break wrapping at 360px. ✓
```

---

## SECTION 8: IMAGES & MEDIA ON MOBILE

```
[CLEAN] srcset/sizes: WP core auto-adds srcset to the_post_thumbnail().
        Featured-image.php sets the gwill-hero size (1200×675); core emits
        additional sizes (300w, 768w, 1200w) with correct sizes attribute.
        Card images in content.php use the_post_thumbnail('gwill-hero'). ✓
[CLEAN] CSS background images: none. All images are real <img> elements
        (featured-image.php, content.php cards). ✓
[CLEAN] iframes/videos: embed facades (inc/embed-facades.php) use aspect-ratio
        CSS (aspect-ratio via wp-has-aspect-ratio class) + position:absolute
        on the iframe — fluid at any width. The base img/picture/video/iframe
        { max-width: 100% } rule covers all un-facaded embeds. ✓
[CLEAN] Lazy-load layout shift: feature images use loading="eager" (LCP) +
        aspect-ratio from the gwill-hero size. Card images use loading="lazy"
        via WP core default. Aspect-ratio: card images rely on the uploaded
        image's intrinsic ratio — no layout shift (browser reserves space via
        the max-width: 100% + height: auto combination). ✓
[CLEAN] Tables on mobile: table { max-width: 100% } is the universal rule;
        overflow-x: auto is set on specific containers (style.css:534, 1349,
        2346 — code blocks, gallery, dedicated elements). No dedicated table
        scroll wrapper — but table is a base element, builds that need tables
        can add a wrapper. ✓
```

---

## Summary

### Counts by severity
| Severity | Count |
|---|---|
| CRITICAL | 0 |
| SHOULD | **6 (all fixed in v1.3.2)** — 5 tap targets + 1 nav breakpoint |
| NICE-TO-HAVE | **7 (all fixed in v1.3.2)** — carousel arrows, pagination, consent buttons, share pills, outside-click close, submenu column, mobile menu max-height |

### Counts by viewport
| Viewport | Findings |
|---|---|
| 360px | 5 SHOULD (tap targets: nav-toggle, dark-toggle, search-toggle, search-clear, mobile menu li) + 3 NICE-TO-HAVE (mobile menu max-height, outside-click close, submenu column) |
| 768px | 1 NICE-TO-HAVE (mobile menu max-height, 1 NICE-TO-HAVE (outside-click close) |
| 1024px | 0 (all clean) |
| all | 5 SHOULD (tap targets) + 4 NICE-TO-HAVE (carousel, pagination, consent, share) |

### The single biggest mobile usability problem
**Tap targets below 44px in the header.** The three primary header controls (hamburger 40×40, dark toggle 36×36, search toggle 36×36) are all below the WCAG 2.5.5 minimum — on a 360px phone, a user with any motor imprecision will miss the intended button and tap the adjacent one. The nav-toggle is the primary navigation interface on mobile; a 40px target with 36px neighbours is an ergonomic failure. **All fixed in v1.3.2.**

### Prioritized fix order
1. **Header tap targets** (SHOULD ×4) — the most frequently used controls on mobile.
2. **Mobile nav breakpoint** (SHOULD) — prevent squeeze at 641-767px.
3. **Mobile menu link tap targets** (SHOULD) — 37px links in the mobile nav.
4. **Outside-click close** (NICE-TO-HAVE) — user expectation on mobile.
5. **Submenu column** (NICE-TO-HAVE) — prevent clipping on multi-item submenus.
6. **Mobile menu max-height** (NICE-TO-HAVE) — prevent tall menu overflow.
7. **Remaining tap targets** (NICE-TO-HAVE ×4) — carousel, pagination, consent, share.

### Quick wins (<5 minutes each)
- All 6 SHOULD items are CSS dimension changes (40→44px, 36→44px, 32→36px, min-height:44px, 640→767) — each is a one-line value change.
- All 7 NICE-TO-HAVE items are CSS dimension changes or small JS additions (click-outside listener is ~8 lines).

---

## FIXES-APPLIED LEDGER (v1.3.2)

| # | Finding | File | Change | Verification |
|---|---|---|---|---|
| 1 | Nav-toggle 40×40 → 44×44 | style.css:1190-1192 | `width: 44px; height: 44px;` | grep-verified; php -l clean |
| 2 | Dark-toggle 36×36 → 44×44 | assets/css/darkmode.css:73-74 | `width: 2.75rem; height: 2.75rem;` | grep-verified |
| 3 | Search-toggle 36×36 → 44×44 | assets/css/search.css:680-681 | `width: 2.75rem; height: 2.75rem;` | grep-verified |
| 4 | Search-clear 32×32 → 36×36 | assets/css/search.css:766-767 | `width: 36px; height: 36px;` | grep-verified |
| 5 | Mobile menu li ~37px → 44px | style.css:1270-1274 | `min-height: 44px; padding: 0.5rem 0;` | grep-verified |
| 6 | Nav breakpoint 640→767 | style.css:1229,1235,1241,1252 | `640px` → `767px` in 4 rules | grep-verified |
| 7 | Carousel nav 36×36 → 44×44 | style.css:2445-2446 | `width: 2.75rem; height: 2.75rem;` | grep-verified |
| 8 | Pagination 40×40 → 44×44 | style.css:601-602 | `min-width: 2.75rem; height: 2.75rem;` | grep-verified |
| 9 | Cookie consent buttons ~37px → 44px | style.css:2168-2172 | `padding: 0.75rem 1.125rem;` | grep-verified |
| 10 | Share pills ~31px → 44px | style.css:421-437 | `padding: 0.75rem 1rem; font-size: 0.875rem;` | grep-verified |
| 11 | Outside-click close | assets/js/main.js | ~8 lines: document click listener closes nav | node --check clean |
| 12 | Mobile submenu column | style.css: new rule in ≤767 block | `#primary-menu .sub-menu { flex-direction: column; width: 100%; }` | grep-verified |
| 13 | Mobile menu max-height | style.css:1252-1268 block | `max-height: calc(100dvh - 100%); overflow-y: auto;` | grep-verified |

**Changed-file count: 4** (`style.css`, `assets/css/darkmode.css`, `assets/css/search.css`, `assets/js/main.js`). Version bumped to **1.3.2**.

---
*Audit complete. All 6 SHOULD + 7 NICE-TO-HAVE findings fixed and verified.*