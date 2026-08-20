<?php
/**
 * Search — backend functions and plugin swap stub.
 *
 * ════════════════════════════════════════════════════════════════════
 * PLUGIN SWAP STUB
 * ════════════════════════════════════════════════════════════════════
 * Every search query in the theme (results page + live-search REST
 * endpoint) routes through gwill_execute_search(). Three filter hooks
 * let you replace the backend without touching any theme file:
 *
 * 1. gwill_search_post_types — change which post types are searched.
 *
 *    add_filter( 'gwill_search_post_types', fn() => [ 'post', 'project' ] );
 *
 * 2. gwill_search_args — modify WP_Query args before execution.
 *
 *    add_filter( 'gwill_search_args', function ( $args, $term ) {
 *        $args['meta_query'] = [ ... ]; // add custom field search
 *        return $args;
 *    }, 10, 2 );
 *
 * 3. gwill_search_backend — return a WP_Query to completely bypass
 *    native WordPress search (e.g. hand off to SearchWP or Algolia).
 *    Return null to let native WP run (default).
 *
 *    add_filter( 'gwill_search_backend', function ( $result, $args, $term ) {
 *        return new SomePlugin_Query( [ 's' => $term ] ); // must expose ->posts
 *    }, 10, 3 );
 *
 * ════════════════════════════════════════════════════════════════════
 * SEARCH PATTERNS SHIPPED
 * ════════════════════════════════════════════════════════════════════
 * Combo A — Default (expandable icon + page-reload + search.php)
 *   template-parts/search/search-form-expandable.php
 *   search.php
 *
 * Combo B — Opt-in (modal overlay + live REST autocomplete + search.php fallback)
 *   template-parts/search/search-form-modal.php
 *   assets/js/search-modal.js
 *
 * To switch from A to B, replace one line in header.php:
 *   gwill_part( 'search/search-form-expandable' );  ← A
 *   gwill_part( 'search/search-form-modal' );        ← B
 *
 * @package GWill_Starter
 * @since   1.0.23
 */

defined( 'ABSPATH' ) || exit;


// ─────────────────────────────────────────────────────────────────────────────
// Core search execution
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Execute a search query through the plugin swap filter chain.
 *
 * This is the single entry point for all search queries in the theme.
 * See the file-header docblock for how to hook in and swap the backend.
 *
 * @param string $term  Raw (unsanitised) search term.
 * @param array  $args  Optional WP_Query args merged over the defaults.
 * @return WP_Query
 * @since 1.0.23
 */
function gwill_execute_search( string $term, array $args = [] ): WP_Query {

	$defaults = [
		's'              => sanitize_text_field( $term ),
		'post_type'      => apply_filters( 'gwill_search_post_types', [ 'post', 'page' ] ),
		'post_status'    => 'publish',
		'posts_per_page' => (int) get_option( 'posts_per_page', 10 ),
	];

	/**
	 * Filter WP_Query args before the search executes.
	 *
	 * @param array  $args     Merged query args — $args['s'] is sanitized via
	 *                         sanitize_text_field() and is what any filter
	 *                         callback should use for DB operations.
	 * @param string $term_raw The raw, UNsanitized term as originally passed
	 *                         to gwill_execute_search() — provided for
	 *                         context/logging only. Do not use this for any
	 *                         database query; use $args['s'] instead.
	 */
	$args = apply_filters( 'gwill_search_args', wp_parse_args( $args, $defaults ), $term );

	/**
	 * Completely replace search execution.
	 *
	 * Return a WP_Query instance (or any object exposing ->posts and
	 * ->found_posts) to bypass native WordPress search. Return null
	 * (default) to let WP_Query run normally.
	 *
	 * @param WP_Query|null $result Null by default.
	 * @param array         $args   Final query args.
	 * @param string        $term   Sanitised search term.
	 */
	$custom = apply_filters( 'gwill_search_backend', null, $args, $term );

	return ( $custom instanceof WP_Query ) ? $custom : new WP_Query( $args );
}


// ─────────────────────────────────────────────────────────────────────────────
// REST endpoint — live search (Combo B modal)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Register /wp-json/gwill/v1/search for the modal autocomplete.
 *
 * Routes through gwill_execute_search() so all backend swap hooks
 * (SearchWP, Algolia, custom meta) also affect the live results.
 *
 * @since 1.0.23
 */
add_action( 'rest_api_init', function () {
	register_rest_route(
		'gwill/v1',
		'/search',
		[
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'gwill_rest_search_handler',
			'permission_callback' => 'gwill_search_rate_limit_check',
			'args'                => [
				's'        => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'minLength'         => 2,
				],
				'per_page' => [
					'default'           => 5,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]
	);
} );

/**
 * Permission callback for the live-search REST route — a request-count
 * window, not the simple "one request, then a five-minute lockout"
 * pattern gwill_form_rate_limited() uses for the contact form in
 * inc/forms.php. That pattern doesn't fit here: a visitor typing into
 * live search legitimately fires several requests within a few seconds
 * as they type, where a contact form is submitted once. This caps total
 * requests within a short window instead of blocking after the first one.
 *
 * Reuses gwill_get_client_ip() from inc/forms.php rather than duplicating
 * IP-detection logic in a second file — forms.php is required before
 * search.php in functions.php, so it's already defined by the time this
 * runs.
 *
 * Implementation note: this is a fixed window that resets its TTL on
 * every request within it, not a true sliding window — a sustained,
 * continuously-active session can stay capped at the limit until a full
 * window passes with no further requests, rather than the limit rolling
 * forward smoothly. Accepted as a reasonable simplification: minLength=2
 * plus the autocomplete UI's own input debouncing means a real typing
 * session realistically generates well under the default 20-per-10s
 * threshold, so this only meaningfully affects a scripted flood, not
 * normal typing.
 *
 * current_user_can( 'edit_posts' ) is exempt, matching the contact form's
 * own exemption and for the same reason — testing shouldn't trip the same
 * protection meant for abuse.
 *
 * @return true|WP_Error
 * @since 1.0.64
 */
function gwill_search_rate_limit_check() {

	if ( current_user_can( 'edit_posts' ) ) {
		return true;
	}

	$key = 'gwill_search_rl_' . hash( 'sha256', gwill_get_client_ip() );

	/**
	 * Max live-search requests allowed per IP within the window.
	 *
	 * @param int $max
	 * @since 1.0.64
	 */
	$max = (int) apply_filters( 'gwill_search_rate_limit_max', 20 );

	/**
	 * The window itself, in seconds.
	 *
	 * @param int $seconds
	 * @since 1.0.64
	 */
	$window = (int) apply_filters( 'gwill_search_rate_limit_seconds', 10 );

	$count = (int) get_transient( $key );

	if ( $count >= $max ) {
		return new WP_Error(
			'gwill_search_rate_limited',
			__( 'Too many search requests. Please wait a moment and try again.', 'gwill-starter' ),
			[ 'status' => 429 ]
		);
	}

	set_transient( $key, $count + 1, $window );

	return true;
}

/**
 * REST callback for GET /gwill/v1/search.
 *
 * Returns a minimal JSON array shaped for the autocomplete UI:
 *   [ { id, title, url, type, excerpt }, ... ]
 *
 * Responses are publicly cacheable for 60 s — this is public search data.
 *
 * @param  WP_REST_Request $request
 * @return WP_REST_Response
 * @since  1.0.23
 */
function gwill_rest_search_handler( WP_REST_Request $request ): WP_REST_Response {

	$term     = (string) $request->get_param( 's' );
	$per_page = min( (int) $request->get_param( 'per_page' ), 10 );

	$query   = gwill_execute_search( $term, [ 'posts_per_page' => $per_page ] );
	$results = [];

	foreach ( $query->posts as $post ) {
		$type_obj  = get_post_type_object( $post->post_type );
		$results[] = [
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'type'    => $type_obj ? $type_obj->labels->singular_name : $post->post_type,
			'excerpt' => has_excerpt( $post )
				? wp_trim_words( get_the_excerpt( $post ), 12 )
				: '',
		];
	}

	$response = new WP_REST_Response( $results );
	$response->header( 'X-WP-Total', (int) $query->found_posts );
	$response->header( 'Cache-Control', 'public, max-age=60, s-maxage=60' );

	return $response;
}


// ─────────────────────────────────────────────────────────────────────────────
// Template helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Return the current search term, sanitised for HTML attribute output.
 *
 * IMPORTANT: The return value is already passed through esc_attr(). Do NOT
 * escape the return value again at the call site — double-encoding will
 * corrupt & → &amp;amp; and produce visible noise in the input field.
 *
 * @return string HTML-attribute-escaped search term.
 * @since 1.0.23
 */
function gwill_get_search_term(): string {
	return esc_attr( get_search_query() );
}

/**
 * Return a localised results-count string for the search results header.
 *
 * Produces:
 *   '12 results for "foo"'   (multiple)
 *   '1 result for "foo"'     (single)
 *   'No results for "foo"'   (zero)
 *
 * @param  WP_Query $query The current search query.
 * @return string          Safe HTML — only <strong> allowed.
 * @since  1.0.23
 */
function gwill_search_results_count( WP_Query $query ): string {

	$count = (int) $query->found_posts;
	$term  = '<strong>' . esc_html( get_search_query() ) . '</strong>';

	if ( 0 === $count ) {
		return sprintf(
			/* translators: %s: search term wrapped in <strong> */
			__( 'No results for %s', 'gwill-starter' ),
			$term
		);
	}

	return sprintf(
		/* translators: 1: formatted integer result count  2: search term in <strong> */
		_n( '%1$s result for %2$s', '%1$s results for %2$s', $count, 'gwill-starter' ),
		number_format_i18n( $count ),
		$term
	);
}

// ─────────────────────────────────────────────────────────────────────────────
// Search suggestions — "Did you mean?" (v1.16.87)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Damerau–Levenshtein distance (optimal string alignment) — the typo model.
 *
 * Classic Levenshtein counts an adjacent transposition ("reids" → "redis")
 * as TWO edits; Damerau counts it as ONE, which is the reality of human
 * typing (transpositions are among the most common typo classes). Google
 * behaves this way; so do we (v1.16.88).
 *
 * @param string $a
 * @param string $b
 * @return int
 * @since 1.16.88
 */
function gwill_damerau( string $a, string $b ): int {

	$la = strlen( $a );
	$lb = strlen( $b );
	if ( 0 === $la ) { return $lb; }
	if ( 0 === $lb ) { return $la; }

	$d = [];
	for ( $i = 0; $i <= $la; $i++ ) {
		$d[ $i ][ 0 ] = $i;
	}
	for ( $j = 0; $j <= $lb; $j++ ) {
		$d[ 0 ][ $j ] = $j;
	}
	for ( $i = 1; $i <= $la; $i++ ) {
		for ( $j = 1; $j <= $lb; $j++ ) {
			$cost       = $a[ $i - 1 ] === $b[ $j - 1 ] ? 0 : 1;
			$d[ $i ][ $j ] = min(
				$d[ $i - 1 ][ $j ] + 1,        // deletion
				$d[ $i ][ $j - 1 ] + 1,        // insertion
				$d[ $i - 1 ][ $j - 1 ] + $cost // substitution
			);
			// Adjacent transposition: ab → ba costs 1.
			if ( $i > 1 && $j > 1 && $a[ $i - 1 ] === $b[ $j - 2 ] && $a[ $i - 2 ] === $b[ $j - 1 ] ) {
				$d[ $i ][ $j ] = min( $d[ $i ][ $j ], $d[ $i - 2 ][ $j - 2 ] + 1 );
			}
		}
	}

	return $d[ $la ][ $lb ];
}

/**
 * Google-style term highlighting for result titles. Self-contained
 * replacement for core wp_highlight_search_terms() — which does NOT
 * exist in WordPress 7.x (fatal "function not found" at runtime,
 * v1.16.90 lesson: never depend on core search helpers). No-op when
 * there is no active search query, so shared card templates stay
 * byte-identical outside the search page.
 *
 * @param string $title Title to highlight (already filter-processed).
 * @return string Escaped title with <mark class="search-term"> wrappers.
 * @since 1.16.90
 */
function gwill_highlight_search_terms( string $title ): string {

	$norm = gwill_search_normalize( get_search_query() );
	if ( '' === $norm ) {
		return esc_html( $title );
	}
	$tokens = array_values( array_filter(
		preg_split( '/\s+/', $norm ),
		static fn( $t ) => strlen( $t ) >= 4
	) );
	if ( ! $tokens ) {
		return esc_html( $title );
	}
	$pattern = '/(' . implode( '|', array_map( 'preg_quote', $tokens, [ '/' ] ) ) . ')/iu';
	$safe    = esc_html( $title );

	return preg_replace( $pattern, '<mark class="search-term">$1</mark>', $safe );
}

/**
 * Stop-words ignored by the suggestion scorer when they match nothing
 * (Google's "how to" tolerance): a missing article/preposition must not
 * tank a match. Deliberately NOT ignored when they DO match ("vs" is a
 * meaningful title word here, so it is not in the list).
 *
 * v1.16.95 (King: "a direct question should also show results"): the list
 * now covers the full question/pronoun set — how, to, what, why, my, we,
 * this, which… — and gwill_search_similarity() skips these tokens
 * ENTIRELY (not just when unmatched), so "why is my website slow" can
 * never score a false hit off the word "my" alone.
 *
 * @return string[]
 * @since 1.16.90
 */
function gwill_search_stopwords(): array {
	static $words = null;
	if ( null === $words ) {
		$words = [ 'how', 'to', 'the', 'a', 'an', 'and', 'or', 'for', 'with', 'without', 'of', 'in', 'on', 'at', 'by', 'from', 'your', 'you', 'it', 'its', 'is', 'are', 'was', 'were', 'what', 'why', 'when', 'where', 'who', 'whom', 'whose', 'which', 'do', 'does', 'did', 'done', 'can', 'could', 'would', 'should', 'will', 'shall', 'may', 'might', 'must', 'get', 'got', 'use', 'using', 'used', 'about', 'into', 'over', 'under', 'up', 'down', 'out', 'all', 'any', 'my', 'me', 'we', 'our', 'us', 'i', 'they', 'them', 'their', 'this', 'that', 'these', 'those', 'there', 'here', 'be', 'been', 'being', 'am', 'have', 'has', 'had', 'not', 'no', 'yes', 'but', 'if', 'than', 'then', 'so', 'too', 'very', 'just', 'also', 'more', 'most', 'some', 'such', 'each', 'own', 'other', 'make', 'making', 'doesnt', 'dont', 'cant', 'wont', 'didnt', 'wasnt', 'isnt', 'arent', 'havent', 'hasnt' ];
	}
	return $words;
}

/**
 * Stop-words for related-search MINING (v1.16.91): title words that
 * produce noise phrases if mined ("vs", "guide", "best", "right").
 * Deliberately separate from gwill_search_stopwords() — a word can be
 * meaningful for matching yet useless for related-search phrases.
 *
 * @return string[]
 * @since 1.16.91
 */
function gwill_search_mining_stopwords(): array {
	static $words = null;
	if ( null === $words ) {
		$words = [ 'vs', 'versus', 'guide', 'guides', 'best', 'new', 'top', 'right', 'ultimate', 'complete', 'easy', 'way', 'ways', 'using', 'make', 'making', 'beyond', 'behind', 'inside', 'per', 'each', 'between', 'through', 'without' ];
	}
	return $words;
}

/**
 * "Eventually what real people are searching for" (King, v1.16.92) —
 * anonymous search-query log, stored in the EXISTING FTS SQLite index
 * file (wp-content/uploads/gwill-search/index.sqlite): one tiny deduped
 * table (query → count + last_seen), capped at 500 rows and pruned to
 * the top 200 by (count, recency). The WordPress database is NEVER
 * touched: no MySQL tables, no wp_options growth — the whole log stays
 * ≈ 12 KB inside a file the theme already owns.
 *
 * Only actual search-page submissions are logged — search pages are
 * FastCGI BYPASS, so every real search reaches PHP. The header dropdown
 * keystrokes are deliberately NOT logged (noise).
 *
 * @param string $query The submitted search query.
 * @since 1.16.92
 */
function gwill_search_log_query( string $query ): void {

	if ( '' === trim( $query ) ) {
		return;
	}
	$pdo = function_exists( 'gwill_fts_pdo' ) ? gwill_fts_pdo() : false;
	if ( ! $pdo ) {
		return;
	}
	try {
		$q = substr( gwill_search_normalize( $query ), 0, 60 );
		if ( '' === $q ) {
			return;
		}
		$pdo->exec( 'CREATE TABLE IF NOT EXISTS gwill_search_log ( query TEXT PRIMARY KEY, count INTEGER NOT NULL DEFAULT 1, last_seen INTEGER NOT NULL )' );
		$st = $pdo->prepare(
			'INSERT INTO gwill_search_log (query, count, last_seen) VALUES (:q, 1, :now)
			 ON CONFLICT(query) DO UPDATE SET count = count + 1, last_seen = excluded.last_seen'
		);
		$st->execute( [ ':q' => $q, ':now' => time() ] );
		// Bounded growth: prune to the top 200 only once the log exceeds 500 rows.
		$rows = (int) $pdo->query( 'SELECT COUNT(*) FROM gwill_search_log' )->fetchColumn();
		if ( $rows > 500 ) {
			$pdo->exec( 'DELETE FROM gwill_search_log WHERE query NOT IN (SELECT query FROM gwill_search_log ORDER BY count DESC, last_seen DESC LIMIT 200)' );
		}
	} catch ( Throwable $e ) {
		// Logging must NEVER break a search — silent fail.
	}
}

/**
 * Does a term actually return results? Google never suggests a search
 * that leads to an empty page (King, v1.16.95: "the search suggestions
 * should be good and have results that people actually search for").
 * Checks FTS5 first (fast), then the fuzzy title scorer.
 *
 * @param string $term Search term.
 * @return bool
 * @since 1.16.95
 */
function gwill_search_term_has_results( string $term ): bool {

	if ( '' === trim( $term ) ) {
		return false;
	}
	if ( function_exists( 'gwill_fts_match_ids' ) && ! empty( gwill_fts_match_ids( $term, 1 ) ) ) {
		return true;
	}
	return ! empty( gwill_search_fuzzy_match_ids( $term, 1 ) );
}

/**
 * Real searches that share a token with the current query — the
 * "People also searched for" source of truth once enough people have
 * searched. Only queries logged ≥ 2 times qualify (one-off typos and
 * bot noise never surface); ranked by frequency, then recency. Every
 * returned term is verified to still return results (v1.16.95 — a
 * real search that now matches nothing is never suggested).
 *
 * @param string $query The current search query.
 * @param int    $limit Max entries.
 * @return array List of [ 'term', 'url' ].
 * @since 1.16.92
 */
function gwill_search_real_related( string $query, int $limit = 4 ): array {

	$norm   = gwill_search_normalize( $query );
	$tokens = array_values( array_filter( preg_split( '/\s+/', trim( $norm ) ), static fn( $t ) => strlen( $t ) >= 3 ) );
	if ( ! $tokens ) {
		return [];
	}
	$pdo = function_exists( 'gwill_fts_pdo' ) ? gwill_fts_pdo() : false;
	if ( ! $pdo ) {
		return [];
	}
	try {
		$likes = [];
		$args  = [];
		foreach ( $tokens as $i => $t ) {
			$likes[]         = "query LIKE :t{$i}";
			$args[ ":t{$i}" ] = '%' . $t . '%';
		}
		$args[':self'] = $norm;
		$st = $pdo->prepare(
			'SELECT query, count FROM gwill_search_log
			 WHERE count >= 2 AND (' . implode( ' OR ', $likes ) . ") AND query != :self
			 ORDER BY count DESC, last_seen DESC LIMIT " . max( 1, (int) $limit )
		);
		$st->execute( $args );
		$out = [];
		foreach ( $st->fetchAll() as $row ) {
			// v1.16.95: never surface a real search that now matches nothing.
			if ( ! gwill_search_term_has_results( $row['query'] ) ) {
				continue;
			}
			$out[] = [
				'term' => $row['query'],
				'url'  => home_url( '/?s=' . rawurlencode( $row['query'] ) ),
			];
		}
		return $out;
	} catch ( Throwable $e ) {
		return [];
	}
}

// Capture every real search-page submission (anonymous, bounded).
add_action( 'template_redirect', function () {
	if ( is_search() && ! is_admin() && ! wp_doing_ajax() ) {
		$q = get_search_query();
		if ( '' !== trim( $q ) ) {
			gwill_search_log_query( $q );
		}
	}
} );

/**
 * Normalize a string for similarity scoring: lowercase, alphanumerics only.
 *
 * @param string $s
 * @return string
 * @since 1.16.87
 */
function gwill_search_normalize( string $s ): string {
	// Lowercase FIRST, then strip — the character class is deliberately
	// lowercase-only because strtolower() already ran (v1.16.87 fixed the
	// original reverse order + missing A–Z, which silently dropped the
	// first letter of every capitalised word: "Redis" → "edis").
	return preg_replace( '/[^a-z0-9]+/', ' ', strtolower( $s ) );
}

/**
 * Similarity score between a query and a title, 0.0–1.0 (higher = closer).
 *
 * Google-grade matcher (v1.16.88 — King: "bring results from misspelled
 * words even if it's the first middle or last letter or spacing"):
 *   1. Token-level Levenshtein WITHOUT a first-letter anchor — a typo on
 *      ANY character (first, middle, last) still matches, guarded only by
 *      a length-ratio filter so unrelated words can't latch on. Token
 *      scores are weighted by query-token length: short filler tokens
 *      ("db", "15") can't zero a strong match, and one strong word can't
 *      hijack a query whose other words match nothing.
 *   2. Spacing-insensitive full-string Levenshtein — "redisvsmongo" vs
 *      "Redis vs MongoDB" and "mongo db" vs "mongodb" both compare
 *      normalized concatenations, so missing/extra spaces are free.
 *   3. Containment boost — when one normalized string sits inside the
 *      other (min 4 chars), the full-string signal is floored at 0.8.
 *
 * @param string $a Normalized query.
 * @param string $b Normalized title.
 * @return float
 * @since 1.16.87
 */
function gwill_search_similarity( string $a, string $b ): float {

	$at = array_values( array_filter( preg_split( '/\s+/', trim( $a ) ) ) );
	$bt = array_values( array_filter( preg_split( '/\s+/', trim( $b ) ) ) );

	if ( ! $at || ! $bt || $a === $b ) {
		return 0.0;
	}

	// Spacing-insensitive forms for the full-string signal.
	$ca = preg_replace( '/\s+/', '', $a );
	$cb = preg_replace( '/\s+/', '', $b );
	if ( '' === $ca || '' === $cb ) {
		return 0.0;
	}

	$stopwords = gwill_search_stopwords();

	// 1. Token-level, length-weighted.
	$weighted  = 0.0;
	$total_len = 0;
	$exact_hit = false;
	foreach ( $at as $qt ) {
		// v1.16.95 (King: "a direct question should also show results"):
		// function words are skipped ENTIRELY — not just when unmatched —
		// so "my" can never earn the exact-word OR bonus off "How I Cut My
		// Android…". A question like "why is my website slow" scores on its
		// real tokens only.
		if ( in_array( $qt, $stopwords, true ) ) {
			continue;
		}
		$qlen = strlen( $qt );
		$best = 0.0;
		foreach ( $bt as $tt ) {
			$tlen = strlen( $tt );
			if ( $qlen < 3 || $tlen < 3 ) {
				// Short tokens (2 chars or less) must be exact — "db" must
				// not fuzzy-match "da", and "15" must match "15" only.
				if ( $qlen !== $tlen ) {
					continue;
				}
			} else {
				// Length-ratio guard: only comparable tokens (missed space,
				// one missing letter, doubled letter) — not random words.
				if ( max( $qlen, $tlen ) / max( min( $qlen, $tlen ), 1 ) > 2.0 ) {
					continue;
				}
			}
			$d   = gwill_damerau( $qt, $tt );
			$sim = max( 0.0, 1.0 - ( $d / max( $qlen, $tlen, 1 ) ) );
			if ( $sim > $best ) {
				$best = $sim;
			}
		}
		// Spacing fix: a concatenated query token ("redisvsmongo") can also
		// match the title's spacing-insensitive concatenation.
		$clen = strlen( $cb );
		if ( $qlen >= 3 && $clen >= 3
			&& max( $qlen, $clen ) / max( min( $qlen, $clen ), 1 ) <= 2.5 ) {
			$d2  = gwill_damerau( $qt, $cb );
			$sim = max( 0.0, 1.0 - ( $d2 / max( $qlen, $clen, 1 ) ) );
			if ( $sim > $best ) {
				$best = $sim;
			}
		}
		// Prefix/stem bonus: "andro" → "android", "postgre" → "postgresql"
		// (Google's prefix matching — a 4+ char prefix is a real signal).
		if ( 0.0 === $best && $qlen >= 3 ) {
			foreach ( $bt as $tt ) {
				$tlen = strlen( $tt );
				if ( $tlen >= $qlen && 0 === strpos( $tt, $qt ) && ( $tlen / $qlen ) <= 2.5 ) {
					$best = 0.85;
					break;
				}
			}
		}
		if ( 1.0 === $best ) {
			// Google's OR behaviour: an EXACT word in the query is a strong
			// signal even when another word matches nothing on the site
			// ("redis vsmongo" → Redis results, never an empty page).
			$exact_hit = true;
		}
		// Stop-word tolerance: an unmatched article/preposition must not
		// tank the match (Google ignores "how to").
		if ( 0.0 === $best && in_array( $qt, $stopwords, true ) ) {
			continue;
		}
		$weighted += $best * $qlen;
		$total_len += $qlen;
	}

	if ( 0 === $total_len ) {
		return 0.0;
	}
	$token_score = $weighted / $total_len;

	// 2. Spacing-insensitive full-string similarity.
	$full = 1.0 - ( gwill_damerau( $ca, $cb ) / max( strlen( $ca ), strlen( $cb ), 1 ) );

	// 3. Containment boost — one normalized string inside the other.
	if ( strlen( $ca ) >= 4 && strlen( $cb ) >= 4
		&& ( false !== strpos( $ca, $cb ) || false !== strpos( $cb, $ca ) ) ) {
		$full = max( $full, 0.8 );
	}

	$score = 0.6 * $token_score + 0.4 * max( 0.0, $full );

	// 4. Exact-word bonus (Google's OR behaviour): one query word matches a
	//    title word perfectly — surface it even if a sibling word is absent
	//    from the corpus ("redis vsmongo" → the Redis guides).
	if ( $exact_hit ) {
		$score = min( 1.0, $score + 0.15 );
	}

	return $score;
}

/**
 * "Did you mean?" — find the closest searchable title for a query that
 * returned no results and derive the corrected WORDS from it (Google
 * suggests words, not post cards — v1.16.89). Database-agnostic by
 * design (portability law): reads titles through get_posts(), so it
 * works with ANY database, no FTS index required — and at this site's
 * scale (≤ 500 titles) it is a handful of milliseconds.
 *
 * @param string $term  Raw search term (get_search_query()).
 * @param int    $limit Max number of distinct corrected terms to return.
 * @return array List of [ 'term' => corrected words, 'url' => search URL ],
 *               best first. Empty when nothing is close enough.
 * @since 1.16.87
 */
function gwill_search_suggest( string $term, int $limit = 3 ): array {

	$term = sanitize_text_field( $term );
	$norm = gwill_search_normalize( $term );

	if ( strlen( $norm ) < 2 ) {
		return [];
	}

	$at = array_values( array_filter( preg_split( '/\s+/', trim( $norm ) ) ) );

	$post_types = apply_filters( 'gwill_search_post_types', [ 'post', 'page' ] );
	$threshold  = (float) apply_filters( 'gwill_search_suggest_threshold', 0.45 );

	// Settings pages (ACF pages in the tech theme; filterable here) must
	// never be suggested. The starter ships no settings pages by default —
	// child themes add slugs via the filter.
	$settings_slugs = apply_filters( 'gwill_search_suggest_excluded_slugs', [] );
	$exclude = [];
	if ( function_exists( 'gwill_page_id' ) ) {
		foreach ( $settings_slugs as $slug ) {
			$id = gwill_page_id( $slug );
			if ( $id ) {
				$exclude[] = $id;
			}
		}
	}

	// v1.16.96 (King: "scaling past 100k posts"): the suggestion pool must
	// cover the whole corpus, not just the newest 500. Prefer the FTS5
	// prefix-relaxation candidates (full coverage, bounded, ~1 ms);
	// get_posts(500) remains the portability fallback without the index.
	// When the index exists, its empty answer is authoritative.
	$fts_available = function_exists( 'gwill_fts_available' ) && gwill_fts_available();
	$candidate_ids = ( $fts_available && function_exists( 'gwill_fts_relaxed_candidate_ids' ) )
		? gwill_fts_relaxed_candidate_ids( $term, 500 )
		: [];

	if ( empty( $candidate_ids ) && ! $fts_available ) {
		$candidate_ids = get_posts( [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'exclude'        => $exclude,
		] );
	} else {
		$candidate_ids = array_values( array_diff( $candidate_ids, $exclude ) );
	}

	// Distinct corrected terms, best score kept per term.
	$candidates = [];

	foreach ( $candidate_ids as $id ) {
		$title = get_the_title( $id );
		$tnorm = gwill_search_normalize( $title );
		if ( '' === $tnorm || $tnorm === $norm ) {
			continue;
		}
		$score = gwill_search_similarity( $norm, $tnorm );
		if ( $score <= $threshold ) {
			continue;
		}
		$bt    = array_values( array_filter( preg_split( '/\s+/', trim( $tnorm ) ) ) );
		$term2 = gwill_search_corrected_term( $at, $bt, preg_replace( '/\s+/', '', $tnorm ) );
		// A corrected term identical to the original query is pointless,
		// and an empty one means no word actually corrected.
		if ( '' === $term2 || $term2 === $norm ) {
			continue;
		}
		// v1.16.95 (King: "the search suggestions should be good and have
		// results that people actually search for"): never suggest a term
		// whose search returns an empty page.
		if ( ! gwill_search_term_has_results( $term2 ) ) {
			continue;
		}
		if ( ! isset( $candidates[ $term2 ] ) || $score > $candidates[ $term2 ]['score'] ) {
			$candidates[ $term2 ] = [
				'score' => $score,
				'term'  => $term2,
			];
		}
	}

	if ( ! $candidates ) {
		return [];
	}

	uasort( $candidates, static function ( $a, $b ) {
		return $b['score'] <=> $a['score'];
	} );

	$out = [];
	foreach ( array_slice( $candidates, 0, max( 1, (int) $limit ), true ) as $c ) {
		$out[] = [
			'term'  => $c['term'],
			'url'   => home_url( '/?s=' . rawurlencode( $c['term'] ) ),
			'score' => $c['score'],
		];
	}

	return $out;
}

/**
 * Fuzzy result retrieval — the "dumb person with typos can find answers"
 * fallback (King, v1.16.95). When FTS5's strict AND-prefix match returns
 * nothing ("docker ubntu", "what is android private space"), this scores
 * every searchable title with the SAME similarity engine that powers
 * "Did you mean?" and returns the best post IDs as real RESULT CARDS —
 * Google shows corrected results, not just a suggestion word.
 *
 * Same portability design as gwill_search_suggest(): reads titles through
 * get_posts(), so it works with ANY database, no FTS index required; at
 * this site's scale (≤ 500 titles) it is a handful of milliseconds.
 *
 * @param string $term  Raw search term.
 * @param int    $limit Max post IDs to return.
 * @return int[] Best-matching post IDs, highest score first.
 * @since 1.16.95
 */
function gwill_search_fuzzy_match_ids( string $term, int $limit = 200 ): array {

	$term = sanitize_text_field( $term );
	$norm = gwill_search_normalize( $term );

	if ( strlen( $norm ) < 2 ) {
		return [];
	}

	$post_types = apply_filters( 'gwill_search_post_types', [ 'post', 'page' ] );
	$threshold  = (float) apply_filters( 'gwill_search_fuzzy_match_threshold', 0.45 );

	// v1.16.96 (King: "scaling past 100k posts"): pull the candidate pool
	// from the FTS5 prefix-relaxation index when available — full-corpus
	// coverage, bounded (~1 ms), immune to corpus size. get_posts(500) is
	// now ONLY the portability fallback for installs without the index
	// (and is capped at the newest 500 — the index path has no such cap).
	// When the index EXISTS, its empty answer is authoritative (full
	// corpus scanned) — never re-scan newest-500 on top of it.
	$fts_available = function_exists( 'gwill_fts_available' ) && gwill_fts_available();
	$candidate_ids = ( $fts_available && function_exists( 'gwill_fts_relaxed_candidate_ids' ) )
		? gwill_fts_relaxed_candidate_ids( $term, max( 60, (int) $limit ) )
		: [];

	$settings_slugs = apply_filters( 'gwill_search_suggest_excluded_slugs', [] );
	$exclude = [];
	if ( function_exists( 'gwill_page_id' ) ) {
		foreach ( $settings_slugs as $slug ) {
			$id = gwill_page_id( $slug );
			if ( $id ) {
				$exclude[] = $id;
			}
		}
	}

	if ( empty( $candidate_ids ) && ! $fts_available ) {
		// Portability fallback: no FTS index → newest-500 titles (legacy
		// behavior, works on any database). When the index exists, its
		// empty answer is authoritative — skip this entirely.
		$candidate_ids = get_posts( [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'exclude'        => $exclude,
		] );
		$title_map = [];
	} else {
		$candidate_ids = array_values( array_diff( $candidate_ids, $exclude ) );
		// v1.16.96 speed: batch-fetch candidate titles from the FTS index
		// in ONE query (a dozen get_the_title() calls are the real cost of
		// the fuzzy path). get_the_title() is the fallback for rows the
		// index doesn't carry (non-post types).
		$title_map = [];
		if ( function_exists( 'gwill_fts_pdo' ) ) {
			$pdo = gwill_fts_pdo();
			if ( $pdo ) {
				try {
					$in   = implode( ',', array_fill( 0, count( $candidate_ids ), '?' ) );
					$stmt = $pdo->prepare( "SELECT id, title FROM gwill_fts_search WHERE id IN ({$in})" );
					$stmt->execute( $candidate_ids );
					foreach ( $stmt->fetchAll() as $r ) {
						$title_map[ (int) $r['id'] ] = $r['title'];
					}
				} catch ( Throwable $e ) {
					$title_map = [];
				}
			}
		}
	}

	$scored = [];
	foreach ( $candidate_ids as $id ) {
		$title = isset( $title_map[ $id ] ) ? $title_map[ $id ] : get_the_title( $id );
		$tnorm = gwill_search_normalize( $title );
		if ( '' === $tnorm || $tnorm === $norm ) {
			continue;
		}
		$score = gwill_search_similarity( $norm, $tnorm );
		if ( $score > $threshold ) {
			$scored[ $id ] = $score;
		}
	}

	if ( ! $scored ) {
		return [];
	}

	arsort( $scored ); // highest score first
	return array_map( 'intval', array_slice( array_keys( $scored ), 0, max( 1, (int) $limit ) ) );
}

/**
 * "People also searched for" — related search terms mined from the result
 * set's OWN titles. Zero extra database cost on the results page: pass
 * the already-loaded $wp_query->posts.
 *
 * ACCURACY CONTRACT (King: "beautiful idea but I need accuracy"):
 *   1. Only CONTIGUOUS significant runs are phrases ("object caching",
 *      "sentinel cluster") — never sliding-window fragments ("caching
 *      cheap", "cluster choosing").
 *   2. Single-word runs are never suggested alone (too fragmentary) —
 *      except as a fallback anchored to the query ("docker compose").
 *   3. Query-anchored phrases ("redis object caching") are emitted ONLY
 *      when the result title actually contains the query token — a
 *      content-only match ("The Hidden Cost of Cheap Hosting" for
 *      "redis") never produces the false phrase "redis hidden cost".
 *   4. Every phrase is real text from a real title on this page, so it
 *      is guaranteed to return results — nothing fabricated.
 *
 * @param array  $posts Result post objects (WP_Post[]).
 * @param string $query The search query.
 * @param int    $limit Max related terms to return.
 * @return array List of [ 'term', 'url' ], best first.
 * @since 1.16.90
 */
function gwill_search_related_terms( array $posts, string $query, int $limit = 4 ): array {

	$norm  = gwill_search_normalize( $query );
	$stop  = gwill_search_stopwords();
	$mine  = gwill_search_mining_stopwords();

	$q_tokens = array_values( array_filter( preg_split( '/\s+/', trim( $norm ) ), static fn( $t ) => strlen( $t ) >= 4 ) );
	$single   = ( 1 === count( $q_tokens ) ) ? $q_tokens[0] : '';

	$candidates = []; // term => [ 'freq' => n, 'order' => first-seen rank ]
	$order      = 0;

	foreach ( $posts as $post ) {
		$order++;
		$tokens = array_values( array_filter(
			preg_split( '/\s+/', gwill_search_normalize( get_the_title( $post ) ) ),
			static function ( $t ) use ( $stop, $mine ) {
				if ( strlen( $t ) < 4 || preg_match( '/^\d+$/', $t ) ) {
					return false;
				}
				return ! in_array( $t, $stop, true ) && ! in_array( $t, $mine, true );
			}
		) );
		if ( ! $tokens ) {
			continue;
		}
		// Contiguous significant runs — capped at 2 words so a phrase is
		// tight and real ("object caching" ✓, "object caching cheap" ✗).
		$runs = [];
		$cur  = [];
		foreach ( $tokens as $t ) {
			if ( in_array( $t, $q_tokens, true ) ) {
				if ( count( $cur ) >= 2 ) {
					$runs[] = implode( ' ', array_slice( $cur, 0, 2 ) );
				}
				$cur = [];
				continue;
			}
			$cur[] = $t;
		}
		if ( count( $cur ) >= 2 ) {
			$runs[] = implode( ' ', array_slice( $cur, 0, 2 ) );
		}
		if ( ! $runs ) {
			// Fallback: single significant words only ever appear
			// query-anchored, never bare ("docker compose" ✓, "compose" ✗).
			foreach ( $tokens as $t ) {
				if ( '' !== $single && strlen( $t ) >= 6 && $t !== $single ) {
					$phrase                        = $single . ' ' . $t;
					$candidates[ $phrase ]         = isset( $candidates[ $phrase ] )
						? [ 'freq' => $candidates[ $phrase ]['freq'] + 1, 'order' => $candidates[ $phrase ]['order'] ]
						: [ 'freq' => 1, 'order' => $order ];
				}
			}
			continue;
		}
		foreach ( $runs as $run ) {
			$phrase = $run;
			// Query-anchored ONLY when this title really contains the query
			// token (title match), so content-only hits stay plain runs.
			if ( '' !== $single && false !== strpos( gwill_search_normalize( get_the_title( $post ) ), $single ) ) {
				$phrase = $single . ' ' . $run;
			}
			$candidates[ $phrase ] = isset( $candidates[ $phrase ] )
				? [ 'freq' => $candidates[ $phrase ]['freq'] + 1, 'order' => $candidates[ $phrase ]['order'] ]
				: [ 'freq' => 1, 'order' => $order ];
		}
	}

	// v1.16.92 (King): REAL people's searches win once enough data exists —
	// title-derived phrases only fill the remaining slots (cold start).
	$real = gwill_search_real_related( $query, $limit );
	if ( count( $real ) >= max( 1, (int) $limit ) ) {
		return $real;
	}

	if ( ! $candidates ) {
		return $real;
	}

	// Rank: frequency desc, then first-seen result order.
	uasort( $candidates, static function ( $a, $b ) {
		if ( $a['freq'] !== $b['freq'] ) {
			return $b['freq'] <=> $a['freq'];
		}
		return $a['order'] <=> $b['order'];
	} );

	$out = [];
	foreach ( array_slice( array_keys( $candidates ), 0, max( 1, (int) $limit ) ) as $term ) {
		$out[] = [
			'term' => $term,
			'url'  => home_url( '/?s=' . rawurlencode( $term ) ),
		];
	}

	// Real searches first, title-derived fills the rest — deduped by term
	// (a real search and a mined phrase can be identical).
	$merged = array_merge( $real, $out );
	$seen   = [];
	$dedup  = [];
	foreach ( $merged as $m ) {
		if ( isset( $seen[ $m['term'] ] ) ) {
			continue;
		}
		$seen[ $m['term'] ] = true;
		$dedup[]            = $m;
	}

	return array_slice( $dedup, 0, max( 1, (int) $limit ) );
}

/**
 * Derive the corrected WORDS for a suggestion: each query token is
 * replaced by its best-matching title token (same guards as the scorer);
 * query tokens with no real match (sim < 0.5) are dropped, Google-style
 * ("redis vs mongo" → "redis vs"; "andrid 15" → "android 15").
 *
 * @param array  $at Query tokens (normalized).
 * @param array  $bt Title tokens (normalized).
 * @param string $cb Title spacing-insensitive concatenation.
 * @return string Corrected search term, '' when nothing matched.
 * @since 1.16.89
 */
function gwill_search_corrected_term( array $at, array $bt, string $cb ): string {

	$stopwords = gwill_search_stopwords();
	$out       = [];
	foreach ( $at as $qt ) {
		// v1.16.95: function words are never suggested ("why is my website
		// slow" must correct to "website", never "my how"). Question words
		// carry no search intent of their own.
		if ( in_array( $qt, $stopwords, true ) ) {
			continue;
		}
		$qlen     = strlen( $qt );
		$best_tt  = null;
		$best_sim = 0.0;
		foreach ( $bt as $tt ) {
			$tlen = strlen( $tt );
			if ( $qlen < 3 || $tlen < 3 ) {
				if ( $qlen !== $tlen ) {
					continue;
				}
			} elseif ( max( $qlen, $tlen ) / max( min( $qlen, $tlen ), 1 ) > 2.0 ) {
				continue;
			}
			$sim = max( 0.0, 1.0 - ( gwill_damerau( $qt, $tt ) / max( $qlen, $tlen, 1 ) ) );
			if ( $sim > $best_sim ) {
				$best_sim = $sim;
				$best_tt  = $tt;
			}
		}
		// Concatenated query token ("postgresqlquery") vs the title's
		// spacing-insensitive form.
		$clen = strlen( $cb );
		if ( $qlen >= 3 && $clen >= 3
			&& max( $qlen, $clen ) / max( min( $qlen, $clen ), 1 ) <= 2.5 ) {
			$sim = max( 0.0, 1.0 - ( gwill_damerau( $qt, $cb ) / max( $qlen, $clen, 1 ) ) );
			if ( $sim > $best_sim ) {
				$best_sim = $sim;
				$best_tt  = $cb;
			}
		}
		// Prefix/stem correction: "andro" → "android", "postgre" → "postgresql".
		if ( 0.0 === $best_sim && $qlen >= 3 ) {
			foreach ( $bt as $tt ) {
				$tlen = strlen( $tt );
				if ( $tlen >= $qlen && 0 === strpos( $tt, $qt ) && ( $tlen / $qlen ) <= 2.5 ) {
					$best_sim = 0.85;
					$best_tt  = $tt;
					break;
				}
			}
		}
		if ( null !== $best_tt && $best_sim >= 0.5 ) {
			$out[] = $best_tt;
		}
	}

	return implode( ' ', $out );
}
