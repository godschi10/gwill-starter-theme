<?php
/**
 * Template Part: Back to Top
 *
 * Fixed circular button, bottom-right. Hidden until the visitor has
 * scrolled past a threshold; smooth-scrolls to top on click unless the OS
 * has prefers-reduced-motion set, in which case it jumps instantly —
 * matching the reduced-motion handling already established elsewhere in
 * this theme's accessibility work.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

defined( 'ABSPATH' ) || exit;
?>
<button type="button" class="gwill-back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'gwill-starter' ); ?>">
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
		<path d="M12 19V5M5 12l7-7 7 7"></path>
	</svg>
</button>
