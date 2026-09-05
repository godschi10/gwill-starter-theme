/**
 * Ad slots - device-aware variant instantiation - GWill Starter (v1.8.0).
 *
 * Ported from gwill-tech-theme assets/js/main.js §9–14 (live-proven),
 * extracted to its own file (the starter keeps main.js lean):
 *   1. picks the visitor's device via matchMedia (mobile ≤767,
 *      tablet 768–1023, desktop ≥1024);
 *   2. instantiates ONLY that variant into .ad-slot__content (one ad
 *      request per slot - the other variants' scripts never execute;
 *      cache-safe since every cached page carries all variants);
 *   3. hides the .ad-slot::before "Advertisement" corner label once
 *      the content div has real (non-template) children.
 *
 * @package GWill_Starter
 * @since   1.8.0
 */

/*
Table of Contents
1. getDevice
2. hasRealContent
3. markFilled
4. instantiate
5. scan
*/

( function () {
	'use strict';

	// ── 1. getDevice ──────────────────────────────────────
	function getDevice() {
		if ( window.matchMedia( '(max-width: 767px)' ).matches ) {
			return 'mobile';
		}
		if ( window.matchMedia( '(min-width: 768px) and (max-width: 1023px)' ).matches ) {
			return 'tablet';
		}
		return 'desktop';
	}

	// ── 2. hasRealContent ──────────────────────────────────
	// Non-template children = the ad network injected real content.
	function hasRealContent( content ) {
		if ( ! content ) { return false; }
		var children = content.children;
		for ( var i = 0; i < children.length; i++ ) {
			if ( children[ i ].nodeName.toLowerCase() !== 'template' ) {
				return true;
			}
		}
		return false;
	}

	// ── 3. markFilled ─────────────────────────────────────
	function markFilled( slot ) {
		if ( slot.classList.contains( 'ad-slot--filled' ) ) { return; }
		if ( hasRealContent( slot.querySelector( '.ad-slot__content' ) ) ) {
			slot.classList.add( 'ad-slot--filled' );
		}
	}

	// ── 4. instantiate ────────────────────────────────────
	function instantiate( slot ) {
		var content = slot.querySelector( '.ad-slot__content' );
		if ( ! content || content.dataset.gwillDevice ) { return; }

		var device = getDevice();
		var tpl = content.querySelector( 'template.ad-variant[data-device="' + device + '"]' );
		if ( ! tpl ) {
			// No device-specific code - fall back to desktop/base.
			tpl = content.querySelector( 'template.ad-variant[data-device="desktop"]' );
		}
		if ( ! tpl ) { return; }

		// Instantiate the matching variant; drop the inert templates.
		content.appendChild( tpl.content.cloneNode( true ) );
		content.dataset.gwillDevice = device;

		var templates = content.querySelectorAll( 'template.ad-variant' );
		Array.prototype.forEach.call( templates, function ( t ) { t.remove(); } );

		markFilled( slot );
	}

	// ── 5. scan ───────────────────────────────────────────
	function scan( root ) {
		var slots = root.querySelectorAll ? root.querySelectorAll( '.ad-slot' ) : [];
		Array.prototype.forEach.call( slots, function ( slot ) {
			instantiate( slot );
			markFilled( slot );
		} );
	}

	// Initial pass - server-rendered slots are in the DOM already.
	scan( document );

	// Watch for dynamically injected slots and runtime fill.
	if ( window.MutationObserver ) {
		var mo = new MutationObserver( function ( records ) {
			records.forEach( function ( rec ) {
				rec.addedNodes.forEach( function ( node ) {
					if ( node.nodeType !== 1 ) { return; }
					if ( node.classList && node.classList.contains( 'ad-slot' ) ) {
						instantiate( node );
						markFilled( node );
					} else if ( node.querySelectorAll ) {
						scan( node );
					}
				} );
			} );
		} );
		mo.observe( document.body, { childList: true, subtree: true } );
	}

} )();
