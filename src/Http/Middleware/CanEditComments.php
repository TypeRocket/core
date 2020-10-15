<?php
namespace TypeRocket\Http\Middleware;

use TypeRocket\Exceptions\HttpError;

/**
 * Class OwnsCommentOrCanEditComments
 *
 * Validate that user owns comment or can edit comments
 * and if the user is not invalidate the response.
 *
 * @package TypeRocket\Http\Middleware
 */
class CanEditComments extends Middleware
{
    public function handle() {

        if( ! $this->isHook() && ! current_user_can( 'edit_posts' ) ) {
            HttpError::abort(401);
        }

        $this->next->handle();
    }
}