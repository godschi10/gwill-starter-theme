<?php
/**
 * Contact Forms  -  GWill Starter
 *
 * Handles all contact form submissions via a single WordPress AJAX action.
 * Every form type in template-parts/forms/ routes through gwill_handle_contact_form().
 * The 'gwill_form_id' hidden field tells the handler which form was submitted.
 *
 * ── CONFIGURATION (add to wp-config.php  -  all constants are optional) ─────────
 *
 *   // SMTP relay  -  replaces wp_mail()'s default PHP mail()
 *   define( 'GWILL_SMTP_HOST',  'smtp-relay.brevo.com' );  // hostname
 *   define( 'GWILL_SMTP_PORT',  587 );                    // 587=TLS, 465=SSL
 *   define( 'GWILL_SMTP_USER',  'your@email.com' );       // username
 *   define( 'GWILL_SMTP_PASS',  'your-app-password' );    // password
 *
 *   // SECURITY: the constants above are readable by any PHP code running
 *   // in the same process as WordPress  -  any plugin, any theme file, any
 *   // compromised dependency. This is the standard, accepted WordPress
 *   // pattern (no better built-in alternative exists for theme-level
 *   // config), but it's worth confirming wp-config.php itself isn't
 *   // directly reachable over HTTP on your host, and isn't exposed via
 *   // phpinfo() if that function happens to be enabled anywhere on the
 *   // same server (shared hosting specifically).
 *
 *   // Sender identity shown in inbox
 *   define( 'GWILL_FROM_EMAIL', 'noreply@yoursite.com' );
 *   define( 'GWILL_FROM_NAME',  'Your Site' );
 *
 *   // Recipient (falls back to WP admin email)
 *   define( 'GWILL_TO_EMAIL', 'you@yoursite.com' );
 *
 *   // Optional feature flags (default false)
 *   define( 'GWILL_AUTOREPLY',  true );  // send confirmation to submitter
 *   define( 'GWILL_LOG_FORMS',  true );  // log to {prefix}gwill_form_submissions
 *
 *   // Newsletter signup ('newsletter' form pattern)  -  Brevo CONTACTS API,
 *   // NOT the SMTP credentials above. Brevo issues these as two entirely
 *   // separate secrets from the same dashboard section (Settings → SMTP
 *   // & API): an SMTP key for sending mail, and an API key for REST calls
 *   // like adding a contact to a list. The SMTP password above will not
 *   // authenticate API requests  -  this needs its own key.
 *   //
 *   // REQUIRED API KEY SCOPE: "Contacts:Write" (or "Full Access" if granular
 *   // scopes unavailable). The key MUST be created in:
 *   //   Settings → SMTP & API → API Keys tab
 *   // NOT the SMTP tab. The SMTP key (used for GWILL_SMTP_PASS) will NOT
 *   // work for the Contacts API  -  they are different credentials with
 *   // different permissions, even though they're generated from the same
 *   // dashboard section. This is Brevo's design, not a limitation of this
 *   // integration.
 *   define( 'GWILL_BREVO_API_KEY',  'xkeysib-...' );  // Settings → SMTP & API → API Keys
 *   define( 'GWILL_BREVO_LIST_ID',  2 );              // Contacts → Lists → the list's numeric ID
 *
 * ── DB TABLE (create manually when GWILL_LOG_FORMS is true) ──────────────────
 *
 *   CREATE TABLE {prefix}gwill_form_submissions (
 *       id         BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *       form_id    VARCHAR(50)  NOT NULL,
 *       email      VARCHAR(200) NOT NULL,
 *       fields     LONGTEXT     NOT NULL,
 *       ip_hash    VARCHAR(64)  NOT NULL,
 *       status     VARCHAR(20)  NOT NULL DEFAULT 'new',
 *       created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *       KEY form_id (form_id),
 *       KEY created_at (created_at)
 *   );
 *
 * @author  G-will Chijioke <hello@gwillchijioke.com>
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

// Load all form modules in dependency order
require_once get_template_directory() . '/inc/forms/smtp.php';        // SMTP configuration
require_once get_template_directory() . '/inc/forms/nonce.php';       // REST + admin-ajax nonce endpoints
require_once get_template_directory() . '/inc/forms/ajax.php';        // Main AJAX handler
require_once get_template_directory() . '/inc/forms/sanitize.php';    // Sanitisation & validation
require_once get_template_directory() . '/inc/forms/email.php';       // Email building (subject, headers, body)
require_once get_template_directory() . '/inc/forms/brevo.php';       // Newsletter signup (Brevo API)
require_once get_template_directory() . '/inc/forms/send.php';        // Email sending (contact + autoreply)
require_once get_template_directory() . '/inc/forms/routing.php';     // Inquiry type → recipient mapping
require_once get_template_directory() . '/inc/forms/spam.php';        // Honeypot, rate limiting, IP resolution