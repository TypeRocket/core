<?php
namespace TypeRocket\Services;

use TypeRocket\Core\Config;
use TypeRocket\Http\Redirect;
use TypeRocket\Services\Service;

class SecureAuthCookiesService extends Service
{
    public const ALIAS = 'cookie-auth-secure';

    public function __construct()
    {
        if(!(\PHP_VERSION_ID >= 70300)) {
            throw new \Error(__(static::class . ' TypeRocket service requires PHP 7.3 > ' . \PHP_VERSION_ID, 'typerocket-core'));
        }

        /**
         * Options: None, Lax or Strict
         *
         * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite
         */
        $same_site = Config::getFromContainer()->locate('cookies.auth.same_site', 'Lax');

        /**
         * By default, WordPress adds `X-Frame-Options: SAMEORIGIN`. However, these headers are often set
         * by the web server instead. Set this option as `false` to disable WordPress' x-frame-options.
         *
         * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
         */
        $x_frame_options = Config::getFromContainer()->locate('cookies.auth.x_frame_options', true);

        if(!$x_frame_options) {
            remove_action('admin_init', 'send_frame_options_header');
            remove_action('init', 'send_frame_options_header');
            remove_action('login_init', 'send_frame_options_header');
        }

        add_filter('send_auth_cookies', '__return_false');

        add_action('set_auth_cookie', function ($auth_cookie, $expire) use ($same_site) {
            setcookie( SECURE_AUTH_COOKIE, $auth_cookie, [
                'expires' => $expire,
                'path' => PLUGINS_COOKIE_PATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None'
            ]);
            setcookie( SECURE_AUTH_COOKIE, $auth_cookie, [
                'expires' => $expire,
                'path' => ADMIN_COOKIE_PATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => true,
                'httponly' => true,
                'samesite' => $same_site
            ]);
        }, 10, 2);

        add_action( 'set_logged_in_cookie', function($logged_in_cookie, $expire) use ($same_site) {
            setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, [
                'expires' => $expire,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => true,
                'httponly' => true,
                'samesite' => $same_site
            ]);
            if ( COOKIEPATH != SITECOOKIEPATH ) {
                setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, [
                    'expires' => $expire,
                    'path' => SITECOOKIEPATH,
                    'domain' => COOKIE_DOMAIN,
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => $same_site
                ]);
            }
        }, 10, 2);

        add_filter('typerocket_cookie_options', function ($options, $name) use ($same_site) {
            if(in_array($name, [
                Redirect::KEY_ADMIN,
                Redirect::KEY_DATA,
                Redirect::KEY_MESSAGE,
                Redirect::KEY_OLD,
                Redirect::KEY_ERROR,
            ])) {
                $options['samesite'] = $same_site;
            }

            return $options;
        }, 10, 2);
    }

    public function register() : Service
    {
        return $this;
    }
}