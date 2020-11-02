<?php
namespace TypeRocket\Http;

class SSL
{
    /**
     * Fix SSL URL
     *
     * @param string $url
     * @param bool $secure_only
     *
     * @return mixed
     */
	public static function fixSSLUrl( $url, $secure_only = false )
    {
        $scheme = null;

	    if(function_exists('is_ssl')) {
            $scheme = $secure_only || \is_ssl() ? 'https' : 'http';
        } elseif ($secure_only) {
            $scheme = 'https';
        }

        if( $scheme == 'https' ) {
            $url = preg_replace('/^http:/i', 'https:', $url);
        }

		return $url;
    }
}