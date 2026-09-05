<?php
/**
 * Template Part: Push notification bell
 *
 * Renders the smart notification bell (inc/webpush.php +
 * assets/js/push.js). Echoed anywhere a build wants the subscribe CTA  - 
 * the footer calls it by default. Safe to render more than once per page:
 * push.js binds ALL instances (docs/LAWS.md L5).
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

defined( 'ABSPATH' ) || exit;

gwill_push_bell();
