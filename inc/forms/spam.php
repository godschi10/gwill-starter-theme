<?php
/**
 * Spam Protection  -  GWill Starter
 *
 * Honeypot, rate limiting, and client IP resolution.
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// Honeypot
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Honeypot helper  -  generates a unique field name per page load and stores
 * it in a transient so validation knows what to expect. Bots that scrape
 * the form and fill all fields will use the stale name, triggering the trap.
 *
 * @param string $prefix Unique prefix per form instance (e.g., $uid).
 * @return string Randomized field name.
 */
function gwill_get_honeypot_name( string $prefix ): string {
	$name = 'gwill_hp_' . $prefix;
	// Store expected name in transient (5 min  -  covers form load + submit)
	set_transient( 'gwill_hp_expected_' . $name, true, 5 * MINUTE_IN_SECONDS );
	return $name;
}

/**
 * Check whether the submitted honeypot value matches the expected name for this request.
 *
 * @return bool True if honeypot was triggered (bot filled wrong field name).
 */
function gwill_form_honeypot_triggered(): bool {
	// Get all submitted gwill_hp_* keys
	foreach ( $_POST as $key => $value ) {
		if ( str_starts_with( $key, 'gwill_hp_' ) ) {
			// Check if this name was expected (transient exists)
			if ( ! get_transient( 'gwill_hp_expected_' . $key ) ) {
				// Submitted a name we didn't generate  -  bot using stale scraped name
				return true;
			}
			// Name was expected  -  check if value is non-empty (human wouldn't fill it)
			if ( ! empty( $value ) ) {
				return true;
			}
			// Valid name and empty  -  clean, not a bot
			return false;
		}
	}
	// No honeypot field submitted at all  -  suspicious but allow (could be JS-disabled)
	return false;
}

// ─────────────────────────────────────────────────────────────────────────────
// Client IP Resolution
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Resolve the real client IP, accounting for CDN and reverse-proxy headers.
 *
 * SECURITY  -  proxy headers are NOT trusted by default.
 *
 * HTTP_X_FORWARDED_FOR (and, by extension, HTTP_CF_CONNECTING_IP) are only
 * trustworthy when EVERY request genuinely passes through the proxy that
 * sets them  -  i.e. the origin server is firewalled to reject connections
 * that don't come from Cloudflare. On typical shared cPanel hosting, the
 * origin is usually reachable directly via its own IP unless that firewall
 * rule is explicitly configured. If it isn't, a request straight to the
 * origin lets an attacker set ANY value they want for these headers  - 
 * including a fresh, unique fake IP on every single request, which
 * completely defeats gwill_form_rate_limited()'s per-IP cooldown and
 * reopens the form to unlimited rapid-fire spam.
 *
 * Default behaviour (GWILL_TRUST_PROXY_HEADERS undefined or false): always
 * use REMOTE_ADDR. It's the actual TCP peer address  -  enforced by the
 * network stack, not an HTTP header  -  so it cannot be spoofed by the
 * client. Behind Cloudflare without the origin firewalled, REMOTE_ADDR
 * will be Cloudflare's edge IP rather than the visitor's, which makes
 * rate-limiting coarser (many visitors briefly share an edge IP) but never
 * spoofable  -  a strictly safer failure mode than trusting an attacker-
 * controlled header.
 *
 * To restore per-visitor accuracy when the origin genuinely IS firewalled
 * to only accept Cloudflare's published IP ranges, opt in explicitly in
 * wp-config.php:
 *
 *   define( 'GWILL_TRUST_PROXY_HEADERS', true );
 *
 * @return string  Raw IP string (not validated  -  immediately hashed by callers).
 * @since  1.0.21
 * @since  1.0.49  Gated proxy-header trust behind an explicit opt-in constant.
 */
function gwill_get_client_ip(): string {

	$remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

	// Trust proxy headers ONLY if:
	// 1. GWILL_TRUST_PROXY_HEADERS is explicitly true AND
	// 2. The connection actually comes from Cloudflare's IP ranges (REMOTE_ADDR validation)
	// This prevents header spoofing from non-Cloudflare IPs.
	if ( defined( 'GWILL_TRUST_PROXY_HEADERS' ) && GWILL_TRUST_PROXY_HEADERS ) {

		// Validate REMOTE_ADDR is within Cloudflare's published IP ranges
		// See: https://www.cloudflare.com/ips/
		// This is the ACTUAL TCP connection source  -  cannot be spoofed.
		if ( gwill_is_cloudflare_ip( $remote_addr ) ) {

			if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed immediately by callers
				return trim( (string) $_SERVER['HTTP_CF_CONNECTING_IP'] );
			}
			if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed immediately by callers
				$ips = explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'] );
				return trim( $ips[0] );
			}
		}
		// If REMOTE_ADDR is not Cloudflare, ignore proxy headers entirely  -  fall through to return REMOTE_ADDR
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed immediately by callers
	return (string) $remote_addr;
}

/**
 * Check if an IP address belongs to Cloudflare's published IP ranges.
 *
 * Uses a static cached array of CIDR blocks (updated from Cloudflare's API).
 * Cache is refreshed daily via transient. Falls back to hardcoded list
 * if transient fetch fails (e.g., no outbound HTTP).
 *
 * @param string $ip Raw IP address (IPv4 or IPv6).
 * @return bool True if IP is in Cloudflare's ranges.
 * @since 1.0.65
 */
function gwill_is_cloudflare_ip( string $ip ): bool {

	// Quick exit for localhost/development
	if ( $ip === '127.0.0.1' || $ip === '::1' || $ip === 'unknown' ) {
		return true;
	}

	$ranges = get_transient( 'gwill_cloudflare_ip_ranges' );

	if ( false === $ranges ) {
		// Try to fetch fresh from Cloudflare's API
		$ranges = gwill_fetch_cloudflare_ips();
		if ( ! empty( $ranges ) ) {
			set_transient( 'gwill_cloudflare_ip_ranges', $ranges, DAY_IN_SECONDS );
		} else {
			// Hardcoded fallback (as of 2024-01)  -  covers vast majority of Cloudflare edges
			$ranges = [
				// IPv4
				'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
				'141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
				'197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
				'104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
				// IPv6
				'2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
				'2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
			];
		}
	}

	foreach ( $ranges as $cidr ) {
		if ( gwill_ip_in_cidr( $ip, $cidr ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Fetch Cloudflare IP ranges from their API.
 *
 * @return string[]|false Array of CIDR strings, or false on failure.
 */
function gwill_fetch_cloudflare_ips(): array|false {
	$response = wp_remote_get( 'https://api.cloudflare.com/client/v4/ips', [
		'timeout' => 10,
		'headers' => [ 'Accept' => 'application/json' ],
	] );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( ! $data || empty( $data['success'] ) || ! $data['success'] ) {
		return false;
	}

	$ranges = [];
	if ( ! empty( $data['result']['ipv4_cidrs'] ) ) {
		$ranges = array_merge( $ranges, $data['result']['ipv4_cidrs'] );
	}
	if ( ! empty( $data['result']['ipv6_cidrs'] ) ) {
		$ranges = array_merge( $ranges, $data['result']['ipv6_cidrs'] );
	}

	return $ranges ?: false;
}

/**
 * Check if an IP (IPv4 or IPv6) falls within a CIDR range.
 *
 * @param string $ip   IP address.
 * @param string $cidr CIDR notation (e.g., '192.0.2.0/24' or '2001:db8::/32').
 * @return bool
 */
function gwill_ip_in_cidr( string $ip, string $cidr ): bool {
	[ $range_ip, $bits ] = explode( '/', $cidr );

	if ( str_contains( $ip, ':' ) ) {
		// IPv6
		if ( ! function_exists( 'inet_pton' ) ) {
			return false;
		}
		$ip_bin   = inet_pton( $ip );
		$range_bin = inet_pton( $range_ip );
		if ( $ip_bin === false || $range_bin === false ) {
			return false;
		}
		$mask = ~((1 << (128 - (int) $bits)) - 1);
		// PHP's bitwise ops on strings work byte-by-byte
		for ( $i = 0; $i < 16; $i++ ) {
			$ip_byte   = ord( $ip_bin[$i] );
			$range_byte = ord( $range_bin[$i] );
			$mask_byte = ( $i < 12 ) ? 0xFF : ( ( $mask >> ( 8 * ( 15 - $i ) ) ) & 0xFF );
			if ( ( $ip_byte & $mask_byte ) !== ( $range_byte & $mask_byte ) ) {
				return false;
			}
		}
		return true;
	} else {
		// IPv4
		$ip_long   = ip2long( $ip );
		$range_long = ip2long( $range_ip );
		if ( $ip_long === false || $range_long === false ) {
			return false;
		}
		$mask = -1 << ( 32 - (int) $bits );
		return ( $ip_long & $mask ) === ( $range_long & $mask );
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Rate Limiting
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Check whether the current IP is within the rate-limit window.
 *
 * Bypassed for users with at least edit_posts capability  -  the same gate
 * template-contact-demo.php itself uses. Without this, testing multiple
 * form patterns in quick succession (exactly what the demo page is for)
 * trips the same 5-minute cooldown meant for anonymous spam, and  -  until
 * v1.0.49's forms.js fix  -  surfaced as an indistinguishable generic error
 * instead of the actual "please wait" message.
 *
 * Rate limit window defaults to 5 minutes; override via filter:
 *   add_filter( 'gwill_rate_limit_seconds', fn() => 2 * MINUTE_IN_SECONDS );
 *
 * @return bool True if rate-limited (too soon since last submission).
 * @since  1.0.20
 * @since  1.0.49  Added the edit_posts bypass.
 */
function gwill_form_rate_limited(): bool {
	if ( current_user_can( 'edit_posts' ) ) {
		return false;
	}
	return (bool) get_transient( 'gwill_rl_' . hash( 'sha256', gwill_get_client_ip() ) );
}

/**
 * Set the rate-limit transient for the current IP.
 *
 * Called after a successful send to start the cooldown window.
 *
 * @since 1.0.20
 */
function gwill_set_rate_limit(): void {
	$seconds = (int) apply_filters( 'gwill_rate_limit_seconds', 5 * MINUTE_IN_SECONDS );
	set_transient( 'gwill_rl_' . hash( 'sha256', gwill_get_client_ip() ), true, $seconds );
}