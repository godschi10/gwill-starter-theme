<?php
defined( 'ABSPATH' ) || exit;

/**
 * External-link hardening - GWill Starter.
 *
 * FRESH MODULE (v1.7.0 - no elder theme owns this; recon verified Aug 30
 * 2026: tech/finance only hand-write rel on their own markup, nothing
 * filters user content). Every external <a> in post content gets
 * target="_blank" + rel="noopener noreferrer":
 *
 *   - target="_blank"      - the visitor keeps their place on the site
 *                            (mobile especially: an external link would
 *                            otherwise replace the page in the same tab).
 *   - rel="noopener"       - REQUIRED with _blank: without it the opened
 *                            page can control `window.opener` and
 *                            redirect the referring tab (tabnabbing).
 *   - rel="noreferrer"     - hides the Referer, so the destination never
 *                            learns the visitor's exact on-site URL.
 *
 * Scope: the_content only (post bodies). Feeds and admin are skipped  - 
 * feed readers must not receive _blank (it is meaningless in a reader
 * and some validators flag it), and the admin editor preview stays a
 * faithful rendering of the stored markup.
 *
 * Interplay: never touches mailto:/tel:/# anchors, internal links
 * (host compared www-stripped, both directions), or links that already
 * carry a target attribute (author intent wins - if the author set
 * target="_self" deliberately, hardening must not override it). Existing
 * rel tokens are MERGED, never replaced (rel="nofollow" stays
 * rel="nofollow noopener noreferrer").
 *
 * @package GWill_Starter
 * @since   1.7.0
 */

/*
* TABLE OF CONTENTS
* ─────────────────────────────────────────────────────────────────────────────
*   1. gwill_is_external_link_host  Host comparison (www-stripped)
*   2. gwill_harden_external_links  the_content filter
* ─────────────────────────────────────────────────────────────────────────────
*/

// ── 1. gwill_is_external_link_host ──────────────────────────
/**
 * Is the link host a different site than this one? Both sides are
 * compared with their "www." prefix stripped, so www.example.com links
 * from example.com (and the reverse) count as internal - that is the
 * behaviour site owners expect.
 *
 * @param string $link_host Host from the href (already lowercased).
 * @param string $site_host This site's host (already lowercased).
 * @return bool True when the link leaves this site.
 */
function gwill_is_external_link_host( $link_host, $site_host ) {
	$strip = static function ( $h ) {
		$h = rtrim( (string) $h, '.' );
		return ( 0 === strpos( $h, 'www.' ) ) ? substr( $h, 4 ) : $h;
	};

	$link_host = $strip( $link_host );
	$site_host = $strip( (string) $site_host );

	return '' !== $link_host && '' !== $site_host && $link_host !== $site_host;
}

// ── 2. gwill_harden_external_links ─────────────────────────
/**
 * Add target="_blank" + rel="noopener noreferrer" to external links
 * in post content.
 *
 * @param string $content Post content.
 * @return string
 */
function gwill_harden_external_links( $content ) {
	if ( ! is_string( $content ) || '' === $content || is_feed() || is_admin() ) {
		return $content;
	}

	static $site_host = null;
	if ( null === $site_host ) {
		$site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	return preg_replace_callback(
		'#<a\b([^>]*)>#i',
		function ( $m ) use ( $site_host ) {
			$attrs = $m[1];

			// Only http(s) links are candidates.
			if ( ! preg_match( '#\bhref\s*=\s*(["\'])https?://([^/"\']+)#i', $attrs, $h ) ) {
				return $m[0];
			}

			if ( ! gwill_is_external_link_host( strtolower( $h[2] ), $site_host ) ) {
				return $m[0]; // internal - untouched.
			}

			// Author intent wins: an explicit target is never overridden.
			$attrs_out = $attrs;
			if ( ! preg_match( '#\btarget\s*=\s*#i', $attrs ) ) {
				$attrs_out .= ' target="_blank"';
			}

			// Merge rel tokens (existing nofollow/ugc/sponsored survive).
			if ( preg_match( '#\brel\s*=\s*(["\'])(.*?)\1#i', $attrs_out, $r ) ) {
				$tokens = preg_split( '/\s+/', strtolower( trim( $r[2] ) ) );
				$tokens = array_unique( array_filter( array_merge( $tokens, [ 'noopener', 'noreferrer' ] ) ) );
				sort( $tokens );
				$new_rel = ' rel="' . esc_attr( implode( ' ', $tokens ) ) . '"';
				$attrs_out = preg_replace( '#\brel\s*=\s*(["\']).*?\1#i', '', $attrs_out );
				$attrs_out = rtrim( $attrs_out ) . $new_rel;
			} else {
				$attrs_out .= ' rel="noopener noreferrer"';
			}

			return '<a' . $attrs_out . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'gwill_harden_external_links', 20 );
