<?php
/**
 * Portfolio / case-studies post type + grid display.
 *
 * Unlike the testimonials CPT, this one is genuinely public — a case
 * study is content worth its own page (`public: true`, `has_archive:
 * true`), where a testimonial is a snippet pulled into someone else's
 * page and nothing more. That difference in kind is the actual reason
 * these two CPTs are configured so differently, not an inconsistency
 * between them.
 *
 * Scope is deliberately exactly what the roadmap specified — a registered
 * post type plus a grid template-part — and nothing more. That means no
 * dedicated single-gwill_portfolio.php or archive-gwill_portfolio.php
 * ships here; both fall through to this theme's existing single.php /
 * archive.php, which already degrade gracefully for a post type with no
 * categories assigned (gwill_get_primary_category() already returns null
 * cleanly when get_the_category() comes back empty — that's not new
 * behaviour added for this feature, it's already how that function
 * handles a post with no categories at all). A project wanting a more
 * tailored single-project layout than the generic single.php gives can
 * add single-gwill_portfolio.php later; that's a per-client decision, not
 * something to bake into the starter.
 *
 * @package GWill_Starter
 * @since   1.0.63
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'gwill_register_portfolio_cpt' );
add_action( 'add_meta_boxes', 'gwill_register_portfolio_meta_box' );
add_action( 'save_post_gwill_portfolio', 'gwill_save_portfolio_meta_box' );
add_shortcode( 'gwill_portfolio', 'gwill_portfolio_shortcode' );

// ── Post type + taxonomy registration ───────────────────────────────────────

/**
 * Register the gwill_portfolio post type and its gwill_portfolio_type
 * taxonomy.
 *
 * The taxonomy exists because "filter by service type" (Branding / Web
 * Design / Development, etc.) is close to a baseline expectation for an
 * agency/freelancer portfolio, not a nice-to-have bolted on afterward —
 * hierarchical to allow a parent/child structure if a project wants one
 * (e.g. "Design" > "Branding"), but works perfectly flat too if a project
 * never adds a child term.
 *
 * @since 1.0.63
 */
function gwill_register_portfolio_cpt(): void {

	$labels = [
		'name'               => _x( 'Portfolio', 'post type general name', 'gwill-starter' ),
		'singular_name'      => _x( 'Project', 'post type singular name', 'gwill-starter' ),
		'menu_name'          => _x( 'Portfolio', 'admin menu', 'gwill-starter' ),
		'add_new_item'       => __( 'Add New Project', 'gwill-starter' ),
		'edit_item'          => __( 'Edit Project', 'gwill-starter' ),
		'new_item'           => __( 'New Project', 'gwill-starter' ),
		'view_item'          => __( 'View Project', 'gwill-starter' ),
		'search_items'       => __( 'Search Portfolio', 'gwill-starter' ),
		'not_found'          => __( 'No projects found.', 'gwill-starter' ),
		'not_found_in_trash' => __( 'No projects found in Trash.', 'gwill-starter' ),
		'all_items'          => __( 'All Projects', 'gwill-starter' ),
		'archives'           => __( 'Portfolio Archive', 'gwill-starter' ),
	];

	register_post_type( 'gwill_portfolio', [
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-portfolio',
		// Directly below Testimonials (25) — both are Tier 2/3 content
		// types added to the same neighbourhood of the admin menu.
		'menu_position'      => 26,
		'supports'           => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
		'has_archive'        => true,
		'rewrite'            => [ 'slug' => 'portfolio' ],
		'capability_type'    => 'post',
	] );

	$tax_labels = [
		'name'          => _x( 'Project Types', 'taxonomy general name', 'gwill-starter' ),
		'singular_name' => _x( 'Project Type', 'taxonomy singular name', 'gwill-starter' ),
		'search_items'  => __( 'Search Project Types', 'gwill-starter' ),
		'all_items'     => __( 'All Project Types', 'gwill-starter' ),
		'edit_item'     => __( 'Edit Project Type', 'gwill-starter' ),
		'update_item'   => __( 'Update Project Type', 'gwill-starter' ),
		'add_new_item'  => __( 'Add New Project Type', 'gwill-starter' ),
		'new_item_name' => __( 'New Project Type Name', 'gwill-starter' ),
		'menu_name'     => __( 'Project Types', 'gwill-starter' ),
	];

	register_taxonomy( 'gwill_portfolio_type', 'gwill_portfolio', [
		'labels'            => $tax_labels,
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'portfolio-type' ],
	] );
}

// ── "Project Details" meta box (client name + live project URL) ──────────

/**
 * Register the meta box. Same pattern as the testimonial and video-embed
 * meta boxes — one consistent way to do this across the whole theme.
 *
 * @since 1.0.63
 */
function gwill_register_portfolio_meta_box(): void {
	add_meta_box(
		'gwill-portfolio-details',
		__( 'Project Details', 'gwill-starter' ),
		'gwill_render_portfolio_meta_box',
		'gwill_portfolio',
		'side',
		'default'
	);
}

/**
 * Render the client name + project URL fields.
 *
 * @param WP_Post $post Current post.
 * @since 1.0.63
 */
function gwill_render_portfolio_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'gwill_save_portfolio_' . $post->ID, 'gwill_portfolio_nonce' );

	$client = get_post_meta( $post->ID, '_gwill_portfolio_client', true );
	$url    = get_post_meta( $post->ID, '_gwill_portfolio_url', true );
	?>
	<p>
		<label for="gwill_portfolio_client" style="display:block;margin-bottom:4px;font-weight:600">
			<?php esc_html_e( 'Client', 'gwill-starter' ); ?>
		</label>
		<input
			type="text"
			id="gwill_portfolio_client"
			name="gwill_portfolio_client"
			value="<?php echo esc_attr( $client ); ?>"
			style="width:100%"
		/>
	</p>
	<p>
		<label for="gwill_portfolio_url" style="display:block;margin-bottom:4px;font-weight:600">
			<?php esc_html_e( 'Live Project URL', 'gwill-starter' ); ?>
		</label>
		<input
			type="url"
			id="gwill_portfolio_url"
			name="gwill_portfolio_url"
			value="<?php echo esc_attr( $url ); ?>"
			style="width:100%"
			placeholder="https://"
		/>
		<small><?php esc_html_e( 'Optional. If set, the grid card links here instead of the project\'s own page.', 'gwill-starter' ); ?></small>
	</p>
	<?php
}

/**
 * Save the client name + project URL fields.
 *
 * Identical security ordering to gwill_save_testimonial_meta_box() and
 * gwill_save_video_meta_box() — capability first, then autosave/revision
 * checks, then the nonce as CSRF protection layered on top of all of it.
 *
 * @param int $post_id Post ID.
 * @since 1.0.63
 */
function gwill_save_portfolio_meta_box( int $post_id ): void {

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['gwill_portfolio_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['gwill_portfolio_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'gwill_save_portfolio_' . $post_id ) ) {
		return;
	}

	if ( isset( $_POST['gwill_portfolio_client'] ) ) {
		update_post_meta(
			$post_id,
			'_gwill_portfolio_client',
			sanitize_text_field( wp_unslash( $_POST['gwill_portfolio_client'] ) )
		);
	}

	if ( isset( $_POST['gwill_portfolio_url'] ) ) {
		update_post_meta(
			$post_id,
			'_gwill_portfolio_url',
			esc_url_raw( wp_unslash( $_POST['gwill_portfolio_url'] ) )
		);
	}
}

// ── Query helper ─────────────────────────────────────────────────────────────

/**
 * Fetch published portfolio items, optionally filtered to one project type.
 *
 * @param  array{count?:int,type?:string,orderby?:string,order?:string} $args
 *         type: a gwill_portfolio_type term slug.
 * @return WP_Post[]
 * @since  1.0.63
 */
function gwill_get_portfolio_items( array $args = [] ): array {

	$defaults = [
		'count'   => 6,
		'orderby' => 'date',
		'order'   => 'DESC',
	];
	$args = wp_parse_args( $args, $defaults );

	$query_args = [
		'post_type'      => 'gwill_portfolio',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['count'],
		'orderby'        => sanitize_key( $args['orderby'] ),
		'order'          => 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC',
		'no_found_rows'  => true,
	];

	if ( ! empty( $args['type'] ) ) {
		// A single explicit term filter, not an open-ended query — the
		// documented, correct way to do this despite the WPCS sniff below.
		$query_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			[
				'taxonomy' => 'gwill_portfolio_type',
				'field'    => 'slug',
				'terms'    => sanitize_title( $args['type'] ),
			],
		];
	}

	$query = new WP_Query( $query_args );

	return $query->posts;
}

// ── Public template tag + shortcode ─────────────────────────────────────────

/**
 * Render a portfolio grid. The public API for this feature — call this
 * directly from any page template.
 *
 * @param array{count?:int,columns?:int,type?:string,orderby?:string,order?:string} $args
 *        columns: 2-4, default 3.
 * @since 1.0.63
 */
function gwill_portfolio_grid( array $args = [] ): void {
	gwill_part( 'portfolio/portfolio', $args );
}

/**
 * [gwill_portfolio] shortcode — same attribute names as
 * gwill_portfolio_grid()'s array keys.
 *
 * @param  array<string,string>|string $atts
 * @return string
 * @since  1.0.63
 */
function gwill_portfolio_shortcode( $atts ): string {
	$atts = shortcode_atts( [
		'count'   => 6,
		'columns' => 3,
		'type'    => '',
		'orderby' => 'date',
		'order'   => 'DESC',
	], $atts, 'gwill_portfolio' );

	$atts['count']   = (int) $atts['count'];
	$atts['columns'] = (int) $atts['columns'];

	ob_start();
	gwill_portfolio_grid( $atts );
	return ob_get_clean();
}
