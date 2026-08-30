<?php
defined( 'ABSPATH' ) || exit;

/**
 * GWill Nav Walker — GWill Starter (v1.7.0).
 *
 * Ported from gwill-tech-theme inc/nav-walker.php (198 lines,
 * live-proven on the tech site), adapted to the starter dialect:
 *   - class names carry over 1:1 from the starter's existing CSS:
 *     #primary-menu, .sub-menu, .nav-link (desktop links), so the
 *     walker plugs into the header wp_nav_menu() call as a drop-in;
 *   - the mobile split-button (.mno-parent > .mno-caret) accordion
 *     pattern is preserved EXACTLY (aria-expanded + aria-controls,
 *     WCAG 2.1.1 operable) — main.js binds the accordion toggle;
 *   - the fallback is BRAND-AGNOSTIC: tech's fallback hardcoded its
 *     own category links (Home/Android/Web-Dev/…); the starter's
 *     fallback lists published PAGES + Home, so every build gets a
 *     sane header without a menu assigned and nothing tech-specific
 *     leaks into client sites.
 *
 * Desktop: .site-nav list, sub-menus as hover dropdowns (CSS).
 * Mobile overlay: flat links, sub-menus as collapsible accordions
 * (real <button> toggles + .open class — main.js drives it).
 *
 * Used for the header wp_nav_menu() so a single Appearance → Menus
 * assignment drives all devices.
 *
 * INTERPLAY — header.php renders wp_nav_menu() with depth 2; the
 * walker adds 'walker' => new GWill_Nav_Walker() via the filter below
 * so the header template itself needs no edit.
 *
 * @package GWill_Starter
 * @since   1.7.0
 */

/*
* TABLE OF CONTENTS
* ─────────────────────────────────────────────────────────────────────────────
*   1. GWill_Nav_Walker            Accessible walker (split-button mobile)
*   2. gwill_nav_fallback          Pages-based fallback (brand-agnostic)
*   3. gwill_nav_walker_args       Inject the walker into wp_nav_menu()
* ─────────────────────────────────────────────────────────────────────────────
*/

if ( ! class_exists( 'GWill_Nav_Walker' ) ) :

class GWill_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Pending sub-menu IDs for the mobile split-button (links aria-controls
	 * to the <ul id>). Keyed by depth: set in start_el(), consumed in
	 * start_lvl().
	 *
	 * @var array
	 */
	public $submenu_ids = array();

	/**
	 * Open the <ul> level.
	 *
	 * @param string   $output Passed by reference.
	 * @param int      $depth  Depth of menu item.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent  = str_repeat( "\t", $depth );
		$classes = 'sub-menu';
		if ( $depth > 0 ) {
			$classes .= ' sub-menu--nested';
		}
		$id = isset( $this->submenu_ids[ $depth ] ) ? ' id="' . esc_attr( $this->submenu_ids[ $depth ] ) . '"' : '';
		$output .= "\n$indent<ul class=\"" . esc_attr( $classes ) . "\"" . $id . ">\n";
	}

	/**
	 * Open a single menu item.
	 *
	 * @param string   $output Passed by reference.
	 * @param WP_Post  $item   Menu item data object.
	 * @param int      $depth  Depth of menu item.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 * @param int      $id      Current item ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		if ( $depth > 0 ) {
			$classes[] = 'menu-item--sub';
		}
		// Add a marker class so CSS can style parents with children.
		if ( in_array( 'menu-item-has-children', $classes, true ) ) {
			$classes[] = 'has-submenu';
		}

		$class_names = implode( ' ', array_filter( $classes ) );

		$output .= $indent . '<li class="' . esc_attr( $class_names ) . '">';

		$atts           = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target ) ? $item->target : '';
		$atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';
		$atts['href']   = ! empty( $item->url ) ? $item->url : '';
		$atts['class']  = ( $depth > 0 ) ? 'sub-link' : 'nav-link';

		if ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) ) {
			$atts['class'] .= ' active';
		}

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = esc_html( $title );

		$has_children = in_array( 'menu-item-has-children', $classes, true );

		$item_output  = $args->before;

		// Split-button pattern for EVERY parent — the starter renders ONE
		// menu (#primary-menu) for both breakpoints, so the markup cannot
		// differ per device (tech had two menus; this adapts): the parent
		// link navigates and a REAL <button> (.mno-caret chip) toggles the
		// sub-menu. CSS shows the chip + accordion behaviour on mobile and
		// the hover dropdown on desktop; nav-accordion.js gates the toggle
		// to mobile viewports. Keyboard/SR operable either way (WCAG 2.1.1).
		if ( $has_children ) {
			$submenu_id                  = 'mno-sub-' . $item->ID;
			$this->submenu_ids[ $depth ] = $submenu_id;
			$row_class                   = ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) ) ? ' mno-parent active' : ' mno-parent';
			$item_output                .= '<div class="' . trim( $row_class ) . '">';
			$item_output                .= '<a' . $attributes . '>' . $args->link_before . $title . $args->link_after . '</a>';
			$item_output                .= '<button type="button" class="mno-caret" aria-expanded="false" aria-controls="' . esc_attr( $submenu_id ) . '">';
			$item_output                .= '<svg class="nav-caret" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><polyline points="6 9 12 15 18 9"/></svg>';
			$item_output                .= '</button></div>';
		} else {
			$item_output .= '<a' . $attributes . '>';
			$item_output .= $args->link_before . $title . $args->link_after;
			$item_output .= '</a>';
		}

		$item_output .= $args->after;

		$output .= $item_output;
	}

	/**
	 * Close a single menu item.
	 *
	 * @param string   $output Passed by reference.
	 * @param WP_Post  $item   Menu item data object.
	 * @param int      $depth  Depth of menu item.
	 * @param stdClass $args   An object of wp_nav_menu() arguments.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= "</li>\n";
	}
}

endif;

// ── 2. gwill_nav_fallback ──────────────────────────────────
/**
 * Fallback when no menu is assigned to a location.
 *
 * Renders Home + published pages (menu_order, alphabetical tiebreak) so
 * the header never goes empty before the user builds a menu in
 * Appearance → Menus. Brand-agnostic by design: no hardcoded content
 * categories (tech's fallback shipped its own site structure — that
 * must not leak into client builds).
 *
 * @param array $args wp_nav_menu() args (used for the container class).
 */
function gwill_nav_fallback( $args = array() ) {
	echo '<ul id="primary-menu" class="nav-menu">';

	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '" class="nav-link' . ( ( is_home() || is_front_page() ) ? ' active' : '' ) . '">' . esc_html__( 'Home', 'gwill-starter' ) . '</a></li>';

	$pages = get_pages( array( 'sort_column' => 'menu_order, post_title' ) );
	foreach ( $pages as $page ) {
		$is_active = is_page( (int) $page->ID );
		echo '<li><a href="' . esc_url( get_permalink( $page ) ) . '" class="nav-link' . ( $is_active ? ' active' : '' ) . '">' . esc_html( get_the_title( $page ) ) . '</a></li>';
	}

	echo '</ul>';
}

// ── 3. gwill_nav_walker_args ────────────────────────────────
/**
 * Inject the walker into every wp_nav_menu() rendered from the
 * theme (wp_nav_menu_args filter) so header.php needs no edit.
 *
 * Only themes that use the 'primary' location get the walker; any
 * other location (a footer menu, a social strip) keeps the default
 * walker — those are flat by convention and the split-button mobile
 * pattern would be wrong there.
 *
 * @param array $args wp_nav_menu() args.
 * @return array
 */
function gwill_nav_walker_args( $args ) {
	if ( isset( $args['theme_location'] ) && 'primary' === $args['theme_location'] ) {
		$args['walker']     = new GWill_Nav_Walker();
		$args['fallback_cb'] = 'gwill_nav_fallback';
	}
	return $args;
}
add_filter( 'wp_nav_menu_args', 'gwill_nav_walker_args', 10, 1 );
