<?php
/**
 * Dark mode toggle button
 *
 * All behaviour (click handling, ARIA sync, OS-preference listener) is
 * inlined directly into <head> by gwill_darkmode_head_script() in
 * inc/darkmode.php — not a separately enqueued script. LiteSpeed Cache's
 * "Load JS Deferred" setting can delay external scripts until after first
 * user interaction on some devices; this is the reason the toggle's click
 * handler must not depend on an external file (see inc/darkmode.php for
 * the full history).
 *
 * data-label-dark / data-label-light hold the translated ARIA label text.
 * They exist as data attributes (not hardcoded into the inline JS) so the
 * label stays translatable via gwill-starter's text domain, while the JS
 * itself stays a static, defer-proof inline block with no PHP-generated
 * strings baked into it directly.
 *
 * Initial aria-label/aria-pressed reflect the light-mode defaults; the
 * head script corrects them before paint if the resolved theme is dark.
 *
 * @package GWill_Starter
 * @since   1.0.30
 */

defined( 'ABSPATH' ) || exit;
?>
<button
	id="gwill-darkmode-toggle"
	class="gwill-darkmode-toggle"
	aria-label="<?php esc_attr_e( 'Switch to dark mode', 'gwill-starter' ); ?>"
	aria-pressed="false"
	data-label-dark="<?php esc_attr_e( 'Switch to dark mode', 'gwill-starter' ); ?>"
	data-label-light="<?php esc_attr_e( 'Switch to light mode', 'gwill-starter' ); ?>"
>
	<?php /* Sun — shown in dark mode (click to return to light) */ ?>
	<span class="gwill-darkmode-toggle__sun" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
			<circle cx="12" cy="12" r="5"/>
			<line x1="12" y1="1" x2="12" y2="3"/>
			<line x1="12" y1="21" x2="12" y2="23"/>
			<line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
			<line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
			<line x1="1" y1="12" x2="3" y2="12"/>
			<line x1="21" y1="12" x2="23" y2="12"/>
			<line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
			<line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
		</svg>
	</span>

	<?php /* Moon — shown in light mode (click to switch to dark) */ ?>
	<span class="gwill-darkmode-toggle__moon" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
			<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
		</svg>
	</span>
</button>
