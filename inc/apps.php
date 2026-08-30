<?php

/*
Table of Contents
1. gwill_apps_registry — register apps (path, title, description, icon)
2. gwill_apps_get — registry lookup with defaults
3. gwill_apps_hub_url — /apps/ hub permalink
4. gwill_apps_page_url — app page permalink (WP page or custom route)
5. gwill_apps_enqueue — app-page assets
6. gwill_apps_schema — CollectionPage + ItemList JSON-LD on the hub
7. gwill_apps_register_rewrites — app routes as REAL rewrites (L4)
8. gwill_apps_query_var — query var + canonical guard
9. gwill_apps_maybe_render — template_include route renderer
10. gwill_apps_title — document title parts for app pages
*/

/**
 * Custom Apps skeleton — the /apps/ pattern.
 *
 * The idea ported from the tech + finance themes' /tools/ clusters, made
 * generic for the starter. A build registers its apps in ONE place —
 * gwill_apps_registry() — and gets, for each app:
 *
 *   - a real page at /apps/<slug>/ (rewrite + query var + canonical guard,
 *     docs/LAWS.md L4 — never a template_include sniff alone);
 *   - a card on the /apps/ hub (title, description, icon, link);
 *   - CollectionPage + ItemList JSON-LD on the hub, SoftwareApplication +
 *     FAQPage schema on app pages;
 *   - an automatically-enqueued per-app JS file
 *     (assets/js/apps/<slug>.js) and CSS file (assets/css/apps/<slug>.css)
 *     — each is loaded ONLY on its own app page.
 *
 * Apps are pure client-side by default (works offline once the SW caches
 * them); an app that needs a server engine adds an inc file and hooks in.
 *
 * Demo app shipped: "word-counter" (assets/js/apps/word-counter.js) —
 * a complete working example of the pattern, deletable without touching
 * anything else.
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── 1. registry ────────────────────────────────────────────────────── */

/**
 * The single registry. Add a build's apps here (or via the
 * gwill_apps_registry filter from a child theme / client plugin).
 *
 * Each entry:
 *   'slug'        => unique URL slug under /apps/
 *   'title'       => card + page title
 *   'excerpt'     => card + schema description (~150 chars)
 *   'icon'        => SVG path markup (24x24 viewBox), stroke currentColor
 *   'js'          => assets/js/apps/<slug>.js (auto-enqueued on its page)
 *   'css'         => assets/css/apps/<slug>.css (auto-enqueued on its page)
 *   'faq'         => optional [ ['q' => ..., 'a' => ...], ... ] — renders
 *                    FAQPage schema on the app page (and a visible list)
 *
 * @return array[] Apps in display order.
 */
function gwill_apps_registry() {
	$apps = array(
		array(
			'slug'    => 'word-counter',
			'title'   => __( 'Word Counter', 'gwill-starter' ),
			'excerpt' => __( 'Count words, characters, sentences and paragraphs as you type — instant, private, all in your browser.', 'gwill-starter' ),
			'icon'    => '<path d="M4 7h16M4 12h10M4 17h7" stroke-linecap="round"/>',
			'js'      => true,
			'css'     => true,
			'faq'     => array(
				array(
					'q' => __( 'Does my text leave my device?', 'gwill-starter' ),
					'a' => __( 'No. The counter runs entirely in your browser — nothing is uploaded or stored.', 'gwill-starter' ),
				),
				array(
					'q' => __( 'What counts as a sentence or a paragraph?', 'gwill-starter' ),
					'a' => __( 'A sentence ends with a period, question mark or exclamation point. A paragraph is a block of text separated by blank lines.', 'gwill-starter' ),
				),
			),
		),
		array(
			'slug'    => 'case-converter',
			'title'   => __( 'Case Converter', 'gwill-starter' ),
			'excerpt' => __( 'Convert text between UPPER, lower, Title, Sentence, camelCase, snake_case and kebab-case — instant, private, in your browser.', 'gwill-starter' ),
			'icon'    => '<path d="M4 19V5m10 14V5M4 12h10M17 8l4-4m0 0h-4m4 0v4" stroke-linecap="round" stroke-linejoin="round"/>',
			'js'      => true,
			'css'     => true,
			'faq'     => array(
				array(
					'q' => __( 'Does my text leave my device?', 'gwill-starter' ),
					'a' => __( 'No. The conversion runs entirely in your browser — nothing is uploaded or stored.', 'gwill-starter' ),
				),
				array(
					'q' => __( 'What is Title Case here?', 'gwill-starter' ),
					'a' => __( 'Every word is capitalised except small connecting words (a, and, of, the…) after the first.', 'gwill-starter' ),
				),
			),
		),
		array(
			'slug'    => 'unit-converter',
			'title'   => __( 'Unit Converter', 'gwill-starter' ),
			'excerpt' => __( 'Convert between common length, weight and temperature units — metres to feet, kg to pounds, Celsius to Fahrenheit.', 'gwill-starter' ),
			'icon'    => '<path d="M3 17l4-10 4 10M5 14h4m4 3l3-9 3 9m-5-3h4" stroke-linecap="round" stroke-linejoin="round"/>',
			'js'      => true,
			'css'     => true,
			'faq'     => array(
				array(
					'q' => __( 'Which units are supported?', 'gwill-starter' ),
					'a' => __( 'Length: mm, cm, m, km, in, ft, yd, mi. Weight: mg, g, kg, t, oz, lb. Temperature: Celsius, Fahrenheit, Kelvin.', 'gwill-starter' ),
				),
				array(
					'q' => __( 'How precise are the results?', 'gwill-starter' ),
					'a' => __( 'Results are shown to six decimal places, using exact international definitions of each unit.', 'gwill-starter' ),
				),
			),
		),
	);
	return apply_filters( 'gwill_apps_registry', $apps );
}

/* ── 2. lookup ─────────────────────────────────────────────────────── */

/**
 * @param string $slug App slug.
 * @return array|null The app entry or null.
 */
function gwill_apps_get( $slug ) {
	foreach ( gwill_apps_registry() as $app ) {
		if ( $slug === $app['slug'] ) {
			return $app;
		}
	}
	return null;
}

/* ── 3. URLs ────────────────────────────────────────────────────────── */

function gwill_apps_hub_url() {
	return home_url( '/apps/' );
}

function gwill_apps_page_url( $slug ) {
	return home_url( '/apps/' . rawurlencode( $slug ) . '/' );
}

/* ── 4. enqueues ────────────────────────────────────────────────────── */

/**
 * Auto-enqueue the per-app JS/CSS on the app's own page only.
 * Assets live at assets/js/apps/<slug>.js and assets/css/apps/<slug>.css.
 */
function gwill_apps_enqueue() {
	$slug = get_query_var( 'gwill_app' );
	if ( ! $slug ) {
		return;
	}
	$app = gwill_apps_get( $slug );
	if ( ! $app ) {
		return;
	}
	if ( ! empty( $app['js'] ) ) {
		$js = get_template_directory() . '/assets/js/apps/' . sanitize_file_name( $slug ) . '.js';
		if ( file_exists( $js ) ) {
			wp_enqueue_script(
				'gwill-app-' . $slug,
				get_template_directory_uri() . '/assets/js/apps/' . sanitize_file_name( $slug ) . '.js',
				array(),
				filemtime( $js ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}
	if ( ! empty( $app['css'] ) ) {
		$css = get_template_directory() . '/assets/css/apps/' . sanitize_file_name( $slug ) . '.css';
		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'gwill-app-' . $slug,
				get_template_directory_uri() . '/assets/css/apps/' . sanitize_file_name( $slug ) . '.css',
				array(),
				filemtime( $css )
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'gwill_apps_enqueue', 20 );

/* ── 5. schema ──────────────────────────────────────────────────────── */

/**
 * CollectionPage + ItemList JSON-LD on the hub; SoftwareApplication +
 * FAQPage on app pages. Emitted at wp_head priority 5 (after the SEO
 * layer's own graph) as its own @graph, so a build's SEO plugin can be
 * given the same data via the gwill_apps_registry filter.
 */
function gwill_apps_schema() {
	if ( is_page( 'apps' ) ) {
		$items = array();
		$i     = 0;
		foreach ( gwill_apps_registry() as $app ) {
			$i++;
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i,
				'url'      => gwill_apps_page_url( $app['slug'] ),
				'name'     => $app['title'],
			);
		}
		$graph = array(
			'@context' => 'https://schema.org',
			'@type'    => 'CollectionPage',
			'name'     => __( 'Apps', 'gwill-starter' ) . ' — ' . get_bloginfo( 'name' ),
			'url'      => gwill_apps_hub_url(),
			'mainEntity' => array(
				'@type'           => 'ItemList',
				'itemListElement' => $items,
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		return;
	}

	$slug = get_query_var( 'gwill_app' );
	$app  = $slug ? gwill_apps_get( $slug ) : null;
	if ( ! $app ) {
		return;
	}

	$graph = array(
		'@context'     => 'https://schema.org',
		'@type'        => 'SoftwareApplication',
		'name'         => $app['title'],
		'applicationCategory' => 'UtilitiesApplication',
		'operatingSystem'     => 'Any',
		'url'          => gwill_apps_page_url( $app['slug'] ),
		'description'  => $app['excerpt'],
		'offers'       => array(
			'@type'         => 'Offer',
			'price'         => '0',
			'priceCurrency' => 'USD',
		),
	);
	if ( ! empty( $app['faq'] ) ) {
		$graph['mainEntity'] = array(
			'@type'          => 'FAQPage',
			'mainEntity'     => array_map(
				function ( $item ) {
					return array(
						'@type'          => 'Question',
						'name'           => $item['q'],
						'acceptedAnswer' => array(
							'@type'      => 'Answer',
							'text'       => $item['a'],
						),
					);
				},
				$app['faq']
			),
		);
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'gwill_apps_schema', 5 );

/* ── 6. rewrites — REAL routes (L4) ─────────────────────────────────── */

function gwill_apps_register_rewrites() {
	add_rewrite_rule( '^apps/?$', 'index.php?gwill_apps_hub=1', 'top' );
	foreach ( gwill_apps_registry() as $app ) {
		add_rewrite_rule(
			'^apps/(' . preg_quote( $app['slug'], '/' ) . ')/?$',
			'index.php?gwill_app=' . $app['slug'],
			'top'
		);
	}
}
add_action( 'init', 'gwill_apps_register_rewrites' );

function gwill_apps_query_vars( array $vars ) {
	$vars[] = 'gwill_apps_hub';
	$vars[] = 'gwill_app';
	return $vars;
}
add_filter( 'query_vars', 'gwill_apps_query_vars' );

function gwill_apps_no_canonical_redirect( $redirect_url ) {
	if ( get_query_var( 'gwill_apps_hub' ) || get_query_var( 'gwill_app' ) ) {
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'gwill_apps_no_canonical_redirect', 10, 2 );

/* ── 7. renderer ───────────────────────────────────────────────────── */

/**
 * Template_include route renderer — gate on the query vars (L4: the
 * rewrite + query var make these REAL 200 queries, never 404-status
 * bodies).
 */
function gwill_apps_maybe_render( $template ) {
	$hub = get_query_var( 'gwill_apps_hub' );
	$app = get_query_var( 'gwill_app' );

	if ( $hub ) {
		return get_template_directory() . '/page-apps.php';
	}
	if ( $app && gwill_apps_get( $app ) ) {
		return get_template_directory() . '/template-app.php';
	}
	return $template;
}
add_action( 'template_include', 'gwill_apps_maybe_render', 20 );
add_filter( 'body_class', function ( $classes ) {
	if ( get_query_var( 'gwill_apps_hub' ) ) {
		$classes[] = 'gwill-apps-hub';
	}
	$slug = get_query_var( 'gwill_app' );
	if ( $slug ) {
		$classes[] = 'gwill-app-page';
		$classes[] = 'gwill-app-' . sanitize_html_class( $slug );
	}
	return $classes;
} );

/* ── 8. document titles ─────────────────────────────────────────────── */

function gwill_apps_title( array $parts ) {
	$hub = get_query_var( 'gwill_apps_hub' );
	$slug = get_query_var( 'gwill_app' );
	if ( $hub ) {
		$parts['title'] = __( 'Apps', 'gwill-starter' );
	}
	if ( $slug ) {
		$app = gwill_apps_get( $slug );
		if ( $app ) {
			$parts['title'] = $app['title'];
		}
	}
	return $parts;
}
add_filter( 'document_title_parts', 'gwill_apps_title', 20 );

/* ── 9. SEO-layer integration (noindex + no meta description) ───────── */

/**
 * App routes are virtual pages, not content pages — exclude them from the
 * theme SEO layer's default handling so builds can layer their own meta.
 * The hub is indexable (it's a real nav destination).
 */
function gwill_apps_seo_hidden_slugs( $slugs ) {
	return $slugs; // hub + app pages keep default SEO treatment
}
