<?php
/**
 * Template Part: No Content Found
 *
 * Displayed when a query returns zero results.
 * Used by index.php, archive.php, search.php, and any template with a query.
 *
 * Accessibility: <section> is promoted to a named landmark via aria-labelledby
 * pointing to the inner <h2>. An unlabelled <section> is treated as a generic
 * container - not a landmark - by screen readers.
 *
 * Usage:
 *   gwill_part( 'content-none' );
 *
 * v1.12.0 (B13): ghost-search glyph + CTA row (home button; filterable,
 * same pattern as the search no-results CTA so builds can point it
 * anywhere - or suppress it with __return_false).
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Filter the content-none CTA (v1.12.0).
 *
 * @param array|false $cta Associative array with 'label' and 'url' keys,
 *                         or false to suppress the CTA entirely.
 */
$gwill_content_none_cta = apply_filters(
	'gwill_content_none_cta',
	[
		'label' => __( '← Back to Home', 'gwill-starter' ),
		'url'   => home_url( '/' ),
	]
);
?>

<section class="content-none" aria-labelledby="content-none-heading">

	<span class="content-none__glyph" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
			<circle cx="11" cy="11" r="7"/>
			<line x1="21" y1="21" x2="16.65" y2="16.65"/>
			<line x1="8" y1="11" x2="14" y2="11"/>
		</svg>
	</span>

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

	<?php if ( $gwill_content_none_cta ) : ?>
		<p class="content-none__cta">
			<a class="gwill-btn gwill-btn--ghost" href="<?php echo esc_url( $gwill_content_none_cta['url'] ); ?>">
				<?php echo esc_html( $gwill_content_none_cta['label'] ); ?>
			</a>
		</p>
	<?php endif; ?>

</section>
