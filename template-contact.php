<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * Standard contact page. Displays the page title and content from the editor
 * above the form. The form partial is controlled by a custom field (ACF or
 * post meta) — falls back to contact-simple.
 *
 * Custom field: gwill_form_type (string, optional)
 * Accepted values: simple | inquiry | routed | multistep | application | partnership
 *
 * Example (functions.php or project-specific file):
 *   update_post_meta( $page_id, 'gwill_form_type', 'inquiry' );
 *
 * @package GWill_Starter
 * @since   1.0.20
 */

defined( 'ABSPATH' ) || exit;

get_header();

$form_type = sanitize_key( get_post_meta( get_the_ID(), 'gwill_form_type', true ) ?: 'simple' );

$allowed_forms = [
	'simple',
	'inquiry',
	'routed',
	'multistep',
	'application',
	'partnership',
];

if ( ! in_array( $form_type, $allowed_forms, true ) ) {
	$form_type = 'simple';
}

while ( have_posts() ) : the_post();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'page-contact' ); ?>>

	<header class="entry-header">
		<h1 class="entry-title"><?php echo esc_html( get_the_title() ); ?></h1>
	</header>

	<?php if ( get_the_content() ) : ?>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	<?php endif; ?>

	<div class="contact-form-wrap">
		<?php gwill_part( 'forms/contact-' . $form_type ); ?>
	</div>

</article>

<?php endwhile;

get_footer();
