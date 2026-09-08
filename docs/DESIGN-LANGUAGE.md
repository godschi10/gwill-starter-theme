# GWill Starter — Design Language

The contract every UI decision obeys. Written before the Section A rebuild
(v1.11.2) after the A–J campaign was rolled back: the campaign changed
surfaces without a shared visual system, and shipped nine releases without
one design review. This document is the correction.

## 1. Principles

1. **Quiet by default.** The starter is a base, not a statement. Structure
   comes from space, hairlines, and type weight — not shadows, gradients,
   or color. Decoration is opt-in per build.
2. **One control family.** Every header/toolbar button (search, hamburger,
   pill segments, close) shares one spec: 44px hit target (law), 8px
   radius, transparent ground, hover = 5% surface tint + accent icon,
   active = 0.94 press, focus-visible = 2px accent ring. No ad-hoc
   one-off button styles.
3. **Tokens only.** No literal colors or radii in new rules; everything
   resolves from `--color-*` / `--form-radius` so dark mode and client
   reskins come free.
4. **States are complete or absent.** An interactive element defines
   hover, focus-visible, and (where meaningful) active + current. A
   control that can't carry all states gets none beyond focus.
5. **Motion is 150–200ms ease-out, hover-gated `(hover:hover)`,**
   reduced-motion honored by the global reset. Nothing animates on load.
6. **Elevation = floating.** Shadows exist only on things that float
   (dropdown, stuck header, sheet). In-flow content is flat.

## 2. Type scale (existing, formalized)

| Role | Size | Weight |
|---|---|---|
| Body | 1rem | 400 |
| Nav / UI controls | 0.95rem | 600 |
| Meta / captions | 0.8125rem | 400–600 |
| Brand title | 1.25rem | 700 |

## 3. Space

4px base rhythm. Header gutter `var(--spacing)`; row gaps 1rem desktop /
0.5rem mobile. Section rhythm multiples of `var(--spacing)`.

## 4. Color roles

`--color-primary` ink · `--color-accent` interactive only ·
`--color-muted` secondary text · `--color-border` structure hairlines ·
`--color-border-input` field borders (WCAG 3:1).

## 5. Header spec (Section A v2 target)

- Desktop: brand left, nav right; links 600-weight, muted→ink on hover
  with a 2px accent underline slide; current page permanently underlined.
- Mobile ≤767px: single row `[brand] [pill icon-only] [search] [menu]`,
  brand truncates with ellipsis — the row NEVER wraps or balloons.
- Stuck (sticky on): hairline + 0 2px 8px shadow (baseline, kept).
- No compact-on-scroll, no control relocation into drawers: the row fits
  because controls are one family and the brand truncates.

## 6. Review gate

Each makeover section ships only after before/after screenshots at
360px and 1280px are delivered to the King and approved.
