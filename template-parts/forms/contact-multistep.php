<?php
/**
 * Form Partial: Multi-step Quote (Pattern 4)
 *
 * Four steps: service + scope → timeline + budget → contact → description.
 * Step navigation managed by assets/js/form-multistep.js.
 * AJAX submission handled by assets/js/forms.js.
 *
 * No-JS fallback: all steps are stacked, Next/Back buttons are inert,
 * the submit button at the bottom works normally.
 *
 * Usage: gwill_part( 'forms/contact-multistep' );
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );
wp_enqueue_script( 'gwill-forms-multistep' );

$uid = wp_unique_id( 'gwill-ms-' );
?>

<div class="gwill-multistep-wrap">

	<?php /* Progress bar */ ?>
	<div class="gwill-form__progress" aria-hidden="true">
		<div class="gwill-form__progress-fill" style="width:4%"></div>
	</div>
	<p class="gwill-form__step-label"><?php esc_html_e( 'Step 1 of 4', 'gwill-starter' ); ?></p>

	<noscript>
		<p class="gwill-form__noscript-notice">
			<?php esc_html_e( 'For the best experience, enable JavaScript. The form will still work — all fields are shown below.', 'gwill-starter' ); ?>
		</p>
	</noscript>

	<form
		class="gwill-form gwill-form--multistep"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		data-form-id="<?php echo esc_attr( $uid ); ?>"
		novalidate
	>
		<div class="gwill-honey" aria-hidden="true">
			<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
			<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
		</div>

		<input type="hidden" name="action"        value="gwill_contact_form">
		<input type="hidden" name="gwill_form_id" value="multistep">

		<?php /* ── Step 1: Service + Scope ── */ ?>
		<div class="gwill-form__step">
			<h3 class="gwill-form__step-heading"><?php esc_html_e( 'What do you need?', 'gwill-starter' ); ?></h3>

			<div class="gwill-form__field">
				<label for="service_<?php echo esc_attr( $uid ); ?>">
					<?php esc_html_e( 'Service type', 'gwill-starter' ); ?>
					<span class="gwill-form__required" aria-hidden="true">*</span>
				</label>
				<select id="service_<?php echo esc_attr( $uid ); ?>" name="gwill_service_type" required>
					<option value=""><?php esc_html_e( 'Choose…', 'gwill-starter' ); ?></option>
					<option value="Web Design"><?php esc_html_e( 'Web Design', 'gwill-starter' ); ?></option>
					<option value="Development"><?php esc_html_e( 'Development', 'gwill-starter' ); ?></option>
					<option value="Redesign"><?php esc_html_e( 'Redesign', 'gwill-starter' ); ?></option>
					<option value="SEO"><?php esc_html_e( 'SEO', 'gwill-starter' ); ?></option>
					<option value="Support Retainer"><?php esc_html_e( 'Support Retainer', 'gwill-starter' ); ?></option>
					<option value="Other"><?php esc_html_e( 'Other', 'gwill-starter' ); ?></option>
				</select>
			</div>

			<fieldset class="gwill-form__field gwill-form__fieldset">
				<legend><?php esc_html_e( 'Project scope', 'gwill-starter' ); ?></legend>
				<div class="gwill-form__radio-group">
					<?php
					$scopes = [
						'Small'  => __( 'Small', 'gwill-starter' ),
						'Medium' => __( 'Medium', 'gwill-starter' ),
						'Large'  => __( 'Large', 'gwill-starter' ),
					];
					foreach ( $scopes as $val => $label ) :
						$rid = 'scope_' . sanitize_key( $val ) . '_' . $uid;
					?>
					<label class="gwill-form__radio-label" for="<?php echo esc_attr( $rid ); ?>">
						<input
							type="radio"
							id="<?php echo esc_attr( $rid ); ?>"
							name="gwill_scope"
							value="<?php echo esc_attr( $val ); ?>"
						>
						<?php echo esc_html( $label ); ?>
					</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<div class="gwill-form__step-nav">
				<button type="button" class="gwill-form__btn-next" data-next>
					<?php esc_html_e( 'Next →', 'gwill-starter' ); ?>
				</button>
			</div>
		</div>

		<?php /* ── Step 2: Timeline + Budget ── */ ?>
		<div class="gwill-form__step">
			<h3 class="gwill-form__step-heading"><?php esc_html_e( 'Timeline &amp; budget', 'gwill-starter' ); ?></h3>

			<div class="gwill-form__field">
				<label for="timeline_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Target timeline', 'gwill-starter' ); ?></label>
				<select id="timeline_<?php echo esc_attr( $uid ); ?>" name="gwill_timeline">
					<option value=""><?php esc_html_e( 'Select…', 'gwill-starter' ); ?></option>
					<option value="ASAP"><?php esc_html_e( 'ASAP', 'gwill-starter' ); ?></option>
					<option value="1-3 months"><?php esc_html_e( '1–3 months', 'gwill-starter' ); ?></option>
					<option value="3-6 months"><?php esc_html_e( '3–6 months', 'gwill-starter' ); ?></option>
					<option value="6+ months"><?php esc_html_e( '6+ months', 'gwill-starter' ); ?></option>
					<option value="Exploring"><?php esc_html_e( 'Just exploring', 'gwill-starter' ); ?></option>
				</select>
			</div>

			<div class="gwill-form__field">
				<label for="budget_<?php echo esc_attr( $uid ); ?>">
					<?php esc_html_e( 'Budget range', 'gwill-starter' ); ?>
					<span class="gwill-form__required" aria-hidden="true">*</span>
				</label>
				<select id="budget_<?php echo esc_attr( $uid ); ?>" name="gwill_budget" required>
					<option value=""><?php esc_html_e( 'Select…', 'gwill-starter' ); ?></option>
					<option value="Under $500"><?php esc_html_e( 'Under $500', 'gwill-starter' ); ?></option>
					<option value="$500-$2k"><?php esc_html_e( '$500–$2k', 'gwill-starter' ); ?></option>
					<option value="$2k-$5k"><?php esc_html_e( '$2k–$5k', 'gwill-starter' ); ?></option>
					<option value="$5k-$10k"><?php esc_html_e( '$5k–$10k', 'gwill-starter' ); ?></option>
					<option value="$10k+"><?php esc_html_e( '$10k+', 'gwill-starter' ); ?></option>
				</select>
			</div>

			<div class="gwill-form__step-nav">
				<button type="button" class="gwill-form__btn-back" data-back><?php esc_html_e( '← Back', 'gwill-starter' ); ?></button>
				<button type="button" class="gwill-form__btn-next" data-next><?php esc_html_e( 'Next →', 'gwill-starter' ); ?></button>
			</div>
		</div>

		<?php /* ── Step 3: Contact details ── */ ?>
		<div class="gwill-form__step">
			<h3 class="gwill-form__step-heading"><?php esc_html_e( 'About you', 'gwill-starter' ); ?></h3>

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
				<label for="company_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Company or website (optional)', 'gwill-starter' ); ?></label>
				<input type="text" id="company_<?php echo esc_attr( $uid ); ?>" name="gwill_company" autocomplete="organization">
			</div>

			<div class="gwill-form__field">
				<label for="referral_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'How did you find me? (optional)', 'gwill-starter' ); ?></label>
				<input type="text" id="referral_<?php echo esc_attr( $uid ); ?>" name="gwill_referral">
			</div>

			<div class="gwill-form__step-nav">
				<button type="button" class="gwill-form__btn-back" data-back><?php esc_html_e( '← Back', 'gwill-starter' ); ?></button>
				<button type="button" class="gwill-form__btn-next" data-next><?php esc_html_e( 'Next →', 'gwill-starter' ); ?></button>
			</div>
		</div>

		<?php /* ── Step 4: Description + Submit ── */ ?>
		<div class="gwill-form__step">
			<h3 class="gwill-form__step-heading"><?php esc_html_e( 'Tell me more', 'gwill-starter' ); ?></h3>

			<div class="gwill-form__field">
				<label for="description_<?php echo esc_attr( $uid ); ?>">
					<?php esc_html_e( 'Project description', 'gwill-starter' ); ?>
					<span class="gwill-form__required" aria-hidden="true">*</span>
				</label>
				<textarea id="description_<?php echo esc_attr( $uid ); ?>" name="gwill_description" rows="6" required></textarea>
			</div>

			<div class="gwill-form__step-nav">
				<button type="button" class="gwill-form__btn-back" data-back><?php esc_html_e( '← Back', 'gwill-starter' ); ?></button>

				<button type="submit" class="gwill-form__submit">
					<span class="gwill-form__submit-text"><?php esc_html_e( 'Submit Request', 'gwill-starter' ); ?></span>
					<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Sending…', 'gwill-starter' ); ?></span>
				</button>
			</div>
		</div>

		<div class="gwill-form__status" role="alert" aria-live="polite"></div>
	</form>
</div>
