<?php
namespace TypeRocket\Http;

class SSL
{
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