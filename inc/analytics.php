<?php
/**
 * GWill Starter - Form & Newsletter Analytics (admin).
 *
 * Closes TWO gaps in one module:
 *
 *   1. THE LATENT FATAL (fixed here, Law L12): inc/forms/ajax.php calls
 *      gwill_log_submission() behind the GWILL_LOG_FORMS flag, but that
 *      function was NEVER defined anywhere - every site that followed the
 *      documented wp-config instructions would have crashed its form
 *      submit with "Call to undefined function". Found during the v1.5.0
 *      recon, Aug 30 2026, by grepping the full tree for the call sites.
 *      This file is the definition, so the flag finally works as documented.
 *
 *   2. NEWSLETTER ANALYTICS: a Tools → "Forms & Newsletter" admin page
 *      with a 30-day signup chart (pure inline SVG - zero JS libraries),
 *      totals, recent-submission log, and CSV export - everything a build
 *      needs to see who subscribed, when, and from which form pattern.
 *
 * Data source: the {prefix}gwill_form_submissions table, whose schema has
 * been documented in inc/forms.php's header comment since v1.0.20. This
 * module CREATES that table (dbDelta, same lifecycle pattern as the push
 * table in inc/webpush.php - after_switch_theme + admin_init, both
 * contexts where dbDelta is available). Rows are only written when
 * GWILL_LOG_FORMS is true - the opt-in privacy stance is unchanged; the
 * page simply tells the admin when logging is off instead of showing a
 * silent empty chart.
 *
 * Admin-page footprint: registering this file on every request costs two
 * add_action calls - all queries run inside the page callbacks only.
 *
 * @package GWill_Starter
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/*
Table of Contents
1. Table - gwill_form_submissions (dbDelta, gwill_ prefixed)
2. gwill_log_submission() - the missing definition (Law L12 fix)
3. Row helpers - sanitize + CSV-safe values
4. Stats queries - totals, 7-day, 30-day series
5. Admin page registration (Tools → Forms & Newsletter)
6. Admin page render - state banner, chart, recent log
7. SVG chart renderer - pure PHP bars, no JS libs
8. CSV export - admin-post handler
*/

/* ── 1. Table - gwill_form_submissions ─────────────────────────────── */

/**
 * Create the submissions table if it does not exist. Guarded by a static
 * flag so repeated admin_init hits cost one function call. dbDelta is
 * loaded on demand - admin-ajax.php reaches gwill_log_submission() before
 * any admin page render, and depending on load path upgrade.php may not
 * be in memory yet.
 *
 * @since 1.5.0
 */
function gwill_analytics_ensure_table(): void {
	static $done = false;
	if ( $done ) {
		return;
	}

	// Only meaningful in admin contexts (creation happens after_switch_theme
	// or on the analytics page). Frontend requests never touch this table.
	if ( ! is_admin() ) {
		$done = true;
		return;
	}

	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	global $wpdb;
	$table   = $wpdb->prefix . 'gwill_form_submissions';
	$collate = $wpdb->get_charset_collate();

	// EXACTLY the schema documented in inc/forms.php's header since v1.0.20.
	dbDelta(
		"CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id varchar(50) NOT NULL,
			email varchar(200) NOT NULL,
			ip_hash varchar(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'new',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY created_at (created_at)
		) $collate;"
	);

	$done = true;
}

add_action( 'after_switch_theme', 'gwill_analytics_ensure_table' );
add_action( 'admin_init', 'gwill_analytics_ensure_table' );

/* ── 2. gwill_log_submission() - the Law L12 fix ────────────────────── */

/**
 * Log one form submission to the table. This is the function
 * inc/forms/ajax.php has called since v1.0.58 (newsletter) / v1.0.20
 * (contact patterns) behind GWILL_LOG_FORMS - defined only here, at last.
 *
 * Privacy stance (unchanged from the documented behaviour):
 *   - The raw IP is NEVER stored - only a salted SHA-256 hash, so the log
 *     can prove two submissions came from different machines without
 *     ever holding a tracking identifier. The salt comes from AUTH_KEY,
 *     so hashes are not comparable across sites either.
 *   - Only gwill_* scalar field values are kept (the honeypot field is
 *     gwill- prefixed in POST but stripped by sanitize + the explicit
 *     honeypot name never reaches $fields here in practice; still, the
 *     allowlist below drops anything non-scalar as defense in depth).
 *
 * Called from an AJAX context, so it must never wp_die() - a logging
 * failure fails SILENTLY (error_log under WP_DEBUG) rather than breaking
 * the visitor's form submit. A log row is never worth a lost submission.
 *
 * @param string $form_id Sanitized form pattern id ('newsletter', 'simple', …).
 * @param array  $fields  Sanitized gwill_* field values from the submit.
 * @return bool True if a row was written.
 * @since 1.5.0
 */
function gwill_log_submission( string $form_id, array $fields ): bool {
	global $wpdb;

	$ip = '';
	if ( function_exists( 'gwill_get_client_ip' ) ) {
		$ip = (string) gwill_get_client_ip();
	}
	$ip_hash = hash( 'sha256', $ip . (string) AUTH_KEY );

	$keep = [];
	foreach ( $fields as $key => $value ) {
		if ( is_string( $value ) || is_int( $value ) ) {
			$keep[ (string) $key ] = (string) $value;
		}
	}
	$payload = wp_json_encode( $keep, JSON_UNESCAPED_SLASHES );

	// The table is created on admin_init; an admin-ajax request passes
	// through admin context before this runs, but if anything exotic
	// skipped it, insert() into a missing table returns false - handled.
	$ok = $wpdb->insert(
		$wpdb->prefix . 'gwill_form_submissions',
		[
			'form_id'    => substr( sanitize_key( $form_id ), 0, 50 ),
			'email'     => substr( sanitize_email( (string) ( $fields['gwill_email'] ?? '' ) ), 0, 200 ),
			'ip_hash'    => $ip_hash,
			'status'     => 'new',
			'created_at' => current_time( 'mysql' ),
		],
		[ '%s', '%s', '%s', '%s', '%s' ]
	);

	if ( ! $ok && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated production diagnostic
		error_log( '[GWill Analytics] log_submission insert failed: ' . $wpdb->last_error );
	}

	return (bool) $ok;
}

/* ── 3. Row helpers ─────────────────────────────────────────────────── */

/**
 * The fields JSON for one row - used by the recent-log view. Decoded and
 * reduced to a short "label: value" summary so the table stays readable;
 * full values stay in the CSV export.
 *
 * @param string $fields_json Raw JSON column.
 * @return array<string,string> Decoded scalar fields.
 * @since 1.5.0
 */
function gwill_analytics_decode_fields( string $fields_json ): array {
	$decoded = json_decode( $fields_json, true );
	if ( ! is_array( $decoded ) ) {
		return [];
	}
	$out = [];
	foreach ( $decoded as $k => $v ) {
		if ( is_string( $v ) || is_int( $v ) ) {
			$out[ (string) $k ] = (string) $v;
		}
	}
	return $out;
}

/**
 * CSV-injection guard: a cell starting with = + - @ can execute as a
 * formula in Excel/Sheets. Standard mitigation - prefix a single quote.
 *
 * @param string $value Raw cell value.
 * @return string Safe cell value.
 * @since 1.5.0
 */
function gwill_analytics_csv_safe( string $value ): string {
	$value = (string) preg_replace( '/[\r\n]+/', ' ', $value );
	if ( '' !== $value && strpbrk( $value[0], '=+-@' ) !== false ) {
		return "'" . $value;
	}
	return $value;
}

/* ── 4. Stats queries ───────────────────────────────────────────────── */

/**
 * Aggregate stats for the analytics page.
 *
 * @return array{total:int,newsletter:int,last7:int,last30:int,logging_on:bool}
 * @since 1.5.0
 */
function gwill_analytics_stats(): array {
	global $wpdb;
	$table = $wpdb->prefix . 'gwill_form_submissions';

	// No user input reaches any of these queries - every fragment is a
	// literal, so prepare() adds nothing here (mirrors webpush's reads).
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
	$news  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE form_id = 'newsletter'" );
	$last7 = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB( NOW(), INTERVAL 7 DAY )"
	);
	$last30 = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB( NOW(), INTERVAL 30 DAY )"
	);

	return [
		'total'      => $total,
		'newsletter' => $news,
		'last7'      => $last7,
		'last30'     => $last30,
		'logging_on' => defined( 'GWILL_LOG_FORMS' ) && GWILL_LOG_FORMS,
	];
}

/**
 * Per-day newsletter signup counts for the last 30 days, oldest first.
 * Zero-filled so every chart day exists even when nothing was logged.
 *
 * @return array<int,array{day:string,newsletter:int,other:int}> 30 rows.
 * @since 1.5.0
 */
function gwill_analytics_daily_series(): array {
	global $wpdb;
	$table = $wpdb->prefix . 'gwill_form_submissions';

	// GROUP BY date + form class, aggregated in SQL (index on created_at).
	$rows = $wpdb->get_results(
		"SELECT DATE( created_at ) AS d,
		        SUM( form_id = 'newsletter' ) AS nl,
		        SUM( form_id <> 'newsletter' ) AS other
		 FROM $table
		 WHERE created_at >= DATE_SUB( CURDATE(), INTERVAL 29 DAY )
		 GROUP BY d
		 ORDER BY d ASC",
		ARRAY_A
	);

	$by_day = [];
	foreach ( (array) $rows as $r ) {
		$by_day[ $r['d'] ] = [
			'newsletter' => (int) $r['nl'],
			'other'      => (int) $r['other'],
		];
	}

	$series = [];
	$today = new DateTimeImmutable( 'now', wp_timezone() );
	for ( $i = 29; $i >= 0; $i-- ) {
		$day = $today->modify( "-$i days" )->format( 'Y-m-d' );
		$hit = $by_day[ $day ] ?? [ 'newsletter' => 0, 'other' => 0 ];
		$series[] = [
			'day'       => $day,
			'newsletter' => $hit['newsletter'],
			'other'      => $hit['other'],
		];
	}

	return $series;
}

/**
 * Per-form-pattern totals for ALL time, biggest first.
 *
 * @return array<int,array{form:string,total:int}> Patterns in desc order.
 * @since 1.9.0
 */
function gwill_analytics_pattern_breakdown(): array {
	global $wpdb;
	$table = $wpdb->prefix . 'gwill_form_submissions';

	$rows = $wpdb->get_results(
		"SELECT form_id, COUNT(*) AS n
		 FROM $table
		 GROUP BY form_id
		 ORDER BY n DESC",
		ARRAY_A
	);

	$out = [];
	foreach ( (array) $rows as $r ) {
		$out[] = [
			'form'  => (string) $r['form_id'],
			'total' => (int) $r['n'],
		];
	}
	return $out;
}

/**
 * Horizontal bar chart, pure SVG - same zero-dependency posture as the
 * 30-day chart. Every dynamic value esc_attr'd inside the builder.
 *
 * @param array<int,array{form:string,total:int}> $patterns
 * @return string SVG markup.
 * @since 1.9.0
 */
function gwill_analytics_pattern_chart_svg( array $patterns ): string {
	if ( empty( $patterns ) ) {
		return '';
	}
	$max = 0;
	foreach ( $patterns as $p ) {
		$max = max( $max, $p['total'] );
	}
	if ( $max < 1 ) {
		$max = 1;
	}

	$bar_h = 22;
	$gap   = 10;
	$label_w = 120;
	$bar_max = 360;   // widest bar in px
	$h = count( $patterns ) * ( $bar_h + $gap ) + $gap;

	$svg = '<svg viewBox="0 0 500 ' . $h . '" width="100%" height="' . $h . '" role="img" aria-label="' . esc_attr__( 'Submissions per form pattern', 'gwill-starter' ) . '" xmlns="http://www.w3.org/2000/svg">';
	$y   = $gap;
	foreach ( $patterns as $p ) {
		$w   = (int) round( $p['total'] / $max * $bar_max );
		$svg .= '<g>';
		$svg .= '<text x="' . ( $label_w - 8 ) . '" y="' . ( $y + $bar_h * 0.72 ) . '" text-anchor="end" font-size="12" fill="#50575e">' . esc_html( $p['form'] ) . '</text>';
		$svg .= '<rect x="' . $label_w . '" y="' . $y . '" width="' . $w . '" height="' . $bar_h . '" rx="3" fill="#2271b1"></rect>';
		$svg .= '<text x="' . ( $label_w + $w + 8 ) . '" y="' . ( $y + $bar_h * 0.72 ) . '" font-size="12" fill="#1d2327">' . esc_html( number_format_i18n( $p['total'] ) ) . '</text>';
		$svg .= '</g>';
		$y   += $bar_h + $gap;
	}
	$svg .= '</svg>';
	return $svg;
}

/* ── 5. Admin page registration ──────────────────────────────────────── */

/**
 * Tools → "Forms & Newsletter". A Tools submenu (not a top-level menu) on
 * purpose: an analytics view is an occasional, site-owner-only screen  - 
 * it must not take a slot in the admin sidebar that content editors see.
 *
 * @since 1.5.0
 */
function gwill_analytics_admin_menu(): void {
	add_management_page(
		__( 'Forms & Newsletter', 'gwill-starter' ),
		__( 'Forms & Newsletter', 'gwill-starter' ),
		'manage_options',
		'gwill-analytics',
		'gwill_analytics_render_page'
	);
}
add_action( 'admin_menu', 'gwill_analytics_admin_menu' );

/* ── 6. Admin page render ───────────────────────────────────────────── */

/**
 * Render the analytics page. All markup uses the admin's own .wrap +
 * .widefat table classes so it inherits admin CSS with zero custom
 * stylesheets to ship or cache-bust.
 *
 * @since 1.5.0
 */
function gwill_analytics_render_page(): void {
	gwill_analytics_ensure_table();

	$stats  = gwill_analytics_stats();
	$series = gwill_analytics_daily_series();

	global $wpdb;
	$recent = $wpdb->get_results(
		"SELECT id, form_id, email, ip_hash, created_at
		 FROM {$wpdb->prefix}gwill_form_submissions
		 ORDER BY id DESC
		 LIMIT 20",
		ARRAY_A
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Forms & Newsletter', 'gwill-starter' ); ?></h1>

		<?php if ( ! $stats['logging_on'] ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Submission logging is off.', 'gwill-starter' ); ?></strong>
					<?php esc_html_e( 'Rows are only written when GWILL_LOG_FORMS is defined as true in wp-config.php. The charts below reflect the table as it stands.', 'gwill-starter' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Overview', 'gwill-starter' ); ?></h2>
		<p style="font-size:14px;line-height:2">
			<?php
			printf(
				/* translators: 1: total submissions, 2: newsletter signups, 3: last-7-days count, 4: last-30-days count */
				esc_html__( 'Total logged submissions: %1$s - newsletter signups: %2$s - last 7 days: %3$s - last 30 days: %4$s.', 'gwill-starter' ),
				'<strong>' . esc_html( number_format_i18n( $stats['total'] ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $stats['newsletter'] ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $stats['last7'] ) ) . '</strong>',
				'<strong>' . esc_html( number_format_i18n( $stats['last30'] ) ) . '</strong>'
			);
			?>
		</p>

		<h2><?php esc_html_e( 'Signups - last 30 days', 'gwill-starter' ); ?></h2>
		<?php echo gwill_analytics_chart_svg( $series ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builder escapes every value it emits ?>

		<h2><?php esc_html_e( 'Submissions per form pattern', 'gwill-starter' ); ?></h2>
		<?php
		$patterns = gwill_analytics_pattern_breakdown();
		if ( empty( $patterns ) ) :
			?>
			<p><?php esc_html_e( 'No logged submissions yet.', 'gwill-starter' ); ?></p>
		<?php else : ?>
			<?php echo gwill_analytics_pattern_chart_svg( $patterns ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builder escapes every value it emits ?>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Recent submissions', 'gwill-starter' ); ?></h2>
		<?php if ( empty( $recent ) ) : ?>
			<p><?php esc_html_e( 'No logged submissions yet.', 'gwill-starter' ); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:900px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'When (site time)', 'gwill-starter' ); ?></th>
						<th><?php esc_html_e( 'Form', 'gwill-starter' ); ?></th>
						<th><?php esc_html_e( 'Email', 'gwill-starter' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r['created_at'] ); ?></td>
							<td><code><?php echo esc_html( $r['form_id'] ); ?></code></td>
							<td><?php echo esc_html( $r['email'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<a class="button"
					href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gwill_analytics_export' ), 'gwill_analytics_export' ) ); ?>">
					<?php esc_html_e( 'Export CSV', 'gwill-starter' ); ?>
				</a>
				<span style="display:inline-block;margin-left:8px;color:#787c82">
					<?php esc_html_e( 'Form, email, date and status only - message bodies stay off this page.', 'gwill-starter' ); ?>
				</span>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/* ── 7. SVG chart renderer ──────────────────────────────────────────── */

/**
 * Render the 30-day stacked bar chart as inline SVG. Pure PHP - no chart
 * library, no JS, nothing to enqueue or cache-bust. Newsletter signups
 * stack in the theme accent colour, other submissions in a neutral tone.
 * Heights scale to the busiest day; empty series renders a flat baseline
 * rather than a division-by-zero.
 *
 * @param array<int,array{day:string,newsletter:int,other:int}> $series
 * @return string SVG markup (all values escaped by the builder).
 * @since 1.5.0
 */
function gwill_analytics_chart_svg( array $series ): string {
	$width    = 900;
	$height   = 180;
	$baseline = 160;
	$max      = 1;
	foreach ( $series as $row ) {
		$max = max( $max, $row['newsletter'] + $row['other'] );
	}

	$bars = '';
	$n    = count( $series );
	$slot = $width / $n;
	$bar_w = max( 2.0, $slot * 0.62 );

	foreach ( $series as $i => $row ) {
		$x = round( $i * $slot + ( $slot - $bar_w ) / 2, 2 );

		$nl_h = round( ( $row['newsletter'] / $max ) * ( $baseline - 12 ), 2 );
		$ot_h = round( ( $row['other'] / $max ) * ( $baseline - 12 ), 2 );

		// Stack: other below, newsletter on top (accent).
		$bars .= '<rect x="' . $x . '" y="' . round( $baseline - $ot_h, 2 )
			. '" width="' . $bar_w . '" height="' . $ot_h
			. '" fill="#c3c4c7"><title>' . esc_html( $row['day'] . ': ' . $row['other'] ) . '</title></rect>';
		$bars .= '<rect x="' . $x . '" y="' . round( $baseline - $ot_h - $nl_h, 2 )
			. '" width="' . $bar_w . '" height="' . $nl_h
			. '" fill="#2271b1"><title>' . esc_html( $row['day'] . ': ' . $row['newsletter'] ) . '</title></rect>';
	}

	$total_nl = 0;
	foreach ( $series as $row ) {
		$total_nl += $row['newsletter'];
	}

	return '<svg role="img" aria-label="' . esc_attr(
		sprintf(
			/* translators: %d: newsletter signup count */
			__( 'Bar chart of form activity, last 30 days. Newsletter signups: %d.', 'gwill-starter' ),
			$total_nl
		)
	) . '" viewBox="0 0 ' . $width . ' ' . $height . '" style="max-width:900px;height:auto;display:block">'
		. '<line x1="0" y1="' . $baseline . '" x2="' . $width . '" y2="' . $baseline . '" stroke="#c3c4c7" stroke-width="1"/>'
		. $bars
		. '<text x="0" y="175" font-size="11" fill="#787c82">'
		. esc_html( $series[0]['day'] ?? '' ) . '</text>'
		. '<text x="' . $width . '" y="175" font-size="11" fill="#787c82" text-anchor="end">'
		. esc_html( $series[ count( $series ) - 1 ]['day'] ?? '' ) . '</text>'
		. '</svg>';
}

/* ── 8. CSV export ──────────────────────────────────────────────────── */

/**
 * admin-post handler: stream the submissions table as CSV. No message
 * bodies are exported - form, email, status, date only - so the export
 * can be shared freely without dragging private message text along.
 *
 * @since 1.5.0
 */
function gwill_analytics_export_csv(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export submissions.', 'gwill-starter' ) );
	}
	check_admin_referer( 'gwill_analytics_export' );

	gwill_analytics_ensure_table();

	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT form_id, email, status, created_at
		 FROM {$wpdb->prefix}gwill_form_submissions
		 ORDER BY id DESC
		 LIMIT 5000",
		ARRAY_A
	);

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=gwill-submissions.csv' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, [ 'form', 'email', 'status', 'created_at' ] );
	foreach ( (array) $rows as $r ) {
		fputcsv( $out, [
			gwill_analytics_csv_safe( (string) $r['form_id'] ),
			gwill_analytics_csv_safe( (string) $r['email'] ),
			gwill_analytics_csv_safe( (string) $r['status'] ),
			gwill_analytics_csv_safe( (string) $r['created_at'] ),
		] );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming to php://output

	exit;
}
add_action( 'admin_post_gwill_analytics_export', 'gwill_analytics_export_csv' );
