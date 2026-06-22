<?php
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {

	/*
	 * Content width — constrains embedded media (oEmbed iframes, etc.)
	 * Keep in sync with --max-width in style.css.
	 * Must be set inside after_setup_theme with global declaration to
	 * satisfy WPCS WordPress.WP.GlobalVariablesOverride.
	 */
	global $content_width;
	if ( ! isset( $content_width ) ) {
		$content_width = 1200;
	}

	load_theme_textdomain( 'gwill-starter', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	] );
	/*
	 * 'customize-selective-refresh-widgets' is intentionally omitted.
	 * That support only has effect when widget areas are registered via
	 * register_sidebar(). This starter ships with no widget areas — add
	 * the support alongside register_sidebar() if widgets are ever needed.
	 */

	/*
	 * 'wp-block-styles' is intentionally omitted from this starter.
	 * That support enqueues wp-block-library-theme.css (~3 KB) — opinionated
	 * Gutenberg default block styles the project may not want. For a blank-slate
	 * starter that owns all its own CSS, it is dead weight. Enable per project
	 * if Gutenberg's built-in block styles are needed:
	 *
	 *   add_theme_support( 'wp-block-styles' );
	 */

	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );

	/*
	 * Custom logo — required by the WordPress Theme Review Team.
	 * Enables the Site Logo section in the Customizer.
	 * flex-height + flex-width allow the logo to maintain its natural
	 * aspect ratio rather than being cropped to fixed dimensions.
	 * The header template shows the custom logo when set, falling back
	 * to the text site title.
	 */
	add_theme_support( 'custom-logo', [
		'height'      => 80,
		'width'       => 320,
		'flex-height' => true,
		'flex-width'  => true,
		'unlink-homepage-logo' => false,
	] );

	register_nav_menus( [
		'primary' => __( 'Primary Navigation', 'gwill-starter' ),
		'footer'  => __( 'Footer Navigation',  'gwill-starter' ),
	] );

	add_editor_style( 'style.css' );

	/*
	 * Register a theme-specific hero image size matching --max-width.
	 * WP's built-in 'large' defaults to 1024px — 15% narrower than this
	 * theme's 1200px content width — so the LCP image is always upscaled
	 * on desktop. Use 'gwill-hero' in single.php and page.php instead.
	 * Crop is soft (false) — portrait images are not centre-cropped.
	 */
	add_image_size( 'gwill-hero', 1200, 675, false );

} );

/*
 * Flush rewrite rules on theme activation.
 *
 * Ensures WordPress re-registers all rewrite rules (including author archives)
 * after the theme is switched. Without this, author archive URLs can resolve
 * stale cached rules and 301 to the homepage.
 *
 * Note: if the author archive still redirects after deploying, the root cause
 * is almost always a cached redirect in LiteSpeed Cache. Fix:
 *   1. LiteSpeed Cache plugin → Manage → Purge All
 *   2. Settings → Permalinks → Save Changes (belt-and-suspenders rule flush)
 */
/*
 * Version-based rewrite flush.
 *
 * Runs once on the first page load after a version bump, then skips on
 * every subsequent request (single option check, ~0 cost). Replaces the
 * previous after_switch_theme approach which never fires on file-upload
 * deployments (only fires on theme activation via WP admin UI).
 *
 * Still requires LiteSpeed Cache → Purge All + Permalinks → Save Changes
 * after deploy when a cached redirect exists — code cannot clear the cache.
 */
add_action( 'init', 'gwill_maybe_flush_rewrites', 1 );
function gwill_maybe_flush_rewrites(): void {
	$theme_ver = wp_get_theme()->get( 'Version' );
	if ( get_option( 'gwill_rewrite_ver' ) === $theme_ver ) {
		return;
	}
	flush_rewrite_rules();
	update_option( 'gwill_rewrite_ver', $theme_ver );
}


// ── Video embed meta box ─────────────────────────────────────────────────────

add_action( 'add_meta_boxes', 'gwill_register_video_meta_box' );

/**
 * Register the "Video Embed" meta box in the post editor sidebar.
 */
function gwill_register_video_meta_box(): void {
	add_meta_box(
		'gwill-video-url',
		__( 'Video Embed (replaces featured image)', 'gwill-starter' ),
		'gwill_render_video_meta_box',
		'post',
		'side',
		'default'
	);
}

/**
 * Render the video embed meta box.
 *
 * @param WP_Post $post Current post.
 */
function gwill_render_video_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'gwill_save_video_url_' . $post->ID, 'gwill_video_url_nonce' );
	$value = get_post_meta( $post->ID, '_gwill_video_url', true );
	?>
	<label for="gwill_video_url" class="screen-reader-text">
		<?php esc_html_e( 'YouTube URL', 'gwill-starter' ); ?>
	</label>
	<input
		type="url"
		id="gwill_video_url"
		name="gwill_video_url"
		value="<?php echo esc_attr( $value ); ?>"
		style="width:100%;margin-top:4px"
		placeholder="https://youtu.be/..."
	/>
	<p style="margin-top:6px;font-size:11px;color:#666;line-height:1.5">
		<?php esc_html_e( 'Paste a YouTube URL to embed a video instead of the featured image on single post view. Leave blank to use the featured image.', 'gwill-starter' ); ?>
	</p>
	<?php
}

add_action( 'save_post', 'gwill_save_video_meta_box' );

/**
 * Save the video embed URL on post save.
 *
 * @param int $post_id Post ID.
 */
function gwill_save_video_meta_box( int $post_id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	$nonce = isset( $_POST['gwill_video_url_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['gwill_video_url_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'gwill_save_video_url_' . $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$url = isset( $_POST['gwill_video_url'] )
		? esc_url_raw( trim( wp_unslash( $_POST['gwill_video_url'] ) ) )
		: '';
	if ( $url ) {
		update_post_meta( $post_id, '_gwill_video_url', $url );
	} else {
		delete_post_meta( $post_id, '_gwill_video_url' );
	}
}
