<?php
/**
 * Form Partial: Simple Contact (Pattern 1)
 *
 * Name / Email / Message. Submits via WordPress AJAX to inc/forms.php.
 * Delivery via wp_mail() — configure SMTP in wp-config.php.
 *
 * Usage: gwill_part( 'forms/contact-simple' );
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-simple-' );
?>

<form
	class="gwill-form gwill-form--simple"
	method="post"
	action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	novalidate
>
	<div class="gwill-honey" aria-hidden="true">
		<label for="hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
		<input type="text" name="gwill_hp" id="hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
	</div>

	<input type="hidden" name="action"        value="gwill_contact_form">
	<input type="hidden" name="gwill_form_id" value="simple">

	<div class="gwill-form__field">
		<label for="name_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'Name', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<input
			type="text"
			id="name_<?php echo esc_attr( $uid ); ?>"
			name="gwill_name"
			required
			autocomplete="name"
		>
	</div>

	<div class="gwill-form__field">
		<label for="email_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'Email', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<input
			type="email"
			id="email_<?php echo esc_attr( $uid ); ?>"
			name="gwill_email"
			required
			autocomplete="email"
		>
	</div>

	<div class="gwill-form__field">
		<label for="message_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'Message', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<textarea
			id="message_<?php echo esc_attr( $uid ); ?>"
			name="gwill_message"
			rows="5"
			required
		></textarea>
	</div>

	<div class="gwill-form__actions">
		<button type="submit" class="gwill-form__submit">
			<span class="gwill-form__submit-text"><?php esc_html_e( 'Send Message', 'gwill-starter' ); ?></span>
			<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Sending…', 'gwill-starter' ); ?></span>
		</button>
	</div>

	<div class="gwill-form__status" role="alert" aria-live="polite"></div>
</form>
