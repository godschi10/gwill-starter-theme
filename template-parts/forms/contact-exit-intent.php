<?php
/**
 * Form Partial: Exit-Intent Overlay (Pattern 7)
 *
 * A full-screen overlay triggered by exit intent (mouseleave at viewport top)
 * or 75% scroll depth on mobile. Used primarily for email capture.
 * Trigger + overlay logic in assets/js/form-exit-intent.js.
 *
 * Place once, near wp_footer() — call from footer.php or functions.php:
 *   add_action( 'wp_footer', function() { gwill_part( 'forms/contact-exit-intent' ); } );
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );
wp_enqueue_script( 'gwill-forms-exit' );

$uid = wp_unique_id( 'gwill-exit-' );
?>

<div
	class="gwill-exit-intent"
	role="dialog"
	aria-modal="true"
	aria-labelledby="exit-title-<?php echo esc_attr( $uid ); ?>"
	aria-hidden="true"
	hidden
>
	<div class="gwill-exit-intent__panel">
		<button
			type="button"
			class="gwill-exit-intent__close"
			aria-label="<?php esc_attr_e( 'Close', 'gwill-starter' ); ?>"
		>&times;</button>

		<div class="gwill-exit-intent__body">
			<h2 id="exit-title-<?php echo esc_attr( $uid ); ?>" class="gwill-exit-intent__heading">
				<?php esc_html_e( 'Before you go…', 'gwill-starter' ); ?>
			</h2>
			<p class="gwill-exit-intent__subheading">
				<?php esc_html_e( 'Get the next guide delivered straight to your inbox.', 'gwill-starter' ); ?>
			</p>

			<form
				class="gwill-form gwill-form--exit-intent"
				method="post"
				action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
				novalidate
			>
				<div class="gwill-honey" aria-hidden="true">
					<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
					<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
				</div>

				<input type="hidden" name="action"        value="gwill_contact_form">
				<input type="hidden" name="gwill_form_id" value="exit_intent">

				<div class="gwill-form__row gwill-form__row--2col">
					<div class="gwill-form__field">
						<label for="fname_<?php echo esc_attr( $uid ); ?>">
							<?php esc_html_e( 'First name', 'gwill-starter' ); ?>
							<span class="gwill-form__required" aria-hidden="true">*</span>
						</label>
						<input type="text" id="fname_<?php echo esc_attr( $uid ); ?>" name="gwill_first_name" required autocomplete="given-name">
					</div>
					<div class="gwill-form__field">
						<label for="email_<?php echo esc_attr( $uid ); ?>">
							<?php esc_html_e( 'Email', 'gwill-starter' ); ?>
							<span class="gwill-form__required" aria-hidden="true">*</span>
						</label>
						<input type="email" id="email_<?php echo esc_attr( $uid ); ?>" name="gwill_email" required autocomplete="email">
					</div>
				</div>

				<div class="gwill-form__actions">
					<button type="submit" class="gwill-form__submit gwill-form__submit--full">
						<span class="gwill-form__submit-text"><?php esc_html_e( 'Yes, send it to me', 'gwill-starter' ); ?></span>
						<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Subscribing…', 'gwill-starter' ); ?></span>
					</button>
				</div>

				<div class="gwill-form__status" role="alert" aria-live="polite"></div>
			</form>
		</div>
	</div>
</div>
