# GWill Starter — UI Makeover List

The audit that produced this list ran 2026-09-07 against v1.11.0: every
surface was read from the real templates + CSS, and the header was
re-measured in a real browser (Obscura) at 360/390/414px with faithful
markup from header.php and the real CSS chain (style.css + search.css +
darkmode.css).

## The measured mobile-header problem (proven)

- At 360px the theme pill (142px even icon-only) + search (44px) +
  burger (44px) consume **73.7%** of the header row; the brand is
  crushed to 58px, its title + description wrap, and the header grows
  to **262px tall (~⅓ of the viewport)**.
- At 414px controls still eat 62.8% (header 178px).
- The open search dropdown at 360px leaves the input wrap **160px
  wide** with a 44px clear button inside it (~116px of typing space).
- Tap targets all measured 44×44 (the one thing already right).

## Standing constraints (every item obeys both)

1. **Token-driven** — everything uses `--color-*` / `--form-radius` /
   `--spacing` tokens; dark mode inverts free, client builds reskin by
   overriding tokens only.
2. **Opt-in or harmless** — no item changes the lean default look of a
   fresh build unless a Customizer toggle/filter explicitly turns it on.
   The starter stays a base, not an opinionated design.

Priority order: C17 (cards) → A1-A3 (mobile header diet + search sheet)
→ H40 (tables) → B12 (404) → E29 (footer) → A5 (active nav) → B11
(generic search form).

---

## A. Header & mobile row (the hot spot)

> **SHIPPED v1.11.2** - A1 (row diet + sheet pill), A3/A4 (search sheet),
> A5 (active nav), A6 (hover indicator), A7 (compact-on-scroll),
> A8 (sheet animation), A9 (dead class). Measured after: controls
> 73.7% -> 28.2% of the 360px row, brand 58 -> 192px, header 262 -> 174px,
> sheet input 160 -> 328px, compact 121 -> 65px. A2 is dissolved by A1
> (the pill no longer occupies the row on mobile); A10 documented no-op.

1. **Mobile row diet** — free the header row below 767px: move the theme
   pill into the mobile menu sheet (full-width, labeled segments — the
   engine's multi-group sync already supports it), keeping only search +
   burger in the row. Frees ~142px at 360px.
2. **Pill floor too heavy** — 3×44px segments = 142px minimum. Solved by
   the sheet move at mobile; desktop row has room and keeps 44px
   segments.
3. **Search dropdown → full-width top sheet on mobile** (Google/finance
   pattern): slide-down sheet spanning the viewport, rounded bottom
   corners, page dim behind — instead of the 280px anchored panel whose
   input gets 160px.
4. **Clear button placement** — dissolved by #3 (the input wrap gets
   ~240px at 360px once the sheet is full-width). Keep clear-inside
   (44px, finance pattern).
5. **No active nav state** — `.current-menu-item` has zero rules
   (verified). Add accent color + underline indicator (desktop) and
   bold + indicator (mobile sheet). Trivial, high perceived polish.
6. **Desktop nav links are bare text** — hover underline-slide indicator,
   gated `@media (hover: hover)`.
7. **Header doesn't compact on scroll** — optional compact-on-scroll
   mode (Customizer toggle, default on when sticky is on): reduced
   padding, description hidden, logo shrunk on scroll-down; restored on
   scroll-up. Standard pro behavior.
8. **Mobile menu sheet is a plain bordered list** — open animation
   (slide-down fade), row rhythm polish, sheet hosts the theme pill
   (from #1).
9. **`.icon-btn` is dead CSS** — the class sits in header.php markup
   with zero rules anywhere (verified). Remove from markup.
10. **Logo lockup rhythm** — documented no-op: the markup never
    co-renders logo + text wordmark (has_custom_logo() branches), so a
    lockup rule would be dead code in a lean starter. Revisit if a build
    needs both.

## B. Search surfaces

11. **Generic searchform.php is browser-default** — used on 404 +
    no-results; zero `.search-field`/`.search-submit` rules exist
    (verified). Give it the token treatment (bordered field + accent
    submit) so it matches the dropdown.
12. **404 page is bare** — giant ghost "404" numeral, styled search
    card, popular-posts row, filterable CTA.
13. **content-none has no visual state** — ghost illustration slot +
    CTA row via the existing `gwill_search_no_results_cta` filter.
14. **Dropdown zero-results state** — inline suggestion chips (the
    related-terms engine already exists).
15. **Correction banner on results page** — card treatment with
    "Search instead" as a proper link-button.
16. **Badge names are tech-legacy** — `.badge-android/webdev/software`
    work but read wrong in a base theme; rename to neutral tokens with
    backward-compat aliases.

## C. Post lists & cards (the biggest visual poverty)

17. **Lists are a plain text stack** — zero card chrome (verified).
    Opt-in card treatment (border + radius + padding, token-driven,
    Customizer toggle `gwill_card_lists`). Highest-impact single item.
18. **No horizontal card variant** — optional thumb-left layout ≥768px
    (part of the #17 toggle family).
19. **No grid option** — optional 2-col archive grid (Customizer) for
    image-heavy builds.
20. **Card hover affordance** — thumbnail zoom + title color, gated
    `(hover:hover)` (ships with #17).
21. **Meta row is muted middot text** — category chip treatment (the
    `.entry-cat` accent already exists).
22. **Archive header is a bare H1** — count pill ("12 articles"),
    description, thin accent rule.

## D. Singular article

23. **Author box is plain** — card chrome + avatar ring + accent top
    stripe.
24. **Related posts have no hover lift** — parity with cards.
25. **Prev/next post nav is two text links** — split-card treatment
    (bordered boxes, arrows + titles, thumbnail optional).
26. **Tag pills are plain-bordered** — hover tint + accent border.
27. **Share pills use hardcoded brand colors** — ghost/outline variant
    filter for neutral builds; brand fills stay default.
28. **No hero treatment for the featured image** — optional full-bleed
    image slot with gradient scrim (opt-in).

## E. Footer

29. **One centered paragraph** — optional 2-3 column layout partial
    (brand blurb / nav / contact) via filter; default stays minimal.
30. **No social row** — social icon set as an opt-in partial (svg set,
    token-colored).
31. **Footer rhythm** — breathing room, subtle top rule, credit line
    styling.

## F. Comments

32. **No card separation** — per-comment card chrome (bg token +
    radius), inverts free in dark mode.
33. **Reply link is plain text** — small ghost button family.
34. **Comment form is WP-default layout** — bolder labels, grouped
    fields (submit already token-styled).
35. **Comments title plain** — count chip ("3 comments").
36. **Avatar treatment** — ring exists; optional size bump.

## G. Global chrome

37. **Cookie consent is a full-width strip** — floating rounded card
    (bottom-sheet on mobile), token-driven, dismiss animation.
38. **Back-to-top is a flat circle** — optional scroll-progress ring
    (SVG stroke-dashoffset, no JS change).
39. **Sticky shadow only** — tie shadow + compact (#7) together.

## H. Content elements (includes one real gap)

40. **TABLES COMPLETELY UNSTYLED** — classic-editor + Gutenberg
    `.wp-block-table` have zero rules (verified). Striped rows, bordered
    head, overflow-x wrapper. A genuine gap, not just polish.
41. **Content lists use default markers** — spacing rhythm + optional
    accent marker.
42. **Code blocks have copy + label** — optional file-window chrome
    (mac dots header), opt-in.
43. **Content images** — shared radius/shadow token so uploads match
    the card language.
44. **Blockquote exists** — large-quote typography + attribution.

## I. Feature components (light polish only)

45. **Apps hub** — tinted icon chips + hover lift.
46. **Pricing featured card** — stronger featured state.
47. **Testimonials** — quote-mark glyph + avatar overlap.
48. **Portfolio grid** — hover meta overlay option.
49. **Exit-intent panel** — token bg + accent top border (plain white
    card today; verify the dark rule exists).
50. **Newsletter/inline forms** — focus glow to match the dropdown
    input (color-mix soft ring).
51. **Multistep progress** — step dots + labels, not just a bar.
52. **Login branding** — reviewed as existing; polish only on request.

## J. Micro-delight (cheap, safe)

53. **`::selection` color** — accent-tinted selection, one line.
54. **Focus rings** — already consistent 2px accent; keep.
55. **Smooth hover elevations** — shared `--shadow-lift` token for one
    rhythm across cards/buttons/pills.

---

*Created 2026-09-07 (v1.11.1). Progress tracked per section in
CHANGELOG.md as sections ship.*
