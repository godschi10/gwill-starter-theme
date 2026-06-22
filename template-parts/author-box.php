<?php
/**
 * Template Part: Author Box
 *
 * Rendered at the bottom of single posts, after entry-content.
 * Skipped entirely when the author has no display name (safety guard for
 * edge-case user accounts with incomplete profiles).
 *
 * Avatar links and name links both point to the author archive so keyboard
 * users do not encounter two consecutive identical tab stops. The avatar
 * link is marked tabindex="-1" aria-hidden="true" — the named link below it
 * provides the accessible navigation target (same pattern as content.php
 * thumbnail links).
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;

$author_id   = (int) get_the_author_meta( 'ID' );
$name        = get_the_author_meta( 'display_name' );

// Nothing to render without a name.
if ( ! $name ) {
	return;
}

$bio         = get_the_author_meta( 'description' );
$archive_url = get_author_posts_url( $author_id );
$avatar      = get_avatar(
	$author_id,
	80,
	'',
	esc_attr( $name ),
	[ 'extra_attr' => 'loading="lazy"' ]
);
?>

<section class="author-box" aria-label="<?php esc_attr_e( 'About the author', 'gwill-starter' ); ?>">

	<?php if ( $avatar ) : ?>
		<div class="author-box__avatar" aria-hidden="true">
			<a href="<?php echo esc_url( $archive_url ); ?>" tabindex="-1">
				<?php echo wp_kses_post( $avatar ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="author-box__info">

		<p class="author-box__label"><?php esc_html_e( 'Written by', 'gwill-starter' ); ?></p>

		<p class="author-box__name">
			<a href="<?php echo esc_url( $archive_url ); ?>">
				<?php echo esc_html( $name ); ?>
			</a>
		</p>

		<?php if ( $bio ) : ?>
			<p class="author-box__bio"><?php echo esc_html( $bio ); ?></p>
		<?php endif; ?>

		<a class="author-box__link" href="<?php echo esc_url( $archive_url ); ?>">
			<?php
			printf(
				/* translators: %s: author display name */
				esc_html__( 'More posts by %s', 'gwill-starter' ),
				esc_html( $name )
			);
			?>
		</a>

		<?php
		$socials = gwill_get_author_socials( $author_id );
		if ( $socials ) :
		?>
		<div class="author-box__socials">
			<?php foreach ( $socials as $social ) : ?>
				<a
					class="author-box__social-link"
					href="<?php echo esc_url( $social['url'] ); ?>"
					target="_blank"
					rel="noopener noreferrer"
					aria-label="<?php echo esc_attr( $social['aria'] ); ?>"
				><?php
					/* Icon is developer-supplied SVG from gwill_author_social_fields(),
					   not user input — direct echo is intentional. */
					echo $social['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?></a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

	</div>

</section>
