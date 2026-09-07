# Starter theme - verification suites

Suites are tree-parametric: run them against ANY copy of the theme
(repo, deployed, fresh clone) so a claim is never "verified" only where
it was written.

## test-pill.js - tri-state darkmode battery (27 assertions)

Added v1.11.0 with the finance-theme tri-state pill port. Verifies the
engine extracted from the REAL `inc/darkmode.php` against the markup of the
REAL `template-parts/ui/theme-pill.php`:

- Part A parse-time resolution (stored dark / stored light / system + OS
  dark / system + OS light / garbage-key normalizes to system)
- The finance v1.12.70 fix: pills sync to the REAL stored choice, never
  the server-rendered `data-current="system"` default
- Click semantics (choice persists; `system` REMOVES the storage key -
  the starter contract; both desktop + mobile groups stay in sync)
- OS live-follow only while on system (explicit overrides hold through
  OS flips)
- Keyboard cycling (ArrowLeft/Right/Up/Down, Home, End, preventDefault)
- Private-mode: localStorage throwing never fatals, theme still applies

Run:

    ./tests/verify-battery.sh .                    # this repo copy
    ./tests/verify-battery.sh /path/to/fresh-clone # any other tree

Requires: php, python3, node + jsdom (falls back to the VPS install at
~/.hermes/hermes-agent/node_modules when not locally installed).
