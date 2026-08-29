<?php
/**
 * Sitemap template — renders /sitemap.xml.
 *
 * Swapped in by gwill_sitemap_template() (inc/sitemap.php) when the
 * gwill_sitemap query var is set. Outputs the XML with the correct
 * content-type header so crawlers parse it as a sitemap, not HTML.
 *
 * @package GWill_Starter
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

// Sitemap protocol requires an XML content type, not text/html.
header( 'Content-Type: application/xml; charset=UTF-8' );

gwill_sitemap_echo();
