<?php
/**
 * Tech Blog 404 (404.php)
 *
 * Custom 404 page with error code and suggestions.
 *
 * @package GWill_Tech
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="section" style="text-align:center">
	<div class="wrap">
		<div class="error-code">404</div>
		<p class="h3" style="margin:20px 0 12px">This page doesn't exist &mdash; or moved.</p>
		<p class="body-sm" style="margin:0 auto 32px;max-width:44ch">Nothing broke on your end. Try one of these:</p>
		<div class="suggest-list" style="margin:0 auto">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Homepage <span>&rarr;</span></a>
			<a href="<?php echo esc_url( home_url( '/category/android/' ) ); ?>">Everything Android <span>&rarr;</span></a>
			<a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">Resources &amp; tools <span>&rarr;</span></a>
		</div>
	</div>
</section>

<?php
get_footer();
