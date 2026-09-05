<?php
/**
 * Click-to-play embed facades - GWill Starter (v1.3.0, ported from finance).
 *
 * YouTube, Vimeo and Spotify oEmbeds are swapped for a lightweight
 * play-button facade. The third-party player (1-2 MB of JS/CSS each)
 * is only fetched when the visitor actually clicks play - the pattern
 * Lighthouse recommends for embed-heavy pages, and the difference
 * between a 50 and a ~95 Performance score on embed-heavy pages.
 *
 * The facade lives INSIDE the block's own .wp-block-embed__wrapper,
 * so the existing aspect-ratio box (wp-has-aspect-ratio) and caption
 * are untouched - layout is identical before and after the click.
 * On activation, assets/js/embeds.js recreates the original iframe
 * (same attributes, autoplay added) and swaps it in.
 *
 * TWO render paths are covered:
 *   1. embed_oembed_html - classic [embed] shortcodes and any fresh
 *      oEmbed fetch (the iframe is produced at render time).
 *   2. render_block (core/embed) - Gutenberg blocks whose rendered
 *      HTML was baked into the post content at save time. Since
 *      core/embed has no server render callback in WP 7.x, that
 *      baked HTML is echoed verbatim and NO oEmbed filter ever
 *      runs - the baked iframe is swapped here instead.
 *
 * Only transforms when the facade assets are actually enqueued
 * (singulars containing a core/embed block) - external oEmbed
 * consumers, feeds and admin contexts always get the plain iframe.
 *
 * @package GWill_Starter
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'embed_oembed_html', 'gwill_embed_facade', 10, 4 );
add_filter( 'render_block', 'gwill_facade_render_block', 10, 2 );

/**
 * Path 1: classic oEmbed render - replace a provider iframe with a
 * click-to-play facade button.
 *
 * @param string|false $html    The cached oEmbed HTML.
 * @param string       $url     The attempted embed URL.
 * @param array        $attr    Shortcode attributes.
 * @param int          $post_id Post ID.
 * @return string
 */
function gwill_embed_facade( $html, $url, $attr, $post_id ) {
	if ( ! is_string( $html ) || false === strpos( $html, '<iframe' ) ) {
		return $html;
	}

	// Facade assets must be present on this page, or a facade would
	// render with no CSS/JS (broken for external oEmbed consumers).
	if ( ! gwill_facade_active() ) {
		return $html;
	}

	$provider = gwill_embed_provider( $url );
	if ( ! $provider ) {
		return $html;
	}

	$iframe = gwill_embed_first_iframe( $html );
	if ( ! $iframe ) {
		return $html;
	}

	$button = gwill_facade_button( $iframe, $provider );
	if ( '' === $button ) {
		return $html;
	}

	return str_replace( $iframe, $button, $html );
}

/**
 * Path 2: Gutenberg core/embed blocks with baked render output.
 *
 * WP 7.x registers core/embed with no server render callback, so the
 * iframe HTML saved inside the wp:embed block comments is echoed
 * verbatim. Swap that baked iframe for the facade.
 *
 * @param string $block_content The rendered block HTML.
 * @param array  $block         Block data (name, attrs, inner_blocks...).
 * @return string
 */
function gwill_facade_render_block( $block_content, $block ) {
	if ( empty( $block['blockName'] ) || 'core/embed' !== $block['blockName'] ) {
		return $block_content;
	}
	if ( ! is_string( $block_content ) || false === strpos( $block_content, '<iframe' ) ) {
		return $block_content;
	}

	// Already a facade (repeat renders, previews) - leave it alone.
	if ( false !== strpos( $block_content, 'gwill-embed--' ) ) {
		return $block_content;
	}

	if ( ! gwill_facade_active() ) {
		return $block_content;
	}

	// The block's own URL is authoritative for the provider check.
	$url = isset( $block['attrs']['url'] ) ? $block['attrs']['url'] : '';
	if ( ! is_string( $url ) || '' === $url ) {
		return $block_content;
	}
	$provider = gwill_embed_provider( $url );
	if ( ! $provider ) {
		return $block_content;
	}

	$iframe = gwill_embed_first_iframe( $block_content );
	if ( ! $iframe ) {
		return $block_content;
	}

	$button = gwill_facade_button( $iframe, $provider );
	if ( '' === $button ) {
		return $block_content;
	}

	return str_replace( $iframe, $button, $block_content );
}

/**
 * Are the facade CSS/JS enqueued for this page?
 *
 * @return bool
 */
function gwill_facade_active() {
	return wp_style_is( 'gwill-embeds', 'enqueued' );
}

/**
 * Grab the first complete <iframe> element (opening + closing tags).
 *
 * @param string $html Markup to scan.
 * @return string The full element, or '' if none found.
 */
function gwill_embed_first_iframe( $html ) {
	if ( preg_match( '#<iframe\b[^>]*>.*?</iframe>#is', $html, $m ) ) {
		return $m[0];
	}
	if ( preg_match( '/<iframe\b[^>]*>/i', $html, $m ) ) {
		return $m[0];
	}
	return '';
}

/**
 * Build the facade <button> for a provider iframe.
 *
 * @param string $iframe   The original <iframe ...>...</iframe> element.
 * @param string $provider Provider slug (youtube|vimeo|spotify).
 * @return string Button markup, or '' when unparseable.
 */
function gwill_facade_button( $iframe, $provider ) {
	$attrs = gwill_embed_attrs( $iframe );

	if ( empty( $attrs['src'] ) ) {
		return '';
	}

	$labels = array(
		'youtube' => 'YouTube',
		'vimeo'   => 'Vimeo',
		'spotify' => 'Spotify',
	);
	$label    = $labels[ $provider ];
	$title    = ! empty( $attrs['title'] ) ? $attrs['title'] : $label;
	$play_src = gwill_embed_play_src( $attrs['src'], $provider );
	$thumb    = gwill_embed_thumb( $attrs['src'], $provider );

	$button  = '<button type="button" class="gwill-embed gwill-embed--' . $provider . '"';
	if ( $thumb ) {
		// Painted as the button's OWN CSS background - no <img> element,
		// so lightbox.js has nothing to hook and the a11y tree stays
		// well-formed (tech-theme v1.16.79 pattern).
		$button .= ' style="background-image:url(\'' . esc_url( $thumb ) . '\')"';
	}
	$button .= ' data-gwill-src="' . esc_url( $play_src ) . '"';
	$button .= ' data-gwill-title="' . esc_attr( $title ) . '"';
	if ( ! empty( $attrs['allow'] ) ) {
		$button .= ' data-gwill-allow="' . esc_attr( $attrs['allow'] ) . '"';
	}
	if ( ! empty( $attrs['referrerpolicy'] ) ) {
		$button .= ' data-gwill-referrer="' . esc_attr( $attrs['referrerpolicy'] ) . '"';
	}
	$button .= ' aria-label="' . esc_attr( sprintf( __( 'Play %s: %s', 'gwill-starter' ), $label, $title ) ) . '">';

	$button .= '<span class="gwill-embed__icon" aria-hidden="true">' . gwill_embed_play_svg() . '</span>';
	$button .= '<span class="gwill-embed__label">' . esc_html( $label ) . '</span>';
	$button .= '</button>';

	return $button;
}

/**
 * Map an embed URL to a supported provider slug, or ''.
 *
 * @param string $url Embed URL.
 * @return string
 */
function gwill_embed_provider( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( false !== strpos( $host, 'youtube.com' ) || false !== strpos( $host, 'youtu.be' ) ) {
		return 'youtube';
	}
	if ( false !== strpos( $host, 'vimeo.com' ) ) {
		return 'vimeo';
	}
	if ( false !== strpos( $host, 'spotify.com' ) ) {
		return 'spotify';
	}
	return '';
}

/**
 * Extract the iframe attributes we replay on click.
 *
 * src/title/allow/referrerpolicy are enough - sizing comes from the
 * block's aspect-ratio CSS. Values are entity-decoded (Vimeo srcs
 * ship &amp;) and re-escaped on output.
 *
 * @param string $iframe The original <iframe ...> tag.
 * @return array
 */
function gwill_embed_attrs( $iframe ) {
	$attrs = array();
	foreach ( array( 'src', 'title', 'allow', 'referrerpolicy' ) as $key ) {
		if ( preg_match( '/\b' . $key . '="([^"]*)"/i', $iframe, $m ) ) {
			$attrs[ $key ] = html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5 );
		}
	}
	return $attrs;
}

/**
 * The src the real iframe gets on click (autoplay where supported).
 *
 * @param string $src      Original iframe src.
 * @param string $provider Provider slug.
 * @return string
 */
function gwill_embed_play_src( $src, $provider ) {
	if ( 'spotify' === $provider ) {
		return $src; // Spotify plays on load; no query param needed.
	}
	return add_query_arg( 'autoplay', '1', $src );
}

/**
 * Cookie-free poster URL for the facade backdrop (tech-theme v1.16.79
 * pattern - restores the preview, done right).
 *
 * YouTube: i.ytimg.com <id>/hqdefault.jpg - keyless, zero Set-Cookie
 *          (tech-theme live-verified).
 * Vimeo:   i.vimeocdn.com <id>_640x360.jpg - the keyless Vimeo poster
 *          (no API key was ever needed; tech-theme live-verified).
 * Spotify: no keyless poster API - keeps the branded #191414 surface.
 *
 * Painted as the button's own inline CSS background-image - NOT an <img>
 * element - so the lightbox hook (querySelectorAll('img')) can't capture
 * it and the presentational-element audit violation is impossible.
 *
 * NOTE: i.ytimg.com can 403 from Google's PSI/Lighthouse browser infra
 * specifically - real visitors get 200. A failed poster just leaves the
 * muted surface color visible, so the facade never breaks.
 *
 * @param string $src      Original iframe src.
 * @param string $provider Provider slug.
 * @return string Poster URL, or '' when none.
 */
function gwill_embed_thumb( $src, $provider ) {
	if ( 'youtube' === $provider && preg_match( '#(?:v=|embed/|youtu\.be/)([A-Za-z0-9_-]{6,15})#', $src, $m ) ) {
		return 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
	}
	if ( 'vimeo' === $provider && preg_match( '#video/(\d+)#', $src, $m ) ) {
		return 'https://i.vimeocdn.com/video/' . $m[1] . '_640x360.jpg';
	}
	return '';
}

/**
 * Play glyph - inline SVG, no icon-font dependency.
 *
 * @return string
 */
function gwill_embed_play_svg() {
	return '<svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
}