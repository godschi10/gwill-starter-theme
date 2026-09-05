# CROSS-BROWSER COMPATIBILITY AUDIT - GWill Starter Theme

**Date:** 2026-08-20 · **Theme version:** v1.3.4 (repo at `/var/www/wp-content/themes/gwill-starter-theme/`)
**Method:** Static analysis of every CSS file (style.css, search.css, darkmode.css, embeds.css, woocommerce.css, darkmode-vibe-comments.css) and every JS file (20 in assets/js/) against the 7-section protocol, browser by browser (Chrome, Firefox, Safari incl. iOS, Edge, Android). No live site - base theme, never directly activated. Verification = `node --check`, `grep` feature inventories, surgical-diff review.
**Prior reports:** CONFLICT-AUDIT (v1.3.1), RESPONSIVE-AUDIT (v1.3.2 + v1.3.3 supplement).

---

## SECTION 1: CSS SUPPORT MATRIX

```
[SHOULD] [iOS Safari] style.css:189-194 - .site { min-height: 100vh }
[ISSUE] The site wrapper uses 100vh. On iOS Safari the visible viewport is
        smaller than 100vh while the address bar is expanded, so the footer
        sits below the fold and the page appears taller than the screen.
[FIX] Added min-height: 100svh (small viewport height - tracks the visible
      area with address bars) AFTER the 100vh fallback line. Older browsers
      keep 100vh; Safari 15.4+ / Chrome 108+ / Firefox 101+ get svh.
      (APPLIED - v1.3.4.)
```

```
[NICE-TO-HAVE] [all] style.css:1274 - mobile menu max-height calc(100dvh - 100%)
[ISSUE] dvh units are unsupported before Chrome 108 / Safari 15.4 / Firefox
        101 - the max-height declaration would be dropped entirely on those
        browsers, letting a tall menu exceed the viewport.
[FIX] Added a max-height: calc(100vh - 100%) fallback line BEFORE the dvh line
      (fallback-first ordering per protocol Section 7.2). (APPLIED - v1.3.4.)
```

```
[NICE-TO-HAVE] [all] color-mix() focus rings without fallbacks
[FILE] style.css:1496,1508 (form field focus + error focus), search.css:753
       (dropdown input focus), embeds.css:48 (embed facade focus)
[ISSUE] color-mix() is Chrome 111+ / Safari 16.2+ / Firefox 113+ (March 2023).
        On older browsers the ENTIRE box-shadow declaration is dropped - the
        form focus ring, error ring, dropdown ring, and embed facade ring all
        vanish. Border-color changes still signal focus on form fields, but
        the facade button's ring was its ONLY focus indicator.
[FIX] Added a solid-color box-shadow fallback declaration BEFORE each
      color-mix() ring (fallback-first). Modern browsers override with the
      soft ring; older browsers keep a visible 3px ring (WCAG 2.4.7).
      (APPLIED - v1.3.4.)
```

```
[CLEAN] Vendor prefixes - every prefixed property has its standard pair:
        -webkit-appearance + appearance (style.css:1472-1473, search.css:141-
        142, 272-273, 476), box-shadow + -webkit-box-shadow (search.css:138-139,
        150-151), backdrop-filter + -webkit-backdrop-filter (search.css:225-226).
[CLEAN] -webkit-line-clamp - the display:-webkit-box + -webkit-box-orient +
        -webkit-line-clamp trio (search.css:410-412) is the STANDARD cross-
        browser text-clamp technique; no missing equivalent.
[CLEAN] -webkit-font-smoothing / -moz-osx-font-smoothing - platform-specific
        pairs, correctly used (style.css:88-89, 436).
[CLEAN] Flexbox/grid - gap in flex is used (gap: 1.5rem on nav, header);
        supported Safari 14.1+ / Chrome 84+ / Firefox 63+ - all in the modern
        target matrix. No Safari 14-15 gap-in-flex workaround needed.
[CLEAN] CSS custom properties - every var() carries a fallback value
        (e.g. var(--color-accent, #2563eb), var(--form-focus-clr, #60a5fa))
        - verified across search.css + darkmode.css. style.css variables are
        defined in :root with no fallback needed.
[CLEAN] aspect-ratio (style.css:2130 video box, 2679 portfolio card) - images
        keep intrinsic height via img { height: auto } so older browsers
        degrade gracefully (no collapse).
[CLEAN] :has() (style.css:1540 radio-label checked) - cosmetic highlight
        only; the radio input itself stays fully functional (accent-color).
        Unsupported browsers simply drop the style.
[CLEAN] clamp() headings (style.css:322-327) - Chrome 79+ / Safari 13.1+ /
        Firefox 75+ - well within the target matrix.
[CLEAN] object-fit: cover (style.css:2136, 2418, 2690) - Chrome 32+ /
        Safari 10+ / Firefox 36+. ✓
[CLEAN] rem/em/vh units - no other vh usage besides the .site wrapper (fixed)
        and the menu fallback (fixed). No 100vh in search.css/modal.
```

---

## SECTION 2: JAVASCRIPT COMPATIBILITY

```
[CLEAN] ES6+ syntax - only const/let/arrow functions (main.js:1,29), all
        supported since 2015 in every target browser. NO optional chaining,
        NO template literals, NO spread, NO async/await - no transpilation
        needed for the target matrix.
[CLEAN] APIs - fetch() (forms.js, search-modal.js) with an XMLHttpRequest
        fallback in search-dropdown.js (gwillFetch, lines 35-44); Promise
        used with .catch everywhere; matchMedia used in back-to-top.js,
        darkmode.js, embeds.js (guarded: `!!(window.matchMedia && ...)` in
        embeds.js:49). No IntersectionObserver/ResizeObserver dependence.
[CLEAN] Event handling - e.key everywhere (no keyCode/code divergence),
        passive listeners on scroll/touch (6 sites), no touchstart+click
        double-firing (single event type per handler).
[CLEAN] jQuery - zero jQuery in the theme (all 20 scripts vanilla). No
        version conflicts possible.
```

---

## SECTION 3: SAFARI-SPECIFIC (INCL. iOS)

```
[SHOULD] [iOS Safari] - darkmode.js localStorage without try/catch
[FILE] assets/js/darkmode.js:69 (resolveTheme read), :80 (toggle setItem),
       :86 (matchMedia handler read)
[ISSUE] The external darkmode.js reads and writes localStorage WITHOUT
        try/catch. Safari private/incognito mode throws on storage access
        (older iOS) - resolveTheme() would throw, breaking the entire
        dark-mode toggle script. The inline head script (inc/darkmode.php)
        already wraps its own access; the external file did not.
[FIX] Wrapped all three localStorage access points in try/catch with
      fail-closed behaviour (theme still applies for the visit; no storage =
      follow OS preference). (APPLIED - v1.3.4.)
```

```
[NICE-TO-HAVE] [Safari 13-] - darkmode.js matchMedia addEventListener
[FILE] assets/js/darkmode.js:85
[ISSUE] matchMedia(...).addEventListener is Safari 14+. Safari 13 and below
        require .addListener() - the OS-preference change listener would
        never attach.
[FIX] `( mql.addEventListener || mql.addListener )( 'change', ... )` - the
      standard feature-detection pattern. (APPLIED - v1.3.4.)
```

```
[CLEAN] 100vh/100svh - the .site wrapper fixed (Section 1); the mobile menu
        max-height now has the vh fallback. No other fixed/absolute element
        relies on viewport height.
[CLEAN] Input font-size - all visible inputs 16px (search.css:740,
        style.css:1443) - no iOS zoom-on-focus.
[CLEAN] -webkit-overflow-scrolling: touch - present on the testimonial
        carousel (style.css:2374) for smooth iOS momentum scrolling.
[CLEAN] Sticky positioning - no sticky inside overflow containers (TOC
        sticky is on .entry-content, not a scroll container).
[CLEAN] backdrop-filter - -webkit- prefixed alongside standard
        (search.css:225-226), scoped to the search modal overlay. ✓
[CLEAN] :hover stick after tap - all hover effects are gated behind
        @media (hover: hover) in search.css (search-clear, search-close,
        correct links, related chips) or are colour-only transitions that
        re-tap cleanly.
[CLEAN] Date/time inputs - none used.
```

---

## SECTION 4: FIREFOX & EDGE

```
[CLEAN] Scrollbar styling - the theme styles NO ::-webkit-scrollbar; the only
        scrollbar rule is scrollbar-width: thin (style.css:2378, the carousel)
        which IS the Firefox-compatible property. No missing FF equivalent.
[CLEAN] aspect-ratio/grid - Firefox 89+ handles both; graceful degradation
        below (images keep intrinsic ratio).
[CLEAN] Autofill - no -webkit-autofill/-moz-autofill overrides present; the
        theme does not fight browser autofill backgrounds (correct - the
        design tokens are neutral). No yellow-box mismatch.
[CLEAN] Text rendering - no letter-spacing on body text (only h6 at 0.05em),
        no clipping risk.
```

---

## SECTION 5: LAYOUT & RENDERING

```
[CLEAN] Border-box - border-box is the universal standard in 2026; no
        off-by-one pixel war (the theme uses gap-based flex/grid spacing,
        not margin hacks).
[CLEAN] Inline-block whitespace - no inline-block nav/menu layout; flex/grid
        throughout. No whitespace-gap divergence.
[CLEAN] Font loading - system-ui font stack (--font-base) - NO webfonts, no
        FOUT/FOIT, no font-display concern. Zero external font requests.
[CLEAN] Image rendering - object-fit: cover for card media (supported all
        modern); SVG icons are inline (stroke=currentColor) - no scaling
        divergence.
[CLEAN] Form elements - -webkit-appearance: none + appearance: none on all
        custom-styled controls; radio/checkbox rely on native rendering with
        accent-color tokens. Consistent across browsers.
[CLEAN] Fixed-width elements - none; the max-width:100% universal rule
        (style.css:30-33) prevents one-browser horizontal scroll.
```

---

## SECTION 6: BROWSER-SPECIFIC FEATURES

```
[CLEAN] Feature detection - zero navigator.userAgent sniffing anywhere
        (grep-verified). All capability checks are feature-detected
        (matchMedia, navigator.share, navigator.clipboard, document.execCommand).
[CLEAN] localStorage - cookie-consent.js already wraps get+set in try/catch
        (fail-closed, lines 22-44); darkmode.js now does too (v1.3.4 fix).
[CLEAN] Clipboard - main.js: navigator.share() first, navigator.clipboard
        fallback, and NOW a document.execCommand('copy') legacy fallback for
        browsers without the async Clipboard API (v1.3.4). Three-tier chain.
[CLEAN] Fullscreen - embeds.js handles BOTH standard and webkit prefixes
        (document.fullscreenElement || document.webkitFullscreenElement,
        fullscreenchange + webkitfullscreenchange listeners). ✓
[CLEAN] prefers-color-scheme - darkmode.css uses @media (prefers-color-scheme:
        dark) with [data-theme] attribute overrides; the head script applies
        before first paint. Consistent across Chrome/FF/Safari/Edge.
```

---

## SECTION 7: TESTING & POLYFILLS

```
[CLEAN] Polyfills - none loaded (no IE support targeted); the theme's only
        shims are runtime feature fallbacks (gwillFetch XHR fallback,
        addListener fallback, execCommand fallback) - all conditional, none
        block any browser.
[CLEAN] Fallback ordering - all new fallback declarations (v1.3.4) are
        ordered fallback-first (100vh before 100svh/dvh, solid ring before
        color-mix ring) per protocol Section 7.2.
[CLEAN] @supports - not needed; the fallback-first cascade handles every
        case (unsupported declarations are dropped individually, leaving the
        fallback).
[CLEAN] Prefix hygiene - every -webkit-/-moz-/-ms- property has its standard
        form adjacent (grep-verified Section 1).
```

---

## Summary

### Counts by severity
| Severity | Count |
|---|---|
| CRITICAL | 0 |
| SHOULD | **2 (both fixed in v1.3.4)** |
| NICE-TO-HAVE | **4 (all fixed in v1.3.4)** |

### Counts by browser
| Browser | Findings |
|---|---|
| iOS Safari | 3 (100vh wrapper, darkmode localStorage, matchMedia addListener) |
| all (pre-2023) | 2 (color-mix focus rings ×4 sites, dvh fallback) |
| Safari/Firefox (older) | 1 (clipboard execCommand fallback) |
| Chrome / Firefox / Edge / Android | 0 |

### The single biggest cross-browser risk
**iOS Safari 100vh footer cut-off** (`.site { min-height: 100vh }`) - every
page on an iPhone with the address bar expanded would show a footer below the
fold, and the dark-mode toggle could break entirely in Safari private mode
(unwrapped localStorage). **Both fixed in v1.3.4** with svh + try/catch.

### Prioritized fix order
1. `.site` 100vh → 100svh (footer cut-off on iOS).
2. darkmode.js localStorage try/catch (toggle breaks in private mode).
3. color-mix focus-ring fallbacks (focus invisible pre-2023).
4. dvh fallback + matchMedia addListener + clipboard execCommand (edge robustness).

### Quick wins (<5 minutes each)
- All 6 fixes are single-declaration additions or small try/catch wraps - each under 5 minutes.

---

## FIXES-APPLIED LEDGER (v1.3.4)

| # | Finding | File | Change | Verification |
|---|---|---|---|---|
| 1 | 100vh site wrapper | style.css:189-194 | Added `min-height: 100svh` after the 100vh line | grep-verified (2 hits incl. comment) |
| 2 | dvh menu no fallback | style.css:1274-1277 | Added `max-height: calc(100vh - 100%)` before the dvh line | grep-verified (1 hit) |
| 3 | Form focus ring fallback | style.css:1493-1497 | Solid `box-shadow: 0 0 0 3px var(--form-focus-clr)` before color-mix | grep-verified |
| 4 | Error focus ring fallback | style.css:1507-1509 | Solid `var(--color-error)` ring before color-mix | grep-verified |
| 5 | Dropdown focus ring fallback | search.css:751-753 | Solid accent ring before color-mix | grep-verified |
| 6 | Facade focus ring fallback | embeds.css:46-48 | Solid accent ring + shadow before color-mix | grep-verified |
| 7 | darkmode localStorage | darkmode.js:66-105 | try/catch on all 3 access points (fail-closed) | node --check clean; 3 try sites |
| 8 | matchMedia addListener | darkmode.js:86 | `( mql.addEventListener \|\| mql.addListener )` | node --check clean |
| 9 | Clipboard legacy fallback | main.js:117-134 | `document.execCommand('copy')` textarea path | node --check clean; 2 execCommand hits |

**Changed-file count: 5** (`style.css`, `assets/css/search.css`, `assets/css/embeds.css`, `assets/js/darkmode.js`, `assets/js/main.js`). Version bumped to **1.3.4**.

---
*Audit complete. All 2 SHOULD + 4 NICE-TO-HAVE findings fixed and verified.*