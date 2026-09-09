<?php
/**
 * 404 — terminal error hero (v1.13.2 B2).
 *
 * Structure (tech-theme pattern, design-language-quiet):
 *   breadcrumb > 404      — existing gwill_breadcrumbs(), 404 branch built-in
 *   mono error code       — the page IS the brand's terminal voice
 *   h1 + one muted line
 *   the B1 search pill    — a 404 should offer search, using the same
 *                           object the header offers
 *   guidance buttons      — filterable: gwill_404_links
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;

get_header();
gwill_breadcrumbs();
?>

<section class="error-404" aria-labelledby="error-404-heading">

	<p class="error-404__code" aria-hidden="true">404</p>

	<h1 id="error-404-heading" class="error-404__title">
		<?php esc_html_e( 'This page doesn’t exist — or moved.', 'gwill-starter' ); ?>
	</h1>

	<p class="error-404__desc">
		<?php esc_html_e( 'Nothing broke on your end. Try searching, or one of these:', 'gwill-starter' ); ?>
	</p>

	<div class="error-404__search">
		<?php get_search_form(); // B1 pill — same object as the header dropdown. ?>
	</div>

	<?php
	/**
	 * Guidance buttons shown under the 404 hero.
	 *
	 * Each item: array( 'label' => string, 'url' => string, 'style' =>
	 * 'primary'|'ghost' ). Return [] to render no buttons.
	 *
	 * @param array $links Default guidance links.
	 */
	$gwill_404_links = apply_filters( 'gwill_404_links', [
		[ 'label' => __( 'Homepage', 'gwill-starter' ),      'url' => home_url( '/' ),    'style' => 'primary' ],
		[ 'label' => __( 'Latest posts', 'gwill-starter' ),  'url' => home_url( '/blog/' ),'style' => 'ghost'   ],
		[ 'label' => __( 'Contact', 'gwill-starter' ),       'url' => home_url( '/contact/' ), 'style' => 'ghost' ],
	] );
	?>
	<?php if ( $gwill_404_links ) : ?>
		<nav class="error-404__actions" aria-label="<?php esc_attr_e( 'Where to next', 'gwill-starter' ); ?>">
			<?php foreach ( $gwill_404_links as $gwill_link ) :
				$style = in_array( $gwill_link['style'] ?? 'ghost', [ 'primary', 'ghost' ], true ) ? $gwill_link['style'] : 'ghost';
				?>
				<a class="gwill-btn gwill-btn--<?php echo esc_attr( $style ); ?>" href="<?php echo esc_url( $gwill_link['url'] ); ?>">
					<?php echo esc_html( $gwill_link['label'] ); ?><?php echo 'primary' === $style ? ' &rarr;' : ''; ?>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

</section>

<?php get_footer(); ?>
