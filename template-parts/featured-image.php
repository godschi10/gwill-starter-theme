<?php
/**
 * Template Part: Featured Image / Video Embed
 *
 * Priority order:
 *   1. YouTube video — if _gwill_video_url post meta is set and contains a
 *      valid YouTube URL (singular context only; suppressed in archive/home
 *      loops to prevent multiple iframes on one page load).
 *   2. Featured image — standard hero treatment with LCP attributes.
 *   3. Nothing — silent bail; callers need no has_post_thumbnail() guard.
 *
 * Set _gwill_video_url via the "Video Embed" meta box in the post editor
 * sidebar (inc/setup.php → gwill_register_video_meta_box).
 *
 * LCP attributes on the featured image:
 *   fetchpriority="high" + loading="eager" + decoding="sync"
 *   WP 6.3 auto-detection does not reliably tag thumbnails outside
 *   the_content(), so these are set explicitly here.
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;

// ── 1. YouTube video embed (singular only) ───────────────────────────────────

if ( is_singular() ) {
	$video_url = get_post_meta( get_the_ID(), '_gwill_video_url', true );

	if ( $video_url ) {
		$video_id = gwill_youtube_id( $video_url );

		if ( $video_id ) {
			?>
			<div class="gwill-video-embed">
				<?php
				/*
				 * loading="eager", not "lazy" — found inconsistent during the
				 * v1.0.49 audit. This iframe occupies the exact same hero slot
				 * the image branch below treats as the LCP candidate
				 * (fetchpriority="high" + loading="eager" + decoding="sync",
				 * explicitly reasoned in this file's header docblock). A video
				 * set here is, by definition, always above the fold on a single
				 * post — lazy-loading it works against fast LCP for the one
				 * case it's guaranteed to matter.
				 *
				 * Counter-argument, for the record: YouTube's embed is genuinely
				 * heavy (loads a chunk of Google's own JS/CSS), and some teams
				 * deliberately lazy-load it even above the fold to keep that
				 * cost off the critical path, accepting a slightly later visual
				 * paint in exchange for lower Total Blocking Time elsewhere on
				 * the page. That's a legitimate, defensible tradeoff — it just
				 * wasn't the one already made (and explicitly justified) for
				 * the image case right below, so the two were inconsistent
				 * with no comment explaining why. If you want lazy back for
				 * this specific reason, that's a one-word revert — just leave
				 * a comment next time saying so, the way the image branch
				 * already does.
				 */
				?>
				<iframe
					src="<?php echo esc_url( 'https://www.youtube-nocookie.com/embed/' . $video_id ); ?>"
					title="<?php echo esc_attr( get_the_title() ); ?>"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
					allowfullscreen
					loading="eager"
				></iframe>
			</div>
			<?php
			return; // Video rendered — skip featured image.
		}
	}
}

// ── 2. Featured image ────────────────────────────────────────────────────────

if ( ! has_post_thumbnail() ) {
	return;
}

$caption = gwill_featured_image_caption();
?>
<figure class="entry-thumbnail" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
	<?php
	the_post_thumbnail( 'gwill-hero', [
		'alt'           => gwill_featured_image_alt(),
		'fetchpriority' => 'high',
		'loading'       => 'eager',
		'decoding'      => 'sync',
		'itemprop'      => 'url',
	] );
	?>
	<?php if ( $caption ) : ?>
		<figcaption class="entry-thumbnail-caption" itemprop="caption"><?php echo $caption; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped by gwill_featured_image_caption(), which returns esc_html()-encoded string ?></figcaption>
	<?php endif; ?>
</figure>
