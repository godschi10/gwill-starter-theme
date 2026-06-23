/**
 * GWill Starter — Cookie Consent Banner
 *
 * Shows the banner once per visitor (no stored choice in localStorage yet),
 * dismisses on either button, and dispatches a DOM event on Accept so any
 * future tracking script added to a specific build can load conditionally.
 *
 * Deferred loading is fine here — unlike the dark-mode toggle, a consent
 * banner appearing slightly after first paint is completely normal,
 * expected behaviour seen on virtually every site. There's no "flash of
 * wrong state" risk the way there was with theme colour.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

( function () {
	'use strict';

	var STORAGE_KEY = 'gwill-cookie-consent';
	var banner       = document.querySelector( '.gwill-cookie-consent' );

	if ( ! banner ) return;

	try {
		if ( localStorage.getItem( STORAGE_KEY ) ) {
			return; // Already chosen — stay hidden.
		}
	} catch ( e ) {
		return; // Private/incognito mode blocking localStorage — fail closed, don't show a banner that can't remember the choice.
	}

	banner.removeAttribute( 'hidden' );

	banner.querySelectorAll( '[data-gwill-consent]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var choice = btn.getAttribute( 'data-gwill-consent' ); // 'accept' | 'reject'

			try {
				localStorage.setItem( STORAGE_KEY, choice );
			} catch ( e ) { /* private mode — choice won't persist, but still dismiss for this pageview */ }

			banner.setAttribute( 'hidden', '' );

			if ( 'accept' === choice ) {
				document.dispatchEvent( new CustomEvent( 'gwill:cookie-consent-given' ) );
			}
		} );
	} );

} )();
