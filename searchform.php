<?php
/**
 * Generic search form (get_search_form output).
 *
 * ONE family object with the header dropdown: pill field, inset
 * magnifier glyph (left), inset submit button (right). No JS required -
 * this form works everywhere the dropdown does not (no-JS fallback,
 * 404 page, widgets).
 *
 * Structure notes:
 * - <label> wraps ONLY the input (label-as-flex-item collapse lesson,
 *   v1.11.x) and stays inside the pill so clicks anywhere on the pill
 *   focus the field.
 * - The submit is an icon button INSIDE the pill: 44px hit target (law),
 *   aria-labelled, text-free so it needs no width on narrow phones.
 * - type="search" kept for semantics; UA decorations are stripped in CSS
 *   (the results-page field already proved the pattern).
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;
?>

<form
	role="search"
	aria-label="<?php esc_attr_e( 'Site search', 'gwill-starter' ); ?>"
	method="get"
	class="search-form"
	action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<span class="search-form__pill">
		<svg
			class="search-form__glyph"
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			stroke-width="2"
			stroke-linecap="round"
			stroke-linejoin="round"
			aria-hidden="true"
			focusable="false">
			<circle cx="11" cy="11" r="8"></circle>
			<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
		</svg>
		<label class="search-form__field">
			<span class="screen-reader-text"><?php esc_html_e( 'Search for:', 'gwill-starter' ); ?></span>
			<input
				type="search"
				class="search-field"
				value="<?php echo esc_attr( get_search_query() ); ?>"
				name="s"
				placeholder="<?php esc_attr_e( 'Search…', 'gwill-starter' ); ?>"
			>
		</label>
		<button type="submit" class="search-submit" aria-label="<?php esc_attr_e( 'Search', 'gwill-starter' ); ?>">
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
				aria-hidden="true"
				focusable="false">
				<line x1="5" y1="12" x2="19" y2="12"></line>
				<polyline points="12 5 19 12 12 19"></polyline>
			</svg>
		</button>
	</span>
</form>
