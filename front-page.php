<?php
/**
 * Tech Blog Front Page (home.php)
 *
 * Hero with boot animation, stat strip, category sections, filter grid.
 *
 * @package GWill_Tech
 */

defined( 'ABSPATH' ) || exit;

// Get posts per category for the category sections
$android_query = new WP_Query( array(
	'category_name' => 'android',
	'posts_per_page' => 5,
	'no_found_rows' => true,
) );

$webdev_query = new WP_Query( array(
	'category_name' => 'web-dev',
	'posts_per_page' => 4,
	'no_found_rows' => true,
) );

$software_query = new WP_Query( array(
	'category_name' => 'software',
	'posts_per_page' => 5,
	'no_found_rows' => true,
) );

// Collect shown post IDs so the filter grid doesn't duplicate them
$shown_ids = array();
foreach ( array( $android_query, $webdev_query, $software_query ) as $q ) {
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			$shown_ids[] = get_the_ID();
		}
		$q->rewind_posts();
	}
}
wp_reset_postdata();

$cat_android = get_category_by_slug( 'android' );
$cat_webdev  = get_category_by_slug( 'web-dev' );
$cat_software = get_category_by_slug( 'software' );
$android_total = $cat_android ? $cat_android->count : 0;
$webdev_total  = $cat_webdev  ? $cat_webdev->count  : 0;
$software_total = $cat_software ? $cat_software->count : 0;
$grand_total   = $android_total + $webdev_total + $software_total;

// Emoji pools per category
$android_emojis = array( '&#128241;', '&#128267;', '&#128274;', '&#128242;', '&#128268;' );
$webdev_emojis  = array( '&#127760;', '&#9889;', '&#128640;', '&#128230;', '&#127912;' );
$software_emojis = array( '&#9000;&#65039;', '&#128451;', '&#9729;', '&#128295;', '&#128421;' );
$generic_emojis = array( '&#128196;', '&#128214;', '&#128200;', '&#128240;', '&#128269;' );

get_header();
?>

	<!-- Hero boot animation inline - must run before hero renders -->
	<script>
(function(){
	if(sessionStorage.getItem('boot_done')) return;
	var te=document.getElementById('boot-terminal');
	var bte=document.getElementById('boot-buttons');
	if(!te) return;
	var lines=[
		'> INITIALIZING tech.gwillchijioke.com...',
		'> LOADING: Android ... 100%',
		'> LOADING: Web-Dev ... 100%',
		'> LOADING: Software ... 100%',
		'> SYSTEM READY.'
	];
	var delay=15;
	function typeLine(line,cb){
		var el=document.createElement('div');
		el.className='boot-line';
		te.appendChild(el);
		var ci=0,chars=line.split('');
		function tc(){ if(ci<chars.length){ el.textContent+=chars[ci]; ci++; setTimeout(tc,delay); } else { setTimeout(function(){ el.classList.add('visible'); if(cb) cb(); },100); } }
		tc();
	}
	function runSeq(i){
		if(i<lines.length){ typeLine(lines[i],function(){ runSeq(i+1); }); } else {
			setTimeout(function(){ var se=document.getElementById('hero-static'); if(se) se.style.display='block'; if(bte) bte.classList.add('visible'); sessionStorage.setItem('boot_done','1'); },300);
		}
	}
	setTimeout(function(){ runSeq(0); },50);
})();
</script>

<section class="hero">
	<div class="wrap hero-inner">
		<div class="hero-boot" id="hero-boot">
			<p class="label label-green reveal">>_ system status: initializing...</p>
			<h1 class="h1 reveal reveal-d1">Android tutorials and web engineering, <span class="accent">tested first.</span></h1>
			<div class="boot-terminal" id="boot-terminal"></div>
			<div class="boot-buttons reveal reveal-d4" id="boot-buttons">
				<a href="<?php echo esc_url( home_url( '/start-here/' ) ); ?>" class="btn btn-primary">Start reading &rarr;</a>
				<a href="<?php echo esc_url( home_url( '/category/android/' ) ); ?>" class="btn btn-ghost">Browse Android</a>
			</div>
		</div>
		<div class="hero-static" id="hero-static" style="display:none">
			<p class="label label-green reveal">>_ system status: operational</p>
			<h1 class="h1 reveal reveal-d1">Android tutorials and web engineering, <span class="accent">tested first.</span></h1>
			<p class="hero-sub reveal reveal-d2">No affiliate fluff, no reposted press releases. Every guide here was tested on real hardware before it was published &mdash; covering Android, web development, and the software underneath both.</p>
			<div class="hero-cta reveal reveal-d3">
				<a href="<?php echo esc_url( home_url( '/start-here/' ) ); ?>" class="btn btn-primary">Start here &rarr;</a>
				<a href="<?php echo esc_url( home_url( '/category/android/' ) ); ?>" class="btn btn-ghost">Browse Android</a>
			</div>
			<div class="stat-strip reveal reveal-d4">
				<div class="stat"><div class="stat-num"><?php echo esc_html( $grand_total ? $grand_total . '+' : '240+' ); ?></div><div class="stat-label">Guides published</div></div>
				<div class="stat"><div class="stat-num">18k</div><div class="stat-label">Monthly readers</div></div>
				<div class="stat"><div class="stat-num">3</div><div class="stat-label">Categories</div></div>
				<div class="stat"><div class="stat-num">4</div><div class="stat-label">Years writing</div></div>
			</div>
		</div>
	</div>
</section>

<?php if ( $android_query->have_posts() ) : ?>
<section class="section-sm">
	<div class="wrap">
		<div class="flex justify-between items-center reveal" style="margin-bottom:20px">
			<p class="label label-green">>_ /android</p>
			<a href="<?php echo esc_url( home_url( '/category/android/' ) ); ?>" style="color:var(--cat-android)">View all &rarr;</a>
		</div>
		<?php
		$first = true;
		$ei = 0;
		while ( $android_query->have_posts() ) : $android_query->the_post();
			$emoji = $android_emojis[ $ei % count( $android_emojis ) ];
			$ei++;
			if ( $first ) :
		?>
		<div class="card card-lead reveal reveal-d1">
			<div class="card-media cat-android" style="position:relative">
				<?php if ( has_post_thumbnail() ) : echo get_the_post_thumbnail( get_the_ID(), 'medium', array( 'style' => 'width:100%;height:100%;object-fit:cover;position:absolute;inset:0' ) ); else : ?><?php echo $emoji; ?><?php endif; ?>
			</div>
			<div class="card-body">
				<span class="badge badge-android">Android</span>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<p class="card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 20 ) ); ?></p>
				<div class="card-meta"><span><?php the_author(); ?></span><span>&middot;</span><span><?php echo esc_html( gwill_reading_time() ); ?> min read</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
			</div>
		</div>
		<div class="grid-2" style="margin-bottom:24px">
		<?php else : ?>
		<div class="card reveal">
			<div class="card-media cat-android"><?php echo $emoji; ?></div>
			<div class="card-body">
				<span class="badge badge-android">Android</span>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<div class="card-meta"><span><?php echo esc_html( gwill_reading_time() ); ?> min</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
			</div>
		</div>
		<?php
			endif;
			$first = false;
		endwhile;
		wp_reset_postdata();
		?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $webdev_query->have_posts() ) : ?>
<section class="section-sm">
	<div class="wrap">
		<div class="flex justify-between items-center reveal" style="margin-bottom:20px">
			<p class="label" style="color:var(--cat-webdev)">>_ /web-dev</p>
			<a href="<?php echo esc_url( home_url( '/category/web-dev/' ) ); ?>" style="color:var(--cat-webdev)">View all &rarr;</a>
		</div>
		<div class="grid-4">
		<?php
		$ei = 0;
		while ( $webdev_query->have_posts() ) : $webdev_query->the_post();
			$emoji = $webdev_emojis[ $ei % count( $webdev_emojis ) ];
			$ei++;
		?>
			<div class="card reveal">
				<div class="card-media cat-webdev"><?php echo $emoji; ?></div>
				<div class="card-body">
					<span class="badge badge-webdev">Web-Dev</span>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<div class="card-meta"><span><?php echo esc_html( gwill_reading_time() ); ?> min</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
				</div>
			</div>
		<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $software_query->have_posts() ) : ?>
<section class="section-sm">
	<div class="wrap">
		<div class="flex justify-between items-center reveal" style="margin-bottom:20px">
			<p class="label" style="color:var(--cat-software)">>_ /software</p>
			<a href="<?php echo esc_url( home_url( '/category/software/' ) ); ?>" style="color:var(--cat-software)">View all &rarr;</a>
		</div>
		<?php
		$first = true;
		$ei = 0;
		while ( $software_query->have_posts() ) : $software_query->the_post();
			$emoji = $software_emojis[ $ei % count( $software_emojis ) ];
			$ei++;
			if ( $first ) :
		?>
		<div class="card card-lead reveal">
			<div class="card-media cat-software" style="position:relative">
				<?php if ( has_post_thumbnail() ) : echo get_the_post_thumbnail( get_the_ID(), 'medium', array( 'style' => 'width:100%;height:100%;object-fit:cover;position:absolute;inset:0' ) ); else : ?><?php echo $emoji; ?><?php endif; ?>
			</div>
			<div class="card-body">
				<span class="badge badge-software">Software</span>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<p class="card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 20 ) ); ?></p>
				<div class="card-meta"><span><?php the_author(); ?></span><span>&middot;</span><span><?php echo esc_html( gwill_reading_time() ); ?> min</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
			</div>
		</div>
		<?php else : ?>
		<div class="card card-lead reveal">
			<div class="card-media cat-software"><?php echo $emoji; ?></div>
			<div class="card-body">
				<span class="badge badge-software">Software</span>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<p class="card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 20 ) ); ?></p>
				<div class="card-meta"><span><?php echo esc_html( gwill_reading_time() ); ?> min</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
			</div>
		</div>
		<?php
			endif;
			$first = false;
		endwhile;
		wp_reset_postdata();
		?>
	</div>
</section>
<?php endif; ?>

<?php
// Filter grid — exclude posts already shown in category sections
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$filter_query = new WP_Query( array(
	'post__not_in'   => $shown_ids,
	'posts_per_page' => 9,
	'paged'          => $paged,
	'no_found_rows'  => false,
) );
?>
<?php if ( $filter_query->have_posts() ) : ?>
<section class="section-sm">
	<div class="wrap">
		<div class="flex justify-between items-center reveal" style="margin-bottom:20px">
			<p class="label">Across the blog</p>
			<div class="flex" style="gap:8px;flex-wrap:wrap">
				<button class="pill on" data-filter="all" onclick="filterHomeGrid('all',this)">All</button>
				<?php if ( $cat_android ) : ?><button class="pill" data-filter="android" onclick="filterHomeGrid('android',this)">Android</button><?php endif; ?>
				<?php if ( $cat_webdev ) : ?><button class="pill" data-filter="webdev" onclick="filterHomeGrid('webdev',this)">Web-Dev</button><?php endif; ?>
				<?php if ( $cat_software ) : ?><button class="pill" data-filter="software" onclick="filterHomeGrid('software',this)">Software</button><?php endif; ?>
			</div>
		</div>
		<div class="grid-3" id="home-grid">
		<?php
		$gi = 0;
		while ( $filter_query->have_posts() ) : $filter_query->the_post();
			$cats = get_the_category();
			$cat_slug = ! empty( $cats ) ? $cats[0]->slug : '';
			$cat_color = 'software';
			if ( strpos( $cat_slug, 'android' ) !== false ) $cat_color = 'android';
			elseif ( strpos( $cat_slug, 'web' ) !== false || strpos( $cat_slug, 'dev' ) !== false ) $cat_color = 'webdev';
			$emoji = $generic_emojis[ $gi % count( $generic_emojis ) ];
			$gi++;
		?>
			<div class="card reveal" data-cat="<?php echo esc_attr( $cat_color ); ?>">
				<div class="card-media cat-<?php echo esc_attr( $cat_color ); ?>"><?php echo $emoji; ?></div>
				<div class="card-body">
					<span class="badge badge-<?php echo esc_attr( $cat_color ); ?>"><?php echo esc_html( ! empty( $cats ) ? $cats[0]->name : 'Article' ); ?></span>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<div class="card-meta"><span><?php echo esc_html( gwill_reading_time() ); ?> min</span><span>&middot;</span><span><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span></div>
				</div>
			</div>
		<?php endwhile; ?>
		</div>
		<?php
		the_posts_pagination( array(
			'mid_size'  => 2,
			'prev_text' => '&larr; Prev',
			'next_text' => 'Next &rarr;',
		) );
		wp_reset_postdata();
		?>
	</div>
</section>
<?php endif; ?>

<script>
/* Homepage boot animation */
(function(){
	if(sessionStorage.getItem('boot_done')){
		var be=document.getElementById('hero-boot');
		var se=document.getElementById('hero-static');
		if(be) be.style.display='none';
		if(se){ se.style.display='block'; }
		return;
	}
	var te=document.getElementById('boot-terminal');
	var bte=document.getElementById('boot-buttons');
	if(!te) return;
	var lines=[
		'> INITIALIZING tech.gwillchijioke.com...',
		'> LOADING: Android ... 100%',
		'> LOADING: Web-Dev ... 100%',
		'> LOADING: Software ... 100%',
		'> SYSTEM READY.'
	];
	var delay=15;
	function typeLine(line,cb){
		var el=document.createElement('div');
		el.className='boot-line';
		te.appendChild(el);
		var ci=0,chars=line.split('');
		function tc(){
			if(ci<chars.length){ el.textContent+=chars[ci]; ci++; setTimeout(tc,delay); }
			else { setTimeout(function(){ el.classList.add('visible'); if(cb) cb(); },100); }
		}
		tc();
	}
	function runSeq(i){
		if(i<lines.length){
			typeLine(lines[i],function(){ runSeq(i+1); });
		} else {
			setTimeout(function(){
				var se=document.getElementById('hero-static');
				if(se){ se.style.display='block'; }
				if(bte) bte.classList.add('visible');
				sessionStorage.setItem('boot_done','1');
			},300);
		}
	}
	setTimeout(function(){ runSeq(0); },200);
})();

/* Filter grid */
function filterHomeGrid(cat,btn){
	var ps=btn.parentElement.querySelectorAll('.pill');
	for(var i=0;i<ps.length;i++) ps[i].classList.remove('on');
	btn.classList.add('on');
	var cs=document.querySelectorAll('#home-grid .card[data-cat]');
	for(var i=0;i<cs.length;i++) cs[i].style.display=(cat==='all'||cs[i].getAttribute('data-cat')===cat)?'':'none';
}
</script>

<?php
get_footer();