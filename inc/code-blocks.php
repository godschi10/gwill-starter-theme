<?php
defined( 'ABSPATH' ) || exit;

/**
 * Code Blocks  -  copy buttons + syntax highlighting  -  GWill Starter.
 *
 * Ported from gwill-tech-theme inc/code-blocks.php (187 lines,
 * live-proven on the tech site), adapted to the starter dialect:
 *   - content scope .entry-content (tech used .prose)  -  used ONLY by
 *     code-copy.js's sniffer query, never by CSS;
 *   - vendor assets at assets/vendor/prism (self-hosted, zero CDN);
 *   - @since/@package headers for the starter.
 *
 * 1. the_content filter (priority 15): injects a "Copy" button +
 *    language label into every <pre><code> block (Gutenberg
 *    wp-block-code or raw HTML). tabindex="0" on the <pre> keeps
 *    keyboard users able to scroll wide code (WCAG 2.1.1).
 * 2. wp_enqueue_scripts (priority 5): Prism loads ONLY on singulars
 *    whose content actually contains a code block  -  zero cost on
 *    pages without code. The sniffer grammars ship in ONE deferred
 *    bundle; explicit language-* classes beyond the bundle load
 *    their single grammar file on demand.
 *
 * INTERPLAY  -  minify.php (v1.6.0): the copy button and label are
 * injected BEFORE the minifier's buffer runs (the_content renders
 * inside the template, the buffer closes at shutdown), so the
 * injected markup is minified like everything else  -  protected
 * regions keep the code body byte-exact.
 *
 * @package GWill_Starter
 * @since   1.7.0
 */

/*
* TABLE OF CONTENTS
* ─────────────────────────────────────────────────────────────────────────────
*   1. gwill_code_block_markup  the_content: copy button + language label
*   2. gwill_code_assets        Conditional Prism enqueue
* ─────────────────────────────────────────────────────────────────────────────
*/

// ── 1. gwill_code_block_markup ────────────────────────────
/**
 * Wrap every code block with copy button + language label.
 *
 * @param string $content Post content.
 * @return string
 */
function gwill_code_block_markup( $content ) {
	if ( ! is_string( $content ) || false === strpos( $content, '<pre' ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/<pre([^>]*)>(.*?)<\/pre>/is',
		function ( $m ) {
			$pre_attrs = $m[1];
			$inner     = $m[2];

			// Find the <code> element inside (may carry a language class).
			if ( ! preg_match( '/<code([^>]*)>(.*?)<\/code>/is', $inner, $cm ) ) {
				return $m[0]; // Not a code block  -  leave untouched.
			}

			$code_attrs = $cm[1];
			$code_body  = $cm[2];

			// Language detection from class: language-php / lang-js / php.
			$lang = '';
			if ( preg_match( '/(?:language|lang)-([a-z0-9+#.]+)/i', $code_attrs, $lm ) ) {
				$lang = strtolower( $lm[1] );
			} elseif ( preg_match( '/class="([^"]*)"/', $code_attrs, $cm2 ) ) {
				foreach ( preg_split( '/\s+/', $cm2[1] ) as $cls ) {
					$cls = strtolower( trim( $cls ) );
					if ( $cls && ! in_array( $cls, array( 'code', 'wp-block-code' ), true ) ) {
						$lang = $cls;
						break;
					}
				}
			}

			// Normalise a few aliases Prism understands.
			$lang_map = array(
				'js'        => 'javascript',
				'ts'        => 'typescript',
				'py'        => 'python',
				'sh'        => 'bash',
				'shell'     => 'bash',
				'yml'       => 'yaml',
				'html'      => 'markup',
				'xml'       => 'markup',
				'svg'       => 'markup',
				'md'        => 'markdown',
				'rb'        => 'ruby',
				'rs'        => 'rust',
				'cs'        => 'csharp',
				'c++'       => 'cpp',
				'c#'        => 'csharp',
				'golang'    => 'go',
				'text'      => '',
				'plaintext' => '',
				'plain'     => '',
			);
			if ( isset( $lang_map[ $lang ] ) ) {
				$lang = $lang_map[ $lang ];
			}

			// Ensure the code element carries the language class for Prism.
			$lang_class = $lang ? 'language-' . $lang : '';
			if ( $lang_class && false === strpos( $code_attrs, 'language-' ) ) {
				$code_attrs = trim( $code_attrs . ' class="' . $lang_class . '"' );
			}

			// Drop invalid lang/xml:lang attributes (e.g. lang="php") from the
			// code element  -  they are not valid BCP-47 tags and trip axe's
			// valid-lang (WCAG 3.1.1). The language is already conveyed via
			// class="language-*" Prism uses; a bogus lang adds nothing.
			$code_attrs = trim( (string) preg_replace( '/\s(?:xml:)?lang=(["\'])[^"\']*\1/i', '', $code_attrs ) );
			if ( $code_attrs ) {
				$code_attrs = ' ' . $code_attrs;
			}

			// Language label + copy button (absolute-positioned by CSS inside
			// the relative <pre>). Escaped lang label  -  safe as text.
			$label  = $lang ? '<span class="code-lang">' . esc_html( $lang ) . '</span>' : '';
			$button = '<button type="button" class="copy-btn" aria-label="' . esc_attr__( 'Copy code', 'gwill-starter' ) . '">' . esc_html__( 'Copy', 'gwill-starter' ) . '</button>';

			return '<pre' . $pre_attrs . ' tabindex="0">' . $label . $button . '<code' . $code_attrs . '>' . $code_body . '</code></pre>';
		},
		$content
	);
}
add_filter( 'the_content', 'gwill_code_block_markup', 15 );

// ── 2. gwill_code_assets ──────────────────────────────────
/**
 * Enqueue Prism (self-hosted) only when the current page actually
 * contains a code block  -  zero cost on pages without code.
 */
function gwill_code_assets() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( ! $post || false === strpos( (string) $post->post_content, '<pre' ) ) {
		return;
	}

	// Cache-buster  -  same version key as the theme's own stylesheet.
	$ver = wp_get_theme( get_template() )->get( 'Version' );

	// Prism theme  -  loaded BEFORE the theme stylesheet (priority 5) so
	// the theme's .entry-content pre rules win on backgrounds/borders.
	wp_enqueue_style(
		'gwill-prism',
		get_template_directory_uri() . '/assets/vendor/prism/prism-tomorrow.css',
		array(),
		$ver
	);

	// Core + sniffer grammars bundled into ONE deferred file: core,
	// markup-templating (php grammar dependency), then php/python/
	// bash/sql/json/yaml/rust/java. All local  -  zero CDN at runtime.
	wp_enqueue_script(
		'gwill-prism',
		get_template_directory_uri() . '/assets/vendor/prism/prism-bundle.min.js',
		array(),
		$ver,
		array( 'in_footer' => true, 'strategy' => 'defer' )
	);

	// Grammars already inside the bundle (sniffer set)  -  extras below.
	$bundled = array(
		'php', 'python', 'bash', 'sql', 'json', 'yaml', 'rust', 'java',
		'markup-templating', // PHP grammar dependency  -  keep with php.
	);

	// On-demand extras: any explicit language-* class in the post
	// content NOT covered by the bundle loads that single grammar file.
	$content = (string) $post->post_content;
	if ( preg_match_all( '/(?:language|lang)-([a-z0-9+#.-]+)/i', $content, $cm ) ) {
		foreach ( array_unique( $cm[1] ) as $extra ) {
			$extra = strtolower( $extra );
			$extra = preg_replace( '/[^a-z0-9-]/', '', $extra );
			if ( $extra && ! in_array( $extra, $bundled, true ) && file_exists( get_template_directory() . '/assets/vendor/prism/prism-' . $extra . '.min.js' ) ) {
				wp_enqueue_script(
					'gwill-prism-' . $extra,
					get_template_directory_uri() . '/assets/vendor/prism/prism-' . $extra . '.min.js',
					array( 'gwill-prism' ),
					$ver,
					array( 'in_footer' => true, 'strategy' => 'defer' )
				);
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'gwill_code_assets', 5 );
