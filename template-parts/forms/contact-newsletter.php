<?php
/**
 * Form Partial: Newsletter Signup (Pattern 11)
 *
 * A single-field email capture that adds the address to a Brevo contact
 * list via gwill_brevo_add_contact() — see inc/forms.php. Does not email
 * anyone; there's no "message" for a list subscription. Requires
 * GWILL_BREVO_API_KEY and GWILL_BREVO_LIST_ID to be defined in
 * wp-config.php (see the config block at the top of inc/forms.php) —
 * without them, submission fails gracefully with a translated error
 * rather than a fatal.
 *
 * Usage: gwill_part( 'forms/contact-newsletter' );
 *
 * @package GWill_Starter
 * @since   1.0.58
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-newsletter-' );
?>

<div class="gwill-newsletter">
	<p class="gwill-newsletter__prompt">
		<?php esc_html_e( 'Get new posts in your inbox.', 'gwill-starter' ); ?>
	</p>

	<form
		class="gwill-form gwill-form--newsletter"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		novalidate
	>
		<div class="gwill-honey" aria-hidden="true">
			<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
			<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
		</div>

		<input type="hidden" name="action"        value="gwill_contact_form">
		<input type="hidden" name="gwill_form_id" value="newsletter">

		<div class="gwill-form--newsletter__fields">
			<div class="gwill-form__field">
				<label for="email_<?php echo esc_attr( $uid ); ?>" class="screen-reader-text">
					<?php esc_html_e( 'Email', 'gwill-starter' ); ?>
				</label>
				<input
					type="email"
					id="email_<?php echo esc_attr( $uid ); ?>"
					name="gwill_email"
					placeholder="<?php esc_attr_e( 'Your email', 'gwill-starter' ); ?>"
					required
					autocomplete="email"
				>
			</div>

			<button type="submit" class="gwill-form__submit">
				<span class="gwill-form__submit-text"><?php esc_html_e( 'Subscribe', 'gwill-starter' ); ?></span>
				<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Subscribing…', 'gwill-starter' ); ?></span>
			</button>
		</div>

		<div class="gwill-form__status" role="alert" aria-live="polite"></div>
	</form>
</div>
