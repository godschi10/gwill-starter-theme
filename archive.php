<?php
/**
 * Tech Blog Archive / Category (archive.php)
 *
 * Page hero, filter bar, lead card + grid-2, sub-categories sidebar.
 *
 * @package GWill_Tech
 */

defined( 'ABSPATH' ) || exit;

get_header();

$queried = get_queried_object();
$is_cat  = is_category();
$cat_slug = $is_cat ? $queried->slug : '';
$cat_name = $is_cat ? $queried->name : ( is_author() ? get_the_author() : ( is_tag() ? single_tag_title( '', false ) : get_the_archive_title() ) );

// Category color
$cat_color = 'software';
if ( strpos( $cat_slug, 'android' ) !== false ) $cat_color = 'android';
elseif ( strpos( $cat_slug, 'web' ) !== false || strpos( $cat_slug, 'dev' ) !== false ) $cat_color = 'webdev';

// Get sub-categories (for category pages)
$sub_cats = $is_cat ? get_terms( array(
	'taxonomy' => 'category',
	'parent'   => $queried->term_id,
	'hide_empty' => false,
) ) : array();

// Description
$description = $is_cat ? term_description() : '';

// Total count
$total_posts = $is_cat ? $queried->count : $wp_query->found_posts;
?>

<section class="page-hero">
	<div class="wrap">
		<nav class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<span>/</span>
			<span style="color:var(--cat-<?php echo esc_attr( $cat_color ); ?>)"><?php echo esc_html( $cat_name ); ?></span>
		</nav>
		<?php if ( $is_cat ) : ?>
		<span class="badge badge-<?php echo esc_attr( $cat_color ); ?>" style="margin-bottom:16px">Category</span>
		<h1 class="h1"><?php echo esc_html( $cat_name ); ?></h1>
		<?php if ( $description ) : ?>
		<p><?php echo wp_kses_post( $description ); ?></p>
		<?php endif; ?>
		<?php else : ?>
		<h1 class="h1"><?php echo esc_html( $cat_name ); ?></h1>
		<?php endif; ?>
	</div>
</section>

<?php if ( have_posts() ) : ?>
<section class="section-sm">
	<div class="wrap">
		<div class="filter-bar">
			<button class="pill on">All (<?php echo esc_html( $total_posts ); ?>)</button>
		</div>

		<?php
		$first = true;
		$count = 0;
		while ( have_posts() ) : the_post();
			$count++;
			$cats_local = get_the_category();
			$cat_local_slug = ! empty( $cats_local ) ? $cats_local[0]->slug : $cat_slug;
			$cat_local_color = 'software';
			if ( strpos( $cat_local_slug, 'android' ) !== false ) $cat_local_color = 'android';
			elseif ( strpos( $cat_local_slug, 'web' ) !== false || strpos( $cat_local_slug, 'dev' ) !== false ) $cat_local_color = 'webdev';
		?>

		<?php if ( $first && $is_cat ) : ?>
		<div style="display:grid;grid-template-columns:1fr 300px;gap:24px">
			<div>
			<div class="card card-lead" style="margin-bottom:24px">
				<div class="card-media cat-<?php echo esc_attr( $cat_local_color ); ?>" style="position:relative">
					<?php if ( has_post_thumbnail() ) : echo get_the_post_thumbnail( get_the_ID(), 'medium', array( 'style' => 'width:100%;height:100%;object-fit:cover;position:absolute;inset:0' ) ); else : ?>&#128241;<?php endif; ?>
				</div>
				<div class="card-body">
					<span class="badge badge-<?php echo esc_attr( $cat_local_color ); ?>"><?php echo esc_html( ! empty( $cats_local ) ? $cats_local[0]->name : get_post_type() ); ?></span>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p class="card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 20 ) ); ?></p>
					<div class="card-meta"><span><?php echo esc_html( gwill_reading_time() ); ?> min</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
				</div>
			</div>
			<div class="grid-2" style="margin-bottom:24px">
		<?php $first = false; ?>

		<?php elseif ( ! $first && $is_cat ) : ?>
			<div class="card">
				<div class="card-media cat-<?php echo esc_attr( $cat_local_color ); ?>">&#128241;</div>
				<div class="card-body">
					<span class="badge badge-<?php echo esc_attr( $cat_local_color ); ?>"><?php echo esc_html( ! empty( $cats_local ) ? $cats_local[0]->name : get_post_type() ); ?></span>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<div class="card-meta"><span><?php echo esc_html( gwill_reading_time() ); ?> min</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! $is_cat ) : ?>
		<div class="card" style="margin-bottom:16px">
			<div class="card-body">
				<span class="badge badge-<?php echo esc_attr( $cat_local_color ); ?>"><?php echo esc_html( ! empty( $cats_local ) ? $cats_local[0]->name : get_post_type() ); ?></span>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<p class="card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 20 ) ); ?></p>
				<div class="card-meta"><span><?php echo esc_html( gwill_reading_time() ); ?> min</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
			</div>
		</div>
		<?php endif; ?>

		<?php endwhile; ?>

		<?php if ( $is_cat ) : ?>
			</div><!-- /.grid-2 -->
			</div><!-- /.left column -->

			<aside class="sidebar-sticky">
				<?php if ( ! empty( $sub_cats ) ) : ?>
				<div class="sidebar-widget">
					<h4>Sub-categories</h4>
					<div style="display:flex;flex-direction:column;gap:4px">
						<?php foreach ( $sub_cats as $sub ) : ?>
						<a href="<?php echo esc_url( get_category_link( $sub->term_id ) ); ?>" class="body-sm" style="padding:6px 0;color:var(--mid)"><?php echo esc_html( $sub->name ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</aside>
		</div><!-- /.grid -->
		<?php endif; ?>

		<?php
		the_posts_pagination( array(
			'mid_size'  => 2,
			'prev_text' => '&larr; Prev',
			'next_text' => 'Next &rarr;',
		) );
		?>
	</div>
</section>
<?php else : ?>
<section class="section">
	<div class="wrap empty-state">
		<div class="icon-wrap">
			<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
		</div>
		<p class="h3" style="margin-bottom:10px">No posts found</p>
		<div class="suggest-list">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home <span>&rarr;</span></a>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
