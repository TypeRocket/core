<?php
namespace TypeRocket\Http\Middleware;

/**
 * Class IsUserOrCanEditUsers
 *
 * Validate that is user or can edit users and if the user is not
 * invalidate the response.
 *
 * @package TypeRocket\Http\Middleware
 */
class CanEditUsers extends Middleware
{
    public function handle() {

        if( ! $this->handler->getHook() && ! current_user_can( 'edit_users' ) ) {
            tr_abort(401);
        }

        $this->next->handle();
    }
}