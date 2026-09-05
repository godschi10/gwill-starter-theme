<?php
/**
 * Theme-owned SEO layer (no plugin needed).
 *
 * Provides what WP core does not:
 *   - front-page <title> capping (~60 chars: brand + tagline trimmed
 *     word-by-word)
 *   - long-title singular <title> trim (drop the brand suffix)
 *   - meta description (post excerpt / term description / author line /
 *     template one-liners / site tagline fallback)
 *   - robots meta (noindex search, 404, date archives, attachments,
 *     tags, authors, paged archives; index everywhere else)
 *   - JSON-LD schema: WebSite + Organization (all pages), Article
 *     (single posts)
 *   - self-referencing canonical for non-singulars
 *   - robots.txt pointing crawlers at the theme-owned /sitemap.xml
 *
 * Kept minimal on purpose: title tags are handled by WP core
 * (wp_get_document_title), canonical links on singulars by core
 * (rel_canonical), and the XML sitemap by the theme itself
 * (inc/sitemap.php - serves /sitemap.xml). No plugin, no bloat - the
 * same approach as the GWill tech and finance themes; this is the
 * generic-base adaptation of finance's inc/seo.php (finance's ACF-flavored
 * pieces - hardcoded brand titles, ACF settings-page slugs - are replaced
 * with filterable generics here).
 *
 * BreadcrumbList JSON-LD is deliberately NOT emitted: the visible
 * breadcrumbs (gwill_breadcrumbs(), inc/helpers.php) already carry
 * BreadcrumbList microdata, and a second JSON-LD copy of the same trail
 * would duplicate structured data in <head>.
 *
 * @package GWill_Starter
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

// ── Front-page <title> (WP core title-tag) ───────────────────────────────────

add_filter( 'pre_get_document_title', 'gwill_front_page_title', 11 );

/**
 * Cap the front-page <title> at ~60 chars: brand first, tagline trimmed
 * WORD-BY-WORD to fit the budget.
 *
 * WP core's default front-page title is "Blogname - full tagline", which
 * routinely exceeds Google's ~60-char display truncation. This builds
 * "Brand - Tagline" and, when that overflows, trims the tagline back
 * word-by-word (never mid-word). Brand alone when even the first word
 * cannot fit.
 *
 * The is_category() guard excludes a bare category index served at the
 * front URL (is_front_page() is true there) - a build that sets one up
 * owns its own title filter for that page.
 *
 * Filterable per build:
 *   add_filter( 'gwill_front_page_tagline', fn() => 'My keyword headline' );
 *
 * @since 1.2.0
 * @param string $title Current title.
 * @return string
 */
function gwill_front_page_title( string $title ): string {
	// Defer to a major SEO plugin when one is active - RankMath hooks
	// pre_get_document_title at priority 10; this filter at 11 would
	// otherwise run after it and override the admin's configured
	// homepage title (finance conflict-audit lesson).
	if ( gwill_seo_plugin_active() ) {
		return $title;
	}

	if ( is_category() || ! is_front_page() ) {
		return $title;
	}

	$name      = (string) get_bloginfo( 'name' );
	$tagline   = (string) apply_filters( 'gwill_front_page_tagline', (string) get_bloginfo( 'description' ) );
	$sep       = (string) apply_filters( 'document_title_separator', '–' );
	$candidate = $name . ' ' . $sep . ' ' . $tagline;

	if ( mb_strlen( $candidate ) <= 60 ) {
		return $candidate;
	}

	// Budget for the tagline after "<name> <sep> " (name + sep + 2 spaces).
	$budget = 60 - mb_strlen( $name ) - mb_strlen( $sep ) - 2;
	if ( $budget <= 0 ) {
		return $name;
	}

	$fit = '';
	foreach ( preg_split( '/[\s,]+/u', $tagline ) as $word ) {
		$next = '' === $fit ? $word : $fit . ' ' . $word;
		if ( mb_strlen( $next ) > $budget ) {
			break;
		}
		$fit = $next;
	}

	return $fit ? $name . ' ' . $sep . ' ' . $fit : $name;
}

/**
 * Long-titled singulars: drop the brand suffix from the <title> output
 * only when the full "Post Title - Site" string would exceed ~65 chars.
 * Settings and brand usage everywhere else stay untouched.
 *
 * Uses the real separator (document_title_separator, core default '-') so
 * the length check matches what wp_get_document_title() will render.
 *
 * @since 1.2.0
 * @param array $parts Document title parts.
 * @return array
 */
function gwill_document_title_parts( array $parts ): array {
	if ( gwill_seo_plugin_active() ) {
		return $parts;
	}

	if ( is_singular() && ! empty( $parts['site'] ) ) {
		$sep   = (string) apply_filters( 'document_title_separator', '-' );
		$title = trim( (string) ( $parts['title'] ?? '' ) );
		$site  = trim( (string) $parts['site'] );
		if ( mb_strlen( $title . ' ' . $sep . ' ' . $site ) > 65 ) {
			$parts['site'] = '';
		}
	}

	return $parts;
}
add_filter( 'document_title_parts', 'gwill_document_title_parts', 10, 1 );

// ── Core XML sitemaps - disabled (the theme owns /sitemap.xml) ───────────────

add_filter( 'wp_sitemaps_enabled', 'gwill_wp_sitemaps_enabled', 10, 1 );

/**
 * Disable WP core's wp-sitemap.xml - the theme owns /sitemap.xml and
 * robots.txt only advertises that one; a second live sitemap is dead
 * weight. Defer to a major SEO plugin when one is active.
 *
 * @since 1.2.0
 * @param bool $enabled Whether core sitemaps are enabled.
 * @return bool
 */
function gwill_wp_sitemaps_enabled( $enabled ) {
	if ( gwill_seo_plugin_active() ) {
		return $enabled;
	}
	return false;
}

// ── Meta description ─────────────────────────────────────────────────────────

add_action( 'wp_head', 'gwill_meta_description', 2 );

/**
 * Output a meta description: post excerpt (singular), term description
 * (taxonomy archives), a template one-liner, or the site tagline
 * everywhere else.
 *
 * @since 1.2.0
 */
function gwill_meta_description(): void {
	if ( gwill_seo_plugin_active() ) {
		return;
	}

	$description = '';

	if ( is_singular() ) {
		$description = has_excerpt()
			? get_the_excerpt()
			: wp_trim_words( wp_strip_all_tags( get_the_content() ), 30 );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term && ! empty( $term->description ) ) {
			$description = wp_trim_words( wp_strip_all_tags( $term->description ), 30 );
		}
	} elseif ( is_author() ) {
		$description = sprintf(
			/* translators: 1: author display name, 2: site name. */
			__( 'Articles written by %1$s on %2$s.', 'gwill-starter' ),
			get_the_author_meta( 'display_name', get_queried_object_id() ),
			get_bloginfo( 'name' )
		);
	}

	// Template-specific one-liners before the global tagline fallback  - 
	// these pages render with empty post content, so their derived
	// descriptions would otherwise all fall back to the site tagline
	// (several pages sharing one boilerplate description). Copy is
	// filterable per build.
	if ( ! $description && is_home() ) {
		$description = (string) apply_filters(
			'gwill_home_meta_description',
			sprintf(
				/* translators: %s: site name. */
				__( 'Every guide on %s - tutorials, tips and walkthroughs, newest first.', 'gwill-starter' ),
				get_bloginfo( 'name' )
			)
		);
	}

	if ( ! $description && is_page_template( 'template-contact.php' ) ) {
		$description = (string) apply_filters(
			'gwill_contact_meta_description',
			__( 'Contact - partnerships, questions and requests. Responses within 48 hours.', 'gwill-starter' )
		);
	}

	if ( ! $description ) {
		$description = get_bloginfo( 'description' );
	}

	$description = trim( wp_strip_all_tags( (string) $description ) );

	// Cap at ~160 characters - wp_trim_words(..., 30) can run ~180+ chars,
	// beyond the search-engine display budget. Word-safe ellipsis cap
	// (SEO audit v1.3.8).
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $description ) > 160 ) {
		$description = mb_substr( $description, 0, 157 ) . '…';
	}

	if ( $description ) {
		printf(
			'<meta name="description" content="%s">' . "\n",
			esc_attr( $description )
		);
	}
}

// ── Robots meta ──────────────────────────────────────────────────────────────

/**
 * Hidden page slugs - noindexed, never linked or sitemapped.
 *
 * Generic base default is empty; a build with hidden settings/utility
 * pages filters its own slugs in:
 *
 *   add_filter( 'gwill_hidden_slugs', fn() => [ 'site-settings', 'newsletter-thanks' ] );
 *
 * (Finance's ACF-flavored original hardcoded its slugs here; the generic
 * base makes the list a filter so every build owns its own.)
 *
 * @since 1.2.0
 * @return string[]
 */
function gwill_hidden_slugs(): array {
	return (array) apply_filters( 'gwill_hidden_slugs', [] );
}

/**
 * True on any hidden page slug (noindexed + sitemap-excluded).
 *
 * @since 1.2.0
 * @return bool
 */
function gwill_is_hidden_page(): bool {
	foreach ( gwill_hidden_slugs() as $slug ) {
		if ( is_page( $slug ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Noindex low-value templates; everything else stays indexable.
 *
 * @since 1.2.0
 */
function gwill_robots_meta(): void {
	if ( gwill_seo_plugin_active() ) {
		return;
	}

	// Respect an explicit per-post noindex (yoast-style meta key; WP core
	// does not read it, so honor it here for editor control).
	if ( is_singular() && (int) get_post_meta( get_the_ID(), '_yoast_wpseo_meta-robots-noindex', true ) === 1 ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
		return;
	}

	if ( gwill_is_hidden_page() ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
		return;
	}

	// Tag and author archives are thin duplicate territory; date archives
	// and paged views are pagination duplicates; search and 404 have no
	// index value. Categories, the front page and singulars index.
	$noindex = is_search() || is_404() || is_date() || is_attachment() || is_paged()
		|| is_tag() || is_author();

	if ( $noindex ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}
}
add_action( 'wp_head', 'gwill_robots_meta', 3 );

// ── JSON-LD schema ───────────────────────────────────────────────────────────

add_action( 'wp_head', 'gwill_json_ld', 5 );

/**
 * Output schema.org JSON-LD: WebSite (+ Organization) globally, Article
 * on single posts.
 *
 * BreadcrumbList is intentionally absent - the visible breadcrumbs in
 * gwill_breadcrumbs() already carry BreadcrumbList microdata (see the
 * file header).
 *
 * @since 1.2.0
 */
function gwill_json_ld(): void {
	if ( gwill_seo_plugin_active() ) {
		return;
	}

	$site_url  = home_url( '/' );
	$site_name = get_bloginfo( 'name' );
	$graph     = [];

	// WebSite + Organization (site-level, printed once).
	$graph[] = [
		'@type'       => 'WebSite',
		'@id'         => $site_url . '#website',
		'url'         => $site_url,
		'name'        => $site_name,
		'description' => get_bloginfo( 'description' ),
		'publisher'   => [
			'@id' => $site_url . '#organization',
		],
		// SearchAction - lets Google show the site search box in SERPs.
		'potentialAction' => [
			'@type'       => 'SearchAction',
			'target'      => [
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			],
			'query-input' => 'required name=search_term_string',
		],
	];

	$graph[] = [
		'@type' => 'Organization',
		'@id'   => $site_url . '#organization',
		'name'  => $site_name,
		'url'   => $site_url,
	];

	// Article (single posts only).
	if ( is_singular( 'post' ) ) {
		$post_id     = get_the_ID();
		$author_name = gwill_article_author_name();
		$image       = has_post_thumbnail( $post_id )
			? wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'gwill-hero' )
			: null;

		$article = [
			'@type'            => 'Article',
			'@id'              => get_permalink( $post_id ) . '#article',
			'headline'         => get_the_title( $post_id ),
			'datePublished'    => get_the_date( 'c', $post_id ),
			'dateModified'     => get_the_modified_date( 'c', $post_id ),
			'author'           => $author_name ? [
				'@type' => 'Person',
				'name'  => $author_name,
			] : [],
			'publisher'        => [ '@id' => $site_url . '#organization' ],
			'mainEntityOfPage' => get_permalink( $post_id ),
		];

		if ( $image ) {
			$article['image'] = [
				'@type'  => 'ImageObject',
				'url'    => $image[0],
				'width'  => $image[1],
				'height' => $image[2],
			];
		}

		$graph[] = $article;
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode(
			[
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			],
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		)
	);
}

// ── Canonical (non-singulars) ────────────────────────────────────────────────

/**
 * Self-referencing canonical for non-singular templates.
 *
 * WP core's rel_canonical() only fires on singulars (and the front page),
 * so category archives - the indexed hubs - and the posts page had no
 * canonical at all. Never fires on singulars (core already emits exactly
 * one there).
 *
 * @since 1.2.0
 */
function gwill_canonical_meta(): void {
	if ( gwill_seo_plugin_active() || is_singular() ) {
		return;
	}

	$url = '';

	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_home() ) {
		// Posts page (Settings → Reading → "Posts page"): the canonical
		// must point at the posts page itself, not the homepage.
		$posts_page = (int) get_option( 'page_for_posts' );
		$url        = $posts_page ? (string) get_permalink( $posts_page ) : home_url( '/' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );
			$url  = is_wp_error( $link ) ? '' : (string) $link;
		}
	} elseif ( is_author() ) {
		$url = (string) get_author_posts_url( (int) get_queried_object_id() );
	} elseif ( is_search() ) {
		$url = (string) get_search_link();
	} elseif ( is_date() ) {
		$url = (string) get_pagenum_link();
	}

	if ( ! $url ) {
		return;
	}

	printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) );
}
add_action( 'wp_head', 'gwill_canonical_meta', 10 );

// ── robots.txt ───────────────────────────────────────────────────────────────

/**
 * robots.txt - point crawlers at the theme-owned sitemap.
 *
 * Strips WP core's default wp-sitemap.xml line (the theme owns
 * /sitemap.xml via inc/sitemap.php) and advertises the real one - two
 * Sitemap: entries would give crawlers mixed signals about which index
 * to trust. Also sets explicit AI-crawler rules (allow the useful ones,
 * block the ones known to scrape without consent).
 *
 * @since 1.2.0
 * @param string $output Core robots.txt body.
 * @return string
 */
function gwill_robots_txt( string $output ): string {
	// Defer to a major SEO plugin when one is active (same rule as every
	// other theme-owned SEO output) - a plugin owns robots.txt then, and
	// the theme must not fight it with a second Sitemap: line.
	if ( gwill_seo_plugin_active() ) {
		return $output;
	}
	$output = preg_replace( '/^Sitemap: .*wp-sitemap\.xml.*$/mi', '', $output );

	$extra = "\n"
		. "User-agent: GPTBot\nAllow: /\n\n"
		. "User-agent: OAI-SearchBot\nAllow: /\n\n"
		. "User-agent: ChatGPT-User\nAllow: /\n\n"
		. "User-agent: ClaudeBot\nAllow: /\n\n"
		. "User-agent: PerplexityBot\nAllow: /\n\n"
		. "User-agent: CCBot\nDisallow: /\n\n"
		. "User-agent: Bytespider\nDisallow: /\n\n"
		. "Sitemap: " . esc_url( home_url( '/sitemap.xml' ) ) . "\n";

	return $output . $extra;
}
add_filter( 'robots_txt', 'gwill_robots_txt', 10 );
