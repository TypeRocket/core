<?php
namespace TypeRocket\Http\Middleware;

/**
 * Class BaseVerifyNonce
 *
 * Validate WP Nonce
 *
 * @package TypeRocket\Http\Middleware
 */
class BaseVerify extends Middleware
{
    public $except = [];

    /**
     * Handle CSRF
     *
     * Checks wp_verify_nonce()
     */
    public function handle() {

        $path = $this->request->getPathWithoutRoot();

        if( ! $this->excludePath($path) ) {
            if( ! $this->request->isGet() ) {
                $action = sanitize_key($_REQUEST['_tr_nonce_form_action'] ?? '');
                $token = $this->request->checkNonce( $action );
                if ( ! $token ) {
                    $this->response->setError('csrf', true);
                    $this->response->flashNow('Request Failed. Invalid CSRF Token. Try reloading the page or reauthenticate.', 'error');
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
        $except = apply_filters('typerocket_verify_nonce_except', $this->except);
        foreach ($except as $exclude ) {
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

        }

        return false;
    }
}
