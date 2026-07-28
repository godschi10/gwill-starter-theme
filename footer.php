<?php
/**
 * Tech Blog Footer
 *
 * 4-column footer grid, newsletter CTA, mobile nav, inline JS.
 *
 * @package GWill_Tech
 */

defined( 'ABSPATH' ) || exit;
?>
	</div><!-- /#vp -->

	<footer class="site-footer">
		<div class="footer-cta">
			<div class="wrap">
				<p class="h3" style="margin-bottom:8px">Get the Monday dispatch.</p>
				<p class="body-sm" style="margin-bottom:20px;max-width:44ch;margin-left:auto;margin-right:auto">One email a week &mdash; what I tested, what broke, what's worth your time. No spam.</p>
				<form class="newsletter-row" style="max-width:400px;margin:0 auto" action="#" method="post">
					<input type="email" class="input" placeholder="you@email.com" required>
					<button class="btn btn-primary" type="submit">Subscribe</button>
				</form>
			</div>
		</div>
		<div class="wrap footer-grid">
			<div class="footer-brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo logo-sm"><span class="prompt">&gt;_</span><span class="gap"></span><span class="name">gwillchijioke</span><span class="cursor"></span></a>
				<p>Android tutorials and web engineering from Lagos, Nigeria. Tested before it's published.</p>
				<div class="footer-social">
					<a href="https://github.com/godschi10" class="icon-btn" aria-label="GitHub">
						<svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M12 .3a12 12 0 00-3.8 23.4c.6.1.8-.3.8-.6v-2c-3.3.7-4-1.6-4-1.6-.6-1.4-1.4-1.8-1.4-1.8-1.1-.8.1-.8.1-.8 1.2.1 1.9 1.2 1.9 1.2 1.1 1.9 2.9 1.3 3.6 1 .1-.8.4-1.3.8-1.6-2.7-.3-5.5-1.3-5.5-5.9 0-1.3.5-2.4 1.2-3.2-.1-.3-.5-1.5.1-3.2 0 0 1-.3 3.3 1.2a11.5 11.5 0 016 0c2.3-1.5 3.3-1.2 3.3-1.2.6 1.7.2 2.9.1 3.2.8.8 1.2 1.9 1.2 3.2 0 4.6-2.8 5.6-5.5 5.9.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6A12 12 0 0012 .3z"/></svg>
					</a>
					<a href="https://x.com/godschi10" class="icon-btn" aria-label="X">
						<svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M18.9 2H22l-7.6 8.7L23.3 22h-7l-5.5-7.2L4.4 22H1.3l8.1-9.3L1 2h7.2l5 6.6L18.9 2z"/></svg>
					</a>
				</div>
			</div>
			<div class="footer-col"><h4>Explore</h4><a href="<?php echo esc_url( home_url( '/category/android/' ) ); ?>">Android</a><a href="<?php echo esc_url( home_url( '/category/web-dev/' ) ); ?>">Web-Dev</a><a href="<?php echo esc_url( home_url( '/category/software/' ) ); ?>">Software</a></div>
			<div class="footer-col"><h4>Site</h4><a href="<?php echo esc_url( home_url( '/start-here/' ) ); ?>">Start Here</a><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a><a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">Resources</a></div>
			<div class="footer-col"><h4>Legal</h4><a href="<?php echo esc_url( home_url( '/disclaimer/' ) ); ?>">Disclaimer</a><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy</a></div>
		</div>
		<div class="wrap footer-bottom">
			<span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> tech.gwillchijioke.com</span>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home</a>
		</div>
	</footer>

	<div class="mob-btm">
		<div class="mtb-i">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mtb-t <?php echo is_home() || is_front_page() ? 'on' : ''; ?>"><span class="ti">&#127968;</span>Home</a>
			<a href="<?php echo esc_url( home_url( '/category/android/' ) ); ?>" class="mtb-t <?php echo is_category() ? 'on' : ''; ?>"><span class="ti">&#128194;</span>Topics</a>
			<button class="mtb-t" id="tab-search" onclick="var st=document.getElementById('search-toggle');if(st)st.click();"><span class="ti">&#128269;</span>Search</button>
			<button class="mtb-t" onclick="document.getElementById('mob-nav').classList.add('open');document.body.style.overflow='hidden'"><span class="ti">&#9776;</span>Menu</button>
		</div>
	</div>

	<div class="mno" id="mob-nav">
		<div class="mno-top">
			<span class="logo" style="font-size:15px"><span class="prompt">&gt;_</span><span class="gap"></span><span class="name">gwillchijioke</span></span>
			<button class="mno-x" onclick="document.getElementById('mob-nav').classList.remove('open');document.body.style.overflow=''">
				<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
			</button>
		</div>
		<div class="mno-items">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mni">Home</a>
			<a href="<?php echo esc_url( home_url( '/category/android/' ) ); ?>" class="mni">Android</a>
			<a href="<?php echo esc_url( home_url( '/category/web-dev/' ) ); ?>" class="mni">Web-Dev</a>
			<a href="<?php echo esc_url( home_url( '/category/software/' ) ); ?>" class="mni">Software</a>
			<a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>" class="mni">Resources</a>
			<a href="<?php echo esc_url( home_url( '/start-here/' ) ); ?>" class="mni">Start Here</a>
			<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="mni">About</a>
		</div>
		<div class="mno-bot">
			<a href="<?php echo esc_url( home_url( '/start-here/' ) ); ?>" class="mno-cta">Start here
				<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
		</div>
	</div>

	<?php wp_footer(); ?>
	<script>
	/* ── Cookie consent ─────────────────────────────────────── */
	(function(){
		if(sessionStorage.getItem('cc')){
			var ck=document.getElementById('cookie');
			if(ck) ck.style.display='none';
			return;
		}
		setTimeout(function(){
			var ck=document.getElementById('cookie');
			if(ck) ck.classList.add('show');
		},2000);
	})();
	function acceptCookies(){
		var ck=document.getElementById('cookie');
		if(ck) ck.classList.remove('show');
		sessionStorage.setItem('cc','1');
	}
	function declineCookies(){
		var ck=document.getElementById('cookie');
		if(ck) ck.classList.remove('show');
		sessionStorage.setItem('cc','1');
	}
	/* ── Reading progress ────────────────────────────────────── */
	(function(){
		var pb=document.getElementById('reading-progress');
		if(!pb) return;
		function up(){
			var st=window.scrollY,sh=document.documentElement.scrollHeight,ch=window.innerHeight,mx=sh-ch,pc=mx>0?Math.min(100,Math.max(0,(st/mx)*100)):0;
			pb.style.width=pc+'%';
		}
		window.addEventListener('scroll',up,{passive:true});
		up();
	})();
	/* ── Code copy buttons ───────────────────────────────────── */
	document.addEventListener('click',function(e){
		var btn=e.target.closest('.copy-btn');
		if(!btn) return;
		var c=btn.parentElement.querySelector('code'),txt=c?c.textContent:'',orig=btn.textContent;
		btn.textContent='Copied!';
		setTimeout(function(){btn.textContent=orig},1600);
		try{navigator.clipboard.writeText(txt)}catch(ex){}
	});
	/* ── Live search dropdown ─────────────────────────────────── */
	(function(){
		var toggle=document.getElementById('search-toggle');
		var dropdown=document.getElementById('search-dropdown');
		var input=document.getElementById('search-input');
		var results=document.getElementById('search-results');
		var closeBtn=document.getElementById('search-close');
		if(!toggle||!dropdown||!input) return;

		var restUrl='<?php echo esc_url_raw( rest_url( 'wp/v2/posts' ) ); ?>?search=';
		var debounceTimer,activeIndex=-1,currentData=[];

		function open(){ dropdown.hidden=false; toggle.setAttribute('aria-expanded','true'); setTimeout(function(){ input.focus(); },100); }
		function close(){ dropdown.hidden=true; toggle.setAttribute('aria-expanded','false'); input.value=''; results.innerHTML=''; results.classList.remove('has-results'); currentData=[]; activeIndex=-1; }
		function search(q){
			results.innerHTML='<div class="search-loading">Searching...</div>';
			results.classList.add('has-results');
			fetch(restUrl+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(data){
				if(!Array.isArray(data)||!data.length){
					results.innerHTML='<div class="search-empty">No results found</div>';
					currentData=[]; return;
				}
				currentData=data; activeIndex=-1;
				var html='';
				for(var i=0;i<data.length;i++){
					var p=data[i];
					var title=p.title?p.title.rendered.replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&#038;/g,'&'):'';
					var cats=(p.class_list||[]).filter(function(c){ return c.indexOf('category-')===0; }).map(function(c){ return c.replace('category-',''); });
					var cat=cats[0]||'article';
					html+='<a href="'+p.link+'" class="search-result-item" data-index="'+i+'"><span class="badge badge-'+cat+'">'+cat+'</span><div><div class="sr-title">'+title+'</div><div class="sr-meta">'+new Date(p.date).toLocaleDateString('en-US',{month:'short',year:'numeric'})+'</div></div></a>';
				}
				results.innerHTML=html;
			}).catch(function(){
				results.innerHTML='<div class="search-empty">Search unavailable</div>';
			});
		}

		toggle.addEventListener('click',function(e){ e.stopPropagation(); if(dropdown.hidden) open(); else close(); });
		if(closeBtn) closeBtn.addEventListener('click',close);
		if(input){
			input.addEventListener('keydown',function(e){
				if(e.key==='Escape'){ e.preventDefault(); close(); }
				if(e.key==='ArrowDown'){ e.preventDefault(); activeIndex=Math.min(activeIndex+1,currentData.length-1); highlight(); }
				if(e.key==='ArrowUp'){ e.preventDefault(); activeIndex=Math.max(activeIndex-1,0); highlight(); }
				if(e.key==='Enter'&&activeIndex>=0&&currentData[activeIndex]){ e.preventDefault(); window.location=currentData[activeIndex].link; }
			});
			input.addEventListener('input',function(){
				clearTimeout(debounceTimer);
				var v=this.value.trim();
				if(v.length<2){ results.innerHTML=''; results.classList.remove('has-results'); return; }
				debounceTimer=setTimeout(function(){ search(v); },300);
			});
		}
		document.addEventListener('click',function(e){ if(!dropdown.hidden&&!dropdown.contains(e.target)&&e.target!==toggle&&!toggle.contains(e.target)) close(); });

		function highlight(){
			var items=results.querySelectorAll('.search-result-item');
			items.forEach(function(el,i){ el.classList.toggle('highlighted',i===activeIndex); });
			if(items[activeIndex]) items[activeIndex].scrollIntoView({block:'nearest'});
		}
	})();
	</script>
</body>
</html>
