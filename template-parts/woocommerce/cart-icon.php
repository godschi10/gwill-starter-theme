<?php
/**
 * Template Part: WooCommerce Cart Icon
 *
 * Re-rendered wholesale on every AJAX cart fragment update (see
 * gwill_woocommerce_cart_fragment() in inc/woocommerce.php) — the whole
 * <a> tag is the fragment, not just the count, so the badge's visibility
 * (hidden at 0 items) stays correct without separate JS logic for it.
 *
 * Only ever included from contexts that have already confirmed
 * WooCommerce is active (gwill_render_cart_icon() / the fragment filter
 * above) — no class_exists() guard needed in here as a result.
 *
 * @package GWill_Starter
 * @since   1.0.60
 */

defined( 'ABSPATH' ) || exit;

$count = WC()->cart->get_cart_contents_count();
?>
<a
	class="gwill-cart-icon"
	href="<?php echo esc_url( wc_get_cart_url() ); ?>"
	aria-label="<?php
		printf(
			/* translators: %d: number of items in the cart */
			esc_attr__( 'Cart, %d items', 'gwill-starter' ),
			$count
		);
	?>"
>
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
		<circle cx="9" cy="21" r="1"></circle>
		<circle cx="20" cy="21" r="1"></circle>
		<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
	</svg>
	<?php if ( $count > 0 ) : ?>
		<span class="gwill-cart-icon__count"><?php echo esc_html( $count ); ?></span>
	<?php endif; ?>
</a>
