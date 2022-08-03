<?php
if(file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
}

if(file_exists(__DIR__.'/../../../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
}

date_default_timezone_set('UTC');

$_SERVER['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'] ?? null;
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$_SERVER['REQUEST_URI'] = '/php-unit-tests?phpunit=yes';
$_SERVER['HTTP_HOST'] = 'example.com';
$_SERVER['SERVER_NAME'] = 'trtestsys';

function getWordpressPath()
{
    $fileToCheck = 'wp-settings.php';
    $paths = [];

    foreach (range(1, 8) as $level) {
        $nested = str_repeat('/..', $level);
        array_push($paths, __DIR__ . "$nested/wordpress/$fileToCheck", __DIR__ . "$nested/$fileToCheck");
    }

    $filtered = array_filter($paths, function ($path) {
        return file_exists($path);
    });

    return str_replace("/$fileToCheck", '', array_shift($filtered));
}

$wp_load = getWordpressPath();

if( ! file_exists($wp_load) ) {
    echo 'PHP Unit: WordPress Not Connected at ' . $wp_load . PHP_EOL;
} else {
    define('BASE_WP', $wp_load);
    define('WP_USE_THEMES', true);

    // Disable email
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) { return true; }
    global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;


    function __test_false() { return false; }
    define('TYPEROCKET_AUTO_LOADER', '__test_false');
    $typerocket_init_root = realpath(__DIR__ . '/../../../../init.php');
    $typerocket_init_tests = realpath(__DIR__ . '/../typerocket/init.php');

    if( file_exists($typerocket_init_tests) ) {
        require $typerocket_init_tests;
    } elseif(file_exists( $typerocket_init_root)) {
        require $typerocket_init_root;
    }

    if(! defined( 'ABSPATH' )) {
        require BASE_WP . '/wp-load.php';
    }

    if(!function_exists('wp_signon')) {
        require BASE_WP . '/wp-admin/includes/user.php';
    }

    if(!defined('WP_INSTALLING')) {
        require BASE_WP . '/wp-admin/includes/upgrade.php';
    }

    // Create Mock Tables
    dbDelta('CREATE TABLE `posts_terms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `terms_id` int(11) DEFAULT NULL,
  `posts_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}
