<?php
/**
 * Attachment Template
 *
 * Redirects attachment page URLs to the parent post, or to the homepage
 * if the attachment has no parent (e.g. a media-library standalone upload).
 *
 * WordPress creates an attachment page for every uploaded file. Without a
 * redirect, visitors land on a sparse page that renders just the image with
 * default styling — no navigation, no context, poor UX and weak SEO.
 *
 * To disable the redirect and build full attachment pages instead:
 *   1. Delete this file — WordPress falls through to index.php.
 *   2. Or replace the redirect block below with get_header() + your markup.
 *
 * @package GWill_Starter
 * @since   1.0.18
 */

defined( 'ABSPATH' ) || exit;

global $post;

$parent_id = ! empty( $post->post_parent ) ? (int) $post->post_parent : 0;
$redirect  = $parent_id
	? get_permalink( $parent_id )
	: home_url( '/' );

wp_safe_redirect( esc_url_raw( $redirect ), 301 );
exit;
