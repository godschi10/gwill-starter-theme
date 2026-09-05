<?php
/**
 * Email Building - GWill Starter
 *
 * Functions for building email subjects, headers, and HTML bodies.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// Email building
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Human-readable labels for gwill_* field names used in email body output.
 *
 * Filterable per project.
 *
 * @return array<string,string>
 * @since  1.0.20
 */
function gwill_get_field_labels(): array {
	return apply_filters( 'gwill_field_labels', [
		'gwill_name'            => __( 'Name',                       'gwill-starter' ),
		'gwill_first_name'      => __( 'First Name',                 'gwill-starter' ),
		'gwill_email'           => __( 'Email',                      'gwill-starter' ),
		'gwill_company'         => __( 'Company / Website',          'gwill-starter' ),
		'gwill_message'         => __( 'Message',                    'gwill-starter' ),
		'gwill_service_type'    => __( 'Service Type',               'gwill-starter' ),
		'gwill_scope'           => __( 'Project Scope',              'gwill-starter' ),
		'gwill_timeline'        => __( 'Timeline',                   'gwill-starter' ),
		'gwill_budget'          => __( 'Budget',                     'gwill-starter' ),
		'gwill_description'     => __( 'Project Description',        'gwill-starter' ),
		'gwill_ask'             => __( 'What do you need help with?', 'gwill-starter' ),
		'gwill_inquiry_type'    => __( 'Inquiry Type',               'gwill-starter' ),
		'gwill_referral'        => __( 'How did you find me?',       'gwill-starter' ),
		'gwill_brand'           => __( 'Brand / Company',            'gwill-starter' ),
		'gwill_brand_url'       => __( 'Brand Website',              'gwill-starter' ),
		'gwill_campaign_type'   => __( 'Campaign Type',              'gwill-starter' ),
		'gwill_campaign_goal'   => __( 'Campaign Goal',              'gwill-starter' ),
		'gwill_audience_fit'    => __( 'Audience Fit',               'gwill-starter' ),
		'gwill_site_url'        => __( 'Current Website',            'gwill-starter' ),
		'gwill_project'         => __( 'What are you working on?',   'gwill-starter' ),
		'gwill_revenue'         => __( 'Current Revenue',            'gwill-starter' ),
		'gwill_outcome'         => __( 'Desired Outcome',            'gwill-starter' ),
		'gwill_why_now'         => __( 'Why Now?',                   'gwill-starter' ),
		'gwill_response'        => __( 'Response',                   'gwill-starter' ),
		'gwill_feedback'        => __( 'Feedback',                   'gwill-starter' ),
		'gwill_source_post'     => __( 'Post',                       'gwill-starter' ),
	] );
}

/**
 * Build a branded HTML email body from sanitised fields.
 *
 * Structure:
 *   - Dark #111111 header: site icon (if set) + site name
 *   - One card per field: label in small-caps, value in left-bordered block
 *   - Submission metadata (timestamp + referring page)
 *   - Light footer: "Sent via [site name] contact form"
 *
 * Site icon is fetched via get_site_icon_url(64) - the image set under
 * Appearance → Customize → Site Identity. Falls back to text-only header
 * silently if no icon is uploaded.
 *
 * Skips internal meta fields (form_id, nonce, honeypot) so they never
 * appear in the notification email. Textarea values use nl2br() to
 * preserve line breaks in HTML output.
 *
 * @param  array  $fields Sanitised form fields.
 * @return string         HTML email body.
 * @since  1.0.41
 */
function gwill_build_email_body( array $fields ): string {
	$labels    = gwill_get_field_labels();
	$skip      = [ 'gwill_form_id', 'gwill_nonce', 'gwill_hp' ];
	$site_name = esc_html( get_bloginfo( 'name' ) );
	$icon_url  = get_site_icon_url( 64 );

	// Inline styles only - email clients strip <style> blocks.
	$html  = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $site_name . '</title></head>';
	$html .= '<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Helvetica,Arial,sans-serif;">';

	$html .= '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:32px 16px;">';
	$html .= '<table width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="max-width:600px;width:100%;">';

	// Header strip
	$html .= '<tr><td style="background:#111111;padding:28px 32px;border-radius:8px 8px 0 0;">';
	if ( $icon_url ) {
		$html .= '<img src="' . esc_url( $icon_url ) . '" width="40" height="40" alt="" style="display:block;margin-bottom:14px;border-radius:6px;">';
	}
	$html .= '<span style="color:#ffffff;font-size:17px;font-weight:700;letter-spacing:-0.01em;">' . $site_name . '</span>';
	$html .= '</td></tr>';

	// Fields
	$html .= '<tr><td style="background:#ffffff;padding:32px;">';

	foreach ( $fields as $key => $value ) {
		if ( empty( $value ) || ! str_starts_with( $key, 'gwill_' ) || in_array( $key, $skip, true ) ) {
			continue;
		}
		$label = $labels[ $key ] ?? ucwords( str_replace( [ 'gwill_', '_' ], [ '', ' ' ], $key ) );
		$value = nl2br( esc_html( (string) $value ) );

		$html .= '<div style="margin-bottom:22px;">';
		$html .= '<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888888;margin-bottom:7px;">' . esc_html( $label ) . '</div>';
		$html .= '<div style="border-left:3px solid #111111;padding-left:14px;font-size:15px;color:#333333;line-height:1.65;">' . $value . '</div>';
		$html .= '</div>';
	}

	// Metadata
	$html .= '<div style="margin-top:28px;padding-top:20px;border-top:1px solid #eeeeee;font-size:12px;color:#aaaaaa;line-height:1.6;">';
	$html .= 'Submitted: ' . esc_html( gmdate( 'Y-m-d H:i:s' ) . ' UTC' );
	if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$html .= '<br>Page: ' . esc_html( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
	}
	$html .= '</div>';

	$html .= '</td></tr>';

	// Footer
	$html .= '<tr><td style="background:#f4f4f5;padding:16px 32px;border-radius:0 0 8px 8px;font-size:12px;color:#aaaaaa;text-align:center;">';
	/* translators: %s: site name */
	$html .= sprintf( esc_html__( 'Sent via %s contact form', 'gwill-starter' ), $site_name );
	$html .= '</td></tr>';

	$html .= '</table></td></tr></table></body></html>';

	return $html;
}

/**
 * Build the email subject line for a given form type.
 *
 * Filterable. Override per project via 'gwill_form_subjects':
 *
 *   add_filter( 'gwill_form_subjects', function( $map ) {
 *       $map['inquiry'] = '[' . get_bloginfo('name') . '] New Inquiry';
 *       return $map;
 *   } );
 *
 * @param  string $form_id Submitted form identifier.
 * @param  array  $fields  Sanitised form fields.
 * @return string          Email subject line.
 * @since  1.0.20
 */
function gwill_build_subject( string $form_id, array $fields ): string {
	$site = get_bloginfo( 'name' );
	$name = $fields['gwill_name'] ?? ( $fields['gwill_first_name'] ?? __( 'Someone', 'gwill-starter' ) );

	$subjects = apply_filters( 'gwill_form_subjects', [
		/* translators: 1: site name, 2: submitter name */
		'simple'      => sprintf( '[%1$s] Contact from %2$s', $site, $name ),
		'inquiry'     => sprintf( '[%s] %s Inquiry: %s', $site, $fields['gwill_service_type'] ?? 'Project', $name ),
		'routed'      => sprintf( '[%s] %s: %s', $site, $fields['gwill_inquiry_type'] ?? 'Contact', $name ),
		'multistep'   => sprintf( '[%s] Quote Request from %s', $site, $name ),
		'inline'      => sprintf( '[%s] Quick Message from %s', $site, $name ),
		'sidebar'     => sprintf( '[%s] Sidebar Inquiry: %s', $site, $name ),
		'exit_intent' => sprintf( '[%s] New Subscriber: %s', $site, $name ),
		'application' => sprintf( '[%s] Application: %s', $site, $name ),
		'partnership' => sprintf( '[%s] Partnership: %s', $site, $fields['gwill_brand'] ?? $name ),
		'feedback'    => sprintf( '[%s] Post Feedback', $site ),
	] );

	return $subjects[ $form_id ] ?? sprintf( '[%s] Form Submission: %s', $site, $name );
}

/**
 * Build headers array for wp_mail() with Reply-To set to the submitter.
 *
 * @param  array    $fields Sanitised form fields.
 * @return string[]         Headers array.
 * @since  1.0.20
 */
function gwill_build_email_headers( array $fields ): array {
	$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

	if ( ! empty( $fields['gwill_email'] ) && is_email( $fields['gwill_email'] ) ) {
		$name      = $fields['gwill_name'] ?? ( $fields['gwill_first_name'] ?? '' );
		$reply_to  = $name ? "{$name} <{$fields['gwill_email']}>" : $fields['gwill_email'];
		$headers[] = 'Reply-To: ' . $reply_to;
	}

	return $headers;
}