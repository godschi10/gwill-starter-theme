# Email Setup

Contact form notifications use `wp_mail()`. On most cPanel/LiteSpeed hosts,
`wp_mail()` works out of the box via Exim (the server's built-in mail agent)
with zero configuration. Start there — only add SMTP if delivery is actually
failing.

---

## Step 1 — Test server mail first

1. Publish any page using a contact form template.
2. Submit a test message to yourself.
3. Check your inbox (and spam folder).

If the email arrives: you're done. No SMTP needed.

If it doesn't arrive after a few minutes, check your spam folder and
confirm your hosting provider hasn't blocked outbound port 25. Only then
move to SMTP.

---

## Step 2 — Add SMTP (only if server mail fails)

SMTP guarantees delivery from a trusted sending domain. Add constants to
`wp-config.php` above the `/* That's all, stop editing! */` line.

### Required

```php
define( 'GWILL_SMTP_HOST',  'smtp.your-provider.com' );
define( 'GWILL_SMTP_PORT',  587 );               // 587 (TLS) or 465 (SSL)
define( 'GWILL_SMTP_USER',  'you@yourdomain.com' );
define( 'GWILL_SMTP_PASS',  'your-smtp-password' );
define( 'GWILL_SMTP_FROM',  'you@yourdomain.com' ); // must match SMTP account
```

### Optional

```php
define( 'GWILL_FROM_NAME',  'Your Site Name' );  // default: get_bloginfo('name')
define( 'GWILL_FROM_EMAIL', 'you@yourdomain.com' ); // overrides wp_mail_from filter
```

`GWILL_FROM_NAME` and `GWILL_FROM_EMAIL` apply whether SMTP is active or not.
Without them, the sender name defaults to the WordPress site title and the
From address defaults to `wordpress@yourdomain.com`.

### SMTP providers

Any provider with SMTP credentials works. Common free-tier options:

| Provider       | Free limit       | Notes                               |
| -------------- | ---------------- | ----------------------------------- |
| Brevo          | 300 emails/day   | Needs a separate SMTP key (not API key) |
| Mailgun        | 1,000/month      | Requires DNS records                |
| SendGrid       | 100/day          | Requires sender verification        |
| Gmail (App PW) | Personal use     | Requires 2FA + App Password enabled |
| cPanel SMTP    | Varies by host   | Same server — no external account needed |

For Brevo specifically: the SMTP password is the **SMTP key** found under
SMTP & API → SMTP tab. It starts with `xsmtpsib-`. The API key (`xkeysib-`)
does not work for SMTP.

---

## How it works

The theme sets the sender name via `wp_mail_from_name` filter and optionally
overrides the From address via `wp_mail_from` filter. These run for all
`wp_mail()` calls regardless of SMTP.

If the GWILL_SMTP_* constants are defined, `inc/forms.php` hooks into
`phpmailer_init` to configure PHPMailer for SMTP delivery.

---

## Email format

Notifications are sent as branded HTML emails:

- **Header**: Dark `#111111` strip — site icon (if set) + site name.
  Upload a site icon under Appearance → Customize → Site Identity.
- **Body**: One card per field — label in small-caps, value left-bordered.
- **Footer**: "Sent via [site name] contact form."

---

## Nonce handling

Form nonces are fetched fresh at submit time via a REST endpoint
(`/wp-json/gwill/v1/form-nonce`) rather than being baked into page HTML.
This means LiteSpeed can cache contact pages indefinitely without causing
stale nonce failures for real visitors.
