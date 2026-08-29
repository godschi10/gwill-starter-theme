<?php
/**
 * Template Part: No Content Found
 *
 * Displayed when a query returns zero results.
 * Used by index.php, archive.php, search.php, and any template with a query.
 *
 * Accessibility: <section> is promoted to a named landmark via aria-labelledby
 * pointing to the inner <h2>. An unlabelled <section> is treated as a generic
 * container — not a landmark — by screen readers.
 *
 * Usage:
 *   gwill_part( 'content-none' );
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="content-none" aria-labelledby="content-none-heading">

	<?php if ( is_search() ) : ?>

		<h2 id="content-none-heading" class="content-none__title">
			<?php esc_html_e( 'Nothing matched your search', 'gwill-starter' ); ?>
		</h2>
		<p><?php esc_html_e( 'Try different keywords, or check for typos.', 'gwill-starter' ); ?></p>

	<?php else : ?>

		<h2 id="content-none-heading" class="content-none__title">
			<?php esc_html_e( 'Nothing here yet', 'gwill-starter' ); ?>
		</h2>
		<p><?php esc_html_e( 'It looks like nothing was found at this location.', 'gwill-starter' ); ?></p>

	<?php endif; ?>

</section>
