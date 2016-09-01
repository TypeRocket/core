<?php
namespace TypeRocket\Http\Middleware;

use \TypeRocket\Core\Config;

/**
 * Class BaseVerifyNonce
 *
 * Validate WP Nonce
 *
 * @package TypeRocket\Http\Middleware
 */
class BaseVerify extends Middleware  {

    public $except = [];

    public function handle() {

        $path = $this->request->getPath();

        if( ! $this->excludePath($path) ) {
            if( $this->request->getMethod() != 'GET' ) {
                $token = check_ajax_referer( 'form_' . Config::getSeed(), '_tr_nonce_form', false );
                if ( ! $token ) {
                    $this->response->setError( 'csrf', true );
                    $this->response->flashNow( 'Invalid CSRF Token', 'error' );
                    $this->response->exitAny( 403 );
                }
            }
        }



        $this->next->handle();
    }

    public function excludePath($path)
    {
        $path = trim($path, '/');
        foreach ($this->except as $exclude ) {
            $exclude = explode( '/', trim($exclude, '/') );
            $path = explode('/', $path);
            $excluding = true;

            if( count($path) == count($exclude) ) {
                foreach ($path as $index => $part) {
                    if ($exclude[$index] != '*' && $exclude[$index] != $part) {
                        $excluding = false;
                        break;
                    }
                }
            }

            if($excluding) {
                return true;
            }

        };

        return false;
    }
}
