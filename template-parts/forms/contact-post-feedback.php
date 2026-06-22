<?php
/**
 * Form Partial: Post Feedback (Pattern 10)
 *
 * Micro-interaction at the bottom of every post.
 * Yes → immediate submit with gwill_response=yes.
 * No  → reveals textarea for more detail, then submit.
 * Handled by the Yes/No click handlers in assets/js/forms.js.
 *
 * Usage: gwill_part( 'forms/contact-post-feedback' );
 *        Add near the bottom of single.php.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-feedback-' );
?>

<div class="gwill-feedback-wrap">
	<p class="gwill-feedback-wrap__question">
		<?php esc_html_e( 'Was this helpful?', 'gwill-starter' ); ?>
	</p>

	<form
		class="gwill-form gwill-form--feedback"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		novalidate
	>
		<div class="gwill-honey" aria-hidden="true">
			<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
			<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
		</div>

		<input type="hidden" name="action"        value="gwill_contact_form">
		<input type="hidden" name="gwill_form_id" value="feedback">
		<input type="hidden" name="gwill_response" value="">
		<?php
		if ( is_singular() ) {
			echo '<input type="hidden" name="gwill_source_post" value="' . esc_attr( get_the_title() ) . '">';
		}
		?>

		<div class="gwill-feedback-wrap__actions">
			<button type="button" class="gwill-feedback__yes gwill-feedback-wrap__btn gwill-feedback-wrap__btn--yes">
				<?php esc_html_e( '👍 Yes', 'gwill-starter' ); ?>
			</button>
			<button type="button" class="gwill-feedback__no gwill-feedback-wrap__btn gwill-feedback-wrap__btn--no">
				<?php esc_html_e( '👎 No', 'gwill-starter' ); ?>
			</button>
		</div>

		<div class="gwill-feedback__extra" hidden>
			<div class="gwill-form__field">
				<label for="feedback_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'What would make this more useful?', 'gwill-starter' ); ?></label>
				<textarea id="feedback_<?php echo esc_attr( $uid ); ?>" name="gwill_feedback" rows="3"></textarea>
			</div>
			<div class="gwill-form__actions">
				<button type="submit" class="gwill-form__submit">
					<span class="gwill-form__submit-text"><?php esc_html_e( 'Send Feedback', 'gwill-starter' ); ?></span>
					<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Sending…', 'gwill-starter' ); ?></span>
				</button>
			</div>
		</div>

		<div class="gwill-form__status" role="alert" aria-live="polite"></div>
	</form>
</div>
