<?php
/**
 * Form Partial: Partnership / Brand Deal (Pattern 9)
 *
 * Structured intake for sponsorship and collaboration requests.
 * The audience-fit question screens brands that haven't done their homework.
 *
 * Usage: gwill_part( 'forms/contact-partnership' );
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-partner-' );
?>

<form
	class="gwill-form gwill-form--partnership"
	method="post"
	action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	novalidate
>
	<div class="gwill-honey" aria-hidden="true">
		<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
		<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
	</div>

	<input type="hidden" name="action"        value="gwill_contact_form">
	<input type="hidden" name="gwill_form_id" value="partnership">

	<div class="gwill-form__row gwill-form__row--2col">
		<div class="gwill-form__field">
			<label for="name_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'Your name', 'gwill-starter' ); ?>
				<span class="gwill-form__required" aria-hidden="true">*</span>
			</label>
			<input type="text" id="name_<?php echo esc_attr( $uid ); ?>" name="gwill_name" required autocomplete="name">
		</div>
		<div class="gwill-form__field">
			<label for="brand_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'Brand / company', 'gwill-starter' ); ?>
				<span class="gwill-form__required" aria-hidden="true">*</span>
			</label>
			<input type="text" id="brand_<?php echo esc_attr( $uid ); ?>" name="gwill_brand" required>
		</div>
	</div>

	<div class="gwill-form__row gwill-form__row--2col">
		<div class="gwill-form__field">
			<label for="brand_url_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Brand website', 'gwill-starter' ); ?></label>
			<input type="url" id="brand_url_<?php echo esc_attr( $uid ); ?>" name="gwill_brand_url" placeholder="https://example.com">
		</div>
		<div class="gwill-form__field">
			<label for="email_<?php echo esc_attr( $uid ); ?>">
				<?php esc_html_e( 'Your email', 'gwill-starter' ); ?>
				<span class="gwill-form__required" aria-hidden="true">*</span>
			</label>
			<input type="email" id="email_<?php echo esc_attr( $uid ); ?>" name="gwill_email" required autocomplete="email">
		</div>
	</div>

	<div class="gwill-form__field">
		<label for="campaign_type_<?php echo esc_attr( $uid ); ?>">
			<?php esc_html_e( 'Campaign type', 'gwill-starter' ); ?>
			<span class="gwill-form__required" aria-hidden="true">*</span>
		</label>
		<select id="campaign_type_<?php echo esc_attr( $uid ); ?>" name="gwill_campaign_type" required>
			<option value=""><?php esc_html_e( 'Choose…', 'gwill-starter' ); ?></option>
			<option value="Sponsored Post"><?php esc_html_e( 'Sponsored Post', 'gwill-starter' ); ?></option>
			<option value="Newsletter Mention"><?php esc_html_e( 'Newsletter Mention', 'gwill-starter' ); ?></option>
			<option value="Social Media"><?php esc_html_e( 'Social Media', 'gwill-starter' ); ?></option>
			<option value="Product Review"><?php esc_html_e( 'Product Review', 'gwill-starter' ); ?></option>
			<option value="Long-term Partnership"><?php esc_html_e( 'Long-term Partnership', 'gwill-starter' ); ?></option>
			<option value="Other"><?php esc_html_e( 'Other', 'gwill-starter' ); ?></option>
		</select>
	</div>

	<div class="gwill-form__field">
		<label for="campaign_goal_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Campaign goal', 'gwill-starter' ); ?></label>
		<textarea id="campaign_goal_<?php echo esc_attr( $uid ); ?>" name="gwill_campaign_goal" rows="3"
			placeholder="<?php esc_attr_e( 'What are you trying to achieve?', 'gwill-starter' ); ?>"></textarea>
	</div>

	<div class="gwill-form__field">
		<label for="audience_fit_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Why is my audience a match?', 'gwill-starter' ); ?></label>
		<textarea id="audience_fit_<?php echo esc_attr( $uid ); ?>" name="gwill_audience_fit" rows="3"
			placeholder="<?php esc_attr_e( 'Why does your product fit my readers specifically?', 'gwill-starter' ); ?>"></textarea>
	</div>

	<div class="gwill-form__field">
		<label for="budget_<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Campaign budget', 'gwill-starter' ); ?></label>
		<select id="budget_<?php echo esc_attr( $uid ); ?>" name="gwill_budget">
			<option value=""><?php esc_html_e( 'Select…', 'gwill-starter' ); ?></option>
			<option value="Under $500"><?php esc_html_e( 'Under $500 (gifted-product-only)', 'gwill-starter' ); ?></option>
			<option value="$500-$2k"><?php esc_html_e( '$500–$2k', 'gwill-starter' ); ?></option>
			<option value="$2k-$10k"><?php esc_html_e( '$2k–$10k', 'gwill-starter' ); ?></option>
			<option value="Custom"><?php esc_html_e( "Custom \u{2014} let's discuss", 'gwill-starter' ); ?></option>
		</select>
	</div>

	<div class="gwill-form__actions">
		<button type="submit" class="gwill-form__submit">
			<span class="gwill-form__submit-text"><?php esc_html_e( 'Send Partnership Inquiry', 'gwill-starter' ); ?></span>
			<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Sending…', 'gwill-starter' ); ?></span>
		</button>
	</div>

	<div class="gwill-form__status" role="alert" aria-live="polite"></div>
</form>
