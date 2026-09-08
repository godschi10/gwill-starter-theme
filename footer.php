<?php defined( 'ABSPATH' ) || exit; ?>
		</div><!-- .inner -->
	</main><!-- #content -->

	<footer class="site-footer">
		<div class="inner">

			<?php
			/*
			 * v1.12.3 (E29): optional column layout. Default 1 renders the
			 * classic centered stack (nav / bell / credit) exactly as before.
			 * Builds opt in with:
			 *   add_filter( 'gwill_footer_columns', fn() => 2 ); // or 3
			 * Column CONTENT is a second filter (gwill_footer_columns_content)
			 * so nothing is hardcoded: each entry is
			 *   [ 'title' => ..., 'content' => '<ul>...' ]  (html, builder's
			 *   responsibility) or a 'menu' key naming a registered location.
			 */
			$gwill_footer_cols = (int) apply_filters( 'gwill_footer_columns', 1 );
			$gwill_footer_cols = max( 1, min( 3, $gwill_footer_cols ) );
			?>

			<?php if ( $gwill_footer_cols > 1 ) : ?>

				<?php
				$gwill_footer_col_content = (array) apply_filters( 'gwill_footer_columns_content', array_fill( 0, $gwill_footer_cols, [] ) );
				?>
				<div class="site-footer__grid site-footer__grid--<?php echo esc_attr( $gwill_footer_cols ); ?>">
					<?php foreach ( $gwill_footer_col_content as $gwill_col ) : ?>
						<div class="site-footer__col">
							<?php if ( ! empty( $gwill_col['title'] ) ) : ?>
								<p class="site-footer__col-title"><?php echo esc_html( $gwill_col['title'] ); ?></p>
							<?php endif; ?>
							<?php
							if ( ! empty( $gwill_col['menu'] ) && has_nav_menu( $gwill_col['menu'] ) ) {
								wp_nav_menu( [
									'theme_location' => $gwill_col['menu'],
									'container'      => false,
									'fallback_cb'    => false,
									'depth'          => 1,
								] );
							} elseif ( ! empty( $gwill_col['content'] ) ) {
								echo wp_kses_post( $gwill_col['content'] );
							}
							?>
						</div>
					<?php endforeach; ?>
				</div>

			<?php else : ?>

				<?php
				/*
				 * Only render the nav when a footer menu has actually been assigned.
				 * fallback_cb => false prevents a page dump, but wp_nav_menu() still
					 * outputs an empty string (and returns false) when no menu is set -
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

			<?php endif; ?>

			<?php
			/*
			 * v1.12.3 (E30): opt-in social row. gwill_footer_socials returns
			 * [ [ 'name' => X, 'url' => ..., 'icon' => '<svg...' ], ... ] -
			 * empty by default, so the row stays out of the DOM.
			 */
			$gwill_footer_socials = (array) apply_filters( 'gwill_footer_socials', [] );
			if ( $gwill_footer_socials ) :
			?>
				<div class="site-footer__social">
					<?php foreach ( $gwill_footer_socials as $gwill_social ) : ?>
						<a
							class="site-footer__social-link"
							href="<?php echo esc_url( $gwill_social['url'] ); ?>"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php echo esc_attr( $gwill_social['name'] ); ?>"
						><?php echo $gwill_social['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builder-supplied SVG ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php
			/*
			 * Push bell (inc/webpush.php). Rendered in the footer above the
			 * credit line. Safe to render multiple times - push.js binds ALL
			 * instances (docs/LAWS.md L5). Rendered only when VAPID keys
			 * exist (they self-generate on first admin visit).
			 */
			gwill_push_bell();
			?>

			<?php
			/*
			 * Footer credit - filterable for client builds.
			 *
			 * To remove: add_filter( 'gwill_footer_credit', '__return_empty_string' );
			 * To replace: add_filter( 'gwill_footer_credit', fn() => ' - Built by <a href="https://example.com">Studio Name</a>' );
			 *
			 * Replace or remove for every client site before launch.
			 */
			$credit = apply_filters(
				'gwill_footer_credit',
				' - Built by <a href="https://gwillchijioke.com" target="_blank" rel="noopener noreferrer">G-will Chijioke</a>'
			);
			?>

			<p<?php echo $gwill_footer_cols > 1 ? ' class="site-footer__credit"' : ''; ?>>
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
				</a>
				<?php echo wp_kses( $credit, [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?>
			</p>

		</div>
	</footer>

</div><!-- #page -->

<?php
gwill_part( 'back-to-top' );
gwill_part( 'cookie-consent' );
?>

<?php wp_footer(); ?>
</body>
</html>
