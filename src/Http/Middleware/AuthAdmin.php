<?php
namespace TypeRocket\Http\Middleware;

use TypeRocket\Exceptions\HttpError;

/**
 * Class AuthAdmin
 *
 * Authenticate user as administrator and if the user is not
 * invalidate the response.
 *
 * @package TypeRocket\Http\Middleware
 */
class AuthAdmin extends Middleware
{
    public function handle() {

        if ( ! current_user_can('administrator') ) {
            HttpError::abort(401);
        }

        $this->next->handle();
    }
}