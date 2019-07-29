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

    /**
     * Handle CSRF
     */
    public function handle() {

        $path = $this->request->getPath();

        if( ! $this->excludePath($path) ) {
            if( ! $this->request->isGet() ) {
                $token = check_ajax_referer( 'form_' . Config::locate('app.seed'), '_tr_nonce_form', false );
                if ( ! $token ) {
                    $this->response->setError( 'csrf', true );
                    $this->response->flashNow( 'Invalid CSRF Token', 'error' );
                    $this->response->exitAny( 403 );
                }
            }
        }



        $this->next->handle();
    }

    /**
     * Check for excluded paths
     *
     * @param string $path
     *
     * @return bool
     */
    public function excludePath($path)
    {
        $path = trim($path, '/');
        foreach ($this->except as $exclude ) {
            $exclude = explode( '/', trim($exclude, '/') );
            $explodedPath = explode('/', $path);
            $excluding = true;

            if( count($explodedPath) == count($exclude) ) {
                foreach ($explodedPath as $index => $part) {
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
