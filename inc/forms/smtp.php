<?php
/**
 * SMTP Configuration — GWill Starter
 *
 * Handles PHPMailer SMTP injection for wp_mail().
 * Only runs when GWILL_SMTP_HOST is defined.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inject SMTP credentials into PHPMailer before every wp_mail() call.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer The PHPMailer instance passed by reference.
 * @since 1.0.20
 */
function gwill_configure_smtp( $phpmailer ): void {
	if ( ! defined( 'GWILL_SMTP_HOST' ) ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = GWILL_SMTP_HOST;
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Port       = defined( 'GWILL_SMTP_PORT' ) ? (int) GWILL_SMTP_PORT : 587;
	$phpmailer->Username   = defined( 'GWILL_SMTP_USER' ) ? GWILL_SMTP_USER : '';
	$phpmailer->Password   = defined( 'GWILL_SMTP_PASS' ) ? GWILL_SMTP_PASS : '';
	$phpmailer->SMTPSecure = ( 465 === (int) $phpmailer->Port ) ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

	if ( defined( 'GWILL_FROM_EMAIL' ) && is_email( GWILL_FROM_EMAIL ) ) {
		// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- PHPMailer method
		$phpmailer->setFrom( GWILL_FROM_EMAIL, defined( 'GWILL_FROM_NAME' ) ? GWILL_FROM_NAME : '' );
	}
}
add_action( 'phpmailer_init', 'gwill_configure_smtp' );

/**
 * Replace "WordPress" sender name with the site name (or GWILL_FROM_NAME constant).
 *
 * Fires for every wp_mail() call — SMTP or server mail. Without this, the
 * default WordPress core value of "WordPress" appears in the inbox From field.
 */
add_filter( 'wp_mail_from_name', function ( string $name ): string {
	return defined( 'GWILL_FROM_NAME' ) ? GWILL_FROM_NAME : get_bloginfo( 'name' );
} );

/**
 * Override the From address only when GWILL_FROM_EMAIL is explicitly set.
 *
 * Without the constant, the server default (wordpress@domain.com) is preserved
 * so we do not break any existing delivery path.
 */
add_filter( 'wp_mail_from', function ( string $email ): string {
	if ( defined( 'GWILL_FROM_EMAIL' ) && is_email( GWILL_FROM_EMAIL ) ) {
		return GWILL_FROM_EMAIL;
	}
	return $email;
} );