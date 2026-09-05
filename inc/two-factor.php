<?php

/*
Table of Contents
1. TOTP PRIMITIVES (RFC 6238 - HMAC-SHA1, 30s, 6 digits)
2. gwill_base32_decode
3. gwill_totp_code_at
4. gwill_totp_verify
5. gwill_2fa_new_secret
6. STATE HELPERS
7. gwill_2fa_secret_for
8. gwill_2fa_is_enabled
9. gwill_2fa_otpauth_uri
10. LOGIN ENFORCEMENT
11. gwill_2fa_last_error
12. gwill_2fa_authenticate
13. gwill_2fa_login_field
14. gwill_2fa_login_css
15. BACKUP CODES
16. gwill_2fa_generate_backup_codes
17. gwill_2fa_consume_backup_code
18. PROFILE PANEL (own profile - show_user_profile)
19. gwill_2fa_profile_panel
20. ADMIN FORCE-DISABLE (other users - edit_user_profile)
21. gwill_2fa_profile_panel_admin
22. SAVE HANDLERS
23. gwill_2fa_save_gate
24. gwill_2fa_submitted_code
25. gwill_2fa_save_own
26. gwill_2fa_save_admin
27. USERS-LIST COLUMN
28. gwill_2fa_users_column
29. gwill_2fa_users_column_value
*/

/**
 * GWill Starter - Two-Factor Authentication (TOTP), zero plugins.
 *
 * Ported from gwill-tech-theme v1.8.0 (live-proven on the tech site),
 * adapted to the starter dialect: text domain gwill-starter, the
 * starter's own security interplay (login_errors obfuscation at
 * inc/security.php:118 must keep 2FA guidance visible - patched there
 * in this release), and the login rate limiter that also ships in
 * this release (bad 2FA codes count against it by design).
 *
 * RFC 6238 TOTP: HMAC-SHA1, 30-second period, 6 digits, ±1 step drift
 * allowance. Backup codes: 10 single-use, wp_hash() digests (never
 * plaintext), shown exactly once in a 10-minute transient.
 *
 * @package GWill_Starter
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;

// ── 1. TOTP PRIMITIVES (RFC 6238 - HMAC-SHA1, 30s, 6 digits) ─────

// ── 2. gwill_base32_decode ────────────────────────────────
/**
 * Decode a base32 string (RFC 4648 alphabet, case-insensitive).
 *
 * @param string $b32 Base32 input (spaces/dashes tolerated).
 * @return string Binary key.
 */
function gwill_base32_decode( $b32 ) {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$b32      = strtoupper( preg_replace( '/[^A-Z2-7]/i', '', (string) $b32 ) );
	$bits     = '';
	foreach ( str_split( $b32 ) as $char ) {
		$pos = strpos( $alphabet, $char );
		if ( false === $pos ) {
			continue; // Invalid char - skip (lenient like most authenticators).
		}
		$bits .= str_pad( decbin( $pos ), 5, '0', STR_PAD_LEFT );
	}
	$out = '';
	foreach ( str_split( $bits, 8 ) as $chunk ) {
		if ( strlen( $chunk ) === 8 ) {
			$out .= chr( bindec( $chunk ) );
		}
	}
	return $out;
}

// ── 3. gwill_totp_code_at ─────────────────────────────────
/**
 * Compute the TOTP code for a secret at a given time.
 *
 * @param string   $secret Base32 secret.
 * @param int|null $time   Unix timestamp (default: now).
 * @return string 6-digit code (zero-padded).
 */
function gwill_totp_code_at( $secret, $time = null ) {
	$key     = gwill_base32_decode( $secret );
	$counter = pack( 'N*', 0 ) . pack( 'N*', (int) floor( ( null === $time ? time() : $time ) / 30 ) );
	$hash    = hash_hmac( 'sha1', $counter, $key, true );
	$offset  = ord( substr( $hash, -1 ) ) & 0x0F;
	$value   = ( ( ord( $hash[ $offset ] ) & 0x7F ) << 24 )
		| ( ( ord( $hash[ $offset + 1 ] ) & 0xFF ) << 16 )
		| ( ( ord( $hash[ $offset + 2 ] ) & 0xFF ) << 8 )
		| ( ord( $hash[ $offset + 3 ] ) & 0xFF );
	return str_pad( (string) ( $value % 1000000 ), 6, '0', STR_PAD_LEFT );
}

// ── 4. gwill_totp_verify ──────────────────────────────────
/**
 * Verify a submitted code against a secret with ±$window time-steps.
 *
 * @param string $secret Base32 secret.
 * @param string $code   Submitted code.
 * @param int    $window Drift allowance in 30s steps (default 1).
 * @return bool
 */
function gwill_totp_verify( $secret, $code, $window = 1 ) {
	$code = preg_replace( '/[^0-9]/', '', (string) $code );
	if ( 6 !== strlen( $code ) || '' === (string) $secret ) {
		return false;
	}
	$now = time();
	for ( $i = - (int) $window; $i <= (int) $window; $i++ ) {
		if ( hash_equals( gwill_totp_code_at( $secret, $now + $i * 30 ), $code ) ) {
			return true;
		}
	}
	return false;
}

// ── 5. gwill_2fa_new_secret ───────────────────────────────
/**
 * Generate a new 160-bit base32 secret (32 chars - Google Authenticator
 * compatible). 20 random bytes → 160 bits → exactly 32 base32 chars.
 *
 * @return string
 */
function gwill_2fa_new_secret() {
	$bytes = function_exists( 'random_bytes' ) ? random_bytes( 20 ) : wp_generate_password( 20, false, false );
	$bin   = '';
	foreach ( str_split( $bytes ) as $byte ) {
		$bin .= str_pad( decbin( ord( $byte ) ), 8, '0', STR_PAD_LEFT );
	}
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$secret   = '';
	foreach ( str_split( $bin, 5 ) as $chunk ) {
		$secret .= $alphabet[ bindec( str_pad( $chunk, 5, '0', STR_PAD_RIGHT ) ) ];
	}
	return $secret;
}

// ── 6. STATE HELPERS ─────────────────────────────────────────────

// ── 7. gwill_2fa_secret_for ───────────────────────────────
/**
 * The active TOTP secret for a user ('' when 2FA is off).
 *
 * @param int $user_id User ID.
 * @return string
 */
function gwill_2fa_secret_for( $user_id ) {
	return (string) get_user_meta( $user_id, 'gwill_2fa_secret', true );
}

// ── 8. gwill_2fa_is_enabled ───────────────────────────────
/**
 * Is two-factor enabled for a user?
 *
 * @param int $user_id User ID.
 * @return bool
 */
function gwill_2fa_is_enabled( $user_id ) {
	return '' !== gwill_2fa_secret_for( $user_id );
}

// ── 9. gwill_2fa_otpauth_uri ──────────────────────────────
/**
 * Build the otpauth:// provisioning URI (manual entry / authenticator scan).
 *
 * @param WP_User $user   User object.
 * @param string  $secret Base32 secret.
 * @return string
 */
function gwill_2fa_otpauth_uri( $user, $secret ) {
	$site = get_bloginfo( 'name' );
	return 'otpauth://totp/' . rawurlencode( $site . ':' . $user->user_login )
		. '?secret=' . rawurlencode( $secret )
		. '&issuer=' . rawurlencode( $site )
		. '&period=30&digits=6&algorithm=SHA1';
}

// ── 10. LOGIN ENFORCEMENT ─────────────────────────────────────────

// ── 11. gwill_2fa_last_error ───────────────────────────────
/**
 * Remember the last 2FA login error within this request, so the
 * login_errors obfuscation filter (inc/security.php) can keep 2FA
 * guidance visible while still suppressing every other error.
 *
 * @param string|null $set Error code to remember ('' to clear).
 * @return string|null Last remembered code.
 */
function gwill_2fa_last_error( $set = null ) {
	static $code = null;
	if ( null !== $set ) {
		$code = $set;
	}
	return $code;
}

// ── 12. gwill_2fa_authenticate ─────────────────────────────
/**
 * Require the authenticator code once the password checks out.
 *
 * Priority 30 - after wp_authenticate_username_password (20). A WP_Error
 * here triggers wp_login_failed, so bad codes ALSO count against the
 * login rate limiter in this release (5 failures / 15 min) - brute-forcing
 * the 6-digit space is throttled by design.
 *
 * @param mixed  $user     WP_User|WP_Error|null from earlier filters.
 * @param string $username Submitted login.
 * @param string $password Submitted password.
 * @return mixed
 */
function gwill_2fa_authenticate( $user, $username, $password ) {
	if ( ! $user instanceof WP_User ) {
		return $user; // Password already failed - don't stack errors.
	}
	if ( ! gwill_2fa_is_enabled( $user->ID ) ) {
		return $user; // 2FA not enabled on this account - pass through.
	}
	$code = isset( $_POST['gwill_2fa_code'] )
		? wp_unslash( $_POST['gwill_2fa_code'] ) // Raw - each verifier normalizes (TOTP digits / backup alnum).
		: '';
	if ( '' === $code ) {
		gwill_2fa_last_error( 'gwill_2fa_required' );
		return new WP_Error(
			'gwill_2fa_required',
			__( 'This account has two-factor authentication enabled. Enter the six-digit code from your authenticator app.', 'gwill-starter' )
		);
	}
	if ( gwill_totp_verify( gwill_2fa_secret_for( $user->ID ), $code ) ) {
		return $user;
	}
	if ( gwill_2fa_consume_backup_code( $user->ID, $code ) ) {
		return $user;
	}
	gwill_2fa_last_error( 'gwill_2fa_invalid' );
	return new WP_Error(
		'gwill_2fa_invalid',
		__( 'That authenticator code is invalid or expired. Try again, or use a backup code.', 'gwill-starter' )
	);
}
add_filter( 'authenticate', 'gwill_2fa_authenticate', 30, 3 );

// ── 13. gwill_2fa_login_field ─────────────────────────────
/**
 * Inject the authenticator-code field on the login form - only when the
 * submitted username actually has 2FA enabled (no field otherwise, so
 * non-2FA users see an untouched login form).
 */
function gwill_2fa_login_field() {
	if ( empty( $_POST['log'] ) ) {
		return;
	}
	$user = get_user_by( 'login', wp_unslash( $_POST['log'] ) );
	if ( ! $user || ! gwill_2fa_is_enabled( $user->ID ) ) {
		return;
	}
	?>
	<p class="gwill-2fa-row">
		<label for="gwill_2fa_code"><?php esc_html_e( 'Authenticator code', 'gwill-starter' ); ?></label>
		<input type="text" name="gwill_2fa_code" id="gwill_2fa_code" class="input"
			inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6"
			placeholder="000000" autofocus />
	</p>
	<?php
}
add_action( 'login_form', 'gwill_2fa_login_field' );

// ── 14. gwill_2fa_login_css ───────────────────────────────
/**
 * Minimal login-page styling for the 2FA row (core login look preserved).
 */
function gwill_2fa_login_css() {
	echo '<style>#loginform .gwill-2fa-row{margin-bottom:16px}.gwill-2fa-row input{letter-spacing:.35em;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}</style>';
}
add_action( 'login_head', 'gwill_2fa_login_css' );

// ── 15. BACKUP CODES ──────────────────────────────────────────────

// ── 16. gwill_2fa_generate_backup_codes ───────────────────
/**
 * Generate 10 single-use backup codes (8-char dash-groups). Stored as
 * wp_hash() digests (site-keyed, never plaintext); the plaintext is kept
 * in a 10-minute transient so it can be shown exactly ONCE.
 *
 * The digests are hashed over the NORMALIZED form (uppercase, no dashes)
 * so gwill_2fa_consume_backup_code() - which normalizes before hashing  - 
 * always matches, whether the user types the pretty dashes or not.
 *
 * @param int $user_id User ID.
 * @return array Plaintext codes (for the one-time display).
 */
function gwill_2fa_generate_backup_codes( $user_id ) {
	$codes      = array();
	$normalized = array();
	for ( $i = 0; $i < 10; $i++ ) {
		$raw          = strtoupper( wp_generate_password( 16, false, false ) );
		$codes[]     = substr( chunk_split( $raw, 4, '-' ), 0, -1 ); // ABCD-EFGH-IJKL-MNOP
		$normalized[] = $raw; // ABCDEFGHIJKLMNOP - the form verification hashes.
	}
	update_user_meta( $user_id, 'gwill_2fa_backup', array_map( 'wp_hash', $normalized ) );
	set_transient( 'gwill_2fa_codes_' . $user_id, $codes, 10 * MINUTE_IN_SECONDS );
	return $codes;
}

// ── 17. gwill_2fa_consume_backup_code ─────────────────────
/**
 * Consume a backup code (one-time). Returns true on first hash match and
 * permanently removes that digest from the store.
 *
 * @param int    $user_id User ID.
 * @param string $code    Submitted code (any formatting).
 * @return bool
 */
function gwill_2fa_consume_backup_code( $user_id, $code ) {
	$code = preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) $code ) ); // Uppercase FIRST - a lowercase strip would delete letters.
	if ( strlen( $code ) < 8 ) {
		return false;
	}
	$hashes = get_user_meta( $user_id, 'gwill_2fa_backup', true );
	if ( ! is_array( $hashes ) || empty( $hashes ) ) {
		return false;
	}
	$target = wp_hash( $code );
	foreach ( $hashes as $index => $hash ) {
		if ( hash_equals( $hash, $target ) ) {
			unset( $hashes[ $index ] );
			update_user_meta( $user_id, 'gwill_2fa_backup', array_values( $hashes ) );
			return true;
		}
	}
	return false;
}

// ── 18. PROFILE PANEL (own profile - show_user_profile) ───────────

// ── 19. gwill_2fa_profile_panel ───────────────────────────
/**
 * Render the two-factor panel on the user's OWN profile page.
 *
 * @param WP_User $user The profile being viewed.
 */
function gwill_2fa_profile_panel( $user ) {
	$uid     = (int) $user->ID;
	$enabled = gwill_2fa_is_enabled( $uid );
	$pending = (string) get_user_meta( $uid, 'gwill_2fa_pending', true );
	?>
	<h2><?php esc_html_e( 'Two-Factor Authentication', 'gwill-starter' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Status', 'gwill-starter' ); ?></th>
			<td>
				<?php if ( $enabled ) : ?>
					<span class="gwill-2fa-on"><?php esc_html_e( 'Enabled - an authenticator code is required at login.', 'gwill-starter' ); ?></span>
				<?php else : ?>
					<span class="gwill-2fa-off"><?php esc_html_e( 'Disabled - password-only login.', 'gwill-starter' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>

		<?php if ( ! $enabled && '' === $pending ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'gwill-starter' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="gwill_2fa_enable" value="1" />
						<?php esc_html_e( 'Enable two-factor authentication', 'gwill-starter' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'You will be guided through pairing an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password) after saving.', 'gwill-starter' ); ?>
					</p>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( '' !== $pending ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Pair your app', 'gwill-starter' ); ?></th>
				<td>
					<p class="description">
						<?php esc_html_e( 'Open your authenticator app and add this account manually (or scan via the URI). Then enter the current code below to activate.', 'gwill-starter' ); ?>
					</p>
					<p>
						<label for="gwill_2fa_uri"><strong><?php esc_html_e( 'Account URI', 'gwill-starter' ); ?></strong></label><br />
						<code id="gwill_2fa_uri" style="display:inline-block;max-width:100%;overflow-wrap:anywhere;margin:4px 0 8px;padding:6px 8px;background:#f0f0f1;border-radius:4px;"><?php echo esc_html( gwill_2fa_otpauth_uri( $user, $pending ) ); ?></code>
					</p>
					<p>
						<label for="gwill_2fa_pending_secret"><strong><?php esc_html_e( 'Manual entry key', 'gwill-starter' ); ?></strong></label><br />
						<code style="display:inline-block;letter-spacing:.1em;margin:4px 0 8px;padding:6px 8px;background:#f0f0f1;border-radius:4px;"><?php echo esc_html( $pending ); ?></code>
					</p>
					<p>
						<label for="gwill_2fa_code"><?php esc_html_e( 'Code from your app', 'gwill-starter' ); ?></label><br />
						<input type="text" name="gwill_2fa_code" id="gwill_2fa_code" class="regular-text"
							inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" />
					</p>
					<label style="display:block;margin:6px 0;">
						<input type="checkbox" name="gwill_2fa_cancel_pending" value="1" />
						<?php esc_html_e( 'Cancel - keep current setup as it is', 'gwill-starter' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Save your profile to activate. Codes rotate every 30 seconds.', 'gwill-starter' ); ?>
					</p>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( $enabled && '' === $pending ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Authenticator code', 'gwill-starter' ); ?></th>
				<td>
					<p class="description"><?php esc_html_e( 'To change or disable two-factor, enter your current code (or a backup code) and choose an action below.', 'gwill-starter' ); ?></p>
					<p>
						<input type="text" name="gwill_2fa_code" class="regular-text"
							inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" />
					</p>
					<label style="display:block;margin:6px 0;">
						<input type="checkbox" name="gwill_2fa_regenerate" value="1" />
						<?php esc_html_e( 'Regenerate secret (re-pair your app)', 'gwill-starter' ); ?>
					</label>
					<label style="display:block;margin:6px 0;">
						<input type="checkbox" name="gwill_2fa_disable" value="1" />
						<?php esc_html_e( 'Disable two-factor authentication', 'gwill-starter' ); ?>
					</label>
					<label style="display:block;margin:6px 0;">
						<input type="checkbox" name="gwill_2fa_new_codes" value="1" />
						<?php esc_html_e( 'Generate new backup codes (invalidates old ones)', 'gwill-starter' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Backup codes', 'gwill-starter' ); ?></th>
				<td>
					<?php
					$plain = get_transient( 'gwill_2fa_codes_' . $uid );
					if ( is_array( $plain ) ) :
						?>
						<p class="description" style="color:#b32d2e;">
							<?php esc_html_e( 'Store these now - they are shown once. Each code works exactly once, then is destroyed.', 'gwill-starter' ); ?>
						</p>
						<ol style="columns:2;max-width:480px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">
							<?php foreach ( $plain as $code ) : ?>
								<li><?php echo esc_html( $code ); ?></li>
							<?php endforeach; ?>
						</ol>
					<?php else : ?>
						<p class="description">
							<?php esc_html_e( 'Backup codes are only shown once at generation. Generate a new set to see fresh ones (old sets stop working).', 'gwill-starter' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>
	</table>
	<?php
	wp_nonce_field( 'gwill_2fa_profile', 'gwill_2fa_nonce' );
}
add_action( 'show_user_profile', 'gwill_2fa_profile_panel' );

// ── 20. ADMIN FORCE-DISABLE (other users - edit_user_profile) ─────

// ── 21. gwill_2fa_profile_panel_admin ─────────────────────
/**
 * Recovery path: an administrator can clear another user's 2FA from that
 * user's profile page (e.g. lost phone AND lost backup codes).
 *
 * @param WP_User $user The profile being viewed.
 */
function gwill_2fa_profile_panel_admin( $user ) {
	if ( get_current_user_id() === (int) $user->ID ) {
		return; // Own profile - handled by the panel above.
	}
	if ( ! current_user_can( 'edit_user', (int) $user->ID ) ) {
		return;
	}
	$uid     = (int) $user->ID;
	$enabled = gwill_2fa_is_enabled( $uid );
	?>
	<h2><?php esc_html_e( 'Two-Factor Authentication', 'gwill-starter' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Status', 'gwill-starter' ); ?></th>
			<td>
				<?php if ( $enabled ) : ?>
					<span class="gwill-2fa-on"><?php esc_html_e( 'Enabled - this user needs their authenticator at login.', 'gwill-starter' ); ?></span>
					<label style="display:block;margin-top:8px;color:#b32d2e;">
						<input type="checkbox" name="gwill_2fa_force_disable" value="1" />
						<?php esc_html_e( 'Force-disable two-factor for this user (recovery only)', 'gwill-starter' ); ?>
					</label>
				<?php else : ?>
					<span class="gwill-2fa-off"><?php esc_html_e( 'Disabled.', 'gwill-starter' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
	wp_nonce_field( 'gwill_2fa_profile', 'gwill_2fa_nonce' );
}
add_action( 'edit_user_profile', 'gwill_2fa_profile_panel_admin' );

// ── 22. SAVE HANDLERS ─────────────────────────────────────────────

// ── 23. gwill_2fa_save_gate ───────────────────────────────
/**
 * Verify the 2FA nonce + capability on profile saves.
 *
 * @param int $user_id User being saved.
 * @return bool
 */
function gwill_2fa_save_gate( $user_id ) {
	if ( ! current_user_can( 'edit_user', (int) $user_id ) ) {
		return false;
	}
	if ( ! isset( $_POST['gwill_2fa_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['gwill_2fa_nonce'] ), 'gwill_2fa_profile' ) ) {
		return false;
	}
	return true;
}

// ── 24. gwill_2fa_submitted_code ──────────────────────────
/**
 * Clean a submitted code - raw (wp_unslash only). Digits vs alphanumeric
 * normalization is owned by the verifiers: gwill_totp_verify() strips to
 * 6 digits, gwill_2fa_consume_backup_code() normalizes to uppercase
 * alnum. Stripping here would destroy alphanumeric backup codes.
 *
 * @return string
 */
function gwill_2fa_submitted_code() {
	return isset( $_POST['gwill_2fa_code'] )
		? wp_unslash( $_POST['gwill_2fa_code'] )
		: '';
}

// ── 25. gwill_2fa_save_own ────────────────────────────────
/**
 * Own-profile save: enable / activate / cancel / regenerate / disable.
 *
 * State machine: "pending" holds a secret awaiting app-pairing proof.
 *  - Enable     → write pending (no active secret yet).
 *  - Regenerate → write pending while KEEPING the active secret until
 *                 the new one is proven (no lockout window).
 *  - Activate   → pending verified → promoted to active + fresh backups.
 *  - Cancel     → pending dropped; active secret (if any) untouched.
 *  - Disable    → active secret removed (code or backup code required).
 *
 * @param int $user_id Current user ID.
 */
function gwill_2fa_save_own( $user_id ) {
	if ( ! gwill_2fa_save_gate( $user_id ) ) {
		return;
	}
	$pending = (string) get_user_meta( $user_id, 'gwill_2fa_pending', true );
	$enabled = gwill_2fa_is_enabled( $user_id );

	// Cancel a pending setup or re-pair.
	if ( '' !== $pending && ! empty( $_POST['gwill_2fa_cancel_pending'] ) ) {
		delete_user_meta( $user_id, 'gwill_2fa_pending' );
		return;
	}

	// Enable request (first-time): generate a pending secret.
	if ( ! $enabled && '' === $pending && ! empty( $_POST['gwill_2fa_enable'] ) ) {
		update_user_meta( $user_id, 'gwill_2fa_pending', gwill_2fa_new_secret() );
		return;
	}

	// Activate: code matches the pending secret → promote to active.
	// Runs for first-time enable AND re-pair (regenerate) alike.
	if ( '' !== $pending ) {
		$code = gwill_2fa_submitted_code();
		if ( gwill_totp_verify( $pending, $code ) ) {
			update_user_meta( $user_id, 'gwill_2fa_secret', $pending );
			delete_user_meta( $user_id, 'gwill_2fa_pending' );
			gwill_2fa_generate_backup_codes( $user_id );
		}
		return;
	}

	if ( ! $enabled ) {
		return;
	}

	// Regenerate request: current code (or backup) → new pending secret.
	// The active secret stays valid until the new one is activated above.
	if ( ! empty( $_POST['gwill_2fa_regenerate'] ) ) {
		$code = gwill_2fa_submitted_code();
		if ( gwill_totp_verify( gwill_2fa_secret_for( $user_id ), $code )
			|| gwill_2fa_consume_backup_code( $user_id, $code ) ) {
			update_user_meta( $user_id, 'gwill_2fa_pending', gwill_2fa_new_secret() );
		}
		return;
	}

	$code = gwill_2fa_submitted_code();
	$ok   = gwill_totp_verify( gwill_2fa_secret_for( $user_id ), $code )
		|| gwill_2fa_consume_backup_code( $user_id, $code );

	if ( ! $ok ) {
		return; // Invalid code - no action taken.
	}

	if ( ! empty( $_POST['gwill_2fa_disable'] ) ) {
		delete_user_meta( $user_id, 'gwill_2fa_secret' );
		delete_user_meta( $user_id, 'gwill_2fa_pending' );
		delete_user_meta( $user_id, 'gwill_2fa_backup' );
		delete_transient( 'gwill_2fa_codes_' . $user_id );
		return;
	}

	if ( ! empty( $_POST['gwill_2fa_new_codes'] ) ) {
		gwill_2fa_generate_backup_codes( $user_id );
	}
}
add_action( 'personal_options_update', 'gwill_2fa_save_own' );

// ── 26. gwill_2fa_save_admin ──────────────────────────────
/**
 * Admin save on another user: force-disable recovery.
 *
 * @param int $user_id Edited user ID.
 */
function gwill_2fa_save_admin( $user_id ) {
	if ( get_current_user_id() === (int) $user_id ) {
		return; // Own profile - handled by the own-save handler.
	}
	if ( ! gwill_2fa_save_gate( $user_id ) ) {
		return;
	}
	if ( empty( $_POST['gwill_2fa_force_disable'] ) ) {
		return;
	}
	delete_user_meta( $user_id, 'gwill_2fa_secret' );
	delete_user_meta( $user_id, 'gwill_2fa_pending' );
	delete_user_meta( $user_id, 'gwill_2fa_backup' );
	delete_transient( 'gwill_2fa_codes_' . $user_id );
}
add_action( 'edit_user_profile_update', 'gwill_2fa_save_admin' );

// ── 27. USERS-LIST COLUMN ─────────────────────────────────────────

// ── 28. gwill_2fa_users_column ────────────────────────────
/**
 * Add a "2FA" column to the Users list so admins see who is protected.
 *
 * @param array $columns Column map.
 * @return array
 */
function gwill_2fa_users_column( $columns ) {
	$columns['gwill_2fa'] = __( '2FA', 'gwill-starter' );
	return $columns;
}
add_filter( 'manage_users_columns', 'gwill_2fa_users_column' );

// ── 29. gwill_2fa_users_column_value ─────────────────────────
/**
 * Render the 2FA column value.
 *
 * @param string $output      Existing output.
 * @param string $column_name Column key.
 * @param int    $user_id     User ID.
 * @return string
 */
function gwill_2fa_users_column_value( $output, $column_name, $user_id ) {
	if ( 'gwill_2fa' !== $column_name ) {
		return $output;
	}
	return gwill_2fa_is_enabled( (int) $user_id )
		? '<span style="color:#00a32a;font-weight:600;">' . esc_html__( 'On', 'gwill-starter' ) . '</span>'
		: '<span style="color:#a7aaad;">' . esc_html__( 'Off', 'gwill-starter' ) . '</span>';
}
add_filter( 'manage_users_custom_column', 'gwill_2fa_users_column_value', 10, 3 );
