<?php
namespace TypeRocket\Http\Middleware;

use TypeRocket\Exceptions\HttpError;

/**
 * Class OwnsPostOrCanEditPosts
 *
 * Validate that user can owns post or can edit posts and
 * if the user is not invalidate the response.
 *
 * @package TypeRocket\Http\Middleware
 */
class CanEditPosts extends Middleware
{
    public function handle() {

        if( ! $this->isHook() && ! current_user_can( 'edit_posts' ) ) {
            HttpError::abort(401);
        }

        $this->next->handle();
    }
}