<?php
/**
 * Testimonials custom post type + grid/carousel display.
 *
 * Field mapping, deliberately simple rather than inventing a pile of meta
 * fields for things WordPress already has a field for:
 *   - Post title    → the person's name
 *   - Post content  → the quote itself
 *   - Featured image → their photo (falls back to a generic avatar glyph
 *                       in the template part when absent — never breaks
 *                       the card layout waiting on a photo that may not
 *                       exist yet)
 *   - Two actual custom fields, because nothing built-in covers them:
 *     role/company (text) and star rating (1–5)
 *
 * Not publicly queryable on purpose — no single-testimonial page, no
 * archive. A testimonial isn't content anyone navigates to directly, it's
 * a card that gets pulled into a grid or carousel wherever a developer
 * places gwill_testimonials_grid() or the [gwill_testimonials] shortcode.
 * Forcing a permalink structure onto content that's never meant to be
 * visited at its own URL would be the wrong default to ship.
 *
 * @package GWill_Starter
 * @since   1.0.62
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'gwill_register_testimonial_cpt' );
add_action( 'add_meta_boxes', 'gwill_register_testimonial_meta_box' );
add_action( 'save_post_gwill_testimonial', 'gwill_save_testimonial_meta_box' );
add_shortcode( 'gwill_testimonials', 'gwill_testimonials_shortcode' );

// ── Post type registration ──────────────────────────────────────────────────

/**
 * Register the gwill_testimonial post type.
 *
 * @since 1.0.62
 */
function gwill_register_testimonial_cpt(): void {

	$labels = [
		'name'                  => _x( 'Testimonials', 'post type general name', 'gwill-starter' ),
		'singular_name'         => _x( 'Testimonial', 'post type singular name', 'gwill-starter' ),
		'menu_name'             => _x( 'Testimonials', 'admin menu', 'gwill-starter' ),
		'add_new_item'          => __( 'Add New Testimonial', 'gwill-starter' ),
		'edit_item'             => __( 'Edit Testimonial', 'gwill-starter' ),
		'new_item'              => __( 'New Testimonial', 'gwill-starter' ),
		'view_item'             => __( 'View Testimonial', 'gwill-starter' ),
		'search_items'          => __( 'Search Testimonials', 'gwill-starter' ),
		'not_found'             => __( 'No testimonials found.', 'gwill-starter' ),
		'not_found_in_trash'    => __( 'No testimonials found in Trash.', 'gwill-starter' ),
		'all_items'             => __( 'All Testimonials', 'gwill-starter' ),
		'featured_image'        => __( 'Photo', 'gwill-starter' ),
		'set_featured_image'    => __( 'Set photo', 'gwill-starter' ),
		'remove_featured_image' => __( 'Remove photo', 'gwill-starter' ),
	];

	register_post_type( 'gwill_testimonial', [
		'labels'              => $labels,
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => true,
		'menu_icon'           => 'dashicons-format-quote',
		'menu_position'       => 25,
		'supports'            => [ 'title', 'editor', 'thumbnail' ],
		'capability_type'     => 'post',
		'rewrite'             => false,
		'has_archive'         => false,
	] );
}

// ── "Testimonial Details" meta box (role/company + star rating) ───────────

/**
 * Register the meta box. Same pattern as gwill_register_video_meta_box()
 * in inc/setup.php — kept consistent rather than inventing a second way
 * to do the same kind of thing.
 *
 * @since 1.0.62
 */
function gwill_register_testimonial_meta_box(): void {
	add_meta_box(
		'gwill-testimonial-details',
		__( 'Testimonial Details', 'gwill-starter' ),
		'gwill_render_testimonial_meta_box',
		'gwill_testimonial',
		'side',
		'default'
	);
}

/**
 * Render the role/company + star-rating fields.
 *
 * @param WP_Post $post Current post.
 * @since 1.0.62
 */
function gwill_render_testimonial_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'gwill_save_testimonial_' . $post->ID, 'gwill_testimonial_nonce' );

	$role   = get_post_meta( $post->ID, '_gwill_testimonial_role', true );
	$rating = (int) get_post_meta( $post->ID, '_gwill_testimonial_rating', true );
	$rating = $rating >= 1 && $rating <= 5 ? $rating : 5; // Default to 5 on a brand-new testimonial, not 0/empty stars.
	?>
	<p>
		<label for="gwill_testimonial_role" style="display:block;margin-bottom:4px;font-weight:600">
			<?php esc_html_e( 'Role / Company', 'gwill-starter' ); ?>
		</label>
		<input
			type="text"
			id="gwill_testimonial_role"
			name="gwill_testimonial_role"
			value="<?php echo esc_attr( $role ); ?>"
			style="width:100%"
			placeholder="<?php esc_attr_e( 'e.g. CEO, Acme Inc.', 'gwill-starter' ); ?>"
		/>
	</p>
	<p>
		<label for="gwill_testimonial_rating" style="display:block;margin-bottom:4px;font-weight:600">
			<?php esc_html_e( 'Star Rating', 'gwill-starter' ); ?>
		</label>
		<select id="gwill_testimonial_rating" name="gwill_testimonial_rating" style="width:100%">
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
				<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $rating, $i ); ?>>
					<?php
					printf(
						/* translators: %d: number of stars, 1-5 */
						esc_html( _n( '%d star', '%d stars', $i, 'gwill-starter' ) ),
						$i
					);
					?>
				</option>
			<?php endfor; ?>
		</select>
	</p>
	<?php
}

/**
 * Save the role/company + star-rating fields.
 *
 * Identical security ordering to gwill_save_video_meta_box() in
 * inc/setup.php, and for the same reason — capability is the
 * authoritative check, the nonce is CSRF protection layered on top of it,
 * and save_post_{post_type} is never fired by core without the edit
 * capability already having been verified earlier in admin/post.php, so
 * checking capability first costs nothing and removes any doubt.
 *
 * @param int $post_id Post ID.
 * @since 1.0.62
 */
function gwill_save_testimonial_meta_box( int $post_id ): void {

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['gwill_testimonial_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['gwill_testimonial_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'gwill_save_testimonial_' . $post_id ) ) {
		return;
	}

	if ( isset( $_POST['gwill_testimonial_role'] ) ) {
		update_post_meta(
			$post_id,
			'_gwill_testimonial_role',
			sanitize_text_field( wp_unslash( $_POST['gwill_testimonial_role'] ) )
		);
	}

	if ( isset( $_POST['gwill_testimonial_rating'] ) ) {
		$rating = (int) $_POST['gwill_testimonial_rating'];
		$rating = max( 1, min( 5, $rating ) ); // Clamp — a tampered or malformed value still has to land somewhere sane.
		update_post_meta( $post_id, '_gwill_testimonial_rating', $rating );
	}
}

// ── Query helper ─────────────────────────────────────────────────────────────

/**
 * Fetch published testimonials.
 *
 * @param  array{count?:int,orderby?:string,order?:string} $args
 * @return WP_Post[]
 * @since  1.0.62
 */
function gwill_get_testimonials( array $args = [] ): array {

	$defaults = [
		'count'   => 6,
		'orderby' => 'date',
		'order'   => 'DESC',
	];
	$args = wp_parse_args( $args, $defaults );

	$query = new WP_Query( [
		'post_type'              => 'gwill_testimonial',
		'post_status'            => 'publish',
		'posts_per_page'         => (int) $args['count'],
		'orderby'                => sanitize_key( $args['orderby'] ),
		'order'                  => 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC',
		'no_found_rows'          => true, // No pagination UI ever needed here — skip the COUNT query.
		'update_post_term_cache' => false, // Testimonials don't use categories/tags.
	] );

	return $query->posts;
}

// ── Public template tag + shortcode ─────────────────────────────────────────

/**
 * Render a testimonials grid or carousel. The actual public API for this
 * feature — call this directly from any page template.
 *
 * @param array{mode?:string,count?:int,columns?:int,orderby?:string,order?:string} $args
 *        mode: 'grid' (default) or 'carousel'.
 *        columns: grid mode only, 2-4, default 3.
 * @since 1.0.62
 */
function gwill_testimonials_grid( array $args = [] ): void {
	gwill_part( 'testimonials/testimonials', $args );
}

/**
 * [gwill_testimonials] shortcode — a thin wrapper around
 * gwill_testimonials_grid() for the rare case a content editor wants to
 * drop this into a regular post/page via the Shortcode block, rather than
 * a developer placing the template tag directly in a template file.
 *
 * Attribute names match gwill_testimonials_grid()'s array keys exactly —
 * one set of names to remember, not two.
 *
 * @param  array<string,string>|string $atts Shortcode attributes.
 * @return string
 * @since  1.0.62
 */
function gwill_testimonials_shortcode( $atts ): string {
	$atts = shortcode_atts( [
		'mode'    => 'grid',
		'count'   => 6,
		'columns' => 3,
		'orderby' => 'date',
		'order'   => 'DESC',
	], $atts, 'gwill_testimonials' );

	$atts['count']   = (int) $atts['count'];
	$atts['columns'] = (int) $atts['columns'];

	ob_start();
	gwill_testimonials_grid( $atts );
	return ob_get_clean();
}

// ── Star rating render helper ───────────────────────────────────────────────

/**
 * Render a 1–5 star rating as inline SVG markup.
 *
 * Filled and outline stars are the exact same <path>, switched by a CSS
 * class rather than two different SVGs — half the markup, and it means a
 * future style change to "what a star looks like" only ever has to happen
 * in one path definition.
 *
 * @param  int $rating 1–5. Already clamped by the save handler, but
 *                      clamped again here too — this function has no way
 *                      to know whether its caller already validated input,
 *                      so it doesn't assume.
 * @return string
 * @since  1.0.62
 */
function gwill_render_star_rating( int $rating ): string {

	$rating = max( 0, min( 5, $rating ) );
	$star   = '<path d="M12 2.5l2.9 6.4 6.9.7-5.2 4.7 1.6 6.8L12 17.6l-6.2 3.5 1.6-6.8L2.2 9.6l6.9-.7L12 2.5z"></path>';
	$html   = '<span class="gwill-stars" aria-label="' . esc_attr(
		sprintf(
			/* translators: %d: rating out of 5, e.g. "5 out of 5 stars" */
			__( '%d out of 5 stars', 'gwill-starter' ),
			$rating
		)
	) . '">';

	for ( $i = 1; $i <= 5; $i++ ) {
		$filled = $i <= $rating;
		$html  .= sprintf(
			'<svg class="gwill-star%s" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">%s</svg>',
			$filled ? ' gwill-star--filled' : '',
			$star
		);
	}

	return $html . '</span>';
}
