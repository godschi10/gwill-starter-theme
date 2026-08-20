<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>

<section class="error-404 not-found" aria-labelledby="error-404-heading">
	<h1 id="error-404-heading"><?php esc_html_e( '404 — Page Not Found', 'gwill-starter' ); ?></h1>
	<p><?php esc_html_e( 'The page you\'re looking for doesn\'t exist. Try searching:', 'gwill-starter' ); ?></p>
	<?php get_search_form(); ?>
	<p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( '← Back to Home', 'gwill-starter' ); ?>
		</a>
	</p>
</section>

<?php get_footer(); ?>
