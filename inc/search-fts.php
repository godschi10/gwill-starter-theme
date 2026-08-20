<?php
/**
 * Full-coverage search via a theme-owned SQLite FTS5 index (v1.16.81)
 *
 * WHY: the client-side search index (inc/search-index.php) is
 * instant and typo-tolerant but has a hard scale ceiling (~5-8k posts:
 * sessionStorage 5MB quota, per-keystroke linear scan, multi-MB payloads
 * at 100k — measured 2.7-4.1s/keystroke at 100k posts). This file adds a
 * server-side inverted index that scales to any size (FTS5 + bm25:
 * ~10-30ms queries at 100k docs).
 *
 * PORTABILITY (King's law): the FTS index is an INDEPENDENT SQLite file
 * owned by the theme (wp-content/uploads/gwill-search/index.sqlite),
 * driven ONLY through PHP's PDO-sqlite driver — NOT through WP's primary
 * database and NOT through the SQLite drop-in's MySQL translator (which
 * rejects CREATE VIRTUAL TABLE). It therefore works identically on
 * MySQL/MariaDB/any WP backend: the only requirement is PDO-sqlite
 * (present on virtually every PHP host; verified on this box). Every
 * entry point is gated on gwill_fts_available(); if the driver or the
 * file is unusable, the feature degrades to zero — the dropdown falls
 * back to the client index, the search page to default WP search, and
 * NOTHING throws.
 *
 * @package GWill_Starter
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

// ── Index location (theme-owned, under uploads) ────────────────────────────
const GWILL_FTS_SUBDIR = 'gwill-search';
const GWILL_FTS_MAX_CONTENT_CHARS = 2000; // bound the file at scale (100k × 2KB ≈ 200MB disk max)

/**
 * Path to the FTS index file.
 *
 * @return string|false Path, or false when the dir can't be created.
 */
function gwill_fts_path() {
	static $path = null;
	if ( null !== $path ) {
		return $path;
	}
	$dir = wp_upload_dir();
	if ( ! empty( $dir['error'] ) ) {
		$path = false;
		return $path;
	}
	$base = $dir['basedir'] . '/' . GWILL_FTS_SUBDIR;
	if ( ! is_dir( $base ) && ! wp_mkdir_p( $base ) ) {
		$path = false;
		return $path;
	}
	// Silence / deny via an .htaccess for Apache/OpenLiteSpeed hosts.
	// Dual-syntax (v1.16.97 — King: "most of my clients use ols"): Apache
	// 2.2 uses Order/Deny, Apache 2.4+ uses Require (mod_access_compat is
	// often disabled on hardened hosts — a bare "Deny from all" is then
	// silently IGNORED and index.sqlite becomes HTTP-downloadable); OLS
	// honors .htaccess and accepts both forms. Nginx hosts ignore
	// .htaccess and use the theme's documented location rule instead.
	$ht_file    = $base . '/.htaccess';
	$ht_content = "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n";
	if ( ! file_exists( $ht_file ) || false === strpos( (string) @file_get_contents( $ht_file ), 'Require all denied' ) ) {
		@file_put_contents( $ht_file, $ht_content ); // phpcs:ignore
	}
	$path = $base . '/index.sqlite';
	return $path;
}

/**
 * Lazy PDO handle to the FTS index (per-request cached).
 *
 * @return PDO|false
 */
function gwill_fts_pdo() {
	static $pdo = false;
	if ( false !== $pdo ) {
		return $pdo;
	}
	if ( ! class_exists( 'PDO' ) || ! in_array( 'sqlite', PDO::getAvailableDrivers(), true ) ) {
		$pdo = false;
		return $pdo;
	}
	$path = gwill_fts_path();
	if ( ! $path ) {
		$pdo = false;
		return $pdo;
	}
	try {
		$conn = new PDO( 'sqlite:' . $path );
		$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$conn->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );
		$conn->exec( 'PRAGMA journal_mode=WAL' );
		$conn->exec( 'PRAGMA busy_timeout=5000' );
		$pdo = $conn;
	} catch ( Exception $e ) {
		$pdo = false;
	}
	return $pdo;
}

/**
 * Feature detection — the single gate for ALL FTS code paths.
 * Portable by construction: the index is a standalone file, so this is
 * true on MySQL WP too (any PHP with PDO-sqlite).
 *
 * @return bool
 */
function gwill_fts_available() {
	static $ok = null;
	if ( null !== $ok ) {
		return $ok;
	}
	$pdo = gwill_fts_pdo();
	if ( ! $pdo ) {
		$ok = false;
		return false;
	}
	try {
		$fts = $pdo->query( "SELECT sqlite_compileoption_used('ENABLE_FTS5')" )->fetchColumn();
		$ok  = (bool) $fts;
	} catch ( Exception $e ) {
		$ok = false;
	}
	return $ok;
}

/**
 * Create the FTS5 tables if missing; seed them when empty.
 *
 * @return bool
 */
function gwill_fts_ensure() {
	$pdo = gwill_fts_pdo();
	if ( ! $pdo ) {
		return false;
	}
	try {
		$pdo->exec(
			'CREATE TABLE IF NOT EXISTS gwill_fts_search('
			. 'id INTEGER PRIMARY KEY,'
			. 'title TEXT,'
			. 'excerpt TEXT,'
			. 'content TEXT,'
			. 'cat TEXT,'
			. 'date TEXT)'
		);
		// FTS5 external-content mode: the plain table stays the source of
		// truth (rebuilds), the virtual table indexes it.
		$pdo->exec(
			"CREATE VIRTUAL TABLE IF NOT EXISTS gwill_fts USING fts5("
			. 'title, excerpt, content, cat, date UNINDEXED,'
			. "content='gwill_fts_search', content_rowid='id')"
		);
		$count = (int) $pdo->query( 'SELECT count(*) FROM gwill_fts_search' )->fetchColumn();
		if ( 0 === $count ) {
			gwill_fts_rebuild( $pdo );
		}
		return true;
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Plain-text payload for one post (mirrors the client index excerpt logic).
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function gwill_fts_post_payload( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
		return null;
	}
	$cats = get_the_category( $post_id );
	$cat  = ! empty( $cats ) ? $cats[0]->name : '';
	$excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : get_post_field( 'post_content', $post_id );
	$excerpt = wp_strip_all_tags( wp_trim_words( $excerpt, 28 ) );
	$content = wp_strip_all_tags( $post->post_content );
	if ( strlen( $content ) > GWILL_FTS_MAX_CONTENT_CHARS ) {
		$content = substr( $content, 0, GWILL_FTS_MAX_CONTENT_CHARS );
	}
	return array(
		'id'      => (int) $post_id,
		'title'   => html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' ),
		'excerpt' => $excerpt,
		'content' => $content,
		'cat'     => $cat,
		'date'    => get_the_date( 'c', $post_id ),
	);
}

/**
 * Upsert one post into the FTS index (also removes on unpublish/delete).
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function gwill_fts_sync_post( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return false;
	}
	if ( ! gwill_fts_ensure() ) {
		return false;
	}
	$payload = gwill_fts_post_payload( $post_id );
	$pdo     = gwill_fts_pdo();
	if ( ! $pdo ) {
		return false;
	}
	try {
		// Remove any stale rows first (covers publish→draft→publish and
		// FTS5 external-content sync requirements).
		$pdo->prepare( 'DELETE FROM gwill_fts WHERE rowid = ?' )->execute( array( $post_id ) );
		$pdo->prepare( 'DELETE FROM gwill_fts_search WHERE id = ?' )->execute( array( $post_id ) );
		if ( $payload ) {
			$stmt = $pdo->prepare(
				'INSERT INTO gwill_fts_search(id, title, excerpt, content, cat, date) VALUES (?,?,?,?,?,?)'
			);
			$stmt->execute( array( $payload['id'], $payload['title'], $payload['excerpt'], $payload['content'], $payload['cat'], $payload['date'] ) );
			// External-content FTS5: insert with the same rowid.
			$pdo->prepare( 'INSERT INTO gwill_fts(rowid, title, excerpt, content, cat, date) VALUES (?,?,?,?,?,?)' )
				->execute( array( $payload['id'], $payload['title'], $payload['excerpt'], $payload['content'], $payload['cat'], $payload['date'] ) );
		}
		return true;
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Full rebuild of the FTS index.
 *
 * @param PDO|null $pdo Optional handle (reused during ensure).
 * @return bool
 */
function gwill_fts_rebuild( $pdo = null ) {
	if ( ! $pdo ) {
		$pdo = gwill_fts_pdo();
	}
	if ( ! $pdo ) {
		return false;
	}
	try {
		$pdo->exec( 'DELETE FROM gwill_fts' );
		$pdo->exec( 'DELETE FROM gwill_fts_search' );
		$ids = get_posts(
			array(
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'numberposts'   => -1,
				'fields'        => 'ids',
				'orderby'       => 'date',
				'order'         => 'DESC',
				'no_found_rows' => true,
			)
		);
		$stmt = $pdo->prepare( 'INSERT INTO gwill_fts_search(id, title, excerpt, content, cat, date) VALUES (?,?,?,?,?,?)' );
		$fst  = $pdo->prepare( 'INSERT INTO gwill_fts(rowid, title, excerpt, content, cat, date) VALUES (?,?,?,?,?,?)' );
		foreach ( $ids as $post_id ) {
			$p = gwill_fts_post_payload( $post_id );
			if ( ! $p ) {
				continue;
			}
			$stmt->execute( array( $p['id'], $p['title'], $p['excerpt'], $p['content'], $p['cat'], $p['date'] ) );
			$fst->execute( array( $p['id'], $p['title'], $p['excerpt'], $p['content'], $p['cat'], $p['date'] ) );
		}
		return true;
	} catch ( Exception $e ) {
		return false;
	}
}

// ── Sync hooks ─────────────────────────────────────────────────────────────
add_action( 'save_post', 'gwill_fts_sync_post' );
add_action( 'deleted_post', 'gwill_fts_sync_post' );
add_action( 'wp_trash_post', 'gwill_fts_sync_post' );

// ── FTS5 MATCH query ───────────────────────────────────────────────────────
/**
 * Tokenize a user query into safe FTS5 prefix terms ("tok"*).
 *
 * Question/stop words are dropped FIRST (v1.16.95 — King: "a direct
 * question should also show results"): "how to clear redis cache" must
 * search as clear+redis+cache, not how+to+clear+redis+cache. FTS5 ANDs
 * every token, so one filler word used to kill the whole query. A query
 * that is ALL stop words falls back to the raw tokens (best effort).
 *
 * @param string $q Raw query.
 * @return string[] Safe MATCH fragments (empty when nothing usable).
 */
function gwill_fts_query_tokens( $q ) {
	$q = strtolower( trim( (string) $q ) );
	$q = preg_replace( '/[^\p{L}\p{N}\s-]/u', ' ', $q ); // keep letters, digits, hyphens
	$q = preg_replace( '/\s+/u', ' ', $q );
	if ( '' === trim( $q ) ) {
		return array();
	}
	$tokens = preg_split( '/[\s-]+/u', trim( $q ) );
	// Drop stop/question words ("how", "to", "what", "why", "my"…) so a
	// single filler word can't AND-kill the whole query. Only applies when
	// real searchable tokens remain.
	if ( function_exists( 'gwill_search_stopwords' ) ) {
		$stop   = gwill_search_stopwords();
		$sig    = array_values( array_filter( $tokens, static fn( $t ) => ! in_array( $t, $stop, true ) ) );
		$tokens = $sig ? $sig : $tokens;
	}
	$out = array();
	foreach ( $tokens as $t ) {
		$t = trim( $t );
		if ( strlen( $t ) < 2 ) {
			continue; // FTS5 minimum token length
		}
		if ( strlen( $t ) > 40 ) {
			$t = substr( $t, 0, 40 );
		}
		// Quote + prefix-star: as-you-type prefix matching, injection-safe.
		$out[] = '"' . str_replace( '"', '""', $t ) . '"*';
	}
	return array_slice( $out, 0, 6 );
}

/**
 * Run an FTS5 query, returning result posts in the SAME shape as the
 * client index items (id/title/url/excerpt/cat/cat_slug/date) so the
 * dropdown can render them unchanged.
 *
 * @param string $q     Query.
 * @param int    $limit Max results.
 * @return array
 */
function gwill_fts_search( $q, $limit = 8 ) {
	if ( ! gwill_fts_available() || ! gwill_fts_ensure() ) {
		return array();
	}
	$tokens = gwill_fts_query_tokens( $q );
	if ( ! $tokens ) {
		return array();
	}
	$pdo = gwill_fts_pdo();
	if ( ! $pdo ) {
		return array();
	}
	$match = implode( ' AND ', $tokens );
	try {
		// bm25 column weights: title ×10, excerpt ×2, content ×1, cat ×0.5.
		$sql = 'SELECT rowid, title, excerpt, cat, date,'
			. ' bm25(gwill_fts, 10.0, 2.0, 1.0, 0.5, 0.0) AS rank'
			. ' FROM gwill_fts WHERE gwill_fts MATCH ? ORDER BY rank LIMIT ' . (int) $limit;
		$stmt = $pdo->prepare( $sql );
		$stmt->execute( array( $match ) );
		$rows = $stmt->fetchAll();
	} catch ( Exception $e ) {
		return array();
	}
	$out = array();
	foreach ( $rows as $row ) {
		$out[] = array(
			'id'       => (int) $row['rowid'],
			'title'    => $row['title'],
			'url'      => get_permalink( $row['rowid'] ),
			'excerpt'  => $row['excerpt'],
			'cat'      => $row['cat'],
			'cat_slug' => '',
			'date'     => $row['date'],
		);
	}
	return $out;
}

/**
 * Match IDs only (search landing page override).
 *
 * @param string $q     Query.
 * @param int    $limit Max ids.
 * @return int[]
 */
function gwill_fts_match_ids( $q, $limit = 200 ) {
	$items = gwill_fts_search( $q, $limit );
	return array_map( 'intval', wp_list_pluck( $items, 'id' ) );
}

/**
 * Full-corpus fuzzy candidate IDs via FTS5 prefix-relaxation OR
 * (v1.16.96 — King: "scaling past 100k posts"). When the strict AND
 * match finds nothing, the fuzzy scorer needs a candidate pool. Scanning
 * get_posts(500) in PHP caps coverage at the NEWEST 500 posts and costs
 * ~18 ms — both fail at 100k. Instead, expand each query token through
 * progressively shorter prefixes ("andriod" → andriod* OR andrio* OR
 * andri* OR andr* OR and*) and let FTS5 (a real index) do the matching:
 * full-corpus coverage, bounded by LIMIT, immune to corpus size.
 *
 * @param string $q     Raw query.
 * @param int    $limit Max candidate ids.
 * @return int[] 0+ candidate post IDs (empty when nothing relaxes).
 * @since 1.16.96
 */
function gwill_fts_relaxed_candidate_ids( $q, $limit = 200 ) {
	if ( ! gwill_fts_available() || ! gwill_fts_ensure() ) {
		return array();
	}
	$pdo = gwill_fts_pdo();
	if ( ! $pdo ) {
		return array();
	}
	$tokens = gwill_fts_query_tokens( $q ); // already stop-word stripped, quoted "tok"*
	if ( ! $tokens ) {
		return array();
	}
	$limit = max( 1, min( 500, (int) $limit ) );

	// Build rungs (most specific first): for each token, its full prefix,
	// its adjacent-swap variants (Damerau transpositions — "reids" → "redis",
	// "andriod" → "android"), then progressively shorter prefixes down to
	// 3 chars. Swap rungs catch typos broken before the 3-char prefix
	// anchor that pure relaxation would miss.
	$rungs = array();
	foreach ( $tokens as $tok ) {
		$bare = ( 2 < strlen( $tok ) ) ? substr( $tok, 1, -2 ) : $tok; // "andriod"* → andriod
		$bare = str_replace( '""', '"', $bare );
		// 1) full prefix
		if ( strlen( $bare ) >= 3 ) {
			$rungs[] = '"' . str_replace( '"', '""', $bare ) . '"*';
		}
		// 2) adjacent-swap variants (bounded to first 6 positions)
		$max_swap = min( 6, strlen( $bare ) - 1 );
		for ( $i = 0; $i < $max_swap; $i++ ) {
			$swapped = substr( $bare, 0, $i ) . $bare[ $i + 1 ] . $bare[ $i ] . substr( $bare, $i + 2 );
			if ( strlen( $swapped ) >= 3 ) {
				$rungs[] = '"' . str_replace( '"', '""', $swapped ) . '"*';
			}
		}
		// 3) progressively shorter prefixes
		for ( $len = strlen( $bare ) - 1; $len >= 3; $len-- ) {
			$sub     = substr( $bare, 0, $len );
			$rungs[] = '"' . str_replace( '"', '""', $sub ) . '"*';
		}
	}
	if ( ! $rungs ) {
		return array();
	}
	// Cap rungs so pathological queries stay bounded.
	$rungs = array_slice( $rungs, 0, 60 );

	$seen = array();
	try {
		foreach ( $rungs as $rung ) {
			$remaining = $limit - count( $seen );
			if ( $remaining <= 0 ) {
				break; // quota filled — never pay to rank a broader rung
			}
			$sql  = 'SELECT rowid FROM gwill_fts WHERE gwill_fts MATCH ? ORDER BY rank LIMIT ' . $remaining;
			$stmt = $pdo->prepare( $sql );
			$stmt->execute( array( $rung ) );
			foreach ( $stmt->fetchAll() as $r ) {
				$seen[ (int) $r['rowid'] ] = true;
			}
		}
	} catch ( Exception $e ) {
		return array();
	}

	return array_map( 'intval', array_keys( $seen ) );
}

// ── Backend swap: the theme's SINGLE search entry point ────────────────────
// gwill_execute_search() (inc/search.php) routes BOTH the results page and
// the live REST endpoint /wp-json/gwill/v1/search?s= through the
// gwill_search_backend filter. Hooking it here means FTS5 ranking powers
// both surfaces at once — no parallel endpoint, no second architecture.
add_filter(
	'gwill_search_backend',
	function ( $result, $args, $term ) {
		if ( ! gwill_fts_available() ) {
			return null; // native WP search (portability)
		}
		$ids = gwill_fts_match_ids( $term, 200 );
		// v1.16.95 (King: "a dumb person with typos can find answers"):
		// when strict FTS5 AND-prefix returns nothing, fall back to the
		// fuzzy title scorer so "docker ubntu" and question queries still
		// surface real result cards (Google shows corrected results, not
		// just a suggestion word).
		if ( empty( $ids ) && function_exists( 'gwill_search_fuzzy_match_ids' ) ) {
			$ids = gwill_search_fuzzy_match_ids( $term, 200 );
		}
		if ( empty( $ids ) ) {
			return null; // let native WP try (it may still LIKE-match)
		}
		$query_args = array(
			'post__in'       => $ids,
			'orderby'        => 'post__in', // preserve bm25 order
			'post_type'      => isset( $args['post_type'] ) ? $args['post_type'] : 'any',
			'post_status'    => 'publish',
			'posts_per_page' => isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 10,
			's'              => '', // neutralize WP LIKE so it can't AND-empty our ids
		);
		return new WP_Query( $query_args );
	},
	10,
	3
);

// ── Search landing page (/?s=…) powered by FTS5 when available ────────────
add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}
		$s = trim( (string) $query->get( 's' ) );
		if ( '' === $s || ! gwill_fts_available() ) {
			return;
		}
		$ids = gwill_fts_match_ids( $s, 200 );
		// v1.16.95: fuzzy fallback on the results page too — a typo or
		// direct question must render real cards, not an empty state.
		if ( empty( $ids ) && function_exists( 'gwill_search_fuzzy_match_ids' ) ) {
			$ids = gwill_search_fuzzy_match_ids( $s, 200 );
		}
		if ( empty( $ids ) ) {
			$query->set( 'post__in', array( 0 ) ); // honest empty result set
		} else {
			$query->set( 'post__in', $ids );
			$query->set( 'orderby', 'post__in' );
		}
		// Neutralize WP's own LIKE search so it can't AND-empty our ids.
		$query->set( 's', '' );
		$GLOBALS['gwill_fts_search_term'] = $s;
		add_filter(
			'get_search_query',
			function ( $term ) {
				return isset( $GLOBALS['gwill_fts_search_term'] ) ? $GLOBALS['gwill_fts_search_term'] : $term;
			},
			99
		);
	}
);