<?php
/**
 * No-results state — shown in search.php when the query returns nothing.
 *
 * The CTA link is filterable so developers can override per project:
 *
 *   add_filter( 'gwill_search_no_results_cta', function( $cta ) {
 *       return [ 'label' => 'Browse work', 'url' => '/projects/' ];
 *   } );
 *
 * Return false from the filter to suppress the CTA entirely.
 *
 * Since v1.1.0 — Google-style "Did you mean?" suggestions (ported from
 * GWill Tech): when the query returns nothing, up to 3 corrected words
 * are offered via the same Damerau–Levenshtein engine the tech theme
 * uses. The block renders only when suggestions exist; the tips + CTA
 * below it always render.
 *
 * @package GWill_Starter
 * @since   1.0.23
 */

defined( 'ABSPATH' ) || exit;

$term = get_search_query();
?>

<div class="search-no-results">

	<p class="search-no-results__headline">
		<?php if ( $term ) : ?>
			<?php printf(
				/* translators: %s: the search term the user entered */
				esc_html__( 'Nothing found for "%s".', 'gwill-starter' ),
				esc_html( $term )
			); ?>
		<?php else : ?>
			<?php esc_html_e( 'Enter a search term above to get started.', 'gwill-starter' ); ?>
		<?php endif; ?>
	</p>

	<?php
	// Google-style "Did you mean?" — corrected WORDS (up to 3)
	// when the query returns nothing (v1.1.0, engine from GWill Tech).
	$gwill_suggestions = ( $term && function_exists( 'gwill_search_suggest' ) )
		? gwill_search_suggest( $term )
		: [];
	if ( $gwill_suggestions ) :
		$gwill_s0 = $gwill_suggestions[0];
		?>
		<div class="search-suggest" role="note">
			<p class="search-suggest-kicker"><?php esc_html_e( 'Did you mean', 'gwill-starter' ); ?></p>
			<a class="search-suggest-card" href="<?php echo esc_url( $gwill_s0['url'] ); ?>">
				<span class="search-suggest-arrow" aria-hidden="true">→</span>
				<span class="search-suggest-title"><?php echo esc_html( $gwill_s0['term'] ); ?></span>
			</a>
			<?php
			$gwill_alt = array_slice( $gwill_suggestions, 1 );
			if ( $gwill_alt ) :
				$gwill_alt_links = [];
				foreach ( $gwill_alt as $gwill_sx ) {
					$gwill_alt_links[] = '<a class="search-suggest-alt-link" href="' . esc_url( $gwill_sx['url'] ) . '">' . esc_html( $gwill_sx['term'] ) . '</a>';
				}
				?>
				<p class="search-suggest-alt"><?php esc_html_e( 'Or try', 'gwill-starter' ); ?>: <?php echo implode( ', ', $gwill_alt_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- links built with esc_url/esc_html above. ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<ul class="search-no-results__tips" aria-label="<?php esc_attr_e( 'Search tips', 'gwill-starter' ); ?>">
		<li><?php esc_html_e( 'Double-check the spelling.', 'gwill-starter' ); ?></li>
		<li><?php esc_html_e( 'Try fewer or more general keywords.', 'gwill-starter' ); ?></li>
		<li><?php esc_html_e( 'Search for a single word rather than a phrase.', 'gwill-starter' ); ?></li>
	</ul>

	<?php
	$posts_page = get_permalink( (int) get_option( 'page_for_posts' ) );
	$default_cta = $posts_page
		? [ 'label' => __( 'Browse all posts', 'gwill-starter' ), 'url' => $posts_page ]
		: [ 'label' => __( 'Go to homepage', 'gwill-starter' ),   'url' => home_url( '/' ) ];

	/**
	 * Override the no-results call-to-action link.
	 *
	 * @param array|false $cta Associative array with 'label' and 'url' keys,
	 *                         or false to suppress the CTA entirely.
	 */
	$cta = apply_filters( 'gwill_search_no_results_cta', $default_cta );

	if ( $cta ) : ?>
		<a
			class="gwill-btn gwill-btn--primary search-no-results__cta"
			href="<?php echo esc_url( $cta['url'] ); ?>"
		><?php echo esc_html( $cta['label'] ); ?></a>
	<?php endif; ?>

</div>
