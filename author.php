<?php
/**
 * The template for displaying Author pages
 *
 * @package GWill_Tech
 */

get_header();

$curauth = (isset($_GET['author_name'])) ? get_user_by('slug', $author_name) : get_userdata(get_query_var('author'));
$author_name_display = $curauth ? $curauth->display_name : get_the_author();
$author_desc = $curauth ? $curauth->description : get_the_author_meta('description');
$author_id = $curauth ? $curauth->ID : get_the_author_meta('ID');
$author_initial = mb_substr($author_name_display, 0, 1);

// Count posts by author
$author_posts_count = count_user_posts($author_id);
?>

<div class="page active" id="page-author" role="main">
<section class="page-hero"><div class="wrap flex items-center" style="gap:20px"><div class="avatar" style="width:64px;height:64px;font-size:24px"><?php echo esc_html($author_initial); ?></div><div><p class="label label-green">Author</p><h1 class="h1"><?php echo esc_html($author_name_display); ?></h1><p><?php echo esc_html($author_desc ? $author_desc : 'Web developer and Android tester, Lagos, Nigeria · 240+ guides published since 2022'); ?></p></div></div></section>
<section class="section-sm"><div class="wrap"><div class="article-layout" style="grid-template-columns:1fr 300px"><div><p class="label" style="margin-bottom:16px">All posts by <?php echo esc_html($author_name_display); ?></p>

<?php if (have_posts()) : while (have_posts()) : the_post(); 
    $cats = get_the_category();
    $cat_name = !empty($cats) ? $cats[0]->name : 'Android';
    $cat_slug = !empty($cats) ? $cats[0]->slug : 'android';
    if (strpos($cat_slug, 'web') !== false) { $badge_class = 'badge-webdev'; $media_class = 'cat-webdev'; $icon = '🌐'; }
    elseif (strpos($cat_slug, 'software') !== false) { $badge_class = 'badge-software'; $media_class = 'cat-software'; $icon = '⌨️'; }
    else { $badge_class = 'badge-android'; $media_class = 'cat-android'; $icon = '📱'; }
    
    $reading_time = get_post_meta(get_the_ID(), 'reading_time', true);
    if (!$reading_time) { $reading_time = '5 min'; }
?>
<div class="card" style="margin-bottom:16px"><div class="flex" style="padding:var(--sp-5);gap:16px"><div class="card-media <?php echo esc_attr($media_class); ?>" style="width:120px;height:80px;aspect-ratio:auto;border-radius:var(--r-sm);flex-shrink:0"><?php echo esc_html($icon); ?></div><div><span class="badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($cat_name); ?></span><h3 style="margin:8px 0 4px"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><div class="card-meta"><span><?php echo esc_html($reading_time); ?> · <?php echo get_the_date('M Y'); ?></span></div></div></div></div>
<?php endwhile; else: ?>
<p class="body-sm">No posts found for this author.</p>
<?php endif; ?>

</div><aside class="sidebar-sticky"><div class="sidebar-widget"><h4>By the numbers</h4><div style="padding:8px 0;border-bottom:1px solid var(--border)"><div class="flex justify-between"><span class="body-sm">Guides published</span><span style="font-size:20px;font-weight:800"><?php echo esc_html($author_posts_count); ?>+</span></div></div><div style="padding:8px 0;border-bottom:1px solid var(--border)"><div class="flex justify-between"><span class="body-sm">Categories</span><span style="font-size:20px;font-weight:800">3</span></div></div><div style="padding:8px 0"><div class="flex justify-between"><span class="body-sm">Years writing</span><span style="font-size:20px;font-weight:800">4</span></div></div></div></aside></div></div></section>
</div>

<?php
get_footer();
