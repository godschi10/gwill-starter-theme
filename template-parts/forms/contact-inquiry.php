<?php
/**
 * Form Partial: Service Inquiry (Pattern 2)
 *
 * Screens clients by service type, timeline, and budget before they reach
 * a calendar or detailed conversation. For freelancers and consultants.
 *
 * Usage: gwill_part( 'forms/contact-inquiry' );
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-inquiry-' );
?>

<form
	class="gwill-form gwill-form--inquiry"
	method="post"
	action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	novalidate
>
	<div class="gwill-honey" aria-hidden="true">
		<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
		<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
	</div>

	<input type="hidden" name="action"        value="gwill_contact_form">
	<input type="hidden" name="gwill_form_id" value="inquiry">

	<div class="gwill-form__row gwill-form__row--2col">
		<div class="gwill-form__field">
			<label for="name_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'Your name', 'gwill-starter' ); ?>
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
		<label for="company_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'Company or website', 'gwill-starter' ); ?>
		</label>
		<input type="text" id="company_<?php echo esc_attr( $uid ); ?>" name="gwill_company" autocomplete="organization">
	</div>

	<div class="gwill-form__field">
		<label for="service_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'What do you need?', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<select id="service_<?php echo esc_attr( $uid ); ?>" name="gwill_service_type" required>
			<option value=""><?php esc_html_e( 'Choose a service…', 'gwill-starter' ); ?></option>
			<option value="Web Design"><?php esc_html_e( 'Web Design', 'gwill-starter' ); ?></option>
			<option value="Development"><?php esc_html_e( 'Development', 'gwill-starter' ); ?></option>
			<option value="SEO"><?php esc_html_e( 'SEO', 'gwill-starter' ); ?></option>
			<option value="Copywriting"><?php esc_html_e( 'Copywriting', 'gwill-starter' ); ?></option>
			<option value="Consultation"><?php esc_html_e( 'Consultation', 'gwill-starter' ); ?></option>
			<option value="Other"><?php esc_html_e( 'Other', 'gwill-starter' ); ?></option>
		</select>
	</div>

	<div class="gwill-form__row gwill-form__row--2col">
		<div class="gwill-form__field">
			<label for="timeline_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'Timeline', 'gwill-starter' ); ?>
			</label>
			<select id="timeline_<?php echo esc_attr( $uid ); ?>" name="gwill_timeline">
				<option value=""><?php esc_html_e( 'Select…', 'gwill-starter' ); ?></option>
				<option value="ASAP"><?php esc_html_e( 'ASAP', 'gwill-starter' ); ?></option>
				<option value="1-3 months"><?php esc_html_e( '1–3 months', 'gwill-starter' ); ?></option>
				<option value="3-6 months"><?php esc_html_e( '3–6 months', 'gwill-starter' ); ?></option>
				<option value="Exploring"><?php esc_html_e( 'Just exploring', 'gwill-starter' ); ?></option>
			</select>
		</div>

		<div class="gwill-form__field">
			<label for="budget_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'Budget range', 'gwill-starter' ); ?>
			</label>
			<select id="budget_<?php echo esc_attr( $uid ); ?>" name="gwill_budget">
				<option value=""><?php esc_html_e( 'Select…', 'gwill-starter' ); ?></option>
				<option value="Under $500"><?php esc_html_e( 'Under $500', 'gwill-starter' ); ?></option>
				<option value="$500-$2k"><?php esc_html_e( '$500–$2k', 'gwill-starter' ); ?></option>
				<option value="$2k-$5k"><?php esc_html_e( '$2k–$5k', 'gwill-starter' ); ?></option>
				<option value="$5k+"><?php esc_html_e( '$5k+', 'gwill-starter' ); ?></option>
				<option value="Let's discuss"><?php esc_html_e( "Let's discuss", 'gwill-starter' ); ?></option>
			</select>
		</div>
	</div>

	<div class="gwill-form__field">
		<label for="message_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'Tell me about your project', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<textarea id="message_<?php echo esc_attr( $uid ); ?>" name="gwill_message" rows="5" required></textarea>
	</div>

	<div class="gwill-form__field">
		<label for="referral_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'How did you find me? (optional)', 'gwill-starter' ); ?>
		</label>
		<input type="text" id="referral_<?php echo esc_attr( $uid ); ?>" name="gwill_referral">
	</div>

	<div class="gwill-form__actions">
		<button type="submit" class="gwill-form__submit">
			<span class="gwill-form__submit-text"><?php esc_html_e( 'Send Inquiry', 'gwill-starter' ); ?></span>
			<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Sending…', 'gwill-starter' ); ?></span>
		</button>
	</div>

	<div class="gwill-form__status" role="alert" aria-live="polite"></div>
</form>
