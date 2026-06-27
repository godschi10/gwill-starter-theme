<?php
/**
 * Template Part: Portfolio Grid
 *
 * Call via gwill_portfolio_grid( $args ) or the [gwill_portfolio]
 * shortcode — never include this file directly, since $args needs to
 * arrive through gwill_part()'s data-passing mechanism.
 *
 * @package GWill_Starter
 * @since   1.0.63
 *
 * @var array{count?:int,columns?:int,type?:string,orderby?:string,order?:string} $args
 */

defined( 'ABSPATH' ) || exit;

$columns = isset( $args['columns'] ) ? max( 2, min( 4, (int) $args['columns'] ) ) : 3;

$items = gwill_get_portfolio_items( $args );

if ( ! $items ) {
	if ( WP_DEBUG ) {
		echo "\n<!-- gwill_portfolio_grid(): no published gwill_portfolio posts found"
			. ( ! empty( $args['type'] ) ? " for type '" . esc_html( $args['type'] ) . "'" : '' )
			. " -->\n";
	}
	return;
}
?>

<div class="gwill-portfolio-grid" style="--gwill-portfolio-columns: <?php echo esc_attr( $columns ); ?>;">
	<?php foreach ( $items as $item ) : ?>
		<?php
		$project_url = get_post_meta( $item->ID, '_gwill_portfolio_url', true );
		$link        = $project_url ? $project_url : get_permalink( $item );
		$is_external = (bool) $project_url;
		$types       = get_the_terms( $item, 'gwill_portfolio_type' );
		?>
		<a
			class="gwill-portfolio-card"
			href="<?php echo esc_url( $link ); ?>"
			<?php if ( $is_external ) : ?>
				target="_blank" rel="noopener noreferrer"
			<?php endif; ?>
		>
			<div class="gwill-portfolio-card__media">
				<?php if ( has_post_thumbnail( $item ) ) : ?>
					<?php echo get_the_post_thumbnail( $item, 'large', [ 'class' => 'gwill-portfolio-card__image', 'alt' => get_the_title( $item ) ] ); ?>
				<?php endif; ?>
				<span class="gwill-portfolio-card__overlay">
					<?php esc_html_e( 'View Project', 'gwill-starter' ); ?>
					<?php if ( $is_external ) : ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7M7 7h10v10"></path></svg>
					<?php endif; ?>
				</span>
			</div>

			<div class="gwill-portfolio-card__body">
				<?php if ( $types && ! is_wp_error( $types ) ) : ?>
					<span class="gwill-portfolio-card__type"><?php echo esc_html( $types[0]->name ); ?></span>
				<?php endif; ?>
				<h3 class="gwill-portfolio-card__title"><?php echo esc_html( get_the_title( $item ) ); ?></h3>
			</div>
		</a>
	<?php endforeach; ?>
</div>
