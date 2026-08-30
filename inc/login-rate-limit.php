<?php

/*
Table of Contents
1. gwill_login_rate_limit_max
2. gwill_login_rate_limit_window
3. gwill_login_client_ip
4. gwill_login_rate_key
5. gwill_count_login_failure
6. gwill_check_login_rate_limit
*/

/**
 * GWill Starter — Login attempt rate limiting (transient-based).
 *
 * Ported from gwill-tech-theme v1.16.41 (live-proven on the tech site),
 * the companion to two-factor.php: a failed 2FA code triggers
 * wp_login_failed, so brute-forcing the 6-digit space is throttled by
 * the same lockout (5 failures / 15 minutes by default).
 *
 * Architecture mirrors the contact form's rate limiter: N failed logins
 * per IP within a rolling window trips a lockout; the counter auto-resets
 * once the window passes without further failures. Keys are SHA-256
 * hashes of the client IP (never the raw IP).
 *
 * IP resolution reuses gwill_get_client_ip() from inc/forms/spam.php when
 * loaded (REMOTE_ADDR by default; proxy headers only on explicit opt-in
 * + Cloudflare-range validation — see that file). The function_exists
 * guard covers the load-order gap (security.php loads before forms.php).
 *
 * @package GWill_Starter
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_login_failed', 'gwill_count_login_failure' );
add_filter( 'authenticate', 'gwill_check_login_rate_limit', 9, 3 );

// ── 1. gwill_login_rate_limit_max ─────────────────────────
/**
 * Maximum failed logins per IP before a lockout window starts.
 *
 * @return int
 */
function gwill_login_rate_limit_max(): int {
	return (int) apply_filters( 'gwill_login_rate_limit_max', 5 );
}

// ── 2. gwill_login_rate_limit_window ──────────────────────
/**
 * Lockout window length in seconds.
 *
 * @return int
 */
function gwill_login_rate_limit_window(): int {
	return (int) apply_filters( 'gwill_login_rate_limit_window', 15 * MINUTE_IN_SECONDS );
}

// ── 3. gwill_login_client_ip ──────────────────────────────
/**
 * Resolve the client IP for login rate limiting (hashed before storage).
 *
 * @return string
 */
function gwill_login_client_ip(): string {
	if ( function_exists( 'gwill_get_client_ip' ) ) {
		return gwill_get_client_ip();
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed immediately by callers
	return (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ); // phpcs:ignore WordPress.Security.NonceVerification
}

// ── 4. gwill_login_rate_key ───────────────────────────────
/**
 * Rate-limit key for the current client IP.
 *
 * @return string
 */
function gwill_login_rate_key(): string {
	return 'gwill_login_rl_' . hash( 'sha256', gwill_login_client_ip() );
}

// ── 5. gwill_count_login_failure ───────────────────────────
/**
 * Record a failed login attempt (wp_login_failed).
 *
 * @return void
 */
function gwill_count_login_failure(): void {
	$key    = gwill_login_rate_key();
	$count  = (int) get_transient( $key );
	$count++;
	set_transient( $key, $count, gwill_login_rate_limit_window() );
}

// ── 6. gwill_check_login_rate_limit ───────────────────────
/**
 * Trip the lockout when the failure count exceeds the max.
 *
 * Priority 9 on authenticate — BEFORE the password check (20) and the
 * 2FA check (30), so a locked-out IP cannot even probe passwords.
 *
 * @param mixed  $user     WP_User|WP_Error|null from earlier filters.
 * @param string $username Submitted login.
 * @param string $password Submitted password.
 * @return mixed
 */
function gwill_check_login_rate_limit( $user, $username, $password ) {
	$count = (int) get_transient( gwill_login_rate_key() );
	if ( $count < gwill_login_rate_limit_max() ) {
		return $user;
	}
	return new WP_Error(
		'gwill_login_locked',
		__( 'Too many failed login attempts. Please wait 15 minutes and try again.', 'gwill-starter' )
	);
}
