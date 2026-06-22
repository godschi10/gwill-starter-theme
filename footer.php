<?php defined( 'ABSPATH' ) || exit; ?>
		</div><!-- .inner -->
	</main><!-- #content -->

	<footer class="site-footer">
		<div class="inner">

			<?php
			/*
			 * Only render the nav when a footer menu has actually been assigned.
			 * fallback_cb => false prevents a page dump, but wp_nav_menu() still
			 * outputs an empty string (and returns false) when no menu is set —
			 * leaving a ghost <nav> element with no content.
			 */
			if ( has_nav_menu( 'footer' ) ) :
				wp_nav_menu( [
					'theme_location' => 'footer',
					'container'      => false,
					'fallback_cb'    => false,
					'depth'          => 1,
				] );
			endif;
			?>

			<?php
			/*
			 * Footer credit — filterable for client builds.
			 *
			 * To remove: add_filter( 'gwill_footer_credit', '__return_empty_string' );
			 * To replace: add_filter( 'gwill_footer_credit', fn() => ' &mdash; Built by <a href="https://example.com">Studio Name</a>' );
			 *
			 * TODO: Replace or remove for every client site before launch.
			 */
			$credit = apply_filters(
				'gwill_footer_credit',
				' &mdash; Built by <a href="https://gwillchijioke.com" target="_blank" rel="noopener noreferrer">G-will Chijioke</a>'
			);
			?>
			<p>
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
				</a>
				<?php echo wp_kses( $credit, [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?>
			</p>

		</div>
	</footer>

</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
