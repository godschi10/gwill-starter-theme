<?php
/**
 * Modal live-search form — Combo B (opt-in).
 *
 * Full-viewport overlay with as-you-type results via REST API.
 * Enter key falls through to search.php for a full results page.
 *
 * To activate: replace the expandable partial call in header.php with:
 *   gwill_part( 'search/search-form-modal' );
 *
 * Behaviour:
 *   - Trigger button opens the modal overlay
 *   - Keystrokes (≥ 2 chars, 300 ms debounce) query /wp-json/gwill/v1/search
 *   - Arrow keys navigate results; Enter follows the focused link
 *   - Enter with no result selected submits the form → search.php
 *   - Escape / close button / backdrop click → close, restore focus
 *   - Focus is trapped within modal while open (WCAG 2.4.3)
 *
 * i18n + REST URL injected by PHP via GwillSearch (enqueue.php).
 *
 * @package GWill_Starter
 * @since   1.0.23
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-search-modal' );
wp_enqueue_style( 'gwill-search' );
?>

<?php /* ── Trigger button (in the header) ────────────────────────────── */ ?>
<button
	class="gwill-search-modal__trigger"
	type="button"
	aria-label="<?php esc_attr_e( 'Open search', 'gwill-starter' ); ?>"
	aria-haspopup="dialog"
	data-gwill-search-trigger
>
	<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
		<circle cx="8.5" cy="8.5" r="5.75" stroke="currentColor" stroke-width="1.5"/>
		<path d="M13 13L17.5 17.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
	</svg>
</button>

<?php /* ── Overlay (full viewport, toggled by JS) ──────────────────────── */ ?>
<div
	class="gwill-search-modal"
	role="dialog"
	aria-label="<?php esc_attr_e( 'Search', 'gwill-starter' ); ?>"
	aria-modal="true"
	aria-hidden="true"
	hidden
	data-gwill-search-modal
>
	<div class="gwill-search-modal__panel">

		<form
			class="gwill-search-modal__form"
			role="search"
			action="<?php echo esc_url( home_url( '/' ) ); ?>"
			method="get"
		>
			<div class="gwill-search-modal__input-row">

				<svg class="gwill-search-modal__input-icon" width="20" height="20"
					viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
					<circle cx="8.5" cy="8.5" r="5.75" stroke="currentColor" stroke-width="1.5"/>
					<path d="M13 13L17.5 17.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>

				<label class="screen-reader-text" for="gwill-search-modal-input">
					<?php esc_html_e( 'Search', 'gwill-starter' ); ?>
				</label>

				<input
					id="gwill-search-modal-input"
					class="gwill-search-modal__input"
					type="search"
					name="s"
					placeholder="<?php esc_attr_e( 'Search…', 'gwill-starter' ); ?>"
					autocomplete="off"
					autocorrect="off"
					spellcheck="false"
					aria-autocomplete="list"
					aria-controls="gwill-search-modal-results"
					aria-expanded="false"
				>

				<button
					class="gwill-search-modal__clear"
					type="button"
					aria-label="<?php esc_attr_e( 'Clear search input', 'gwill-starter' ); ?>"
					hidden
				>
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">
						<path d="M3 3L13 13M13 3L3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
					</svg>
				</button>

			</div>

			<button class="screen-reader-text" type="submit">
				<?php esc_html_e( 'Submit search', 'gwill-starter' ); ?>
			</button>
		</form>

		<?php /* Live results list — populated entirely by search-modal.js */ ?>
		<div
			id="gwill-search-modal-results"
			class="gwill-search-modal__results"
			role="listbox"
			aria-label="<?php esc_attr_e( 'Search suggestions', 'gwill-starter' ); ?>"
		></div>

		<button
			class="gwill-search-modal__close"
			type="button"
			aria-label="<?php esc_attr_e( 'Close search', 'gwill-starter' ); ?>"
			data-gwill-search-close
		>
			<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true" focusable="false">
				<path d="M3 3L15 15M15 3L3 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
			</svg>
			<span class="gwill-search-modal__esc-hint" aria-hidden="true">Esc</span>
		</button>

	</div><!-- .gwill-search-modal__panel -->

	<div class="gwill-search-modal__backdrop" aria-hidden="true" data-gwill-search-backdrop></div>

</div><!-- .gwill-search-modal -->
