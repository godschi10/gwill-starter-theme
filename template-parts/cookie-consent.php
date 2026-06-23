<?php
/**
 * Template Part: Cookie Consent Banner
 *
 * Scope deliberately limited to a notice + stored choice + an
 * extensibility event — this theme ships no analytics/tracking scripts of
 * its own, so building full granular cookie-category management here would
 * be solving a problem that doesn't exist yet. Any future tracking script
 * added to a specific build should listen for the 'gwill:cookie-consent-given'
 * DOM event (dispatched by assets/js/cookie-consent.js on Accept) and load
 * conditionally from there.
 *
 * Visibility is entirely client-side (assets/js/cookie-consent.js checks
 * localStorage) — this template always renders the same markup regardless
 * of visitor or cache state, exactly like the dark-mode toggle and exit-
 * intent overlay elsewhere in this theme. A page cached by LiteSpeed for
 * hours still shows the right thing to every visitor, because the PHP
 * output never varies; only the JS-driven hidden attribute does.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="gwill-cookie-consent" role="region" aria-label="<?php esc_attr_e( 'Cookie consent', 'gwill-starter' ); ?>" hidden>
	<p class="gwill-cookie-consent__text">
		<?php
		$gwill_privacy_url = get_privacy_policy_url(); // WP core — Settings → Privacy. Empty string if none is set.
		if ( $gwill_privacy_url ) {
			printf(
				/* translators: %s: link to the site's configured Privacy Policy page */
				esc_html__( 'This site uses cookies to improve your experience. %s', 'gwill-starter' ),
				'<a href="' . esc_url( $gwill_privacy_url ) . '">' . esc_html__( 'Learn more', 'gwill-starter' ) . '</a>'
			);
		} else {
			esc_html_e( 'This site uses cookies to improve your experience.', 'gwill-starter' );
		}
		?>
	</p>
	<div class="gwill-cookie-consent__actions">
		<button type="button" class="gwill-cookie-consent__btn gwill-cookie-consent__btn--reject" data-gwill-consent="reject">
			<?php esc_html_e( 'Reject', 'gwill-starter' ); ?>
		</button>
		<button type="button" class="gwill-cookie-consent__btn gwill-cookie-consent__btn--accept" data-gwill-consent="accept">
			<?php esc_html_e( 'Accept', 'gwill-starter' ); ?>
		</button>
	</div>
</div>
