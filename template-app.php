<?php
/**
 * Template: single app (/apps/<slug>/)
 *
 * Renders any app from gwill_apps_registry() - the markup is one generic
 * shell (icon + title + description + #gwill-app-root mount point + FAQ);
 * the app's own JS/CSS (auto-enqueued by inc/apps.php) renders its UI
 * into #gwill-app-root. A new app needs NO template - only a registry
 * entry + its JS/CSS files.
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

defined( 'ABSPATH' ) || exit;

$gwill_app = gwill_apps_get( get_query_var( 'gwill_app' ) );

get_header();
?>

<div class="inner">
	<header class="archive-header gwill-app-header">
		<h1>
			<span class="gwill-app-header__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo wp_kses( $gwill_app['icon'], array( 'path' => array( 'd' => array(), 'stroke-linecap' => array() ) ) ); ?></svg>
			</span>
			<?php echo esc_html( $gwill_app['title'] ); ?>
		</h1>
		<p><?php echo esc_html( $gwill_app['excerpt'] ); ?></p>
	</header>

	<div id="gwill-app-root" class="gwill-app-root" data-gwill-app="<?php echo esc_attr( $gwill_app['slug'] ); ?>"<?php
	// v1.9.0 - registry-driven schema variations: a flat 'fields' map on the
	// registry entry renders as data-* attributes here. An app's JS reads its
	// own config straight off its mount point - no server round-trip, no
	// wp_localize_script per app. Scalar values only; nested arrays are
	// skipped (keeps the attribute surface safe and predictable).
	if ( ! empty( $gwill_app['fields'] ) && is_array( $gwill_app['fields'] ) ) {
		foreach ( $gwill_app['fields'] as $gwill_field_key => $gwill_field_value ) {
			if ( is_scalar( $gwill_field_value ) ) {
				echo ' data-' . esc_attr( sanitize_html_class( $gwill_field_key ) ) . '="' . esc_attr( (string) $gwill_field_value ) . '"';
			}
		}
	}
	?>>
		<noscript><?php esc_html_e( 'This app needs JavaScript to run.', 'gwill-starter' ); ?></noscript>
	</div>

	<?php if ( ! empty( $gwill_app['faq'] ) ) : ?>
		<section class="gwill-app-faq">
			<h2><?php esc_html_e( 'Frequently asked questions', 'gwill-starter' ); ?></h2>
			<?php foreach ( $gwill_app['faq'] as $gwill_faq_item ) : ?>
				<h3><?php echo esc_html( $gwill_faq_item['q'] ); ?></h3>
				<p><?php echo esc_html( $gwill_faq_item['a'] ); ?></p>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

	<p class="gwill-app-back">
		<a href="<?php echo esc_url( gwill_apps_hub_url() ); ?>">&larr; <?php esc_html_e( 'All apps', 'gwill-starter' ); ?></a>
	</p>
</div>

<?php
get_footer();
