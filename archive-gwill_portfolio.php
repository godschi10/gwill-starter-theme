<?php
/**
 * Portfolio Archive  -  gwill_portfolio CPT + gwill_portfolio_type taxonomy.
 *
 * The dedicated archive template the roadmap always intended (README
 * v1.0.63 recorded its absence as deliberate "within stated scope"  - 
 * v1.5.0 ships it for real). Renders the SAME grid card as
 * template-parts/portfolio/portfolio.php, but from the native archive
 * query  -  plus type-filter pills (the starter's pill-dialect used by
 * category pills) and pagination.
 *
 * Falls back here automatically for both /portfolio/ and
 * /portfolio-type/<term>/ because has_archive: true + the taxonomy's
 * public rewrite.
 *
 * @package GWill_Starter
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
gwill_breadcrumbs();
?>

<?php if ( have_posts() ) : ?>

	<header class="archive-header">
		<h1 class="archive-title">
			<?php
			if ( is_tax( 'gwill_portfolio_type' ) ) {
				$term = get_queried_object();
				printf(
					/* translators: %s: project type name */
					esc_html__( 'Projects: %s', 'gwill-starter' ),
					esc_html( $term->name )
				);
			} else {
				esc_html_e( 'Portfolio', 'gwill-starter' );
			}
			?>
		</h1>
		<?php
		if ( is_tax( 'gwill_portfolio_type' ) ) {
			the_archive_description( '<div class="archive-description">', '</div>' );
		}
		?>
	</header>

	<?php
	/*
	 * Type-filter pills  -  same dialect as the theme's category pills:
	 * <a> elements with a .is-active state, not a <form>  -  the archive
	 * itself IS the filter result, so pills are pure navigation.
	 */
	$filter_types = get_terms( array(
		'taxonomy'   => 'gwill_portfolio_type',
		'hide_empty' => true,
	) );

	if ( ! is_wp_error( $filter_types ) && count( $filter_types ) > 1 ) :
		$active_type = is_tax( 'gwill_portfolio_type' ) ? get_queried_object()->slug : '';
		?>
		<div class="gwill-portfolio-filter" role="navigation" aria-label="<?php esc_attr_e( 'Filter projects by type', 'gwill-starter' ); ?>">
			<a class="gwill-pill<?php echo '' === $active_type ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( get_post_type_archive_link( 'gwill_portfolio' ) ); ?>">
				<?php esc_html_e( 'All', 'gwill-starter' ); ?>
			</a>
			<?php foreach ( $filter_types as $ft ) : ?>
				<a class="gwill-pill<?php echo $ft->slug === $active_type ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( get_term_link( $ft ) ); ?>">
					<?php echo esc_html( $ft->name ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="gwill-portfolio-grid" style="--gwill-portfolio-columns: 3;">
		<?php
		while ( have_posts() ) : the_post();
			$project_url = get_post_meta( get_the_ID(), '_gwill_portfolio_url', true );
			$link        = $project_url ? $project_url : get_permalink();
			$is_external = (bool) $project_url;
			$types       = get_the_terms( get_the_ID(), 'gwill_portfolio_type' );
			?>
			<a
				class="gwill-portfolio-card"
				href="<?php echo esc_url( $link ); ?>"
				<?php if ( $is_external ) : ?>
					target="_blank" rel="noopener noreferrer"
				<?php endif; ?>
			>
				<div class="gwill-portfolio-card__media">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php echo get_the_post_thumbnail( null, 'large', array( 'class' => 'gwill-portfolio-card__image', 'alt' => get_the_title() ) ); ?>
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
					<h2 class="gwill-portfolio-card__title"><?php echo esc_html( get_the_title() ); ?></h2>
				</div>
			</a>
		<?php endwhile; ?>
	</div>

	<?php
	the_posts_pagination( array(
		'mid_size'  => 2,
		'prev_text' => __( '&larr; Prev', 'gwill-starter' ),
		'next_text' => __( 'Next &rarr;', 'gwill-starter' ),
	) );
	?>

<?php else : ?>
	<?php gwill_part( 'content-none' ); ?>
<?php endif; ?>

<?php get_footer();
