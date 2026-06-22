<?php
/**
 * Form Partial: Sticky Sidebar Form (Pattern 6)
 *
 * A compact form placed in the sidebar. Make it sticky via CSS:
 *   .sidebar-widget-area { position: sticky; top: 2rem; }
 *
 * Usage: gwill_part( 'forms/contact-sidebar' );
 *        Add to sidebar.php inside a widget-area container.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-sidebar-' );
?>

<div class="gwill-sidebar-form">
	<p class="gwill-sidebar-form__heading"><?php esc_html_e( 'Hire Me', 'gwill-starter' ); ?></p>

	<form
		class="gwill-form gwill-form--sidebar"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		novalidate
	>
		<div class="gwill-honey" aria-hidden="true">
			<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
			<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
		</div>

		<input type="hidden" name="action"        value="gwill_contact_form">
		<input type="hidden" name="gwill_form_id" value="sidebar">

		<div class="gwill-form__field">
			<label for="name_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'Name', 'gwill-starter' ); ?>
				<span class="gwill-form__required" aria-hidden="true">*</span>
			</label>
			<input type="text" id="name_<?php echo esc_attr( $uid ); ?>" name="gwill_name" required autocomplete="name">
		</div>

		<div class="gwill-form__field">
			<label for="email_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'Email', 'gwill-starter' ); ?>
				<span class="gwill-form__required" aria-hidden="true">*</span>
			</label>
			<input type="email" id="email_<?php echo esc_attr( $uid ); ?>" name="gwill_email" required autocomplete="email">
		</div>

		<div class="gwill-form__field">
			<label for="ask_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'How can I help?', 'gwill-starter' ); ?>
				<span class="gwill-form__required" aria-hidden="true">*</span>
			</label>
			<input type="text" id="ask_<?php echo esc_attr( $uid ); ?>" name="gwill_ask" required>
		</div>

		<div class="gwill-form__actions">
			<button type="submit" class="gwill-form__submit gwill-form__submit--full">
				<span class="gwill-form__submit-text"><?php esc_html_e( 'Get in Touch', 'gwill-starter' ); ?></span>
				<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Sending…', 'gwill-starter' ); ?></span>
			</button>
		</div>

		<div class="gwill-form__status" role="alert" aria-live="polite"></div>
	</form>
</div>
