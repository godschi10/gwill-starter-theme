<?php
defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="comments-area" aria-labelledby="comments-title">

	<?php if ( have_comments() ) : ?>

		<h2 id="comments-title" class="comments-title">
			<?php
			// (int) cast required: get_comments_number() returns string, _n() expects int.
			$count = (int) get_comments_number();
			printf(
				esc_html( _n( '%s Comment', '%s Comments', $count, 'gwill-starter' ) ),
				number_format_i18n( $count )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments( [
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 48,
			] );
			?>
		</ol>

		<?php the_comments_navigation(); ?>

	<?php endif; ?>

	<?php if ( comments_open() ) : ?>
		<?php comment_form(); ?>
	<?php else : ?>
		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'gwill-starter' ); ?></p>
	<?php endif; ?>

</section><!-- #comments -->
