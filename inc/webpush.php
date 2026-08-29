<?php

/*
Table of Contents
1. Table — gwill_push_subs (dbDelta, gwill_ prefixed)
2. VAPID keys (library-generated, stored autoload-off in wp_options)
3. Subscriber key obfuscation (NONCE_KEY-derived, decoded on send)
4. REST endpoints — /gwill/v1/push/subscribe + /unsubscribe
5. Publish trigger — transition_post_status -> send to all
6. Enqueue bell + localize (REST url + X-WP-Nonce pattern)
7. Bell markup (echoed into the footer)
*/

/**
 * GWill Starter — Web Push (self-hosted VAPID).
 *
 * PORTED from gwill-finance-theme v1.2.3 (the configuration that finally
 * mastered push on real devices — every root cause of the August 2026 saga
 * is baked in; see docs/LAWS.md). The finance implementation is itself the
 * proven gwill-tech-theme lineage. Nothing was re-invented here; only the
 * appearance was stripped to starter tokens (assets/css/push.css) and the
 * text domain changed to 'gwill-starter'.
 *
 *   - Custom table gwill_push_subs (native MySQL via $wpdb + dbDelta).
 *   - VAPID keys generated at first use via VAPID::createVapidKeys(),
 *     stored in wp_options (autoload off, never hardcoded).
 *   - Subscriber keys obfuscated at rest (NONCE_KEY-derived), decoded on send.
 *   - Notifications triggered on post publish (transition_post_status).
 *   - Footer bell — permission-gated, never nags pre-consent.
 *   - vendor/minishlink/web-push is COMMITTED (docs/LAWS.md L1) — a fresh
 *     clone from GitHub/GitLab is fully functional, no composer install.
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;

const GWILL_PUSH_TABLE = 'gwill_push_subs';

/* ── 1. Table ────────────────────────────────────────────────────────── */

function gwill_push_ensure_table() {
	global $wpdb;
	$table   = $wpdb->prefix . GWILL_PUSH_TABLE;
	$collate = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta(
		"CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			endpoint varchar(500) NOT NULL,
			auth varchar(512) NOT NULL,
			p256dh varchar(512) NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY endpoint (endpoint)
		) $collate;"
	);
}
add_action( 'after_switch_theme', 'gwill_push_ensure_table' );
add_action( 'admin_init', 'gwill_push_ensure_table' );

/* ── 2. VAPID keys (library-generated, stored autoload-off) ──────────── */

function gwill_push_vapid() {
	$keys = get_option( 'gwill_vapid_keys' );
	if ( is_array( $keys ) && ! empty( $keys['publicKey'] ) && ! empty( $keys['privateKey'] ) ) {
		return $keys;
	}
	try {
		$keys = VAPID::createVapidKeys();
	} catch ( \Throwable $e ) {
		return false;
	}
	update_option( 'gwill_vapid_keys', $keys, false );
	return $keys;
}

function gwill_push_stream() {
	$vapid_key = gwill_push_vapid();
	if ( ! $vapid_key ) {
		return null;
	}
	static $stream = null;
	if ( null === $stream ) {
		$stream = new WebPush(
			array(
				'VAPID' => array(
					'subject'    => 'mailto:' . get_bloginfo( 'admin_email' ),
					'publicKey'  => $vapid_key['publicKey'],
					'privateKey' => $vapid_key['privateKey'],
				),
			),
			array( 'TTL' => 86400 )
		);
	}
	return $stream;
}

/* ── 3. Subscriber key handling ──────────────────────────────────────── */

function gwill_push_obfuscate( $str ) {
	if ( '' === $str || ! function_exists( 'openssl_encrypt' ) ) {
		return $str;
	}
	$secret = defined( 'NONCE_KEY' ) ? NONCE_KEY : '';
	if ( ! $secret ) {
		return $str;
	}
	$iv  = substr( hash( 'sha256', $secret . $str, true ), 0, 16 );
	$ct  = openssl_encrypt( $str, 'aes-128-cbc', hash( 'sha256', $secret, true ), OPENSSL_RAW_DATA, $iv );
	return ( false === $ct ) ? $str : $iv . $ct;
}

function gwill_push_deobfuscate( $blob ) {
	if ( ! $blob || ! function_exists( 'openssl_decrypt' ) ) {
		return $blob;
	}
	$secret = defined( 'NONCE_KEY' ) ? NONCE_KEY : '';
	if ( ! $secret || strlen( $blob ) < 32 ) {
		return $blob;
	}
	$iv = substr( $blob, 0, 16 );
	$ct = substr( $blob, 16 );
	$pt = openssl_decrypt( $ct, 'aes-128-cbc', hash( 'sha256', $secret, true ), OPENSSL_RAW_DATA, $iv );
	return ( false === $pt ) ? $blob : $pt;
}

/* ── 4. REST endpoints ───────────────────────────────────────────────── */

function gwill_push_register_rest() {
	register_rest_route(
		'gwill/v1',
		'/push/subscribe',
		array(
			'methods'             => 'POST',
			'callback'            => 'gwill_push_subscribe_cb',
			'permission_callback' => '__return_true',
		)
	);
	register_rest_route(
		'gwill/v1',
		'/push/unsubscribe',
		array(
			'methods'             => 'POST',
			'callback'            => 'gwill_push_unsubscribe_cb',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'gwill_push_register_rest' );

function gwill_push_expect( WP_REST_Request $req, $fields ) {
	foreach ( $fields as $f ) {
		$v = $req->get_param( $f );
		if ( empty( $v ) || ! is_string( $v ) ) {
			return new WP_Error( 'missing_' . $f, "Missing field: $f", array( 'status' => 400 ) );
		}
	}
	return true;
}

function gwill_push_subscribe_cb( WP_REST_Request $req ) {
	$ok = gwill_push_expect( $req, array( 'endpoint', 'p256dh', 'auth' ) );
	if ( is_wp_error( $ok ) ) {
		return $ok;
	}
	$endpoint = esc_url_raw( $req->get_param( 'endpoint' ) );
	$p256dh   = sanitize_text_field( $req->get_param( 'p256dh' ) );
	$auth     = sanitize_text_field( $req->get_param( 'auth' ) );
	if ( 'https' !== wp_parse_url( $endpoint, PHP_URL_SCHEME ) ) {
		return new WP_Error( 'not_https', 'Endpoint must be https', array( 'status' => 400 ) );
	}

	try {
		Subscription::create( array(
			'endpoint'        => $endpoint,
			'keys'            => array(
				'p256dh' => $p256dh,
				'auth'   => $auth,
			),
			'contentEncoding' => 'aes128gcm',
		) );
	} catch ( \Throwable $e ) {
		return new WP_Error( 'invalid_subscription', 'Invalid subscription keys', array( 'status' => 400 ) );
	}

	global $wpdb;
	$table = $wpdb->prefix . GWILL_PUSH_TABLE;
	$result = $wpdb->replace(
		$table,
		array(
			'endpoint' => $endpoint,
			'auth'     => base64_encode( gwill_push_obfuscate( $auth ) ),
			'p256dh'   => base64_encode( gwill_push_obfuscate( $p256dh ) ),
			'user_id'  => (int) get_current_user_id(),
		),
		array( '%s', '%s', '%s', '%d' )
	);
	if ( false === $result ) {
		return new WP_Error( 'db_insert_failed', 'Could not save subscription', array( 'status' => 500 ) );
	}
	return array( 'ok' => true );
}

function gwill_push_unsubscribe_cb( WP_REST_Request $req ) {
	$ok = gwill_push_expect( $req, array( 'endpoint' ) );
	if ( is_wp_error( $ok ) ) {
		return $ok;
	}
	global $wpdb;
	$table = $wpdb->prefix . GWILL_PUSH_TABLE;
	$wpdb->delete( $table, array( 'endpoint' => esc_url_raw( $req->get_param( 'endpoint' ) ) ) );
	return array( 'ok' => true );
}

/* ── 5. Publish trigger ──────────────────────────────────────────────── */

/**
 * Filterable: return false to stop this site sending push on publish.
 * Taxonomies can be added with a build-specific callback.
 */
function gwill_push_on_publish( $new_status, $old_status, $post ) {
	if ( ! is_object( $post ) || 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}
	if ( 'post' !== get_post_type( $post ) ) {
		return;
	}
	if ( ! apply_filters( 'gwill_push_on_publish', true, $post ) ) {
		return;
	}
	gwill_push_send_to_all( $post );
}
add_action( 'transition_post_status', 'gwill_push_on_publish', 10, 3 );

function gwill_push_send_to_all( $post ) {
	$stream = gwill_push_stream();
	if ( ! $stream ) {
		return false;
	}
	global $wpdb;
	$table = $wpdb->prefix . GWILL_PUSH_TABLE;
	$rows  = $wpdb->get_results( "SELECT id, endpoint, auth, p256dh FROM $table", ARRAY_A );
	if ( empty( $rows ) ) {
		return 0;
	}

	$title = get_the_title( $post );
	$url   = get_permalink( $post );
	$body  = wp_trim_words(
		wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : $post->post_content ),
		22,
		'…'
	);
	$icon  = get_template_directory_uri() . '/assets/brand/push-icon.png';
	$badge = get_template_directory_uri() . '/assets/brand/push-badge.png';
	$payload = array(
		'title'   => $title,
		'body'    => $body ? $body : __( 'New post on', 'gwill-starter' ) . ' ' . get_bloginfo( 'name' ),
		'icon'    => $icon,
		'badge'   => $badge,
		'url'     => $url,
	);

	$sent = 0;
	foreach ( $rows as $r ) {
		$sub = array(
			'endpoint'        => $r['endpoint'],
			'keys'            => array(
				'auth'   => gwill_push_deobfuscate( base64_decode( $r['auth'] ) ),
				'p256dh' => gwill_push_deobfuscate( base64_decode( $r['p256dh'] ) ),
			),
			'contentEncoding' => 'aes128gcm',
		);
		try {
			$stream->queueNotification( Subscription::create( $sub ), json_encode( $payload, JSON_UNESCAPED_SLASHES ) );
			$sent++;
		} catch ( \Throwable $e ) {
			continue;
		}
	}
	if ( $sent ) {
		$reports = $stream->flush();
		if ( is_object( $reports ) && method_exists( $reports, 'current' ) ) {
			$reports = iterator_to_array( $reports );
		}
		if ( is_array( $reports ) ) {
			foreach ( $reports as $report ) {
				$status = $report->getResponse()->getStatusCode();
				if ( 410 === $status || 404 === $status ) {
					$ep = method_exists( $report, 'getRequest' )
						? (string) $report->getRequest()->getUri()
						: '';
					if ( $ep ) {
						$wpdb->delete( $table, array( 'endpoint' => $ep ), array( '%s' ) );
					}
				}
			}
		}
	}
	return $sent;
}

/* ── 6. Enqueue bell + localize ──────────────────────────────────────── */

function gwill_push_enqueue() {
	$keys = gwill_push_vapid();
	if ( ! $keys ) {
		return;
	}
	wp_enqueue_style(
		'gwill-push',
		get_template_directory_uri() . '/assets/css/push.css',
		array(),
		filemtime( get_template_directory() . '/assets/css/push.css' )
	);
	wp_enqueue_script(
		'gwill-push',
		get_template_directory_uri() . '/assets/js/push.js',
		array(),
		filemtime( get_template_directory() . '/assets/js/push.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_localize_script(
		'gwill-push',
		'gwillPush',
		array(
			'publicKey' => $keys['publicKey'],
			'restUrl'   => esc_url_raw( rest_url( 'gwill/v1/push/' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'icon'      => get_template_directory_uri() . '/assets/brand/push-icon.png',
			'badge'     => get_template_directory_uri() . '/assets/brand/push-badge.png',
			'strings'   => array(
				'subscribe'     => __( 'Enable notifications', 'gwill-starter' ),
				'unsubscribe'   => __( 'Turn off notifications', 'gwill-starter' ),
				'blocked'       => __( 'Notifications blocked by browser', 'gwill-starter' ),
				'error'         => __( 'Could not enable notifications', 'gwill-starter' ),
				/* ── Bell panel — %s in step text = site name ── */
				'title'         => __( 'Notifications', 'gwill-starter' ),
				'statusOn'      => __( 'status: on', 'gwill-starter' ),
				'statusOff'     => __( 'status: off', 'gwill-starter' ),
				'statusBlocked' => __( 'status: blocked', 'gwill-starter' ),
				'statusError'   => __( 'status: error', 'gwill-starter' ),
				'bodyOn'        => __( "You're subscribed — an alert lands on this device the moment a new post goes live.", 'gwill-starter' ),
				'bodyOff'       => __( 'Get notified of every new post. No spam, no marketing — one tap to turn off.', 'gwill-starter' ),
				'bodyBlocked'   => __( 'Notifications are blocked for this site. Unblock them in your browser, then tap Check again — we detect it instantly.', 'gwill-starter' ),
				'bodyError'     => __( 'Something went wrong while enabling notifications. Try again.', 'gwill-starter' ),
				'statusUnsupported' => __( 'status: unsupported', 'gwill-starter' ),
				'bodyUnsupported'   => __( 'Push notifications are not supported in this browser.', 'gwill-starter' ),
				'browserList'       => __( 'Supported browsers: Chrome, Firefox, Edge, Opera, Safari 16+, Samsung Internet.', 'gwill-starter' ),
				'howTo'         => __( 'How to unblock on this device', 'gwill-starter' ),
				'checkAgain'    => __( 'Check again', 'gwill-starter' ),
				'tryAgain'      => __( 'Try again', 'gwill-starter' ),
				'enabling'      => __( 'Enabling…', 'gwill-starter' ),
				'turningOff'    => __( 'Turning off…', 'gwill-starter' ),
				'close'         => __( 'Close notifications panel', 'gwill-starter' ),
				'otherDevices'  => __( 'Different device or browser? Open the site settings (lock icon in the address bar), set Notifications to Allow, then reload.', 'gwill-starter' ),
				'siteName'      => get_bloginfo( 'name' ),
				'steps'         => array(
					'android-chrome'  => array(
						__( 'Tap the lock (or tune) icon at the left of the address bar', 'gwill-starter' ),
						__( 'Tap Permissions, then Notifications', 'gwill-starter' ),
						__( 'Choose Allow, then reload the page', 'gwill-starter' ),
					),
					'firefox-android' => array(
						__( 'Tap the menu (⋮), then Site Settings', 'gwill-starter' ),
						__( 'Tap Notifications, then Allow', 'gwill-starter' ),
						__( 'Reload the page', 'gwill-starter' ),
					),
					'samsung-internet' => array(
						__( 'Tap the menu (☰), then Settings → Sites and downloads', 'gwill-starter' ),
						__( 'Tap Site permissions → Notifications', 'gwill-starter' ),
						__( 'Choose Allow, then reload the page', 'gwill-starter' ),
					),
					'ios-safari'      => array(
						__( 'Tap Aa in the Safari address bar', 'gwill-starter' ),
						__( 'Tap Website Settings, then Notifications', 'gwill-starter' ),
						__( 'Choose Allow, then reload the page', 'gwill-starter' ),
						__( 'If Allow is unavailable: Share → Add to Home Screen first — iOS delivers web push to installed apps', 'gwill-starter' ),
					),
					'desktop-chrome'  => array(
						__( 'Click the lock (or tune) icon at the left of the address bar', 'gwill-starter' ),
						__( 'Open Site settings', 'gwill-starter' ),
						__( 'Set Notifications to Allow', 'gwill-starter' ),
						__( 'Reload the page', 'gwill-starter' ),
					),
					'desktop-edge'    => array(
						__( 'Click the lock icon at the left of the address bar', 'gwill-starter' ),
						__( 'Open Site permissions', 'gwill-starter' ),
						__( 'Set Notifications to Allow', 'gwill-starter' ),
						__( 'Reload the page', 'gwill-starter' ),
					),
					'desktop-opera'   => array(
						__( 'Click the lock (or tune) icon at the left of the address bar', 'gwill-starter' ),
						__( 'Open Site settings', 'gwill-starter' ),
						__( 'Set Notifications to Allow', 'gwill-starter' ),
						__( 'Reload the page', 'gwill-starter' ),
					),
					'desktop-firefox' => array(
						__( 'Click the crossed-out bell (or lock) icon in the address bar', 'gwill-starter' ),
						__( 'Clear the Blocked permission for Notifications', 'gwill-starter' ),
						__( 'Reload the page', 'gwill-starter' ),
					),
					'desktop-safari'  => array(
						__( 'In the menu bar choose Safari → Settings for %s…', 'gwill-starter' ),
						__( 'Set Notifications to Allow', 'gwill-starter' ),
						__( 'Reload the page', 'gwill-starter' ),
					),
					'other'           => array(
						__( "Open your browser's site settings (lock icon in the address bar)", 'gwill-starter' ),
						__( 'Set Notifications to Allow for this site', 'gwill-starter' ),
						__( 'Reload the page', 'gwill-starter' ),
					),
				),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'gwill_push_enqueue', 30 );

/* ── 7. Bell markup (echoed into the footer) ─────────────────────────── */

function gwill_push_bell() {
	if ( ! gwill_push_vapid() ) {
		return;
	}
	?>
	<button type="button" class="gwill-bell" id="gwill-bell"
		aria-label="<?php esc_attr_e( 'Enable notifications', 'gwill-starter' ); ?>"
		title="<?php esc_attr_e( 'Notifications', 'gwill-starter' ); ?>" aria-pressed="false"
		aria-expanded="false" aria-haspopup="dialog" aria-controls="gwill-bell-panel"
		data-label-off="<?php esc_attr_e( 'Notify me of new posts', 'gwill-starter' ); ?>"
		data-label-on="<?php esc_attr_e( 'Notifications on', 'gwill-starter' ); ?>">
		<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm6-6V11a6 6 0 0 0-4.5-5.8V4a1.5 1.5 0 0 0-3 0v1.2A6 6 0 0 0 6 11v5l-1.7 1.7a1 1 0 0 0 .7 1.7h14a1 1 0 0 0 .7-1.7L18 16Z"/></svg><?php esc_html_e( 'Notify me of new posts', 'gwill-starter' ); ?>
	</button>
	<?php
}
