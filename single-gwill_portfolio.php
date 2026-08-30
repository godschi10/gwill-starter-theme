<?php
/**
 * Single Project — gwill_portfolio custom post type.
 *
 * The dedicated template the roadmap always intended for portfolio
 * (README v1.0.63: "No dedicated single/archive templates ship" — until
 * now). Same anatomy as single.php: breadcrumbs → featured image → H1 →
 * meta row → share pills → content → client details card → prev/next.
 *
 * Schema: BlogPosting microdata is WRONG here — a portfolio project is a
 * CreativeWork (the grid's overlay already says "View Project", and the
 * meta box stores client + live URL — classic CreativeWork fields).
 *
 * @package GWill_Starter
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) : the_post();

	gwill_breadcrumbs();

	$client = get_post_meta( get_the_ID(), '_gwill_portfolio_client', true );
	$live   = get_post_meta( get_the_ID(), '_gwill_portfolio_url', true );
	$types  = get_the_terms( get_the_ID(), 'gwill_portfolio_type' );
	?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'gwill-project' ); ?> itemscope itemtype="https://schema.org/CreativeWork">

		<?php gwill_part( 'featured-image' ); ?>

		<h1 class="entry-title" itemprop="name"><?php echo esc_html( get_the_title() ); ?></h1>

		<div class="entry-meta">
			<link itemprop="url" href="<?php echo esc_url( get_permalink() ); ?>">
			<meta itemprop="dateModified" content="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">

			<?php if ( $types && ! is_wp_error( $types ) ) : ?>
				<span class="entry-cats" itemprop="about">
					<?php foreach ( $types as $type ) : ?>
						<a class="entry-cat" href="<?php echo esc_url( get_term_link( $type ) ); ?>">
							<?php echo esc_html( $type->name ); ?>
						</a>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</div>

		<?php gwill_part( 'share-button' ); // top mode — compact pill row ?>

		<div class="entry-content" itemprop="text">
			<?php the_content(); ?>
		</div>

		<?php if ( $client || $live ) : ?>
			<!-- ═══ Project details card ═══ -->
			<aside class="gwill-project-details">
				<h2 class="gwill-project-details__title">
					<?php esc_html_e( 'Project details', 'gwill-starter' ); ?>
				</h2>

				<?php if ( $client ) : ?>
					<p class="gwill-project-details__row">
						<span class="gwill-project-details__label"><?php esc_html_e( 'Client', 'gwill-starter' ); ?></span>
						<span class="gwill-project-details__value" itemprop="creator"><?php echo esc_html( $client ); ?></span>
					</p>
				<?php endif; ?>

				<?php if ( $live ) : ?>
					<p class="gwill-project-details__row">
						<span class="gwill-project-details__label"><?php esc_html_e( 'Live site', 'gwill-starter' ); ?></span>
						<span class="gwill-project-details__value">
							<a href="<?php echo esc_url( $live ); ?>" target="_blank" rel="noopener noreferrer" itemprop="url">
								<?php echo esc_html( wp_parse_url( $live, PHP_URL_HOST ) ?: $live ); ?>
							</a>
						</span>
					</p>
				<?php endif; ?>

				<?php if ( is_array( $types ) && ! is_wp_error( $types ) ) : ?>
					<p class="gwill-project-details__row">
						<span class="gwill-project-details__label"><?php esc_html_e( 'Services', 'gwill-starter' ); ?></span>
						<span class="gwill-project-details__value">
							<?php
							echo esc_html( implode( ', ', wp_list_pluck( $types, 'name' ) ) );
							?>
						</span>
					</p>
				<?php endif; ?>
			</aside>
		<?php endif; ?>

	</article>

<?php endwhile;

get_footer();
