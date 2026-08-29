/**
 * GWill Starter — Customizer Live Preview
 *
 * Handles postMessage transport for Customizer controls.
 * Loaded ONLY inside the Customizer preview iframe via customize_preview_init.
 * Never loaded on the public frontend.
 *
 * How postMessage transport works:
 *   1. User changes a control in the Customizer sidebar.
 *   2. WordPress posts the new value to the preview iframe via postMessage.
 *   3. This script receives it and updates the DOM directly — no page reload.
 *
 * For each setting using transport: 'postMessage', register a binding here
 * via wp.customize( 'setting_name', function( setting ) { ... } ).
 *
 * @package GWill_Starter
 * @since   1.0.18
 */

( function ( api ) {
	'use strict';

	/**
	 * Header padding — live update via CSS custom property.
	 *
	 * Sets --header-padding directly on :root so all elements reading
	 * that variable update immediately without a style recalculation
	 * of the entire stylesheet.
	 *
	 * Corresponds to: inc/customizer.php → 'gwill_header_padding' setting.
	 */
	api( 'gwill_header_padding', function ( setting ) {
		setting.bind( function ( newVal ) {
			var px = parseInt( newVal, 10 );

			if ( ! isNaN( px ) && px >= 0 && px <= 200 ) {
				document.documentElement.style.setProperty(
					'--header-padding',
					px + 'px'
				);
			}
		} );
	} );

	/**
	 * Tagline visibility — live toggle via the HTML `hidden` attribute.
	 *
	 * header.php always renders .site-description when description text
	 * exists, using the `hidden` attribute to hide it when the toggle is
	 * off. This keeps the element in the DOM so this handler can toggle
	 * it without a page reload.
	 *
	 * If the site has no tagline text set, querySelector returns null and
	 * the handler is a no-op — correct behaviour.
	 *
	 * Corresponds to: inc/customizer.php → 'gwill_show_tagline' setting.
	 */
	api( 'gwill_show_tagline', function ( setting ) {
		setting.bind( function ( newVal ) {
			var tagline = document.querySelector( '.site-description' );
			if ( tagline ) {
				tagline.hidden = ! newVal;
			}
		} );
	} );

	/**
	 * Logo width — live preview.
	 *
	 * Updates the --logo-width CSS custom property on :root so any element
	 * using var(--logo-width) (i.e. .custom-logo) reflects the change instantly.
	 *
	 * Corresponds to: inc/customizer.php → 'gwill_logo_width' setting.
	 */
	api( 'gwill_logo_width', function ( setting ) {
		setting.bind( function ( newVal ) {
			var px = parseInt( newVal, 10 );
			if ( ! isNaN( px ) && px >= 20 && px <= 400 ) {
				document.documentElement.style.setProperty( '--logo-width', px + 'px' );
			}
		} );
	} );

} )( wp.customize );
