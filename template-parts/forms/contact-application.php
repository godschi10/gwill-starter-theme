<?php
/**
 * Form Partial: Application / Work-With-Me (Pattern 8)
 *
 * Frames contact as applying to work with you. Revenue and outcome questions
 * qualify applicants before they reach your calendar.
 *
 * Usage: gwill_part( 'forms/contact-application' );
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-app-' );
?>

<form
	class="gwill-form gwill-form--application"
	method="post"
	action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	novalidate
>
	<div class="gwill-honey" aria-hidden="true">
		<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
		<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
	</div>

	<input type="hidden" name="action"        value="gwill_contact_form">
	<input type="hidden" name="gwill_form_id" value="application">

	<div class="gwill-form__field">
		<label for="site_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( "What's the URL of your current site?", 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<input type="url" id="site_<?php echo esc_attr( $uid ); ?>" name="gwill_site_url" required placeholder="https://example.com">
	</div>

	<div class="gwill-form__field">
		<label for="project_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'What are you working on?', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<textarea id="project_<?php echo esc_attr( $uid ); ?>" name="gwill_project" rows="4" required></textarea>
	</div>

	<div class="gwill-form__field">
		<label for="revenue_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( "What's your current annual revenue?", 'gwill-starter' ); ?></label>
		<select id="revenue_<?php echo esc_attr( $uid ); ?>" name="gwill_revenue">
			<option value=""><?php esc_html_e( 'Select…', 'gwill-starter' ); ?></option>
			<option value="Pre-revenue"><?php esc_html_e( 'Pre-revenue', 'gwill-starter' ); ?></option>
			<option value="Under $10k"><?php esc_html_e( 'Under $10k', 'gwill-starter' ); ?></option>
			<option value="$10k-$100k"><?php esc_html_e( '$10k–$100k', 'gwill-starter' ); ?></option>
			<option value="$100k-$500k"><?php esc_html_e( '$100k–$500k', 'gwill-starter' ); ?></option>
			<option value="$500k+"><?php esc_html_e( '$500k+', 'gwill-starter' ); ?></option>
		</select>
	</div>

	<div class="gwill-form__field">
		<label for="outcome_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'What outcome are you hoping for?', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<textarea id="outcome_<?php echo esc_attr( $uid ); ?>" name="gwill_outcome" rows="3" required
			placeholder="<?php esc_attr_e( 'In 2–3 sentences…', 'gwill-starter' ); ?>"></textarea>
	</div>

	<div class="gwill-form__field">
		<label for="why_now_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Why now? (one line)', 'gwill-starter' ); ?></label>
		<input type="text" id="why_now_<?php echo esc_attr( $uid ); ?>" name="gwill_why_now">
	</div>

	<div class="gwill-form__field">
		<label for="email_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'Best email to reach you', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<input type="email" id="email_<?php echo esc_attr( $uid ); ?>" name="gwill_email" required autocomplete="email">
	</div>

	<div class="gwill-form__actions">
		<button type="submit" class="gwill-form__submit">
			<span class="gwill-form__submit-text"><?php esc_html_e( 'Submit Application', 'gwill-starter' ); ?></span>
			<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Submitting…', 'gwill-starter' ); ?></span>
		</button>
	</div>

	<div class="gwill-form__status" role="alert" aria-live="polite"></div>
</form>
