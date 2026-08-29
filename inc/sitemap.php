<?php
/**
 * Theme-owned sitemap.xml (no plugin).
 *
 * Serves /sitemap.xml with published posts, pages, and any public custom
 * post types — excluding content that is noindexed or hidden (pages whose
 * slugs are in gwill_hidden_slugs() — see inc/seo.php — are never in the
 * sitemap; tag/author/date archives are never in a post sitemap). The XML
 * is cached in a transient and rebuilt on every save_post
 * (publish/update/trash), so the sitemap never goes stale after content
 * changes and crawlers never trigger a DB build per hit.
 *
 * The rewrite rule is registered on init and flushed by the theme's
 * existing version-keyed gwill_maybe_flush_rewrites() (inc/setup.php) on
 * the next version bump.
 *
 * Generic-base port of finance's inc/sitemap.php; the only difference is
 * that the hidden-slug list comes from the filterable gwill_hidden_slugs()
 * (inc/seo.php) instead of finance's hardcoded ACF settings slugs.
 *
 * @package GWill_Starter
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Route /sitemap.xml to the theme's sitemap renderer.
 *
 * The rule maps the literal sitemap URL to a private query var; the
 * template_include hook below swaps in this file's output when set.
 *
 * @since 1.2.0
 */
function gwill_sitemap_rewrite(): void {
	// Defer to a major SEO plugin when one is active — same rule as every
	// other theme-owned SEO output. AIOSEO and The SEO Framework both
	// serve their main sitemap at /sitemap.xml; registering this rule
	// anyway would hijack theirs (finance v1.0.201 lesson).
	if ( gwill_seo_plugin_active() ) {
		return;
	}

	// Match with or without trailing slash — WP's redirect_canonical 301s
	// /sitemap.xml to /sitemap.xml/ otherwise, an extra hop for crawlers.
	add_rewrite_rule( '^sitemap\.xml/?$', 'index.php?gwill_sitemap=1', 'top' );
}
add_action( 'init', 'gwill_sitemap_rewrite' );

/**
 * Register the sitemap query var so WP accepts it in the rewrite.
 *
 * @since 1.2.0
 * @param string[] $vars Registered public query vars.
 * @return string[]
 */
function gwill_sitemap_query_var( array $vars ): array {
	$vars[] = 'gwill_sitemap';
	return $vars;
}
add_filter( 'query_vars', 'gwill_sitemap_query_var' );

/**
 * Serve the sitemap when the query var is set.
 *
 * Runs as a template_include filter so the sitemap output lives in a real
 * theme template (sitemap.php), not inline in an inc file.
 *
 * @since 1.2.0
 * @param string $template Resolved template path.
 * @return string
 */
function gwill_sitemap_template( string $template ): string {
	// Same deferral as the rewrite: with an SEO plugin active the theme's
	// rule is never registered, but guard the template swap too so a
	// plugin-owned sitemap route can never be hijacked by a stray
	// gwill_sitemap query var (finance v1.0.201 lesson).
	if ( gwill_seo_plugin_active() ) {
		return $template;
	}

	if ( get_query_var( 'gwill_sitemap' ) ) {
		$sitemap = locate_template( 'sitemap.php' );
		if ( $sitemap ) {
			return $sitemap;
		}
	}
	return $template;
}
add_filter( 'template_include', 'gwill_sitemap_template' );

/**
 * Serve /sitemap.xml directly — no trailing-slash canonical redirect.
 *
 * The rewrite rule above accepts both /sitemap.xml and /sitemap.xml/, but
 * WP's redirect_canonical still 301s the no-slash form to the slashed one
 * on some installs (observed live on finance: /sitemap.xml → 301 →
 * /sitemap.xml/), defeating the rule's documented zero-hop intent. The
 * sitemap URL is not a real routed object, so a canonical redirect adds
 * nothing — suppress it while the sitemap query var is set.
 *
 * @param string|false $redirect_url Canonical redirect URL, false when none.
 * @return string|false Unchanged for every request except the sitemap.
 * @since 1.2.0
 */
function gwill_sitemap_canonical( $redirect_url ) {
	// Deferral for consistency: with an SEO plugin active the theme owns
	// no sitemap route, so canonical redirects are the plugin's business.
	if ( gwill_seo_plugin_active() ) {
		return $redirect_url;
	}

	if ( get_query_var( 'gwill_sitemap' ) ) {
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'gwill_sitemap_canonical' );

/**
 * Build the sitemap XML string for the current site.
 *
 * Includes every published post, page, and public (non-attachment,
 * non-nav-menu) custom post type. Excludes:
 *   - pages whose slug is in gwill_hidden_slugs() (inc/seo.php) — they
 *     are noindexed and must never appear in a sitemap
 *   - posts whose canonical URL is empty (shouldn't happen, but a broken
 *     permalink must not emit a broken <loc>)
 *
 * lastmod uses post_modified (GMT) so crawlers see real change times.
 * One <url> entry per content row — no paged/archive URLs (those are
 * duplicates of the canonical page, and paginated archives are noindexed
 * by gwill_robots_meta).
 *
 * @since 1.2.0
 * @return string
 */
function gwill_sitemap_build(): string {

	$posts = get_posts(
		[
			'post_type'        => [ 'post', 'page' ],
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'orderby'          => 'modified',
			'order'            => 'DESC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		]
	);

	// Public custom post types — future-proof: any CPT the site adds
	// later is included automatically, matching WP core's own sitemap
	// behaviour. Attachments/menus are excluded.
	$cpts = get_post_types(
		[
			'public'             => true,
			'_builtin'           => false,
			'publicly_queryable' => true,
		],
		'objects'
	);

	$hidden = gwill_hidden_slugs();
	$items  = [];

	foreach ( $posts as $post ) {
		if ( 'page' === $post->post_type && in_array( $post->post_name, $hidden, true ) ) {
			continue;
		}
		$items[] = $post;
	}

	foreach ( $cpts as $cpt ) {
		$cpt_posts = get_posts(
			[
				'post_type'        => $cpt->name,
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			]
		);
		foreach ( $cpt_posts as $cpt_post ) {
			$items[] = $cpt_post;
		}
	}

	$urls = '';
	foreach ( $items as $item ) {
		$permalink = get_permalink( $item );
		if ( ! $permalink ) {
			continue;
		}
		$urls .= "\t<url>\n"
			. "\t\t<loc>" . esc_url( $permalink ) . "</loc>\n"
			. "\t\t<lastmod>" . esc_html( get_post_modified_time( 'c', true, $item ) ) . "</lastmod>\n"
			. "\t</url>\n";
	}

	return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
		. "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
		. $urls
		. "</urlset>\n";
}

/**
 * Clear the cached sitemap whenever content changes.
 *
 * save_post fires on publish, update, and trash — exactly the moments the
 * sitemap contents can change. The transient is rebuilt lazily on the next
 * /sitemap.xml request.
 *
 * @since 1.2.0
 * @param int $post_id Post ID (unused — any save invalidates).
 */
function gwill_sitemap_invalidate( int $post_id ): void {
	delete_transient( 'gwill_sitemap_xml' );
}
add_action( 'save_post', 'gwill_sitemap_invalidate' );

/**
 * Echo the sitemap, building + caching it if needed.
 *
 * @since 1.2.0
 */
function gwill_sitemap_echo(): void {

	nocache_headers();

	$xml = get_transient( 'gwill_sitemap_xml' );
	if ( false === $xml || ! is_string( $xml ) ) {
		$xml = gwill_sitemap_build();
		set_transient( 'gwill_sitemap_xml', $xml, DAY_IN_SECONDS );
	}

	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-built XML, escaped during build
}
