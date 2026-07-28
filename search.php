<?php
/**
 * The template for displaying Search Results pages
 *
 * @package GWill_Tech
 */

get_header();

$search_query = get_search_query();
global $wp_query;
$total_results = $wp_query->found_posts;
$has_results = have_posts();
?>

<?php if ($has_results) : ?>
<div class="page active" id="page-search" role="main">
<section class="page-hero"><div class="wrap"><p class="label">Search</p><form role="search" method="get" class="newsletter-row" style="max-width:560px" action="<?php echo esc_url(home_url('/')); ?>"><input type="text" name="s" class="input" placeholder="Search guides" value="<?php echo esc_attr($search_query); ?>"><button class="btn btn-primary" type="submit">Search</button></form><p class="body-sm" style="margin-top:16px"><?php echo esc_html($total_results); ?> result<?php echo $total_results === 1 ? '' : 's'; ?> for <strong style="color:var(--text)">"<?php echo esc_html($search_query); ?>"</strong></p></div></section>
<section class="section-sm"><div class="wrap" style="max-width:760px">
<?php while (have_posts()) : the_post(); 
    $cats = get_the_category();
    $cat_name = !empty($cats) ? $cats[0]->name : 'Android';
    $cat_slug = !empty($cats) ? $cats[0]->slug : 'android';
    if (strpos($cat_slug, 'web') !== false) { $badge_class = 'badge-webdev'; }
    elseif (strpos($cat_slug, 'software') !== false) { $badge_class = 'badge-software'; }
    else { $badge_class = 'badge-android'; }
    
    $reading_time = get_post_meta(get_the_ID(), 'reading_time', true);
    if (!$reading_time) { $reading_time = '5 min'; }
?>
<div class="card" style="margin-bottom:16px"><div class="card-body"><span class="badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($cat_name); ?></span><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p class="card-excerpt"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p><div class="card-meta"><span><?php echo esc_html($reading_time); ?></span><span>·</span><span><?php echo get_the_date('M Y'); ?></span></div></div></div>
<?php endwhile; ?>
</div></section>
</div>

<?php else : ?>

<div class="page active" id="page-search-empty" role="main">
<section class="page-hero"><div class="wrap"><p class="label">Search</p><form role="search" method="get" class="newsletter-row" style="max-width:560px" action="<?php echo esc_url(home_url('/')); ?>"><input type="text" name="s" class="input" placeholder="Search guides" value="<?php echo esc_attr($search_query); ?>"><button class="btn btn-primary" type="submit">Go</button></form></div></section>
<section class="section"><div class="wrap empty-state"><div class="icon-wrap"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg></div><p class="h3" style="margin-bottom:10px">No results for "<?php echo esc_html($search_query); ?>"</p><p class="body-sm" style="max-width:44ch;margin:0 auto">This blog covers using Android, not building it. Try one of these:</p><div class="suggest-list"><a href="<?php echo esc_url(home_url('/category/android/')); ?>">Everything Android <span>→</span></a><a href="<?php echo esc_url(home_url('/resources/')); ?>">Resources &amp; tools <span>→</span></a></div></div></section>
</div>

<?php endif; ?>

<?php
get_footer();
