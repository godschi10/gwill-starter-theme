<?php
/**
 * Expandable search form — Combo A (default).
 *
 * Three-zone layout: [ 🔍 icon | input field | Search button ]
 * Separated by vertical borders. One outer container border — no input box.
 *
 * To switch to Combo B (modal + live search), replace the header.php call:
 *   gwill_part( 'search/search-form-modal' );
 *
 * @package GWill_Starter
 * @since   1.0.23
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-search-expand' );
wp_enqueue_style( 'gwill-search' );
?>

<div class="gwill-search-expand" data-gwill-search-expand>

	<button
		class="gwill-search-expand__toggle"
		type="button"
		aria-label="<?php esc_attr_e( 'Open search', 'gwill-starter' ); ?>"
		aria-expanded="false"
		aria-controls="gwill-search-expand-form"
	>
		<svg class="gwill-search-expand__icon gwill-search-expand__icon--search"
			width="20" height="20" viewBox="0 0 20 20" fill="none"
			aria-hidden="true" focusable="false">
			<circle cx="8.5" cy="8.5" r="5.75" stroke="currentColor" stroke-width="1.5"/>
			<path d="M13 13L17.5 17.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
		</svg>
		<svg class="gwill-search-expand__icon gwill-search-expand__icon--close"
			width="20" height="20" viewBox="0 0 20 20" fill="none"
			aria-hidden="true" focusable="false">
			<path d="M4 4L16 16M16 4L4 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
		</svg>
	</button>

	<form
		id="gwill-search-expand-form"
		class="gwill-search-expand__form"
		role="search"
		action="<?php echo esc_url( home_url( '/' ) ); ?>"
		method="get"
		hidden
		aria-hidden="true"
	>
		<?php /* Zone 1 — search icon */ ?>
		<span class="gwill-search-expand__zone-icon" aria-hidden="true">
			<svg width="17" height="17" viewBox="0 0 20 20" fill="none"
				aria-hidden="true" focusable="false">
				<circle cx="8.5" cy="8.5" r="5.75" stroke="currentColor" stroke-width="1.5"/>
				<path d="M13 13L17.5 17.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
			</svg>
		</span>

		<?php /* Zone 2 — text input, border-left = vertical divider */ ?>
		<div class="gwill-search-expand__zone-input">
			<label class="screen-reader-text" for="gwill-search-expand-input">
				<?php esc_html_e( 'Search', 'gwill-starter' ); ?>
			</label>
			<input
				id="gwill-search-expand-input"
				class="gwill-search-expand__input"
				type="search"
				name="s"
				value="<?php echo gwill_get_search_term(); ?>"
				placeholder="<?php esc_attr_e( 'Type to search…', 'gwill-starter' ); ?>"
				autocomplete="off"
				autocorrect="off"
				spellcheck="false"
			>
		</div>

		<?php /* Zone 3 — submit button, border-left = vertical divider */ ?>
		<button class="gwill-search-expand__submit" type="submit">
			<?php esc_html_e( 'Search', 'gwill-starter' ); ?>
		</button>

	</form>

</div>
