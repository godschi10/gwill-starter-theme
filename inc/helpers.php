<?php
defined( 'ABSPATH' ) || exit;

/**
 * Render a template part from template-parts/.
 *
 * Usage:
 *   gwill_part( 'content' );
 *   gwill_part( 'cards/card' );
 *   gwill_part( 'hero', [ 'title' => 'Hello' ] );  // $args available inside partial
 *
 * @param string $slug  Path relative to template-parts/ (no .php extension).
 * @param array  $data  Optional associative array passed as $args in the partial.
 */
function gwill_part( string $slug, array $data = [] ): void {
	if ( WP_DEBUG ) {
		$path = get_theme_file_path( 'template-parts/' . $slug . '.php' );
		if ( ! file_exists( $path ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- WP_DEBUG-gated developer warning, not production logging
			trigger_error( '[gwill_part] Missing partial: template-parts/' . $slug . '.php', E_USER_WARNING );
		}
	}
	get_template_part( 'template-parts/' . $slug, null, $data );
}

/**
 * Return sanitized alt text for the featured image of a post.
 *
 * Priority order:
 *   1. Alt text stored in the media library for the attachment
 *   2. Post title as a meaningful fallback (always contextual, never empty)
 *
 * IMPORTANT: This function returns sanitized but NOT HTML-escaped text.
 * WordPress applies esc_attr() internally when building <img> attribute
 * strings (inside wp_get_attachment_image()). Pre-escaping with esc_attr()
 * here would cause double-escaping for any alt text containing &, ", <, or >
 * — the browser would render "Dog &amp; cat" instead of "Dog & cat".
 *
 * Usage (correct — WP handles escaping):
 *   the_post_thumbnail( 'gwill-hero', [ 'alt' => gwill_featured_image_alt() ] );
 *
 * Usage (also correct — escape manually for direct HTML output):
 *   <img alt="<?php echo esc_attr( gwill_featured_image_alt() ); ?>">
 *
 * @param int|null $post_id  Post ID. Defaults to current post in The Loop.
 * @return string            Sanitized alt text — call esc_attr() only for direct HTML output.
 */
function gwill_featured_image_alt( ?int $post_id = null ): string {
	$post_id      = $post_id ?? get_the_ID();
	$thumbnail_id = get_post_thumbnail_id( $post_id );

	if ( ! $thumbnail_id ) {
		return '';
	}

	$media_alt = trim( (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) );

	if ( '' !== $media_alt ) {
		// sanitize_text_field: strips HTML tags, removes excess whitespace,
		// removes invalid UTF-8. Does NOT HTML-encode — WP handles that.
		return sanitize_text_field( $media_alt );
	}

	return sanitize_text_field( get_the_title( $post_id ) );
}

/**
 * Return the caption for the featured image, if set in the media library.
 *
 * Returns an empty string when no caption exists. Callers must check the
 * return value before rendering — do not output an empty <figcaption>.
 *
 * The return value is esc_html()'d and safe for direct output in HTML text
 * content (not attributes). Do not apply additional escaping at the call site.
 *
 * @param int|null $post_id  Post ID. Defaults to current post in The Loop.
 * @return string            esc_html()'d caption, or empty string.
 */
function gwill_featured_image_caption( ?int $post_id = null ): string {
	$post_id      = $post_id ?? get_the_ID();
	$thumbnail_id = get_post_thumbnail_id( $post_id );

	if ( ! $thumbnail_id ) {
		return '';
	}

	$caption = wp_get_attachment_caption( $thumbnail_id );

	return $caption ? esc_html( $caption ) : '';
}

// ---------------------------------------------------------------------------
// Excerpt
// ---------------------------------------------------------------------------

// Length is filterable so child themes and per-project inc/ files can override
// without modifying the starter. Example:
//   add_filter( 'gwill_excerpt_length', fn() => 40 );
add_filter( 'excerpt_length', fn() => (int) apply_filters( 'gwill_excerpt_length', 25 ), 999 );

// &#8230; is the numeric XML entity for the ellipsis character (U+2026).
// Unlike &hellip; (an HTML named entity), &#8230; is valid in XML — which
// matters for RSS/Atom feeds where WordPress also uses the excerpt. Named
// entities other than &amp; &lt; &gt; &quot; &apos; are not valid XML.
add_filter( 'excerpt_more', fn() => '&#8230;' );


/**
 * Extract a YouTube video ID from a URL.
 *
 * Handles the four common URL shapes:
 *   https://youtu.be/VIDEO_ID
 *   https://www.youtube.com/watch?v=VIDEO_ID
 *   https://www.youtube.com/embed/VIDEO_ID
 *   https://www.youtube.com/shorts/VIDEO_ID
 *
 * @param string $url Raw YouTube URL from post meta.
 * @return string 11-character video ID, or empty string if unmatched.
 */
function gwill_youtube_id( string $url ): string {
	$patterns = [
		'#youtu\.be/([a-zA-Z0-9_-]{11})#',
		'#[?&]v=([a-zA-Z0-9_-]{11})#',
		'#/embed/([a-zA-Z0-9_-]{11})#',
		'#/shorts/([a-zA-Z0-9_-]{11})#',
	];

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $url, $m ) ) {
			return $m[1];
		}
	}

	return '';
}


// ---------------------------------------------------------------------------
// SEO plugin detection
// ---------------------------------------------------------------------------

/**
 * Detect whether a major SEO plugin is active.
 *
 * Used as a guard before outputting anything this theme would otherwise
 * generate itself — Open Graph / Twitter Card meta tags, for instance —
 * since every one of these plugins already outputs its own equivalent, and
 * outputting both would create duplicate, conflicting meta tags in <head>.
 *
 * [Likely], not [Certain]: detection uses each plugin's standard, long-
 * documented version constant — the most stable, intentionally-maintained
 * compatibility surface plugin authors keep for exactly this purpose — but
 * these are still third-party internals this theme has no control over.
 * Filterable for a specific build that needs to override or extend it:
 *
 *   add_filter( 'gwill_seo_plugin_active', fn( $active ) => true );
 *
 * @return bool
 * @since  1.0.50
 */
function gwill_seo_plugin_active(): bool {
	$active = defined( 'RANK_MATH_VERSION' )                 // RankMath
		|| defined( 'WPSEO_VERSION' )                        // Yoast SEO
		|| defined( 'AIOSEO_VERSION' )                       // All in One SEO
		|| function_exists( 'aioseo' )                       // AIOSEO 4.x accessor — extra check, constant names shift between major versions
		|| defined( 'SEOPRESS_VERSION' )                     // SEOPress
		|| defined( 'THE_SEO_FRAMEWORK_VERSION' );           // The SEO Framework

	return (bool) apply_filters( 'gwill_seo_plugin_active', $active );
}



/**
 * Resolve "the" category for a post — honouring RankMath / Yoast primary
 * term meta when set, falling back to the first assigned category.
 *
 * Extracted in 1.0.50 from three near-identical copies of this exact logic
 * (gwill_breadcrumbs(), template-parts/content.php, single.php) that had
 * accumulated independently as each was built. A fourth consumer
 * (related posts) was the trigger to finally consolidate rather than
 * copy-paste a fourth time — all four now call this one function, so a
 * future fix to the primary-term logic only ever needs to happen once.
 *
 * @param  int $post_id Post ID. Defaults to the current post in the loop.
 * @return WP_Term|null Null if the post has no categories assigned at all.
 * @since  1.0.50
 */
function gwill_get_primary_category( int $post_id = 0 ): ?WP_Term {

	$post_id = $post_id ?: get_the_ID();
	$cats    = get_the_category( $post_id );

	if ( ! $cats ) {
		return null;
	}

	// NOTE (v1.0.54): the RankMath key here was previously the wrong name
	// ('rank_math_primary_term_category', which RankMath never writes) —
	// it never matched, so this always fell through to $cats[0] below.
	// The real key RankMath saves to is 'rank_math_primary_category'.
	$primary_id = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
	if ( ! $primary_id ) {
		$primary_id = (int) get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );
	}

	if ( $primary_id ) {
		foreach ( $cats as $cat ) {
			if ( (int) $cat->term_id === $primary_id ) {
				return $cat;
			}
		}
	}

	return $cats[0];
}

/**
 * Estimate reading time for a post, in whole minutes.
 *
 * @param  int $post_id Post ID. Defaults to the current post in the loop.
 * @return int Minutes, minimum 1.
 * @since  1.0.50
 */
function gwill_reading_time( int $post_id = 0 ): int {

	$post_id = $post_id ?: get_the_ID();
	$content = get_post_field( 'post_content', $post_id );
	$words   = str_word_count( wp_strip_all_tags( $content ) );
	$wpm     = (int) apply_filters( 'gwill_reading_speed_wpm', 200 );

	return max( 1, (int) ceil( $words / max( 1, $wpm ) ) );
}



// ---------------------------------------------------------------------------
// Breadcrumbs
// ---------------------------------------------------------------------------

/**
 * Render accessible breadcrumbs with Schema.org BreadcrumbList markup.
 *
 * Never outputs anything on the front page (is_front_page()).
 * Call after the_post() on singular templates; safe to call before the
 * loop on archive/search templates — those use get_queried_object().
 *
 * SEO-plugin integration: if you prefer Yoast or RankMath breadcrumbs,
 * return false from the filter and output the plugin's breadcrumb in its
 * place:
 *
 *   add_filter( 'gwill_show_breadcrumbs', '__return_false' );
 *
 * Schema.org: uses BreadcrumbList / ListItem / position per Google's
 * structured data spec. The current page (last crumb) is linked via
 * <link itemprop="item"> rather than an <a> so it doesn't duplicate
 * the <h1> anchor in the accessibility tree.
 *
 * @package GWill_Starter
 * @since   1.0.43
 */
function gwill_breadcrumbs(): void {

	if ( ! apply_filters( 'gwill_show_breadcrumbs', true ) ) {
		return;
	}

	// Never show on the front page.
	if ( is_front_page() ) {
		return;
	}

	$crumbs = [];

	// Home is always the first crumb.
	$crumbs[] = [
		'label'   => __( 'Home', 'gwill-starter' ),
		'url'     => home_url( '/' ),
		'current' => false,
	];

	if ( is_home() ) {
		// Posts page (Settings → Reading → "Posts page").
		$page_id = (int) get_option( 'page_for_posts' );
		$crumbs[] = [
			'label'   => $page_id ? get_the_title( $page_id ) : __( 'Blog', 'gwill-starter' ),
			'url'     => $page_id ? (string) get_permalink( $page_id ) : home_url( '/' ),
			'current' => true,
		];

	} elseif ( is_single() ) {
		$cat = gwill_get_primary_category();
		if ( $cat ) {

			// Walk up ancestor categories.
			foreach ( array_reverse( get_ancestors( $cat->term_id, 'category' ) ) as $anc_id ) {
				$anc = get_category( $anc_id );
				if ( $anc && ! is_wp_error( $anc ) ) {
					$crumbs[] = [
						'label'   => $anc->name,
						'url'     => (string) get_category_link( $anc ),
						'current' => false,
					];
				}
			}
			$crumbs[] = [
				'label'   => $cat->name,
				'url'     => (string) get_category_link( $cat->term_id ),
				'current' => false,
			];
		}
		$crumbs[] = [
			'label'   => get_the_title(),
			'url'     => (string) get_permalink(),
			'current' => true,
		];

	} elseif ( is_page() ) {
		global $post;
		foreach ( array_reverse( get_post_ancestors( $post ) ) as $anc_id ) {
			$crumbs[] = [
				'label'   => get_the_title( $anc_id ),
				'url'     => (string) get_permalink( $anc_id ),
				'current' => false,
			];
		}
		$crumbs[] = [
			'label'   => get_the_title(),
			'url'     => (string) get_permalink(),
			'current' => true,
		];

	} elseif ( is_category() ) {
		$cat = get_queried_object();
		if ( $cat instanceof WP_Term ) {
			foreach ( array_reverse( get_ancestors( $cat->term_id, 'category' ) ) as $anc_id ) {
				$anc = get_category( $anc_id );
				if ( $anc && ! is_wp_error( $anc ) ) {
					$crumbs[] = [
						'label'   => $anc->name,
						'url'     => (string) get_category_link( $anc ),
						'current' => false,
					];
				}
			}
			$crumbs[] = [
				'label'   => $cat->name,
				'url'     => (string) get_term_link( $cat ),
				'current' => true,
			];
		}

	} elseif ( is_tag() ) {
		$tag = get_queried_object();
		if ( $tag instanceof WP_Term ) {
			$crumbs[] = [
				'label'   => $tag->name,
				'url'     => (string) get_term_link( $tag ),
				'current' => true,
			];
		}

	} elseif ( is_author() ) {
		$author = get_queried_object();
		if ( $author instanceof WP_User ) {
			$crumbs[] = [
				'label'   => $author->display_name,
				'url'     => get_author_posts_url( $author->ID ),
				'current' => true,
			];
		}

	} elseif ( is_search() ) {
		$crumbs[] = [
			'label'   => sprintf(
				/* translators: %s: search query string */
				__( 'Search: %s', 'gwill-starter' ),
				get_search_query()
			),
			'url'     => (string) get_search_link(),
			'current' => true,
		];

	} elseif ( is_404() ) {
		$crumbs[] = [
			'label'   => __( '404 Not Found', 'gwill-starter' ),
			'url'     => '',
			'current' => true,
		];

	} elseif ( is_day() ) {
		$y = (int) get_query_var( 'year' );
		$m = (int) get_query_var( 'monthnum' );
		$d = (int) get_query_var( 'day' );
		$crumbs[] = [
			'label'   => date_i18n( get_option( 'date_format' ), mktime( 0, 0, 0, $m, $d, $y ) ),
			'url'     => get_day_link( $y, $m, $d ),
			'current' => true,
		];

	} elseif ( is_month() ) {
		$y = (int) get_query_var( 'year' );
		$m = (int) get_query_var( 'monthnum' );
		$crumbs[] = [
			'label'   => date_i18n( 'F Y', mktime( 0, 0, 0, $m, 1, $y ) ),
			'url'     => get_month_link( $y, $m ),
			'current' => true,
		];

	} elseif ( is_year() ) {
		$y = (int) get_query_var( 'year' );
		$crumbs[] = [
			'label'   => (string) $y,
			'url'     => get_year_link( $y ),
			'current' => true,
		];

	} elseif ( is_archive() ) {
		// Generic fallback — post type archives, taxonomy archives, etc.
		$crumbs[] = [
			'label'   => get_the_archive_title(),
			'url'     => '',
			'current' => true,
		];
	}

	// Nothing useful was built (e.g. only Home, on a non-singular non-archive
	// page) — bail silently.
	if ( count( $crumbs ) <= 1 ) {
		return;
	}

	$total = count( $crumbs );
	?>
	<nav class="gwill-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'gwill-starter' ); ?>">
		<ol
			class="gwill-breadcrumbs__list"
			itemscope
			itemtype="https://schema.org/BreadcrumbList"
		>
			<?php foreach ( $crumbs as $i => $crumb ) :
				$pos     = $i + 1;
				$is_last = ( $pos === $total );
			?>
			<li
				class="gwill-breadcrumbs__item<?php echo $is_last ? ' gwill-breadcrumbs__item--current' : ''; ?>"
				itemprop="itemListElement"
				itemscope
				itemtype="https://schema.org/ListItem"
			>
				<?php if ( ! $is_last && $crumb['url'] ) : ?>
					<a href="<?php echo esc_url( $crumb['url'] ); ?>" itemprop="item">
						<span itemprop="name"><?php echo esc_html( $crumb['label'] ); ?></span>
					</a>
				<?php else : ?>
					<span
						itemprop="name"
						<?php echo $is_last ? 'aria-current="page"' : ''; ?>
					><?php echo esc_html( $crumb['label'] ); ?></span>
					<?php if ( $crumb['url'] ) : ?>
						<link itemprop="item" href="<?php echo esc_url( $crumb['url'] ); ?>">
					<?php endif; ?>
				<?php endif; ?>
				<meta itemprop="position" content="<?php echo esc_attr( (string) $pos ); ?>">
			</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}
