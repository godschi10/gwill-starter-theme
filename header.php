<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php
	/*
	 * Flash prevention — must run before wp_head() outputs any stylesheet.
	 * Reads localStorage and sets data-theme on <html> synchronously so the
	 * correct colour tokens are active before the first paint.
	 * See: inc/darkmode.php
	 */
	gwill_darkmode_head_script();
	wp_head();
	?>
</head>

<body <?php body_class(); ?>>
<?php
/*
 * wp_body_open() fires the 'wp_body_open' action — the standard hook for
 * content immediately after <body> (GTM noscript, accessibility overlays).
 * Never remove this call.
 */
wp_body_open();
?>

<a class="skip-link" href="#content">
	<?php echo esc_html_x( 'Skip to content', 'skip link', 'gwill-starter' ); ?>
</a>

<div class="site" id="page">

	<header class="site-header">
		<div class="inner">

			<div class="site-branding">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<div class="site-title">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
						</a>
					</div>
				<?php endif; ?>

				<?php
				$description = get_bloginfo( 'description' );
				/*
				 * Always render .site-description when description text exists.
				 * Keeping the element in DOM (rather than removing it entirely)
				 * lets the Customizer postMessage handler in customizer-preview.js
				 * toggle visibility live without a page reload.
				 *
				 * The HTML `hidden` attribute is the WAI-ARIA-safe way to hide
				 * an element — assistive technology respects it and the attribute
				 * has no visual side effects of its own.
				 */
				if ( $description ) :
					$tagline_on = (bool) get_theme_mod( 'gwill_show_tagline', true );
				?>
					<p class="site-description"<?php echo $tagline_on ? '' : ' hidden'; ?>><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</div>

			<?php gwill_part( 'ui/darkmode-toggle' ); ?>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<?php gwill_render_cart_icon(); ?>
			<?php endif; ?>

			<?php
			/*
			 * Search form — Combo A (expandable icon) ships by default.
			 * To switch to Combo B (modal + live search), replace this line:
			 *   gwill_part( 'search/search-form-modal' );
			 */
			gwill_part( 'search/search-form-expandable' );
			?>

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav aria-label="<?php esc_attr_e( 'Primary Navigation', 'gwill-starter' ); ?>">

				<?php
				/*
				 * The toggle button appears before the menu in DOM order so keyboard
				 * users encounter it first. aria-controls references the menu <ul> id,
				 * set via 'menu_id' in wp_nav_menu() below. aria-expanded starts false
				 * — JS sets it to true when the menu opens.
				 */
				?>
				<button
					class="nav-toggle"
					aria-expanded="false"
					aria-controls="primary-menu"
					aria-label="<?php esc_attr_e( 'Toggle navigation menu', 'gwill-starter' ); ?>"
				>
					<span class="nav-toggle__bar" aria-hidden="true"></span>
					<span class="nav-toggle__bar" aria-hidden="true"></span>
					<span class="nav-toggle__bar" aria-hidden="true"></span>
				</button>

				<?php
				wp_nav_menu( [
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => false,
					'depth'          => 2,
					'menu_id'        => 'primary-menu',
				] );
				?>
			</nav>
			<?php endif; ?>

		</div>
	</header>

	<main class="site-main" id="content">
		<div class="inner">
