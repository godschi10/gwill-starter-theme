<?php

/*
Table of Contents
1. gwill_minify_html
2. gwill_perf_start_buffer
*/

/**
 * TOC — inc/minify.php
 *
 * HTML whitespace minification (output buffering) — GWill Starter.
 *
 * Ported from gwill-tech-theme inc/performance.php (live-proven on the
 * tech site), adapted: the binary-endpoint guard covers the starter's
 * OWN query-var routes (?gwill_manifest= from inc/pwa.php — the tech
 * version guarded ?gwill_icon= / ?gwill_og= which the starter does not
 * have). REST/AJAX/cron/CLI/admin guards unchanged.
 *
 * Collapses runs of whitespace BETWEEN tags in the final HTML output
 * via output buffering, cutting page weight without touching rendered
 * content.
 *
 * SAFETY (never strips content):
 * - <pre> / <code> / <textarea> / <script> / <style> bodies are preserved
 *   verbatim — code blocks and inline JS/CSS keep every byte.
 * - Only whitespace runs of 2+ newlines/blank lines BETWEEN tags are
 *   collapsed to a single newline.
 *
 * The regex runs only on the final page buffer (front-end only), never
 * in admin, AJAX, REST, cron, or CLI contexts.
 *
 * @package GWill_Starter
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;

// ── 1. gwill_minify_html ──────────────────────────────────
/**
 * Collapse inter-tag whitespace runs in final HTML.
 *
 * Sensitive regions (<pre>/<code>/<textarea>/<script>/<style>) are first
 * replaced with placeholder tokens, the whitespace squeeze runs over the
 * rest, then the originals are restored byte-for-byte.
 *
 * @param string $buffer Final HTML output.
 * @return string Minified HTML.
 */
function gwill_minify_html( $buffer ) {
	if ( ! $buffer || strlen( $buffer ) < 200 ) {
		return $buffer;
	}

	// Protect sensitive regions: pre, code, textarea, script, style.
	$protected = array();
	$buffer    = preg_replace_callback(
		'#<(pre|code|textarea|script|style)\b[^>]*>.*?</\1>#is',
		function ( $m ) use ( &$protected ) {
			$token               = "\x1A" . 'GWP' . count( $protected ) . "\x1A";
			$protected[ $token ] = $m[0];
			return $token;
		},
		$buffer
	);

	// Collapse inter-tag whitespace: 2+ newlines (with any spaces/tabs)
	// between a '>' and '<' become a single newline.
	$buffer = preg_replace( '#>\s{2,}<#', ">\n<", $buffer );

	// Also collapse whitespace runs ADJACENT to protected-block tokens
	// (e.g. </p>\n\n\n\n[token]\n\n\n\n<p>) — the placeholder is not '<',
	// so the pattern above skips it. Squeeze both sides of each token.
	$buffer = preg_replace( '#>\s{2,}(\x1AGWP\d+\x1A)\s{2,}<#', ">\n$1\n<", $buffer );

	// Restore protected blocks.
	foreach ( $protected as $token => $html ) {
		$buffer = str_replace( $token, $html, $buffer );
	}

	return $buffer;
}

// ── 2. gwill_perf_start_buffer ────────────────────────────
/**
 * Start output buffering for front-end HTML minification.
 * Runs at template_redirect (after headers are decided), front-end only.
 */
function gwill_perf_start_buffer() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	// Binary/JSON endpoint guard: the PWA manifest route (?gwill_manifest=
	// from inc/pwa.php) serves application/manifest+json via echo+exit at
	// template_include (default priority 99). The minifier's text regexes
	// must never run on it — and an ob_start wrapping an exit would emit
	// the buffer through the minify callback, so skip the buffer for these
	// requests entirely.
	if ( isset( $_GET['gwill_manifest'] ) ) {
		return;
	}

	ob_start( 'gwill_minify_html' );
}
add_action( 'template_redirect', 'gwill_perf_start_buffer', 0 );
