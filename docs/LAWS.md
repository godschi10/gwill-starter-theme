# ⚖️ GWill Starter  -  Theme Laws

Absolute rules for every build made from this starter. Each law was paid for by a
real incident on a live GWill site  -  the incident is recorded under the law so the
cost is never forgotten. Breaking a law re-creates its incident on the client's
site.

Codified 2026-08-29 by royal order, the day the finance theme finally mastered
push notifications. These laws ship INSIDE the theme so every fresh build
inherits them automatically  -  a clone from GitHub or GitLab carries them all.
Read before building; enforce during every deploy.

---

## L1  -  The theme is self-contained: vendor ships with it

**Rule.** `vendor/` is COMMITTED to the repo. `.gitignore` must never carry a
bare `vendor/` exclude. Any deploy exclude must be anchored
(`--exclude='/vendor/'`)  -  and a GWill build never strips vendor at all.

**Incident.** Finance v1.0.246: the WebPush library was committed, but the
deploy ran `rsync --exclude='vendor/'`  -  unanchored, so it matched EVERY
directory named `vendor` and silently dropped the library from the live theme →
`require_once` fatal, push dead on a live site. The starter then carried the
same bare `vendor/` line in its own `.gitignore`  -  it would have prevented
committing the library at all, shipping a broken download from GitHub/GitLab.

**Verify.** After any dependency change: `git ls-files | grep -c '^vendor/'`
must be greater than 0, and a fresh `git clone` must contain
`vendor/autoload.php`.

---

## L2  -  sw.js is never cacheable

**Rule.** `/sw.js` must be served `no-cache, no-store, must-revalidate`. It must
be matched BEFORE (or exact-matched against) any generic static-asset expires
block, or a year-long cache keeps a stale, broken worker on clients.

**Incident.** Finance v1.0.258: a generic `expires 1y` rule on `.js` matched
`/sw.js` → the browser (and an installed WebAPK) kept running a stale broken
worker → push handlers never install even after every fix ships. Presents as
"works in browser, dead after PWA install"  -  the server looks perfect the
whole time.

**Nginx (exact-match wins over any regex, order-proof):**

```nginx
location = /sw.js {
    expires 0;
    add_header Cache-Control "no-cache, no-store, must-revalidate";
}
```

**Verify.** `curl -sI https://<site>/sw.js | grep -i cache-control` must show
`max-age=0` / `no-store`.

---

## L3  -  Permission before subscribe (the dead-bell law)

**Rule.** The push subscribe path MUST call `Notification.requestPermission()`
BEFORE `reg.pushManager.subscribe()`. A bell that goes straight to
`subscribe()` is a dead bell on any device that never asked.

**Incident.** Finance v1.0.257: on `default` (never-asked) permission, Chrome
rejects `subscribe()` with `NotAllowedError` WITHOUT showing any prompt  -  bell
tap does nothing, no error, no network request, DB stays empty. Every health
signal stayed green (site 200, SW baked, REST 200)  -  only a real bell-click
catches it.

**Verify.** A real-browser E2E: CLICK the bell on a fresh profile. Never trust
200s; never trust CDP permission-grant shortcuts (they bypass the prompt path
entirely and cannot catch this).

---

## L4  -  Custom routes are real rewrites  -  all three pieces

**Rule.** Any new URL endpoint (manifest, sitemap, custom route) needs ALL
THREE: (1) `add_rewrite_rule()` on `init`, (2) the `query_vars` filter, (3) a
`redirect_canonical` guard returning false when the var is set. The renderer
gates on `get_query_var()`.

**Incident.** Finance v1.0.255→256: a `template_include` sniff on
`$_SERVER['REQUEST_URI']` rendered the manifest body  -  but WordPress had
already resolved the query as a 404, so the response carried the correct BODY
with a **404 status**. Browsers hard-fail a 404 manifest; install never
worked, and `curl` looked fine until the status code was checked.

**Verify.** `curl -s -o /dev/null -w '%{http_code}' https://<site>/<route>`
must be **200**  -  the right body is not proof.

---

## L5  -  Bind all instances, never getElementById

**Rule.** Any element that can render more than once is bound with
`querySelectorAll()` + a loop. Duplicate IDs happen in practice  -  desktop AND
mobile footers both render the same widget.

**Incident.** Finance v1.0.261: desktop and mobile footers both rendered
`id="gwill-bell"` → `getElementById` bound only the first → the bell on every
phone was dead while desktop worked.

**Verify.** Count the rendered instances in the DOM; click each one.

---

## L6  -  No button may die busy

**Rule.** Any async button handler gated on a `busy` flag wraps its promise
chain in a `settle()` runner  -  a hard timeout (12s) that ALWAYS restores the
button and clears `busy` in every path: resolve, reject, timeout, exception.

**Incident.** Finance v1.2.2: "Turn off notifications" died forever  -  an
unguarded chain (`serviceWorker.ready` → `getSubscription` → server POST) hung
once → `busy` stuck `true` → every later tap was silently swallowed with zero
feedback. The REST endpoint was fine the entire time.

**Pattern.** See `settle()` in `assets/js/push.js`  -  the reusable
implementation. Fallbacks must still do the device-side work (e.g.
`sub.unsubscribe()`) because the browser call is what actually stops push.

**Verify.** DevTools → Network → Offline, click the button, restore the
network  -  the button must recover and re-render, never freeze.

---

## L7  -  Self-heals need an opt-out escape hatch

**Rule.** Any feature that auto re-enables on load (re-subscribe, re-connect)
must FIRST check a persistent user-opt-out flag (localStorage) that the
explicit "off" action sets and an explicit "on" clears.

**Incident.** Finance v1.2.3: the init self-heal saw `permission === 'granted'`
+ no subscription and silently re-subscribed on EVERY page load  - 
unsubscribing keeps permission `granted`, so "Turn off" was unenforceable:
refresh the page and it was back.

**Verify.** Turn off → refresh ×3 → state must remain off.

---

## L8  -  A version bump travels with every asset change

**Rule.** Any CSS/JS edit bumps `Version:` in `style.css` (the cache-buster)
 -  and the bump must reach the LIVE file, not just the repo.

**Incident.** Finance v1.0.137: old sheet kept serving, new rules silently
never parsed. v1.0.255: repo bumped, live not → the version-keyed rewrite flush
never fired and browsers kept old asset URLs.

**Verify.** After deploy, the page HTML must reference the new `?ver=`  - 
fetched from a PLAIN URL (see L9).

---

## L9  -  Cache probes are PLAIN URLs

**Rule.** Verification probes never carry `?query` strings  -  query strings
BYPASS the page cache and lie. After any deploy: purge the REAL page-cache
directory (`/var/run/nginx-cache`  -  not `/var/cache/nginx`) + a FULL nginx
restart (reload is not enough) + FPM restart + opcache file-cache wipe.

**Incident.** Finance v1.0.189/230: `?bust=` probes kept proving the server
"fixed" for hours while cached pages served stale markup to every visitor. The
probe bypassed the exact cache the visitors hit.

**Verify.** The plain URL (no query) shows the new asset version; a MISS→HIT
ladder serves fixed bytes to both mobile and desktop UAs.

---

## L10  -  Every deploy ends with diff -rq + served-bytes proof

**Rule.** After every deploy: (1) `diff -rq <repo> <live> --exclude=.git`
must print clean  -  `cp f1 f2 f3 dir/` dumps subdirectory files into the root
as dead strays; (2) anything that lives OUTSIDE the theme dir (root `sw.js`
publishes to the WP root  -  rsync NEVER reaches it) gets its own explicit copy
AND served-bytes proof.

**Incident.** Finance v1.0.226, v1.0.241, v1.0.185  -  THREE separate
occurrences: root strays shipped silently; the root `sw.js` served stale bytes
across two "shipped" releases while the repo looked perfect.

**Verify.** `diff -rq` clean + `curl -s https://<site>/sw.js | grep <new
generation marker>` shows the new token.

---

## L11  -  Android PWA installs move the notification permission to the APP

**Rule.** On Android, installing the site as a PWA (WebAPK) transfers the
origin's notification permission to the installed app. Chrome's site-info
then misleadingly shows "Notifications: blocked" even though the server
code is perfect. Do NOT ship server fixes for this  -  it is the
installed-app permission model, not a bug. The fix lives on the phone:
Settings → Apps → [PWA name] → Notifications (enable), or Clear data +
re-grant.

**Incident.** Finance, Aug 27 (screenshot-proven): after PWA install, push
stopped on the King's phone; Chrome's site settings read "blocked" while
every server signal was green. Hours were nearly lost to a phantom server
bug that was the OS permission model all along.

**Verify.** Server-side: real-browser bell-click E2E (L3) + the push send
returning 201. Phone-side: the app-level notification toggle  -  never trust
Chrome's site-settings label for an installed PWA.

---

## Fresh-build launch checklist

1. `git clone` → `vendor/autoload.php` present **(L1)**
2. nginx `location = /sw.js` no-cache block in place **(L2)**
3. Real-browser bell-click E2E on a fresh profile **(L3)**
4. `curl -w '%{http_code}'` = 200 on every custom route **(L4)**
5. Click every rendered instance of every widget **(L5)**
6. Offline-mode button test  -  nothing freezes **(L6)**
7. Turn off → refresh ×3 → still off **(L7)**
8. Live page HTML references the new `?ver=` **(L8)**
9. All probes on PLAIN URLs; caches fully purged **(L9)**
10. `diff -rq` clean + served-bytes SW proof **(L10)**
11. On Android: verify the APP-level notification toggle, not Chrome's site
    settings **(L11)**


---

## Law L12  -  A documented flag is a promise: grep call sites before shipping docs

**Rule.** Every optional feature documented behind a wp-config flag is a
promise that the code behind it works. Before documenting (or keeping)
any `GWILL_*` constant, grep the FULL tree for its call sites and prove
the called function EXISTS. A documented flag whose handler fatals is
worse than no flag  -  it converts documentation into a loaded weapon.

**Incident.** Starter theme, found Aug 30 2026 during the v1.5.0 recon:
`inc/forms/ajax.php` called `gwill_log_submission()` behind the
`GWILL_LOG_FORMS` flag documented since v1.0.20 (six years of
documentation), yet the function was defined NOWHERE. Any site that
followed the documented wp-config instructions would have crashed its
form submit with "Call to undefined function"  -  the flag was a latent
fatal. Found only because the v1.5.0 analytics recon grepped for the
call sites before building on top of them. Fixed by defining the
function in `inc/analytics.php` (v1.5.0).

**Verify.** For every documented `GWILL_*` flag: `grep -rn
"<flag>" inc/ --include="*.php"` AND `grep -rn "<handler_function>"
inc/`  -  both must return hits, and the handler must be defined in the
same tree. Run once per release over the whole wp-config documentation
block.

---

## Law L12 checklist item

12. Grep every documented `GWILL_*` flag's call sites  -  handler must
    exist in-tree **(L12)**

---

## Law L13  -  Verify ported code against its source, line by line

**Rule.** A port is not done when it runs  -  it is done when it matches the
source's semantics. Before shipping any module copied from an elder theme,
diff the port against the source function-by-function: every return value,
sentinel, and branch must carry the same truthiness the source guarantees.
A "semantically equivalent" shortcut that flips a truthy/falsy sentinel is
a vulnerability wearing a port's clothes.

**Incident.** Starter theme, Aug 30 2026, during the Tier-A v1.6.0 port:
the backup-code path in the 2FA port introduced `return 2; // Sentinel:
no backup codes exist` where the tech source returns `false`. PHP's truthy
`2` would have let `gwill_2fa_authenticate()`'s OR-chain treat a user with
ZERO backup codes as authenticated on a backup-code attempt  -  the port's
own self-review caught it pre-lint, and the 82-test battery pins it
(`zero backup codes → false`).

**Verify.** After porting any module: read the source's equivalent function
side-by-side with the port, line by line, and diff the RETURN VALUES first
(falsy `false`/`null`/`0`/`''` vs truthy anything). Add a battery test that
pins the sentinel for every security-relevant return. Run the port against
the RFC/spec vectors where they exist (e.g. RFC 6238 TOTP  -  computed
independently, never assumed from a different digit count).

---

## Law L13 checklist item

13. After any port from an elder theme, diff port vs source function-by-function,
    return values first  -  and pin every security sentinel with a battery test
    **(L13)**

## Law L14  -  Generated artifacts are written by the same tool that must read them

**Rule.** When a test battery embeds code in another language inside a
PHP heredoc (node snippets, shell one-liners), the write tool will
mangle it: Python heredocs double-escape regex backslashes (`\s`
arrives as literal `\s` in the JS), PHP heredocs mis-tokenize nested
syntax, and neither tool can lint what it just wrote. Never embed
generated artifacts in a host-language heredoc  -  write them as
standalone files (write_file, which lints per-language), reference
them by path from the battery, and lint/run them with their native
interpreter BEFORE trusting the battery's verdict.

**Incident.** Starter theme, Aug 30 2026, during the v1.9.0 candidate
pool: the battery's two node snippets were forged inside a Python
heredoc; both landed with `\s`/`\d` as literal backslash-letter
poison and computed garbage. The first symptom surfaced as battery
FAILs on checks the modules had passed in isolation  -  the standalone
`node file.js` run exposed the poison immediately. Rewritten via
write_file with native node execution before re-integration: all
vectors exact (acre→m² 4046.8564224, gal→L 3.785411784, 60 mph→km/h
96.56064, MiB→KB 1048.576, all case-count vectors).

**Verify.** Before a battery that execs external scripts: write each
script with write_file (per-language lint), run it standalone
(`node x.js`) and read its output, and only then wire the exec into
the battery. If a battery check fails while the same logic passes
standalone, suspect the embedding, not the module.

---

## Law L14 checklist item

14. Foreign-language snippets for a battery are standalone write_file
    artifacts with native-interpreter verification, never heredoc-
    embedded strings **(L14)**

