<?php
/**
 * Template: Apps hub (/apps/)
 *
 * The card grid for everything in gwill_apps_registry() (inc/apps.php).
 * Cards are auto-rendered from the registry  -  no markup changes needed to
 * add an app. Reached via a REAL rewrite (L4).
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="inner">
	<header class="archive-header">
		<h1><?php esc_html_e( 'Apps', 'gwill-starter' ); ?></h1>
		<p><?php esc_html_e( 'Free tools that run entirely in your browser  -  fast, private, no sign-up.', 'gwill-starter' ); ?></p>
	</header>

	<div class="gwill-apps-grid">
		<?php foreach ( gwill_apps_registry() as $app ) : ?>
			<a class="gwill-app-card" href="<?php echo esc_url( gwill_apps_page_url( $app['slug'] ) ); ?>">
				<span class="gwill-app-card__icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo wp_kses( $app['icon'], array( 'path' => array( 'd' => array(), 'stroke-linecap' => array() ) ) ); ?></svg>
				</span>
				<span class="gwill-app-card__body">
					<strong class="gwill-app-card__title"><?php echo esc_html( $app['title'] ); ?></strong>
					<span class="gwill-app-card__excerpt"><?php echo esc_html( $app['excerpt'] ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</div>

<?php
get_footer();
