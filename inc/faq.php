<?php
/**
 * FAQ accordion + Schema.org FAQPage markup.
 *
 * Uses WordPress core's native <details>/<summary> block (no custom block,
 * no JS accordion library — the browser's own <details> element is already
 * a fully accessible, keyboard-operable accordion with zero JavaScript).
 * This file's actual job is two things: provide an editor-friendly block
 * pattern so building an FAQ section doesn't mean writing raw HTML, and
 * detect that pattern's output in rendered content to emit matching
 * FAQPage JSON-LD — so the visible accordion and the invisible schema are
 * always built from the exact same source and can never drift apart.
 *
 * @package GWill_Starter
 * @since   1.0.50
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'gwill_register_faq_block_pattern' );
add_action( 'wp_head', 'gwill_output_faq_schema' );

/**
 * Register the "FAQ Section" block pattern.
 *
 * A Group block (class gwill-faq) containing three pre-filled Details
 * blocks. An editor inserts the pattern from the block inserter and just
 * edits the question/answer text — no schema, no markup to write by hand.
 *
 * @since 1.0.50
 */
function gwill_register_faq_block_pattern(): void {

	if ( ! function_exists( 'register_block_pattern' ) ) {
		return; // WP < 5.5 — patterns API doesn't exist yet.
	}

	register_block_pattern(
		'gwill-starter/faq-section',
		[
			'title'       => __( 'FAQ Section', 'gwill-starter' ),
			'description' => __( 'A set of expandable questions with automatic FAQ schema markup for search engines.', 'gwill-starter' ),
			'categories'  => [ 'gwill-starter' ],
			'content'     => '<!-- wp:group {"className":"gwill-faq"} -->
<div class="wp-block-group gwill-faq">
<!-- wp:details -->
<details class="wp-block-details"><summary>' . esc_html__( 'Question one goes here?', 'gwill-starter' ) . '</summary><!-- wp:paragraph -->
<p>' . esc_html__( 'Answer one goes here.', 'gwill-starter' ) . '</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>' . esc_html__( 'Question two goes here?', 'gwill-starter' ) . '</summary><!-- wp:paragraph -->
<p>' . esc_html__( 'Answer two goes here.', 'gwill-starter' ) . '</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>' . esc_html__( 'Question three goes here?', 'gwill-starter' ) . '</summary><!-- wp:paragraph -->
<p>' . esc_html__( 'Answer three goes here.', 'gwill-starter' ) . '</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
</div>
<!-- /wp:group -->',
		]
	);

	register_block_pattern_category( 'gwill-starter', [
		'label' => __( 'GWill Starter', 'gwill-starter' ),
	] );
}

/**
 * Scan rendered content for .gwill-faq sections and extract question/answer
 * pairs from each.
 *
 * Uses DOMDocument rather than regex — far more reliable for parsing real
 * HTML, which can vary in whitespace/attribute order in ways a regex would
 * need to fight to handle correctly.
 *
 * @param  string $html Rendered post content (post the_content filter).
 * @return array<int,array{question:string,answer:string}>
 * @since  1.0.50
 */
function gwill_extract_faq_items( string $html ): array {

	if ( ! str_contains( $html, 'gwill-faq' ) ) {
		return []; // Cheap bail-out before paying for a DOM parse on every post.
	}

	$items = [];

	$dom = new DOMDocument();
	// LIBXML_NOERROR | LIBXML_NOWARNING: post content is rendered HTML, not
	// a full document — loadHTML() would otherwise emit warnings for the
	// missing <html>/<body> wrapper that aren't actionable here.
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING );

	$xpath = new DOMXPath( $dom );
	$faqs  = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' gwill-faq ')]" );

	foreach ( $faqs as $faq_section ) {
		$details_nodes = $faq_section->getElementsByTagName( 'details' );

		foreach ( $details_nodes as $details ) {
			$summary_nodes = $details->getElementsByTagName( 'summary' );
			if ( 0 === $summary_nodes->length ) {
				continue;
			}

			$summary = $summary_nodes->item( 0 );
			$question = trim( $summary->textContent );

			// Answer = everything in <details> except the <summary> itself.
			$answer = '';
			foreach ( $details->childNodes as $child ) {
				if ( $child === $summary ) {
					continue;
				}
				$answer .= $dom->saveHTML( $child );
			}
			$answer = trim( wp_strip_all_tags( $answer ) );

			if ( $question && $answer ) {
				$items[] = [
					'question' => $question,
					'answer'   => $answer,
				];
			}
		}
	}

	return $items;
}

/**
 * Output FAQPage JSON-LD for any singular content containing FAQ sections.
 *
 * Deliberately not gated behind gwill_seo_plugin_active() — RankMath ships
 * its own FAQ block with its own schema output, but that's a different,
 * distinctly-classed block; this only ever fires for content actually
 * built from THIS theme's own block pattern (.gwill-faq), so there's no
 * collision risk regardless of which SEO plugin is or isn't active.
 *
 * @since 1.0.50
 */
function gwill_output_faq_schema(): void {

	if ( ! is_singular() ) {
		return;
	}

	// Raw post_content, not apply_filters('the_content', ...) — Gutenberg
	// stores static blocks' rendered HTML directly in post_content; the
	// <!-- wp:details --> wrapper comments are editor metadata DOMDocument
	// simply ignores (they're comment nodes, not elements). Using raw
	// content avoids running the entire the_content filter pipeline a
	// second time on every singular page load, for content that — most of
	// the time — has no FAQ section at all.
	$content = get_post_field( 'post_content', get_the_ID() );

	if ( ! str_contains( $content, 'gwill-faq' ) ) {
		return; // Cheap bail-out before paying for a DOM parse.
	}

	$items = gwill_extract_faq_items( $content );

	if ( ! $items ) {
		return;
	}

	$schema = [
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map(
			static function ( $item ) {
				return [
					'@type'          => 'Question',
					'name'           => $item['question'],
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $item['answer'],
					],
				];
			},
			$items
		),
	];

	echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() output, not raw user input
}
