/**
 * GWill Starter — assets/js/main.js
 *
 * PRE-BUILT FILE — do not edit directly.
 * Source: src/main.js  |  Build: npm run build
 *
 * Committed as a pre-built file so the theme works without a build step.
 * Enqueued by inc/enqueue.php with strategy: 'defer' — the DOM is fully
 * parsed before this runs; no DOMContentLoaded wrapper is needed.
 */

( function () {
  'use strict';

  // -------------------------------------------------------------------------
  // Mobile navigation toggle
  // -------------------------------------------------------------------------
  // HTML contract (header.php):
  //   <button class="nav-toggle" aria-expanded="false" aria-controls="primary-menu">
  //   <ul id="primary-menu">  (wp_nav_menu with 'menu_id' => 'primary-menu')
  //
  // CSS contract (style.css):
  //   .nav-toggle           — hidden on desktop, shown on mobile (display:flex)
  //   .nav-toggle.is-active — button visual active state (bars morph to ×)
  //   #primary-menu         — hidden on mobile by default
  //   #primary-menu.is-open — revealed when toggle is activated
  // -------------------------------------------------------------------------

  const btn  = document.querySelector( '.nav-toggle' );
  const menu = btn ? document.getElementById( btn.getAttribute( 'aria-controls' ) ) : null;

  if ( btn && menu ) {

    btn.addEventListener( 'click', function () {
      const expanded = btn.getAttribute( 'aria-expanded' ) === 'true';
      btn.setAttribute( 'aria-expanded', String( ! expanded ) );
      menu.classList.toggle( 'is-open', ! expanded );
      btn.classList.toggle( 'is-active', ! expanded );
    } );

    // Close on Escape — return focus to the toggle button
    document.addEventListener( 'keydown', function ( e ) {
      if ( e.key === 'Escape' && btn.getAttribute( 'aria-expanded' ) === 'true' ) {
        btn.setAttribute( 'aria-expanded', 'false' );
        menu.classList.remove( 'is-open' );
        btn.classList.remove( 'is-active' );
        btn.focus();
      }
    } );

    // Close when focus moves entirely outside the nav (tab past last item)
    document.addEventListener( 'focusin', function ( e ) {
      const nav = btn.closest( 'nav' );
      if (
        nav &&
        ! nav.contains( e.target ) &&
        btn.getAttribute( 'aria-expanded' ) === 'true'
      ) {
        btn.setAttribute( 'aria-expanded', 'false' );
        menu.classList.remove( 'is-open' );
        btn.classList.remove( 'is-active' );
      }
    } );

  }

} )();


// ── Share — "More" button ─────────────────────────────────────────────────────
// Triggers navigator.share() (system share sheet on Android / iOS / modern
// desktop). Falls back to Clipboard API on browsers without Web Share API,
// with brief "Copied!" label feedback.

( function () {
  'use strict';

  var btns = document.querySelectorAll( '.gwill-share__pill--more' );
  if ( ! btns.length ) { return; }

  btns.forEach( function ( btn ) {
    btn.addEventListener( 'click', function () {
      var url   = btn.dataset.shareUrl   || window.location.href;
      var title = btn.dataset.shareTitle || document.title;
      var label = btn.querySelector( '.gwill-share__name' );

      if ( navigator.share ) {
        navigator.share( { title: title, url: url } ).catch( function () {} );
        return;
      }

      // Clipboard fallback — desktop browsers without Web Share API.
      if ( navigator.clipboard && navigator.clipboard.writeText ) {
        navigator.clipboard.writeText( url ).then( function () {
          if ( label ) {
            label.textContent = 'Copied!';
            setTimeout( function () { label.textContent = 'More'; }, 2000 );
          }
        } ).catch( function () {} );
      }
    } );
  } );

}() );
