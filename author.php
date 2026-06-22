<?php
/**
 * Author Archive Template
 *
 * Displays a list of posts by a single author with an author hero section
 * at the top (avatar, display name, bio). Relies on the standard
 * header.php / footer.php layout wrappers:
 *   header.php opens:  <main class="site-main"><div class="inner">
 *   footer.php closes: </div></main>
 *
 * Template hierarchy: author-{nicename}.php → author-{ID}.php → author.php
 * This file covers the general case.
 *
 * get_queried_object() returns a WP_User on author archive pages.
 * The instanceof guard is defensive — it protects against any edge case
 * where WordPress routes a non-author query through this template.
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;

$author = get_queried_object();

get_header();
gwill_breadcrumbs();
?>

<?php if ( have_posts() ) : ?>

	<header class="archive-header">

		<?php if ( $author instanceof WP_User ) :
			$bio    = get_the_author_meta( 'description', $author->ID );
			$avatar = get_avatar(
				$author->ID,
				96,
				'',
				esc_attr( $author->display_name ),
				[ 'extra_attr' => 'loading="lazy"' ]
			);
		?>

			<div class="author-archive-hero">

				<?php if ( $avatar ) : ?>
					<div class="author-archive-hero__avatar" aria-hidden="true">
						<?php echo wp_kses_post( $avatar ); ?>
					</div>
				<?php endif; ?>

				<div class="author-archive-hero__info">
					<h1 class="archive-title author-archive-hero__name">
						<?php echo esc_html( $author->display_name ); ?>
					</h1>
					<?php if ( $bio ) : ?>
						<p class="author-archive-hero__bio"><?php echo esc_html( $bio ); ?></p>
					<?php endif; ?>
					<?php
					$socials = gwill_get_author_socials( $author->ID );
					if ( $socials ) :
					?>
					<div class="author-box__socials author-archive-hero__socials">
						<?php foreach ( $socials as $social ) : ?>
							<a
								class="author-box__social-link"
								href="<?php echo esc_url( $social['url'] ); ?>"
								target="_blank"
								rel="noopener noreferrer"
								aria-label="<?php echo esc_attr( $social['aria'] ); ?>"
							><?php echo $social['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>

			</div>

		<?php else : ?>
			<h1 class="archive-title"><?php the_archive_title(); ?></h1>
			<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
		<?php endif; ?>

	</header>

	<?php while ( have_posts() ) : the_post(); ?>
		<?php gwill_part( 'content' ); ?>
	<?php endwhile; ?>

	<?php
	the_posts_pagination( [
		'mid_size'  => 2,
		'prev_text' => __( '&larr; Prev', 'gwill-starter' ),
		'next_text' => __( 'Next &rarr;', 'gwill-starter' ),
	] );
	?>

<?php else : ?>
	<?php gwill_part( 'content-none' ); ?>
<?php endif; ?>

<?php get_footer(); ?>
