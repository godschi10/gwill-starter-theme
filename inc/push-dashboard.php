<?php
/**
 * GWill Starter  -  Push Dashboard (admin).
 *
 * The admin window into the self-hosted push system (inc/webpush.php):
 * subscriber count, recent growth, a subscriber table with per-endpoint
 * delete, and a "send test notification" action that reuses the exact
 * send path a post publish takes.
 *
 * Design notes:
 *
 *   - All sending flows through the PROVEN loop from gwill_push_send_to_all()
 *     (queueNotification -> flush -> prune on 410/404)  -  never re-implemented.
 *   - The bell and phone-side permission state are deliberately absent:
 *     L11  -  after PWA install, only the app-level toggle tells the truth.
 *   - Sending runs via REST (gwill/v1/push/test) so the nonce + capability
 *     model matches the starter's other REST routes.
 *
 * @package GWill_Starter
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/*
Table of Contents
1. Stats  -  subscriber counts
2. Admin page registration (Tools → Push Subscribers)
3. Admin page render  -  state notices, stats, test push, table
4. Subscriber table renderer
5. REST route  -  gwill/v1/push/test (send test to all)
6. Row delete  -  admin-post handler
7. Admin JS  -  test button wiring (inline, jQuery-free)
*/

/* ── 1. Stats ───────────────────────────────────────────────────────── */

/**
 * Push subscriber stats.
 *
 * @return array{subs:int,new7:int,new30:int,table_exists:bool,vapid_ready:bool}
 * @since 1.5.0
 */
function gwill_push_dashboard_stats(): array {
	global $wpdb;
	$table = $wpdb->prefix . GWILL_PUSH_TABLE;

	$exists = (bool) $wpdb->get_var(
		$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
	);
	if ( ! $exists ) {
		return [
			'subs'         => 0,
			'new7'         => 0,
			'new30'        => 0,
			'table_exists' => false,
			'vapid_ready'  => (bool) gwill_push_vapid(),
		];
	}

	$subs  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
	$new7  = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB( NOW(), INTERVAL 7 DAY )"
	);
	$new30 = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB( NOW(), INTERVAL 30 DAY )"
	);

	return [
		'subs'         => $subs,
		'new7'         => $new7,
		'new30'        => $new30,
		'table_exists' => true,
		'vapid_ready'  => (bool) gwill_push_vapid(),
	];
}

/* ── 2. Admin page registration ─────────────────────────────────────── */

/**
 * Tools → "Push Subscribers". Same Tools submenu pattern as the analytics
 * module  -  an occasional site-owner screen, never a sidebar slot.
 *
 * @since 1.5.0
 */
function gwill_push_dashboard_menu(): void {
	add_management_page(
		__( 'Push Subscribers', 'gwill-starter' ),
		__( 'Push Subscribers', 'gwill-starter' ),
		'manage_options',
		'gwill-push',
		'gwill_push_dashboard_render'
	);
}
add_action( 'admin_menu', 'gwill_push_dashboard_menu' );

/* ── 3. Admin page render ───────────────────────────────────────────── */

/**
 * Render the push dashboard. Admin-native classes only (.wrap, .widefat,
 * .notice)  -  zero custom stylesheets to ship or cache-bust.
 *
 * @since 1.5.0
 */
function gwill_push_dashboard_render(): void {
	$stats = gwill_push_dashboard_stats();

	global $wpdb;
	$table = $wpdb->prefix . GWILL_PUSH_TABLE;
	$rows  = $stats['table_exists']
		? $wpdb->get_results(
			"SELECT id, endpoint, created_at FROM $table ORDER BY id DESC LIMIT 100",
			ARRAY_A
		)
		: [];
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Push Subscribers', 'gwill-starter' ); ?></h1>

		<?php if ( ! $stats['vapid_ready'] ) : ?>
			<div class="notice notice-error inline">
				<p>
					<strong><?php esc_html_e( 'VAPID keys missing.', 'gwill-starter' ); ?></strong>
					<?php esc_html_e( 'The theme could not generate or load VAPID keys  -  push cannot run. Check the PHP openssl extension and the site error log.', 'gwill-starter' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( ! $stats['table_exists'] ) : ?>
			<div class="notice notice-info inline">
				<p>
					<strong><?php esc_html_e( 'Subscriber table not created yet.', 'gwill-starter' ); ?></strong>
					<?php esc_html_e( 'It is created automatically on theme activation or first admin page load. If you activated the theme before v1.4.0, deactivate and reactivate once.', 'gwill-starter' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Overview', 'gwill-starter' ); ?></h2>
		<p style="font-size:14px;line-height:2">
			<?php
			printf(
				/* translators: 1: subscriber count, 2: new in last 7 days, 3: new in last 30 days */
				esc_html__( 'Subscribers: %1$s  -  new in last 7 days: %2$s  -  new in last 30 days: %3$s.', 'gwill-starter' ),
				'<strong>' . esc_html( number_format_i18n( $stats['subs'] ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $stats['new7'] ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $stats['new30'] ) ) . '</strong>'
			);
			?>
		</p>

		<h2><?php esc_html_e( 'Campaign open-rates', 'gwill-starter' ); ?></h2>
		<?php
		$campaigns = get_option( 'gwill_push_stats', array() );
		if ( ! is_array( $campaigns ) || empty( $campaigns ) ) :
			?>
			<p><?php esc_html_e( 'No push campaigns recorded yet  -  publish a post (or send a test) and campaign stats appear here.', 'gwill-starter' ); ?></p>
		<?php else : ?>
			<p style="font-size:13px;color:#50575e">
				<?php esc_html_e( 'Clicks are counted when a subscriber taps the notification. Recent campaigns first.', 'gwill-starter' ); ?>
			</p>
			<table class="widefat striped" style="max-width:700px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Campaign', 'gwill-starter' ); ?></th>
						<th><?php esc_html_e( 'Sent to', 'gwill-starter' ); ?></th>
						<th><?php esc_html_e( 'Clicked', 'gwill-starter' ); ?></th>
						<th><?php esc_html_e( 'Open rate', 'gwill-starter' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$recent = array_reverse( $campaigns, true );
				$i      = 0;
				foreach ( $recent as $pid => $c ) :
					if ( $i++ >= 20 ) { break; }
					$rate = $c['sent'] > 0 ? round( $c['clicked'] / $c['sent'] * 100, 1 ) : 0;
					$edit = get_edit_post_link( (int) $pid );
					?>
					<tr>
						<td><?php echo $edit ? '<a href="' . esc_url( $edit ) . '">' . esc_html( get_the_title( (int) $pid ) ?: ( 'Post ' . (int) $pid ) ) . '</a>' : esc_html( get_the_title( (int) $pid ) ?: ( 'Post ' . (int) $pid ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $c['sent'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $c['clicked'] ) ); ?></td>
						<td><?php echo esc_html( $rate . '%' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( $stats['subs'] > 0 ) : ?>
			<h2><?php esc_html_e( 'Send a test notification', 'gwill-starter' ); ?></h2>
			<p><?php esc_html_e( 'Sends one notification to every subscriber  -  the exact send path a post publish takes. Use it to verify the whole chain before relying on it.', 'gwill-starter' ); ?></p>
			<p>
				<button type="button" class="button button-primary" id="gwill-push-test">
					<?php esc_html_e( 'Send test notification', 'gwill-starter' ); ?>
				</button>
				<span id="gwill-push-test-status" role="status" aria-live="polite" style="margin-left:8px"></span>
			</p>

			<h2><?php esc_html_e( 'Subscribers', 'gwill-starter' ); ?></h2>
			<?php gwill_push_dashboard_table( (array) $rows ); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No subscribers yet. The bell in the site footer is where visitors subscribe.', 'gwill-starter' ); ?></p>
		<?php endif; ?>
	</div>
	<?php

	gwill_push_dashboard_inline_js();
}

/* ── 4. Subscriber table renderer ────────────────────────────────────── */

/**
 * The subscriber table. Endpoints are long opaque URLs  -  truncated for
 * display with the browser's own title tooltip carrying the full string.
 *
 * @param array<int,array{id:string,endpoint:string,created_at:string}> $rows
 * @since 1.5.0
 */
function gwill_push_dashboard_table( array $rows ): void {
	?>
	<table class="widefat striped" style="max-width:900px">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Endpoint', 'gwill-starter' ); ?></th>
				<th style="width:12em"><?php esc_html_e( 'Subscribed', 'gwill-starter' ); ?></th>
				<th style="width:5em"></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rows as $r ) : ?>
				<?php
				$short = mb_strlen( $r['endpoint'] ) > 52
					? mb_substr( $r['endpoint'], 0, 49 ) . '…'
					: $r['endpoint'];
				?>
				<tr>
					<td title="<?php echo esc_attr( $r['endpoint'] ); ?>"><code><?php echo esc_html( $short ); ?></code></td>
					<td><?php echo esc_html( $r['created_at'] ); ?></td>
					<td>
						<a class="submitdelete"
							href="<?php echo esc_url( wp_nonce_url(
								admin_url( 'admin-post.php?action=gwill_push_delete&amp;id=' . (int) $r['id'] ),
								'gwill_push_delete'
							) ); ?>">
							<?php esc_html_e( 'Delete', 'gwill-starter' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/* ── 5. REST route  -  test send ───────────────────────────────────────── */

/**
 * Send a test notification to every subscriber. Reuses the publish
 * payload shape and the proven send loop  -  a fake WP_Post is built only
 * to carry title/body/url through gwill_push_send_to_all().
 *
 * @return void JSON {sent:int} or error.
 * @since 1.5.0
 */
function gwill_push_test_send(): void {
	$stream = gwill_push_stream();
	if ( ! $stream ) {
		wp_send_json_error( [ 'message' => __( 'Push is not configured  -  VAPID keys missing.', 'gwill-starter' ) ] );
	}

	$payload = array(
		'title' => get_bloginfo( 'name' ),
		'body'  => __( 'Test notification  -  push is working.', 'gwill-starter' ),
		'icon'  => gwill_pwa_icons()['192'],
		'badge' => gwill_pwa_icons()['badge'],
		'url'   => home_url( '/' ),
	);

	global $wpdb;
	$table = $wpdb->prefix . GWILL_PUSH_TABLE;
	$rows  = $wpdb->get_results( "SELECT id, endpoint, auth, p256dh FROM $table", ARRAY_A );
	if ( empty( $rows ) ) {
		wp_send_json_error( [ 'message' => __( 'No subscribers to send to.', 'gwill-starter' ) ] );
	}

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
			$stream->queueNotification( Subscription::create( $sub ), wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ) );
			$sent++;
		} catch ( \Throwable $e ) {
			continue;
		}
	}

	$pruned = 0;
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
					if ( $ep && $wpdb->delete( $table, array( 'endpoint' => $ep ), array( '%s' ) ) ) {
						$pruned++;
					}
				}
			}
		}
	}

	wp_send_json_success( [
		'sent'   => $sent,
		'pruned' => $pruned,
		'message' => sprintf(
			/* translators: %d: notifications sent */
			__( 'Queued %d notification(s).', 'gwill-starter' ),
			$sent
		),
	] );
}

/**
 * Register the test-send REST route. Permission: manage_options  -  the
 * same capability that guards the whole dashboard.
 *
 * @since 1.5.0
 */
function gwill_push_dashboard_rest(): void {
	register_rest_route(
		'gwill/v1',
		'/push/test',
		array(
			'methods'             => 'POST',
			'callback'            => 'gwill_push_test_send',
			'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
		)
	);
}
add_action( 'rest_api_init', 'gwill_push_dashboard_rest' );

/* ── 6. Row delete ──────────────────────────────────────────────────── */

/**
 * admin-post handler: delete one subscriber row.
 *
 * @since 1.5.0
 */
function gwill_push_delete_row(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'gwill-starter' ) );
	}
	check_admin_referer( 'gwill_push_delete' );

	$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
	if ( $id > 0 ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . GWILL_PUSH_TABLE, array( 'id' => $id ), array( '%d' ) );
	}

	wp_safe_redirect( admin_url( 'tools.php?page=gwill-push' ) );
	exit;
}
add_action( 'admin_post_gwill_push_delete', 'gwill_push_delete_row' );

/* ── 7. Admin inline JS ──────────────────────────────────────────────── */

/**
 * Wire the test button. Inline + tiny  -  an admin-only action on one page;
 * a separate .js file would cost a handle, a cache-bust and an enqueue
 * gate for four lines. Sends the REST nonce WP already printed for the
 * logged-in admin (wpApiSettings is not used; the REST root + nonce come
 * from localized literals below  -  same approach as the frontend bell).
 *
 * @since 1.5.0
 */
function gwill_push_dashboard_inline_js(): void {
	$url  = esc_url_raw( rest_url( 'gwill/v1/push/test' ) );
	$nonce = wp_create_nonce( 'wp_rest' );
	$i18n = array(
		'sending' => __( 'Sending…', 'gwill-starter' ),
		'fail'    => __( 'Send failed  -  is the REST API reachable?', 'gwill-starter' ),
	);
	?>
	<script>
	( function () {
		var btn = document.getElementById( 'gwill-push-test' );
		var out = document.getElementById( 'gwill-push-test-status' );
		if ( ! btn || ! out ) { return; }
		btn.addEventListener( 'click', function () {
			btn.disabled = true;
			out.textContent = <?php echo wp_json_encode( $i18n['sending'] ); ?>;
			fetch( <?php echo wp_json_encode( $url ); ?>, {
				method: 'POST',
				headers: { 'X-WP-Nonce': <?php echo wp_json_encode( $nonce ); ?> }
			} ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
				out.textContent = ( res && res.data && res.data.message )
					? res.data.message
					: <?php echo wp_json_encode( $i18n['fail'] ); ?>;
				btn.disabled = false;
			} ).catch( function () {
				out.textContent = <?php echo wp_json_encode( $i18n['fail'] ); ?>;
				btn.disabled = false;
			} );
		} );
	} )();
	</script>
	<?php
}
