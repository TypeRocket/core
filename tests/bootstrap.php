<?php
if(file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
}

if(file_exists(__DIR__.'/../../../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
}

date_default_timezone_set('UTC');
define( 'DISABLE_WP_CRON', true );

define( 'WP_MEMORY_LIMIT', -1 );
define( 'WP_MAX_MEMORY_LIMIT', -1 );
$PHP_SELF            = '/index.php';
$GLOBALS['PHP_SELF'] = '/index.php';
$GLOBALS['_wp_die_disabled'] = false;

$_SERVER['PHP_SELF'] = '/index.php';
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
    function typerocketTestsDatabaseSetup($clear_table, $sql = null)
    {
        /** @var \wpdb */
        global $wpdb;

        if($sql) {
            dbDelta($sql);
        }

        $wpdb->query("DELETE FROM {$clear_table}");
        $wpdb->query("ALTER TABLE {$clear_table} AUTO_INCREMENT = 1");
    }

    // terms
    typerocketTestsDatabaseSetup('posts_terms', 'CREATE TABLE  if not exists `posts_terms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `terms_id` int(11) DEFAULT NULL,
  `posts_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');


    // Products
    typerocketTestsDatabaseSetup('products_variants', 'CREATE TABLE `products_variants` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `variant_sku` varchar(11) DEFAULT NULL,
  `product_number` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    typerocketTestsDatabaseSetup('products', 'CREATE TABLE if not exists `products` (
  `product_number` int(11) UNIQUE,
  `title` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`product_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    typerocketTestsDatabaseSetup('variants', 'CREATE TABLE if not exists `variants` (
  `sku` varchar(11) UNIQUE,
  `barcode` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    // People
    typerocketTestsDatabaseSetup('peoples_roles', 'CREATE TABLE if not exists `peoples_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `people_number` int(11) DEFAULT NULL,
  `role_number` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    typerocketTestsDatabaseSetup('roles', 'CREATE TABLE  if not exists `roles` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `r_number` int(11)  DEFAULT NULL,
    `name` varchar(11) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    typerocketTestsDatabaseSetup('peoples', 'CREATE TABLE  if not exists `peoples` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `p_number` int(11)  DEFAULT NULL,
    `name` varchar(11) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    typerocketTestsDatabaseSetup('orders', 'CREATE TABLE  if not exists `orders` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `per_number` int(11)  DEFAULT NULL,
    `name` varchar(11) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    typerocketTestsDatabaseSetup('items', 'CREATE TABLE  if not exists `items` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `order_id` int(11)  DEFAULT NULL,
    `name` varchar(11) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

}
