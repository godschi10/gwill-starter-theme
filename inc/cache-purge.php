<?php
defined( 'ABSPATH' ) || exit;

/**
 * Cache Purge Hook  -  GWill Starter.
 *
 * Ported from gwillchijioke-theme inc/cache.php (portfolio, live-proven),
 * stripped of the portfolio-specific LiteSpeed settings doc-block (the
 * starter has no LiteSpeed dependency; server tuning belongs to the host
 * layer, not the starter).
 *
 * On every published save_post: if PURGE_SECRET is defined (production
 * nginx layer), fan out a purge of the affected URLs to the site's
 * /api/cache-purge endpoint (derived from home_url() so the purge
 * follows the site on migration). Otherwise (dev box) wipe the local
 * nginx FastCGI page cache  -  FILES only, never directories.
 *
 * @package GWill_Starter
 * @since   1.6.0
 */
/*
* TABLE OF CONTENTS
* ─────────────────────────────────────────────────────────────────────────────
*   1. gwill_trigger_cache_purge  Fan-out purge on saves
*   2. gwill_purge_local_fastcgi_cache  Local FastCGI cache wipe
* ─────────────────────────────────────────────────────────────────────────────
*/
add_action( 'save_post', 'gwill_trigger_cache_purge' );

// ── 1. gwill_trigger_cache_purge ──────────────────────────
function gwill_trigger_cache_purge( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( get_post_status( $post_id ) !== 'publish' ) return;

	// Dev box (no PURGE_SECRET): purge the local nginx FastCGI page cache so
	// anonymous visitors see the edit immediately. FILES only  -  never dirs
	// (deleting dirs cascades and nukes the cache root; see workflow skill).
	if ( ! defined( 'PURGE_SECRET' ) ) {
		gwill_purge_local_fastcgi_cache();
		return;
	}

	$urls = array( get_permalink( $post_id ), home_url( '/' ) );
	foreach ( wp_get_post_categories( $post_id, array( 'fields' => 'slugs' ) ) as $slug ) {
		$urls[] = home_url( '/' . $slug . '/' );
	}

	// Purge endpoint  -  derived from home_url() so the purge follows the site
	// on migration (portability audit 2026-08-20 on the source theme: a
	// hardcoded domain broke on any domain change).
	$purge_url = home_url( '/api/cache-purge' );
	wp_remote_post( $purge_url, array(
		'headers'  => array(
			'Content-Type'   => 'application/json',
			'X-Purge-Secret' => PURGE_SECRET,
		),
		'body'     => wp_json_encode( array( 'urls' => array_unique( $urls ) ) ),
		'timeout'  => 5,
		'blocking' => false,
	) );
}

// ── 2. gwill_purge_local_fastcgi_cache ────────────────────
/**
 * Purge the local nginx FastCGI page cache (dev-box path).
 * Deletes files only, never directories.
 */
function gwill_purge_local_fastcgi_cache() {
	// Cache root configurable via constant (portability: the default is the
	// dev-box path; a migrated install defines its own in wp-config).
	$cache_root = defined( 'GWILL_NGINX_CACHE_DIR' ) ? GWILL_NGINX_CACHE_DIR : '/var/run/nginx-cache';
	if ( ! is_dir( $cache_root ) ) {
		return;
	}

	// A purge failure must NEVER break a post save  -  the cache dirs can be
	// unreadable (root-owned after nginx reloads, perms drift, SELinux, …).
	// Wrap the walk in try/catch and skip unreadable subtrees.
	// (Hit Aug 15 2026 on the source theme: save_post fatals when
	// /var/run/nginx-cache/* dirs aren't traversable by the PHP user  - 
	// wp_update_post aborted mid-hook.)
	try {
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $cache_root, FilesystemIterator::SKIP_DOTS )
		);
		$it->setMaxDepth( 8 );
		foreach ( $it as $file ) {
			if ( $file->isFile() ) {
				@unlink( $file->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}
	} catch ( UnexpectedValueException $e ) {
		// Unreadable subtree  -  purge what we can, never fatal.
		return;
	}
}
