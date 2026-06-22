<?php
/**
 * Form Partial: Inline Post Form (Pattern 5)
 *
 * A compact two-field form embedded within post content or at the bottom
 * of single.php. Converts readers while intent is highest.
 *
 * Usage: gwill_part( 'forms/contact-inline' );
 *        Add to single.php after the_content().
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'gwill-forms' );

$uid = wp_unique_id( 'gwill-inline-' );
?>

<aside class="gwill-inline-cta">
	<p class="gwill-inline-cta__prompt">
		<?php esc_html_e( 'Want me to build this for you?', 'gwill-starter' ); ?>
	</p>

	<form
		class="gwill-form gwill-form--inline"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		novalidate
	>
		<div class="gwill-honey" aria-hidden="true">
			<label for="gwill_hp_<?php echo esc_attr( $uid ); ?>">Leave this blank</label>
			<input type="text" name="gwill_hp" id="gwill_hp_<?php echo esc_attr( $uid ); ?>" tabindex="-1" autocomplete="off">
		</div>

		<input type="hidden" name="action"        value="gwill_contact_form">
		<input type="hidden" name="gwill_form_id" value="inline">
		<?php
		if ( is_singular() ) {
			// Attach the post title to the email subject so you know which post triggered the lead.
			echo '<input type="hidden" name="gwill_source_post" value="' . esc_attr( get_the_title() ) . '">';
		}
		?>

		<div class="gwill-form--inline__fields">
			<div class="gwill-form__field">
				<label for="ask_<?php echo esc_attr( $uid ); ?>" class="screen-reader-text">
					<?php esc_html_e( 'What do you need help with?', 'gwill-starter' ); ?>
				</label>
				<input
					type="text"
					id="ask_<?php echo esc_attr( $uid ); ?>"
					name="gwill_ask"
					placeholder="<?php esc_attr_e( 'What do you need help with?', 'gwill-starter' ); ?>"
					required
				>
			</div>

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
				<span class="gwill-form__submit-text"><?php esc_html_e( "Let's talk", 'gwill-starter' ); ?></span>
				<span class="gwill-form__submit-loading" aria-hidden="true"><?php esc_html_e( 'Sending…', 'gwill-starter' ); ?></span>
			</button>
		</div>

		<div class="gwill-form__status" role="alert" aria-live="polite"></div>
	</form>
</aside>
