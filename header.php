<?php
/**
 * Tech Blog Header
 *
 * Terminal-style header with two-row nav, cookie bar, reading progress bar.
 * Always dark mode — no toggle, no light mode.
 *
 * @package GWill_Tech
 */

defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<script>
		/* Always dark — no toggle, no localStorage, no OS preference check */
		(function(){
			var root = document.documentElement;
			root.dataset.theme = 'dark';
			root.style.background = '#0d0d0d';
		})();
	</script>
	<style>
		/* Flash prevention — pure dark, always */
		:root{color-scheme:dark;background-color:#0d0d0d}
		body{color:#e2e8f0}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="vp">

	<?php if ( is_singular( 'post' ) ) : ?>
	<div class="reading-progress" id="reading-progress" aria-hidden="true"></div>
	<?php endif; ?>

	<header class="site-header" role="banner">
		<div class="header-row">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo"><span class="prompt">&gt;_</span><span class="gap"></span><span class="name">gwillchijioke</span><span class="cursor"></span></a>
			<div class="header-actions">
				<button class="icon-btn" id="search-toggle" aria-label="Search" aria-expanded="false">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				</button>
				<button class="hamburger" aria-label="Menu" onclick="document.getElementById('mob-nav').classList.add('open');document.body.style.overflow='hidden'">
					<span></span><span></span><span></span>
				</button>
			</div>
		</div>
		<nav class="header-row-2" role="navigation">
			<div class="site-nav">
				<?php
				$current_cats = array();
				if ( is_single() ) {
					$post_cats = get_the_category( get_queried_object_id() );
					if ( ! empty( $post_cats ) ) {
						foreach ( $post_cats as $c ) {
							$current_cats[] = $c->slug;
						}
					}
				}

				$nav_items = array(
					'/'               => 'Home',
					'/category/android/' => 'Android',
					'/category/web-dev/' => 'Web-Dev',
					'/category/software/' => 'Software',
					'/about/'         => 'About',
				);

				foreach ( $nav_items as $url => $label ) {
					$is_active = false;
					$url_slug = trim( $url, '/' );

					if ( $url === '/' ) {
						$is_active = is_home() || is_front_page();
					} elseif ( strpos( $url_slug, 'category/' ) === 0 ) {
						$cat_slug = str_replace( 'category/', '', $url_slug );
						$is_active = is_category( $cat_slug ) || ( is_single() && in_array( $cat_slug, $current_cats, true ) );
					} else {
						$page_slug = basename( $url_slug );
						$is_active = is_page( $page_slug );
					}

					echo '<a href="' . esc_url( home_url( $url ) ) . '" class="' . ( $is_active ? 'active' : '' ) . '">' . esc_html( $label ) . '</a>';
				}
				?>
			</div>
		</nav>
	</header>

	<div id="cookie">
		<p>We use cookies to analyze traffic. <a href="/privacy-policy/" style="color:var(--green);text-decoration:underline">Privacy Policy</a></p>
		<button class="btn btn-ghost btn-sm" onclick="acceptCookies()">Accept</button>
		<button class="btn btn-ghost btn-sm" onclick="declineCookies()">Decline</button>
	</div>

	<div class="search-dropdown" id="search-dropdown" hidden>
		<div class="search-dropdown-inner">
			<form class="search-dropdown-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
				<svg class="search-dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				<input class="search-dropdown-input" type="text" id="search-input" name="s" placeholder="Search guides..." autocomplete="off" aria-label="Search">
				<button class="search-dropdown-close" type="button" id="search-close" aria-label="Close search">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
				</button>
			</form>
			<div class="search-results" id="search-results"></div>
			<div class="search-dropdown-footer">
				Press <kbd>Enter</kbd> for full results or <kbd>Esc</kbd> to close
			</div>
		</div>
	</div>
