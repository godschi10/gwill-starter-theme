<?php
/**
 * Form Partial: Type-Router (Pattern 3)
 *
 * One form, multiple recipients. The inquiry_type value maps to an email
 * address via gwill_get_routing_email() in inc/forms.php.
 * Configure the routing map with the 'gwill_form_routing_map' filter.
 *
 * Usage: gwill_part( 'forms/contact-routed' );
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-routed-' );
?>

<form
	class="gwill-form gwill-form--routed"
	method="post"
	action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	novalidate
>
	<div class="gwill-honey" aria-hidden="true">
		<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
		<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
	</div>

	<input type="hidden" name="action"        value="gwill_contact_form">
	<input type="hidden" name="gwill_form_id" value="routed">

	<div class="gwill-form__field">
		<label for="type_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( "What's this about?", 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<select id="type_<?php echo esc_attr( $uid ); ?>" name="gwill_inquiry_type" required>
			<option value=""><?php esc_html_e( 'Choose…', 'gwill-starter' ); ?></option>
			<option value="press"><?php esc_html_e( 'Press &amp; Media', 'gwill-starter' ); ?></option>
			<option value="partnership"><?php esc_html_e( 'Brand Partnerships', 'gwill-starter' ); ?></option>
			<option value="reader"><?php esc_html_e( 'Reader Question', 'gwill-starter' ); ?></option>
			<option value="support"><?php esc_html_e( 'Technical Support', 'gwill-starter' ); ?></option>
			<option value="general"><?php esc_html_e( 'General', 'gwill-starter' ); ?></option>
		</select>
	</div>

	<div class="gwill-form__row gwill-form__row--2col">
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
	</div>

	<div class="gwill-form__field">
		<label for="message_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'Message', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<textarea id="message_<?php echo esc_attr( $uid ); ?>" name="gwill_message" rows="5" required></textarea>
	</div>

	<div class="gwill-form__actions">
		<button type="submit" class="gwill-form__submit">
			<span class="gwill-form__submit-text"><?php esc_html_e( 'Send Message', 'gwill-starter' ); ?></span>
			<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Sending…', 'gwill-starter' ); ?></span>
		</button>
	</div>

	<div class="gwill-form__status" role="alert" aria-live="polite"></div>
</form>
