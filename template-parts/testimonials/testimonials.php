<?php
/**
 * Template Part: Testimonials Grid / Carousel
 *
 * Call via gwill_testimonials_grid( $args ) or the [gwill_testimonials]
 * shortcode — never include this file directly, since $args needs to
 * arrive through gwill_part()'s data-passing mechanism.
 *
 * @package GWill_Starter
 * @since   1.0.62
 *
 * @var array{mode?:string,count?:int,columns?:int,orderby?:string,order?:string} $args
 */

defined( 'ABSPATH' ) || exit;

$mode    = isset( $args['mode'] ) && 'carousel' === $args['mode'] ? 'carousel' : 'grid';
$columns = isset( $args['columns'] ) ? max( 2, min( 4, (int) $args['columns'] ) ) : 3;

$testimonials = gwill_get_testimonials( $args );

if ( ! $testimonials ) {
	// Loud in debug (a developer placed this tag and almost certainly
	// wants to know why nothing's showing — most likely "no testimonials
	// published yet"), completely silent in production — an empty HTML
	// comment is harmless but an empty section isn't worth rendering at
	// all for a real visitor.
	if ( WP_DEBUG ) {
		echo "\n<!-- gwill_testimonials_grid(): no published gwill_testimonial posts found -->\n";
	}
	return;
}

if ( 'carousel' === $mode ) {
	wp_enqueue_script(
		'gwill-testimonials-carousel',
		get_template_directory_uri() . '/assets/js/testimonials-carousel.js',
		[],
		wp_get_theme( get_template() )->get( 'Version' ),
		[ 'in_footer' => true, 'strategy' => 'defer' ]
	);
}
?>

<div
	class="gwill-testimonials gwill-testimonials--<?php echo esc_attr( $mode ); ?>"
	<?php if ( 'grid' === $mode ) : ?>
		style="--gwill-testimonials-columns: <?php echo esc_attr( $columns ); ?>;"
	<?php endif; ?>
>
	<div class="gwill-testimonials__track">
		<?php foreach ( $testimonials as $testimonial ) : ?>
			<?php
			$role   = get_post_meta( $testimonial->ID, '_gwill_testimonial_role', true );
			$rating = (int) get_post_meta( $testimonial->ID, '_gwill_testimonial_rating', true );
			?>
			<div class="gwill-testimonial-card">

				<?php if ( $rating > 0 ) : ?>
					<?php echo gwill_render_star_rating( $rating ); // Already escaped internally. ?>
				<?php endif; ?>

				<blockquote class="gwill-testimonial-card__quote">
					<?php echo wp_kses_post( wpautop( get_the_content( null, false, $testimonial ) ) ); ?>
				</blockquote>

				<div class="gwill-testimonial-card__byline">
					<?php if ( has_post_thumbnail( $testimonial ) ) : ?>
						<?php
						// 'alt' is passed RAW on purpose, not esc_attr()'d here —
						// get_the_post_thumbnail() escapes its whole $attr array
						// internally via wp_get_attachment_image(). Pre-escaping
						// it ourselves would double-escape any title containing
						// an apostrophe or ampersand (the exact gotcha already
						// documented on gwill_get_image_alt() in inc/helpers.php).
						echo get_the_post_thumbnail( $testimonial, 'thumbnail', [
							'class' => 'gwill-testimonial-card__photo',
							'alt'   => get_the_title( $testimonial ),
						] );
						?>
					<?php else : ?>
						<span class="gwill-testimonial-card__photo gwill-testimonial-card__photo--placeholder" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
								<circle cx="12" cy="7" r="4"></circle>
							</svg>
						</span>
					<?php endif; ?>

					<div class="gwill-testimonial-card__who">
						<cite class="gwill-testimonial-card__name"><?php echo esc_html( get_the_title( $testimonial ) ); ?></cite>
						<?php if ( $role ) : ?>
							<span class="gwill-testimonial-card__role"><?php echo esc_html( $role ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( 'carousel' === $mode ) : ?>
		<!-- Prev/Next buttons are inserted by assets/js/testimonials-carousel.js,
		     not rendered here — a button with no JS behind it to actually
		     scroll the track would be worse than no button at all. Without
		     JS, the track is still fully usable: native touch swipe, or
		     shift+scroll-wheel on desktop, via plain CSS scroll-snap. -->
	<?php endif; ?>
</div>
