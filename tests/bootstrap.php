<?php
require __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('UTC');

$wp_load = __DIR__.'/../wordpress';
define('BASE_WP', $wp_load);
if( ! file_exists($wp_load) ) {
    echo 'PHP Unit: WordPress Not Connected at ' . $wp_load . PHP_EOL;
} else {
    define('WP_USE_THEMES', false);

    // Disable email
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) { return true; }
    global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
    require BASE_WP . '/wp-load.php';
    require BASE_WP . '/wp-admin/includes/user.php';
}
