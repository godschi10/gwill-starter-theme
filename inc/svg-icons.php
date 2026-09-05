<?php

/*
Table of Contents
1. gwill_icon
2. gwill_icons
*/

/**
 * Unified inline-SVG icon helper  -  GWill Starter (v1.8.0).
 *
 * Ported from finance inc/svg-icons.php (live-proven), unified and made
 * brand-agnostic:
 *   - ONE registry + ONE renderer replaces finance's two functions
 *     (social + toggle); partials never hand-roll <svg> again;
 *   - every icon string is lifted VERBATIM from a proven live file
 *     (finance svg-icons, starter share-button, embed-facades,
 *     back-to-top, nav-walker)  -  never hand-typed;
 *   - the registry is filterable (gwill_icons) so builds append brand
 *     icons without touching partials; single icons filter via gwill_icon;
 *   - icons inherit font-size (width/height="1em") and paint via
 *     currentColor, so dark mode + brand skins recolour them free;
 *   - aria-hidden + focusable="false" everywhere  -  icons are decorative,
 *     adjacent text carries the meaning (WCAG 1.1.1).
 *
 * Usage: gwill_icon( 'x' );  gwill_icon( 'instagram', [ 'size' => 20 ] );
 *
 * @package GWill_Starter
 * @since   1.8.0
 */

defined( 'ABSPATH' ) || exit;

// ── 1. gwill_icon ─────────────────────────────────────────
/**
 * Render (or return) an inline SVG icon by name.
 *
 * @param string $name Icon key (see gwill_icons()).
 * @param array  $args {
 *     @type int  $size Pixel size. 0 = inherit font-size (1em default).
 *     @type bool $echo True echoes; false returns. Default true.
 * }
 * @return string|void SVG markup when $echo is false.
 */
function gwill_icon( string $name, array $args = array() ) {
	$size = isset( $args['size'] ) ? (int) $args['size'] : 0;
	$echo = ! isset( $args['echo'] ) || $args['echo'];

	$icons = gwill_icons();

	if ( ! isset( $icons[ $name ] ) ) {
		if ( $echo ) {
			return;
		}
		return '';
	}

	$svg = $icons[ $name ];

	// Fixed pixel size when asked; otherwise keep 1em (inherit font-size).
	if ( $size > 0 ) {
		$svg = (string) preg_replace(
			'/(width|height)="[^"]*"/',
			'$1="' . esc_attr( (string) $size ) . '"',
			$svg,
			2
		);
	}

	/**
	 * Filter the final SVG markup for an icon.
	 *
	 * @param string $svg  Final markup (after size substitution).
	 * @param string $name Requested icon key.
	 */
	$svg = apply_filters( 'gwill_icon', $svg, $name );

	if ( ! $echo ) {
		return $svg;
	}
	echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput -- static, filtered SVG markup.
}

// ── 2. gwill_icons ────────────────────────────────────────
/**
 * The icon registry  -  inline SVG only, no icon fonts, no external
 * requests (the finance law). Stroke icons share one 2px family so
 * they read as a set.
 *
 * Build-time note: every value below was extracted verbatim from a
 * proven live file by build-svg-icons.py  -  do not hand-edit paths.
 *
 * @return array<string, string> name => SVG markup.
 */
function gwill_icons(): array {
	static $icons = null;
	if ( null !== $icons ) {
		return $icons;
	}

	$icons = array(
		'x' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
		'instagram' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
		'linkedin' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.555V9h3.564v11.452z"/></svg>',
		'youtube' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
		'facebook' => '<svg width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
		'whatsapp' => '<svg width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>',
		'sun' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4.5"/><path d="M12 1.5v2.5M12 20v2.5M4.2 4.2l1.8 1.8M18 18l1.8 1.8M1.5 12H4M20 12h2.5M4.2 19.8L6 18M18 6l1.8-1.8"/></svg>',
		'moon' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
		'play' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z"/></svg>',
		'arrow-up' => '<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"> <path d="M12 19V5M5 12l7-7 7 7"></path> </svg>',
		'chevron' => '<svg class="nav-caret" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><polyline points="6 9 12 15 18 9"/></svg>',
	);

	/**
	 * Filter the whole registry  -  append brand icons without touching
	 * partials: add_filter( 'gwill_icons', fn( $i ) => $i + [ ... ] );
	 *
	 * @param array<string, string> $icons
	 */
	$icons = apply_filters( 'gwill_icons', $icons );

	return $icons;
}
