/**
 * Click-to-play embed facades — GWill Starter (v1.3.0).
 *
 * Deferred, dependency-free. Swaps the facade <button> for the real
 * iframe on activation. A <button> gives Enter/Space activation for
 * free; the block's aspect-ratio box survives because we only replace
 * the button inside .wp-block-embed__wrapper (its ::before padding
 * keeps the height).
 *
 * Also ships the FULLSCREEN-EXIT SCROLL RESTORE (v1.0.189 pattern):
 * on phones, exiting video fullscreen can jump the page to the top
 * silently — iOS native fullscreen fires no fullscreenchange/resize/
 * orientationchange at all. The scroll watchdog below catches that
 * failure signature and snaps the page back to the video.
 */
(function () {
	'use strict';

	// ------------------------------------------------------------------
	// Fullscreen-exit scroll restore.
	//
	// HISTORY:
	//  - v1.0.186 added the first restore but its rAF re-apply read the
	//    reset closure variable → scrollTo(0,-1) → SCROLLED TO TOP
	//    (root-caused in real Firefox; that version made it worse).
	//  - v1.0.187 fixed the closure bug and restored EXACTLY ONCE at
	//    ~16 ms after exit. On mobile WebViews / in-app browsers
	//    (Android custom-view fullscreen, iOS native fullscreen) the
	//    page scroll resets to the top LATE — after the exit transition,
	//    ~300–800 ms later — so the single restore was overwritten.
	//  - v1.0.188 added: (1) the facade button KEPT in the DOM, hidden
	//    under the iframe (stable anchor, zero layout shift); (2) a
	//    GUARDED rAF restore loop (~1.5 s) so the LAST write wins;
	//    (3) coarse-pointer resize/orientationchange fallback triggers
	//    for WebViews that never fire fullscreenchange.
	//  - v1.0.189 (THIS) adds the SCROLL WATCHDOG: on phones, exiting
	//    fullscreen can jump the page to the top SILENTLY — iOS native
	//    fullscreen fires no fullscreenchange/resize/orientationchange
	//    at all. So while a video is active, ANY single scroll jump of
	//    >200 px that lands at the top (~120 px) with no user gesture
	//    in the last 600 ms is treated as the failure signature and the
	//    guarded restore snaps the page straight back to the video.
	//    (Gesture gate: taps inside the player are cross-origin — they
	//    never reach the parent, so exit-jumps are caught; page-side
	//    touch/wheel always exempt the user's own scrolling.)
	// ------------------------------------------------------------------

	var active = null; // { y, iframe, w, h, armed, lastRestoreAt }
	var isCoarse = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);
	var lastGesture = 0;
	var lastY = -1;

	function pageY() {
		return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
	}

	function inFullscreen() {
		return !!(document.fullscreenElement || document.webkitFullscreenElement);
	}

	function restoreScroll(target) {
		var html = document.documentElement;
		var prev = html.style.scrollBehavior;
		var deadline = Date.now() + 1500; // outlast the WebView's late reset
		var cancelled = false;

		// Rotation + resize fire back-to-back for the same exit — one
		// guarded restore is enough.
		if (active && active.lastRestoreAt && Date.now() - active.lastRestoreAt < 500) {
			return;
		}
		if (active) {
			active.lastRestoreAt = Date.now();
		}

		html.style.scrollBehavior = 'auto'; // instant — never animate the restore

		function apply() {
			if (cancelled || Date.now() > deadline) {
				if (!cancelled) {
					html.style.scrollBehavior = prev;
				}
				return;
			}
			if (Math.abs(pageY() - target) > 2) {
				window.scrollTo(0, target);
			}
			if (window.requestAnimationFrame) {
				window.requestAnimationFrame(apply);
			} else {
				window.setTimeout(apply, 32);
			}
		}

		// Never fight the user: the guard stops on their first input.
		function stop() {
			cancelled = true;
			html.style.scrollBehavior = prev;
		}
		document.addEventListener('wheel', stop, { passive: true, once: true });
		document.addEventListener('touchstart', stop, { passive: true, once: true });
		document.addEventListener('keydown', stop, { once: true });

		apply();
	}

	function onFullscreenChange() {
		if (!active) {
			return;
		}
		if (inFullscreen()) {
			// Entered fullscreen. Refresh the anchor on fine pointers
			// (desktop: the page has not scrolled yet). On touch devices
			// the layout may already be rotating — trust the activation
			// offset instead of reading a possibly-clobbered position.
			if (!isCoarse) {
				var y = pageY();
				if (y > 0) {
					active.y = y;
				}
			}
		} else {
			// Exited fullscreen: restore, with the guard.
			restoreScroll(active.y);
		}
	}

	function onResize() {
		if (!active || !isCoarse) {
			return;
		}
		// Mobile WebViews that use a rotating custom view (or iOS native
		// fullscreen) may never fire fullscreenchange — the viewport
		// resize IS the fullscreen signal. Width grows when rotating to
		// landscape; height grows when the system UI hides without any
		// rotation.
		var w = window.innerWidth;
		var h = window.innerHeight;
		var wider = w > active.w + 40; // rotation into landscape
		var narrower = w < active.w - 40; // back to portrait
		var taller = h > active.h + 300; // fullscreen chrome hidden, no rotation
		if (wider || taller) {
			active.armed = true;
		} else if (narrower || (h < active.h - 300 && active.armed)) {
			active.armed = false;
			restoreScroll(active.y);
		}
		active.w = w;
		active.h = h;
	}

	function onOrientationChange() {
		if (!active || !isCoarse) {
			return;
		}
		if (Math.abs(window.orientation) === 90) {
			active.armed = true; // landscape — fullscreen-ish
		} else if (active.armed) {
			active.armed = false; // back to portrait — exit fullscreen
			restoreScroll(active.y);
		}
	}

	// ── Scroll watchdog ───────────────────────────────────────────────
	// The failure signature on phones: a SINGLE programmatic jump that
	// lands at the top while the video is active and no page gesture
	// preceded it (iOS native fullscreen fires no events at all; the
	// 186 bug, WebView resets and Chrome's own exit-scroll do the same).
	// The user's own scrolling is exempt: it is made of small deltas and
	// always follows a touch/wheel gesture.
	function bumpGesture() {
		lastGesture = Date.now();
	}
	document.addEventListener('touchstart', bumpGesture, { passive: true });
	document.addEventListener('touchend', bumpGesture, { passive: true });
	document.addEventListener('wheel', bumpGesture, { passive: true });

	function onScrollWatchdog() {
		if (!active) {
			lastY = pageY();
			return;
		}
		var y = pageY();
		var jump = lastY >= 0 && Math.abs(y - lastY) > 200; // one big move
		lastY = y;
		if (!jump) {
			return; // gesture scrolling / momentum steps
		}
		if (y > 120) {
			return; // not the top — leave it
		}
		if (active.y < 300) {
			return; // the anchor is near the top anyway — nothing to save
		}
		if (Date.now() - lastGesture < 600) {
			return; // the user's own gesture drove this
		}
		restoreScroll(active.y);
	}
	window.addEventListener('scroll', onScrollWatchdog, { passive: true });

	document.addEventListener('fullscreenchange', onFullscreenChange);
	document.addEventListener('webkitfullscreenchange', onFullscreenChange);
	window.addEventListener('resize', onResize);
	window.addEventListener('orientationchange', onOrientationChange);

	function activate(facade) {
		var src = facade.getAttribute('data-gwill-src');
		if (!src) {
			return;
		}

		var iframe = document.createElement('iframe');
		iframe.src = src;
		iframe.title = facade.getAttribute('data-gwill-title') || '';
		iframe.allow = facade.getAttribute('data-gwill-allow') || '';
		iframe.referrerPolicy = facade.getAttribute('data-gwill-referrer') || 'strict-origin-when-cross-origin';
		iframe.allowFullscreen = true;
		iframe.loading = 'lazy';
		iframe.setAttribute('frameborder', '0');

		if (facade.classList.contains('gwill-embed--spotify')) {
			// No aspect-ratio class — the iframe provides its own box.
			iframe.style.width = '100%';
			iframe.style.height = '152px';
			iframe.style.borderRadius = '12px';
			facade.replaceWith(iframe);
		} else {
			// Aspect-ratio box comes from the block's wp-has-aspect-ratio
			// ::before. Lay the iframe OVER the button and HIDE the button
			// in place: removing it would destroy the browser's scroll
			// anchor and focus target at the video's position.
			iframe.style.position = 'absolute';
			iframe.style.inset = '0';
			iframe.style.width = '100%';
			iframe.style.height = '100%';
			facade.style.visibility = 'hidden';
			facade.style.pointerEvents = 'none';
			facade.setAttribute('tabindex', '-1');
			facade.setAttribute('aria-hidden', 'true');
			facade.parentNode.insertBefore(iframe, facade.nextSibling);
		}

		// Hand focus to the player (keyboard path) — also gives the
		// browser a stable focus target for its own exit restoration.
		iframe.focus();

		// Remember where the video lives on the page; the guarded restore
		// and the scroll watchdog both return here.
		active = { y: pageY(), iframe: iframe, w: window.innerWidth, h: window.innerHeight, armed: false, lastRestoreAt: 0 };
		lastY = active.y;
	}

	document.addEventListener('click', function (event) {
		var facade = event.target && event.target.closest ? event.target.closest('.gwill-embed') : null;
		if (facade) {
			activate(facade);
		}
	});
})();