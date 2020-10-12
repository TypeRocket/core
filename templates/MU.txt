<?php
/*
Plugin Name: TypeRocket Root
Description: Root installation.
Author: TypeRocket
Version: 5
Author URI: http://typerocket.com
*/
namespace TypeRocket\Core;

if(!defined('TR_ROOT_INSTALL'))
    define('TR_ROOT_INSTALL', true);

if( defined('TR_PATH') ) {

    if( file_exists(TR_ALT_PATH . '/rooter.php') ) {
        include(TR_ALT_PATH . '/rooter.php');
    }

    (new System)->boot();
    (new Rooter)->boot();
}