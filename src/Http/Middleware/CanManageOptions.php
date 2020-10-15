<?php
namespace TypeRocket\Http\Middleware;

use TypeRocket\Exceptions\HttpError;

/**
 * Class CanManageOptions
 *
 * Validate that the user can manage options and if the user can
 * not invalidate the response.
 *
 * @package TypeRocket\Http\Middleware
 */
class CanManageOptions extends Middleware
{
    public function handle() {

        if ( ! current_user_can( 'manage_options' ) && ! $this->isHook() ) {
            HttpError::abort(401);
        }

        $this->next->handle();
    }
}