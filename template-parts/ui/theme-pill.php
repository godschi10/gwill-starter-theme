<?php
/**
 * Theme switcher pill - tri-state segmented control (Dark / System / Light).
 *
 * Markup only. All behaviour (state sync, click, keyboard, OS-follow) is
 * inlined in <head> by gwill_darkmode_head_script() (inc/darkmode.php) and
 * targets [data-theme-group] / [data-theme-set] attributes - the same
 * contract as the finance theme's pill, ported in v1.11.0.
 *
 * data-current="system" is the server-side DEFAULT only. The head script
 * overwrites it with the visitor's REAL stored choice on DOMContentLoaded
 * (the finance v1.12.70 fix) so the pill never lies on refresh.
 *
 * The text span (gwill-theme-pill__text) carries the translatable label;
 * the parent button keeps a descriptive aria-label ("Dark" etc.) so the
 * control reads sensibly on the mobile icon-only variant where the text
 * span is hidden but must remain announced by screen readers... the span
 * is hidden via display:none on mobile, so aria-label carries the name.
 *
 * @package GWill_Starter
 * @since   1.11.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	class="gwill-theme-pill"
	role="group"
	aria-label="<?php esc_attr_e( 'Theme', 'gwill-starter' ); ?>"
	data-theme-group
	data-current="system"
>
	<button
		type="button"
		class="gwill-theme-pill__seg"
		data-theme-set="dark"
		aria-label="<?php esc_attr_e( 'Dark', 'gwill-starter' ); ?>"
		aria-pressed="false"
		title="<?php esc_attr_e( 'Always dark', 'gwill-starter' ); ?>"
	>
		<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
			<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
		</svg>
		<span class="gwill-theme-pill__text"><?php esc_html_e( 'Dark', 'gwill-starter' ); ?></span>
	</button>
	<button
		type="button"
		class="gwill-theme-pill__seg"
		data-theme-set="system"
		aria-label="<?php esc_attr_e( 'System default', 'gwill-starter' ); ?>"
		aria-pressed="true"
		title="<?php esc_attr_e( 'Follow my device', 'gwill-starter' ); ?>"
	>
		<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
			<rect x="2.5" y="4" width="19" height="13" rx="2"/>
			<path d="M8 21h8M12 17v4"/>
		</svg>
		<span class="gwill-theme-pill__text"><?php esc_html_e( 'System', 'gwill-starter' ); ?></span>
	</button>
	<button
		type="button"
		class="gwill-theme-pill__seg"
		data-theme-set="light"
		aria-label="<?php esc_attr_e( 'Light', 'gwill-starter' ); ?>"
		aria-pressed="false"
		title="<?php esc_attr_e( 'Always light', 'gwill-starter' ); ?>"
	>
		<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
			<circle cx="12" cy="12" r="4.5"/>
			<path d="M12 1.5v2.5M12 20v2.5M4.2 4.2l1.8 1.8M18 18l1.8 1.8M1.5 12H4M20 12h2.5M4.2 19.8L6 18M18 6l1.8-1.8"/>
		</svg>
		<span class="gwill-theme-pill__text"><?php esc_html_e( 'Light', 'gwill-starter' ); ?></span>
	</button>
</div>
