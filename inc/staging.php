<?php
/**
 * Staging-environment banner.
 *
 * Solves a problem this exact project has run into repeatedly during its
 * own development: a screenshot or bug report doesn't say whether it's
 * from a staging clone or the live site, and that ambiguity has cost real
 * debugging time more than once. Shown automatically on a recognised
 * staging domain pattern — but unlike the first version of this feature
 * (1.0.57, removed in 1.0.59), it's now also gated behind a Customizer
 * toggle (default ON), so a developer who genuinely doesn't want it on a
 * given project can turn it off deliberately, rather than the only option
 * being "it's always there" or "it doesn't exist." Defaulting ON rather
 * than OFF is the actual point here: a banner that's off until someone
 * remembers to enable it defeats its own purpose just as much as having
 * no toggle at all — the developer should see it exists and make an
 * active choice either way, not silently never know about it.
 *
 * Detection is host-based, not environment-constant-based (no reliance on
 * WP_ENVIRONMENT_TYPE), since most of this project's actual staging clones
 * have been plain subdomain/hosting-panel clones, not config-managed
 * environments with that constant set.
 *
 * @package GWill_Starter
 * @since   1.0.57
 * @since   1.0.62 Added the Customizer toggle (gwill_show_staging_banner).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_body_open', 'gwill_render_staging_banner' );
add_filter( 'body_class', 'gwill_staging_body_class' );
add_filter( 'wp_robots', 'gwill_staging_noindex' );

/**
 * Whether the current request is on a recognised staging domain.
 *
 * Checks the actual request host ($_SERVER['HTTP_HOST']), not home_url() —
 * home_url() reflects the *configured* site URL, which on some staging
 * setups is deliberately left pointing at the live domain (to avoid asset-
 * URL rewriting headaches) even while being accessed via a staging host.
 * The thing we actually want to detect is "what domain is this browser
 * looking at right now," which is the request host.
 *
 * Deliberately does NOT check the Customizer toggle — this function
 * answers "is this a staging domain," a fact about the request. Whether
 * to actually show the banner given that fact is a separate question,
 * checked separately in gwill_render_staging_banner() — keeping the two
 * concerns apart means other code can ask "are we on staging?" without
 * getting a false "no" just because a developer turned the banner off.
 *
 * @return bool
 * @since  1.0.57
 */
function gwill_is_staging_environment(): bool {

	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';

	/**
	 * Filter the list of patterns checked against the request host.
	 *
	 * Each pattern is a plain substring match (not a regex) — kept simple
	 * since every real pattern seen on this project's own staging clones
	 * so far (qzz.io, .local, a literal "staging." or "dev." subdomain
	 * prefix) is a plain substring, not something that needs wildcards.
	 *
	 * @param string[] $patterns
	 * @since 1.0.57
	 */
	$patterns = apply_filters( 'gwill_staging_domain_patterns', [
		'.qzz.io',
		'.local',
		'staging.',
		'dev.',
		'test.',
	] );

	$is_staging = false;
	foreach ( $patterns as $pattern ) {
		if ( $host && str_contains( $host, $pattern ) ) {
			$is_staging = true;
			break;
		}
	}

	/**
	 * Final override for the staging-environment determination.
	 *
	 * Escape hatch for a site whose staging clone genuinely doesn't match
	 * any of the above (e.g. a full custom domain used only for staging) —
	 * a client-specific functions.php can force this true or false outright
	 * without needing to touch the pattern list.
	 *
	 * @param bool $is_staging
	 * @since 1.0.57
	 */
	return (bool) apply_filters( 'gwill_is_staging_environment', $is_staging );
}

/**
 * Echo the staging banner markup, right after <body>, on staging only —
 * and only when the Customizer toggle for it is on.
 *
 * Hooked to wp_body_open() rather than the top of header.php so it renders
 * before any header markup at all — it should be the very first thing in
 * the DOM, not nested inside whatever the header's own markup structure is.
 *
 * @since 1.0.57
 * @since 1.0.62 Added the Customizer-toggle check.
 */
function gwill_render_staging_banner(): void {

	if ( ! gwill_is_staging_environment() ) {
		return;
	}

	if ( ! get_theme_mod( 'gwill_show_staging_banner', true ) ) {
		return;
	}

	gwill_part( 'staging-banner' );
}

/**
 * Add .gwill-staging-active to body_class() — on staging AND the toggle on.
 *
 * Matches gwill_render_staging_banner()'s own two-part check exactly,
 * since the CSS this class drives (body padding-top, the sticky-header
 * offset) only needs to exist when the banner itself is actually present.
 *
 * @param  string[] $classes
 * @return string[]
 * @since  1.0.57
 * @since  1.0.62 Added the Customizer-toggle check, matching the render function.
 */
function gwill_staging_body_class( array $classes ): array {
	if ( gwill_is_staging_environment() && get_theme_mod( 'gwill_show_staging_banner', true ) ) {
		$classes[] = 'gwill-staging-active';
	}
	return $classes;
}

/**
 * Add noindex via the wp_robots filter whenever on a recognised staging
 * domain — wp_robots() is the current, correct mechanism for this since
 * WP 5.7 (replacing the older wp_no_robots action).
 *
 * Deliberately checked against gwill_is_staging_environment() alone, NOT
 * also gated behind the gwill_show_staging_banner toggle the way the
 * visible banner is. Whether to show a visual ribbon is a developer
 * preference; whether a staging clone should be kept out of search
 * results isn't a preference at all — it should always apply whenever
 * the domain genuinely is staging, regardless of whether anyone chose to
 * hide the banner on this particular project.
 *
 * @param  array<string,bool> $robots
 * @return array<string,bool>
 * @since  1.0.64
 */
function gwill_staging_noindex( array $robots ): array {
	if ( gwill_is_staging_environment() ) {
		$robots = wp_robots_noindex( $robots );
	}
	return $robots;
}
