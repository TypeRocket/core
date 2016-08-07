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
        $process = true;

        foreach ($this->except as $exclude ) {
            if( trim($exclude, '/') === trim($path, '/') ) {
                $process = false;
                break;
            }
        };

        if( $process ) {
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
}
