<?php
/**
 * Template Part: Social Share — Pill Buttons
 *
 * Two modes — set via set_query_var( 'gwill_share_mode', 'footer' ) before calling:
 *
 *   top (default) — Compact pill row. Always visible. No heading.
 *                   Sits between .entry-meta and .entry-content in single.php.
 *
 *   footer        — Large pill row. Always visible. "Share this article" heading.
 *                   Top border divider. Sits after </article>, before author box.
 *
 * Platforms shown: X · Facebook · WhatsApp · LinkedIn · More
 * "More" triggers navigator.share() (system share sheet on mobile) with a
 * clipboard-copy fallback on desktop browsers that lack the Web Share API.
 *
 * SVG sources:
 *   X, Facebook, WhatsApp, LinkedIn — Simple Icons (simpleicons.org, MIT)
 *   More (share icon)               — Lucide Icons
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_singular() ) {
	return;
}

$mode      = get_query_var( 'gwill_share_mode', 'top' );
$is_footer = 'footer' === $mode;

$permalink = get_permalink();
$url       = rawurlencode( $permalink );
$title     = rawurlencode( html_entity_decode( get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

$platforms = [

	'x' => [
		'name'  => 'X',
		'label' => __( 'Share on X', 'gwill-starter' ),
		'url'   => 'https://x.com/intent/post?url=' . $url . '&text=' . $title,
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
	],

	'facebook' => [
		'name'  => 'Facebook',
		'label' => __( 'Share on Facebook', 'gwill-starter' ),
		'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
	],

	'whatsapp' => [
		'name'  => 'WhatsApp',
		'label' => __( 'Share on WhatsApp', 'gwill-starter' ),
		'url'   => 'https://wa.me/?text=' . $title . '%20' . $url,
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>',
	],

	'linkedin' => [
		'name'  => 'LinkedIn',
		'label' => __( 'Share on LinkedIn', 'gwill-starter' ),
		'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
	],

];
?>
<div class="gwill-share<?php echo $is_footer ? ' gwill-share--footer' : ' gwill-share--top'; ?>">

	<?php if ( $is_footer ) : ?>
		<span class="gwill-share__heading"><?php esc_html_e( 'Share this article', 'gwill-starter' ); ?></span>
	<?php endif; ?>

	<?php foreach ( $platforms as $key => $p ) : ?>
		<a
			href="<?php echo esc_url( $p['url'] ); ?>"
			class="gwill-share__pill gwill-share__pill--<?php echo esc_attr( $key ); ?>"
			target="_blank"
			rel="noopener noreferrer"
			aria-label="<?php echo esc_attr( $p['label'] ); ?>"
		>
			<?php echo $p['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="gwill-share__name"><?php echo esc_html( $p['name'] ); ?></span>
		</a>
	<?php endforeach; ?>

	<button
		type="button"
		class="gwill-share__pill gwill-share__pill--more"
		data-share-url="<?php echo esc_attr( $permalink ); ?>"
		data-share-title="<?php echo esc_attr( get_the_title() ); ?>"
	>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
			<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
			<line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
		</svg>
		<span class="gwill-share__name"><?php esc_html_e( 'More', 'gwill-starter' ); ?></span>
	</button>

</div>
