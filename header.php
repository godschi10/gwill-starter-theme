<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php
	/*
	 * Flash prevention  -  must run before wp_head() outputs any stylesheet.
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
 * wp_body_open() fires the 'wp_body_open' action  -  the standard hook for
 * content immediately after <body> (GTM noscript, accessibility overlays).
 * Never remove this call.
 */
wp_body_open();
?>

<?php
/*
 * Reading progress bar (v1.7.0)  -  fixed 3px bar at the viewport top,
 * driven by assets/js/reading-progress.js on singular posts. Printed
 * immediately after <body> opens so it never shifts layout. The JS
 * no-ops when the element is absent, so printing it unconditionally
 * costs one empty div; the CSS keeps it invisible (scaleX(0)) until
 * the first scroll on a post.
 */
if ( is_singular( 'post' ) ) :
?>
<div class="reading-progress" id="reading-progress" aria-hidden="true"></div>
<?php endif; ?>

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
				 * an element  -  assistive technology respects it and the attribute
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

			<button class="gwill-search-toggle icon-btn" id="search-toggle" aria-label="<?php esc_attr_e( 'Search', 'gwill-starter' ); ?>" aria-expanded="false" data-gwill-search-toggle>
				<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			</button>

			<div class="search-dropdown" id="search-dropdown" hidden>
				<div class="search-dropdown-inner">
					<form class="search-dropdown-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
						<svg aria-hidden="true" focusable="false" class="search-dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						<label class="screen-reader-text" for="search-input"><?php esc_html_e( 'Search', 'gwill-starter' ); ?></label>
						<span class="search-input-wrap">
							<input class="search-dropdown-input" type="text" id="search-input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search…', 'gwill-starter' ); ?>" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="search-results" aria-label="<?php esc_attr_e( 'Search', 'gwill-starter' ); ?>">
							<button class="search-clear" type="button" id="search-clear" aria-label="<?php esc_attr_e( 'Clear search text', 'gwill-starter' ); ?>" hidden>
								<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
							</button>
						</span>
						<button class="search-dropdown-close" type="button" id="search-close" aria-label="<?php esc_attr_e( 'Close search', 'gwill-starter' ); ?>">
							<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
						</button>
					</form>
					<div class="search-results" id="search-results" role="status" aria-live="polite"></div>
				</div>
			</div>

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<nav aria-label="<?php esc_attr_e( 'Primary Navigation', 'gwill-starter' ); ?>">

				<?php
				/*
				 * The toggle button appears before the menu in DOM order so keyboard
				 * users encounter it first. aria-controls references the menu <ul> id,
				 * set via 'menu_id' in wp_nav_menu() below. aria-expanded starts false
				 *  -  JS sets it to true when the menu opens.
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
