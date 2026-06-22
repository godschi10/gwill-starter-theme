<?php
/**
 * Template Name: Contact Demo (Dev Only)
 * Template Post Type: page
 *
 * Renders all 10 contact form patterns on one page for development testing.
 * Access is restricted at the code level to logged-in users with edit_posts
 * capability — no WP admin visibility setting required.
 *
 * To use: Create a new page, assign this template.
 * URL: /contact-demo/ (or whatever slug you set)
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

// Hard gate — logged-out users and subscribers are turned away regardless
// of the page's WordPress visibility setting.
if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die(
		esc_html__( 'You do not have permission to view this page.', 'gwill-starter' ),
		esc_html__( 'Access Restricted', 'gwill-starter' ),
		[ 'response' => 403 ]
	);
}

get_header();

// Run the WordPress loop so the_post() sets up the global $post context.
// Required: template parts that call is_singular(), get_the_title(), etc.
// need the global post to be fully initialised — without the_post() they
// receive post data from the query setup but not the full loop context.
while ( have_posts() ) :
	the_post();
?>

<div class="content-wrap contact-demo">

	<header class="contact-demo__header">
		<h1>Contact Form Demo</h1>
		<p class="contact-demo__notice">
			<strong>Dev only.</strong> This page demonstrates all 10 contact form patterns
			from the GWill Starter contact form system. Set to Private before deploying.
		</p>
	</header>

	<?php
	/*
	 * Pattern index.
	 * IDs must match the filename suffix of the template part:
	 *   'forms/contact-{id}' → template-parts/forms/contact-{id}.php
	 *
	 * Pattern 7 (exit-intent overlay) is excluded from the loop because it
	 * renders as a full-viewport fixed overlay with a separate trigger button,
	 * not an inline form section. It is included once at the end of the template.
	 */
	$demos = [
		[
			'id'          => 'simple',
			'label'       => '1 — Simple Contact',
			'description' => 'Name / Email / Message. General-purpose fallback.',
		],
		[
			'id'          => 'inquiry',
			'label'       => '2 — Service Inquiry',
			'description' => 'Screens by service type, timeline, and budget. For freelancers.',
		],
		[
			'id'          => 'routed',
			'label'       => '3 — Type Router',
			'description' => 'Routes to different email addresses by inquiry type. Configure via gwill_form_routing_map filter.',
		],
		[
			'id'          => 'multistep',
			'label'       => '4 — Multi-step Quote',
			'description' => '4 steps: service → budget → contact → description. sessionStorage persists values on Back.',
		],
		[
			'id'          => 'inline',
			'label'       => '5 — Inline Post Form',
			'description' => 'Compact 2-field embed for post content. Converts readers at high-intent moments.',
		],
		[
			'id'          => 'sidebar',
			'label'       => '6 — Sidebar Form',
			'description' => 'Compact sticky sidebar form. Make sticky with CSS on the sidebar container.',
		],
		[
			'id'          => 'application',
			'label'       => '8 — Application Form',
			'description' => 'Work-with-me framing. Revenue + outcome questions qualify applicants.',
		],
		[
			'id'          => 'partnership',
			'label'       => '9 — Partnership / Brand Deal',
			'description' => 'Structured intake for sponsorships and collaborations.',
		],
		[
			'id'          => 'post-feedback',
			'label'       => '10 — Post Feedback (Yes/No)',
			'description' => 'Micro-interaction. Yes submits instantly. No reveals a textarea.',
		],
	];

	foreach ( $demos as $demo ) :
	?>
	<section class="contact-demo__section" id="demo-<?php echo esc_attr( $demo['id'] ); ?>">
		<div class="contact-demo__meta">
			<h2 class="contact-demo__pattern-title"><?php echo esc_html( $demo['label'] ); ?></h2>
			<p class="contact-demo__pattern-desc"><?php echo esc_html( $demo['description'] ); ?></p>
		</div>
		<div class="contact-demo__form-wrap">
			<?php gwill_part( 'forms/contact-' . $demo['id'] ); ?>
		</div>
	</section>
	<?php endforeach; ?>

	<?php /* Pattern 7 is an overlay — trigger button only, overlay rendered after footer */ ?>
	<section class="contact-demo__section" id="demo-exit_intent">
		<div class="contact-demo__meta">
			<h2 class="contact-demo__pattern-title">7 — Exit-Intent Overlay</h2>
			<p class="contact-demo__pattern-desc">Triggered by cursor leaving viewport or 75% scroll depth. Click button below to trigger manually.</p>
		</div>
		<div class="contact-demo__form-wrap">
			<button
				type="button"
				onclick="(function(){var o=document.querySelector('.gwill-exit-intent');if(o){o.setAttribute('aria-hidden','false');o.removeAttribute('hidden');}})();"
				class="gwill-form__submit"
				style="cursor:pointer;"
			>
				Trigger Exit-Intent Overlay
			</button>
		</div>
	</section>

</div>

<?php
endwhile;

// Pattern 7 overlay — rendered outside .content-wrap so it covers the full
// viewport when triggered. Must be before get_footer() so wp_footer() runs
// after this element is in the DOM, allowing form-exit-intent.js to find it.
gwill_part( 'forms/contact-exit-intent' );

get_footer();
