<?php
/**
 * Template Part: Staging Environment Banner
 *
 * Only ever rendered when gwill_is_staging_environment() is true AND the
 * gwill_show_staging_banner Customizer toggle is on (see inc/staging.php)
 * — no markup output, no CSS class added, when either is false.
 *
 * @package GWill_Starter
 * @since   1.0.57
 */

defined( 'ABSPATH' ) || exit;

$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';

/**
 * Filter the staging banner's message text.
 *
 * %s, if present, is replaced with the current request host — left out by
 * default to keep the banner short enough not to wrap on a phone screen,
 * but a site that wants the domain visible can add it back via this filter.
 *
 * @param string $text
 * @param string $host Current request host.
 * @since 1.0.57
 */
$text = apply_filters(
	'gwill_staging_banner_text',
	__( 'STAGING — this is not the live site', 'gwill-starter' ),
	$host
);
?>
<div class="gwill-staging-banner" role="note">
	<?php echo esc_html( $text ); ?>
</div>
