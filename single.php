<?php
/**
 * Tech Blog Single Post (single.php)
 *
 * Article layout with TOC sidebar, author bio, post nav, comments.
 *
 * @package GWill_Tech
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) : the_post();

	$cats     = get_the_category();
	$cat_slug = ! empty( $cats ) ? $cats[0]->slug : '';
	$cat_name = ! empty( $cats ) ? $cats[0]->name : '';
	$cat_color = 'software';
	if ( strpos( $cat_slug, 'android' ) !== false ) $cat_color = 'android';
	elseif ( strpos( $cat_slug, 'web' ) !== false || strpos( $cat_slug, 'dev' ) !== false ) $cat_color = 'webdev';

	$prev_post = get_previous_post();
	$next_post = get_next_post();

	$toc_items = array();
	if ( preg_match_all( '/<h([23])[^>]*\sid=(["\x27])([^"\x27]+)\2[^>]*>([^<]+)<\/h\1>/i', get_the_content(), $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $m ) {
			$toc_items[] = array(
				'level' => (int) $m[1],
				'id'    => $m[3],
				'title' => wp_strip_all_tags( $m[4] ),
			);
		}
	}
?>

<section class="section-sm">
	<div class="wrap">
		<nav class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<span>/</span>
			<?php if ( ! empty( $cats ) ) : ?>
			<a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>"><?php echo esc_html( $cat_name ); ?></a>
			<span>/</span>
			<?php endif; ?>
			<span><?php echo esc_html( wp_trim_words( get_the_title(), 6 ) ); ?></span>
		</nav>

		<div class="article-layout">
			<article id="article-scroll-area" <?php post_class(); ?> itemscope itemtype="https://schema.org/BlogPosting">
				<div class="article-header">
					<span class="badge badge-<?php echo esc_attr( $cat_color ); ?>"><?php echo esc_html( $cat_name ?: get_post_type() ); ?></span>
					<h1 itemprop="headline"><?php the_title(); ?></h1>
					<div class="article-meta">
						<div class="avatar"><?php echo esc_html( strtoupper( substr( get_the_author(), 0, 1 ) ) ); ?></div>
						<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" style="color:var(--text)"><?php the_author(); ?></a>
						<span>&middot;</span>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" itemprop="datePublished"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></time>
						<span>&middot;</span>
						<span><?php echo esc_html( gwill_reading_time() ); ?> min read</span>
					</div>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
				<div class="article-cover"><?php the_post_thumbnail( 'large', array( 'style' => 'width:100%;height:100%;object-fit:cover' ) ); ?></div>
				<?php endif; ?>

				<div class="prose" id="article-body" itemprop="articleBody">
					<?php the_content(); ?>
					<?php wp_link_pages(); ?>
				</div>
			</article>

			<aside class="sidebar-sticky">
				<?php if ( ! empty( $toc_items ) ) : ?>
				<div class="sidebar-widget">
					<h4>On this page</h4>
					<details class="toc-dropdown" open>
						<summary class="toc-summary">Table of contents</summary>
						<div class="toc-list">
							<?php foreach ( $toc_items as $item ) : ?>
							<a href="#<?php echo esc_attr( $item['id'] ); ?>" style="padding-left:<?php echo $item['level'] === 3 ? '24' : '12'; ?>px"><?php echo esc_html( $item['title'] ); ?></a>
							<?php endforeach; ?>
						</div>
					</details>
				</div>
				<?php endif; ?>

				<div class="sidebar-widget">
					<h4>Share</h4>
					<button class="btn btn-ghost btn-sm" style="width:100%" onclick="copyLink(this)">Copy link</button>
				</div>
			</aside>
		</div>

		<div class="share-row">
			<button class="btn btn-ghost btn-sm" onclick="copyLink(this)">Copy link</button>
		</div>

		<div class="author-bio">
			<div class="avatar" style="width:52px;height:52px;font-size:18px"><?php echo esc_html( strtoupper( substr( get_the_author(), 0, 1 ) ) ); ?></div>
			<div>
				<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" style="color:var(--text)"><strong><?php the_author(); ?></strong></a>
				<p class="body-sm"><?php echo esc_html( get_the_author_meta( 'description' ) ?: 'Web developer and Android tester, Lagos, Nigeria.' ); ?></p>
			</div>
		</div>

		<?php if ( $prev_post || $next_post ) : ?>
		<div class="post-nav">
			<?php if ( $prev_post ) : ?>
			<a href="<?php echo esc_url( get_permalink( $prev_post->ID ) ); ?>">
				<span>Previous</span><?php echo esc_html( wp_trim_words( get_the_title( $prev_post->ID ), 8 ) ); ?>
			</a>
			<?php endif; ?>
			<?php if ( $next_post ) : ?>
			<a href="<?php echo esc_url( get_permalink( $next_post->ID ) ); ?>">
				<span>Next</span><?php echo esc_html( wp_trim_words( get_the_title( $next_post->ID ), 8 ) ); ?>
			</a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<?php comments_template(); ?>
		<?php endif; ?>
	</div>
</section>

<script>
function copyLink(btn){
	var orig=btn.textContent;
	btn.textContent='Copied!';
	setTimeout(function(){btn.textContent=orig},1800);
	var url=window.location.href;
	try{ navigator.clipboard.writeText(url); }catch(e){}
}
</script>

<?php
endwhile;

get_footer();
