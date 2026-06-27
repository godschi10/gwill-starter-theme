<?php
/**
 * Table of contents — auto-generated from actual <h2>/<h3> structure.
 *
 * Built entirely inside one the_content filter pass: parse the rendered
 * content once via DOMDocument (same approach as inc/faq.php — far more
 * reliable than regex for real HTML), add an id to any heading that
 * doesn't already have one, build the nav from the same headings, and
 * prepend it to the same content string the modified-with-ids version is
 * returned as. One pass does both jobs; there's no second filter run and
 * no risk of the nav's ids and the headings' actual ids ever drifting
 * apart, because they're generated from the exact same loop.
 *
 * <details>/<summary> for the wrapper — same reasoning as the FAQ
 * accordion: it's a fully accessible, keyboard-operable, zero-JS
 * collapse/expand primitive already built into the browser. Collapsed by
 * default everywhere; CSS forces it open and sticky past a wide-viewport
 * breakpoint only (see the "Table of Contents" section in style.css) —
 * this theme's single.php has no separate sidebar column, but
 * position: sticky on an element inside .entry-content still works
 * exactly as "sticky sidebar" implementations do, because it sticks
 * relative to .entry-content's own height, which spans the whole article.
 *
 * @package GWill_Starter
 * @since   1.0.62
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'the_content', 'gwill_inject_table_of_contents', 20 );

/**
 * Add a table of contents to the start of singular content with enough
 * headings to be worth one.
 *
 * Priority 20 — deliberately after the_content's default-priority work
 * (wpautop, wptexturize at 10; do_shortcode at 11), so a heading produced
 * by a shortcode is just as visible to this scan as one saved directly
 * by the block editor. Real heading blocks render as complete <h2>/<h3>
 * tags regardless of this timing — Gutenberg never relies on wpautop to
 * form them — so this only matters for the shortcode-generated-heading
 * edge case, but there's no actual cost to running later, so there's no
 * reason not to cover that case too.
 *
 * @param  string $content
 * @return string
 * @since  1.0.62
 */
function gwill_inject_table_of_contents( string $content ): string {

	/**
	 * Which post types get an auto-generated table of contents.
	 *
	 * @param string[] $post_types
	 * @since 1.0.62
	 */
	$post_types = apply_filters( 'gwill_toc_post_types', [ 'post' ] );

	if ( ! is_singular( $post_types ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	// Cheap bail-out before paying for a DOM parse on content that almost
	// certainly has no headings at all — same pattern as gwill_extract_faq_items().
	if ( ! str_contains( $content, '<h2' ) && ! str_contains( $content, '<h3' ) ) {
		return $content;
	}

	$dom = new DOMDocument();
	// LIBXML_NOERROR | LIBXML_NOWARNING: post content is rendered HTML, not
	// a full document — loadHTML() would otherwise emit warnings for the
	// missing <html>/<body> wrapper that aren't actionable here.
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $content, LIBXML_NOERROR | LIBXML_NOWARNING );

	$xpath    = new DOMXPath( $dom );
	$headings = $xpath->query( '//h2 | //h3' );

	/**
	 * Minimum number of headings before a table of contents is worth
	 * showing at all — a two-heading post doesn't need a navigation aid
	 * for itself.
	 *
	 * @param int $minimum
	 * @since 1.0.62
	 */
	$minimum = (int) apply_filters( 'gwill_toc_min_headings', 3 );

	if ( $headings->length < $minimum ) {
		return $content;
	}

	$used_ids = [];
	$items    = []; // [ 'level' => 2|3, 'text' => string, 'id' => string ]

	foreach ( $headings as $heading ) {

		$id = $heading->getAttribute( 'id' );

		if ( '' === $id ) {
			// Respect a manually-set anchor (Gutenberg's own "HTML anchor"
			// field, or hand-written markup) — only generate one when
			// genuinely missing, never overwrite an existing id.
			$id = sanitize_title( $heading->textContent );
			if ( '' === $id ) {
				$id = 'toc'; // Genuinely empty heading text — rare, but sanitize_title('') is also ''.
			}

			// Dedupe: two headings with the same text would otherwise
			// generate the same id, and two elements sharing one id is
			// invalid HTML that breaks #fragment links to either of them.
			$unique_id = $id;
			$i         = 2;
			while ( in_array( $unique_id, $used_ids, true ) ) {
				$unique_id = $id . '-' . $i;
				++$i;
			}
			$id = $unique_id;

			$heading->setAttribute( 'id', $id );
		}

		$used_ids[] = $id;
		$items[]    = [
			'level' => 'h3' === $heading->nodeName ? 3 : 2,
			'text'  => trim( $heading->textContent ),
			'id'    => $id,
		];
	}

	// Re-serialize the modified DOM back to an HTML fragment string — not
	// $dom->saveHTML() on the whole document, which would include the
	// <html><body> wrapper loadHTML() added for us. Walking the actual
	// <body>'s child nodes and concatenating each one's HTML is the
	// standard technique for getting a clean fragment back out.
	$body        = $dom->getElementsByTagName( 'body' )->item( 0 );
	$new_content = '';
	foreach ( $body->childNodes as $child ) {
		$new_content .= $dom->saveHTML( $child );
	}

	return gwill_render_toc_nav( $items ) . $new_content;
}

/**
 * Render the actual <details> table-of-contents markup from a flat list
 * of headings.
 *
 * H3s nest under the nearest preceding H2's <li> — a nested <ol> has to
 * live *inside* its parent <li> to be valid HTML, so the H2's <li> stays
 * open (no closing tag yet) until either the next H2 arrives or the loop
 * ends, at which point it's closed along with any sublist inside it.
 * An H3 appearing before any H2 at all (unusual, but not invalid HTML)
 * gets its own flat top-level <li> instead of inventing a parent for it.
 *
 * @param  array<int,array{level:int,text:string,id:string}> $items
 * @return string
 * @since  1.0.62
 */
function gwill_render_toc_nav( array $items ): string {

	$html = '<details class="gwill-toc">';
	$html .= '<summary>' . esc_html__( 'Table of Contents', 'gwill-starter' ) . '</summary>';
	$html .= '<ol class="gwill-toc__list">';

	$top_li_open  = false;
	$sublist_open = false;

	foreach ( $items as $item ) {

		if ( 3 === $item['level'] && $top_li_open ) {
			// Nest under the currently-open H2's <li>.
			if ( ! $sublist_open ) {
				$html        .= '<ol class="gwill-toc__sublist">';
				$sublist_open = true;
			}
			$html .= sprintf(
				'<li><a href="#%s">%s</a></li>',
				esc_attr( $item['id'] ),
				esc_html( $item['text'] )
			);
			continue;
		}

		// Either an H2, or an H3 with no preceding H2 to nest under —
		// both need whatever top-level <li> was previously open closed
		// first (sublist closes before the <li> containing it does).
		if ( $sublist_open ) {
			$html        .= '</ol>';
			$sublist_open = false;
		}
		if ( $top_li_open ) {
			$html .= '</li>';
		}

		$html .= sprintf(
			'<li><a href="#%s">%s</a>',
			esc_attr( $item['id'] ),
			esc_html( $item['text'] )
		);
		$top_li_open = true;

		// An H3 with nothing to nest under is a flat single item, not a
		// parent waiting for sublist content — close its <li> immediately.
		if ( 3 === $item['level'] ) {
			$html       .= '</li>';
			$top_li_open = false;
		}
	}

	if ( $sublist_open ) {
		$html .= '</ol>';
	}
	if ( $top_li_open ) {
		$html .= '</li>';
	}

	$html .= '</ol></details>';

	return $html;
}
