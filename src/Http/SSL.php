<?php

namespace TypeRocket\Http;

/**
 * Class SSL
 * @package Discover
 *
 * Fork of https://wordpress.org/plugins/ssl-insecure-content-fixer/developers/
 */
class SSL
{

    /**
     * Force Content SSL
     */
    public function forceContentSSL()
    {
        add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );

        if( ( !empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443 ) || !empty($_SERVER['HTTPS']) ) {
            add_filter('the_content', array($this, 'fixContent'), 9999); // also for fix_level 'content'
        }
    }

    /**
     * Fix Content
     *
     * @param $content
     * @return mixed
     */
    public function fixContent($content) {
        static $searches = array(
            '#<(?:img|iframe) .*?src=[\'"]\Khttp://[^\'"]+#i',		// fix image and iframe elements
            '#<link .*?href=[\'"]\Khttp://[^\'"]+#i',				// fix link elements
            '#<script [^>]*?src=[\'"]\Khttp://[^\'"]+#i',			// fix script elements
            '#url\([\'"]?\Khttp://[^)]+#i',							// inline CSS e.g. background images
        );
        $content = preg_replace_callback($searches, array(__CLASS__, 'fixContent_src_callback'), $content);

        // fix object embeds
        static $embed_searches = array(
            '#<object .*?</object>#is',								// fix object elements, including contained embed elements
            '#<embed .*?(?:/>|</embed>)#is',						// fix embed elements, not contained in object elements
        );
        $content = preg_replace_callback($embed_searches, array(__CLASS__, 'fixContent_embed_callback'), $content);

        return $content;
    }

    /**
     * callback for fixContent() regex replace for URLs
     * @param array $matches
     * @return string
     */
    public static function fixContent_src_callback($matches) {
        return 'https' . substr($matches[0], 4);
    }

    /**
     * callback for fixContent() regex replace for embeds
     * @param array $matches
     * @return string
     */
    public static function fixContent_embed_callback($matches) {
        // match from start of http: URL until either end quotes or query parameter separator, thus allowing for URLs in parameters
        $content = preg_replace_callback('#http://[^\'"&\?]+#i', array(__CLASS__, 'fixContent_src_callback'), $matches[0]);

        return $content;
    }

	/**
	 * Fix SSL URL
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function fixSSLUrl( $url ) {
		$scheme = is_ssl() ? 'https' : 'http';
		if( $scheme == 'https' ) {
			$url = preg_replace('/^http:/i', 'https:', $url);
		}

		return $url;
    }
}