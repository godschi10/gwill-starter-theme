<?php
/**
 * Template Part: Pricing Table
 *
 * Call via gwill_pricing_table( $plans, $args ) — never include this file
 * directly, since $args needs to arrive through gwill_part()'s
 * data-passing mechanism.
 *
 * @package GWill_Starter
 * @since   1.0.63
 *
 * @var array{plans:array,currency:string} $args
 */

defined( 'ABSPATH' ) || exit;

$plans    = $args['plans'] ?? [];
$currency = $args['currency'] ?? '$';

if ( ! $plans ) {
	// Loud in debug — a developer called this with an empty array, almost
	// certainly unintentionally; silent in production, since an empty
	// section is better left unrendered than shown as a visible gap.
	if ( WP_DEBUG ) {
		echo "\n<!-- gwill_pricing_table(): called with an empty \$plans array -->\n";
	}
	return;
}

// Columns track however many plans were actually passed, capped at 4 for
// layout sanity — three plans show three columns, not three columns
// stretched out to fill four. No separate columns argument exists for
// this component the way gwill_testimonials_grid() has one: a pricing
// table's column count is the plan count, not an independent choice.
$columns = max( 1, min( 4, count( $plans ) ) );
?>
<div class="gwill-pricing-table" style="--gwill-pricing-columns: <?php echo esc_attr( $columns ); ?>;">
	<?php foreach ( $plans as $plan ) : ?>
		<?php $featured = ! empty( $plan['featured'] ); ?>
		<div class="gwill-pricing-card<?php echo $featured ? ' gwill-pricing-card--featured' : ''; ?>">

			<?php if ( $featured ) : ?>
				<span class="gwill-pricing-card__badge">
					<?php echo esc_html( ! empty( $plan['badge'] ) ? $plan['badge'] : __( 'Most Popular', 'gwill-starter' ) ); ?>
				</span>
			<?php endif; ?>

			<h3 class="gwill-pricing-card__name"><?php echo esc_html( $plan['name'] ?? '' ); ?></h3>

			<?php if ( ! empty( $plan['description'] ) ) : ?>
				<p class="gwill-pricing-card__description"><?php echo esc_html( $plan['description'] ); ?></p>
			<?php endif; ?>

			<?php if ( isset( $plan['price'] ) && '' !== $plan['price'] ) : ?>
				<div class="gwill-pricing-card__price">
					<span class="gwill-pricing-card__amount"><?php echo esc_html( $currency . $plan['price'] ); ?></span>
					<?php if ( ! empty( $plan['period'] ) ) : ?>
						<span class="gwill-pricing-card__period"><?php echo esc_html( $plan['period'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $plan['features'] ) && is_array( $plan['features'] ) ) : ?>
				<ul class="gwill-pricing-card__features">
					<?php foreach ( $plan['features'] as $feature ) : ?>
						<li>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="M20 6L9 17l-5-5"></path>
							</svg>
							<span><?php echo esc_html( $feature ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $plan['cta_text'] ) ) : ?>
				<a class="gwill-pricing-card__cta" href="<?php echo esc_url( $plan['cta_url'] ?? '#' ); ?>">
					<?php echo esc_html( $plan['cta_text'] ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
