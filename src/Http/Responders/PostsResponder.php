<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Controllers\WPPostController;
use TypeRocket\Http\Request;
use TypeRocket\Register\Registry;
use TypeRocket\Utility\Str;

class PostsResponder extends Responder
{

    /**
     * Respond to posts hook
     *
     * Detect the post types registered resource and run the Kernel
     * against that resource.
     *
     * @param array $args
     * @throws \Exception
     */
    public function respond( $args )
    {
        $postId = $args['@first'];

        if ( ! $id = wp_is_post_revision( $postId ) ) {
            $id = $postId;
        }

        $post_type = get_post_type($id);

        if($post_type == 'nav_menu_item') {
            return;
        }

        $registered = Registry::getPostTypeResource($post_type);
        $controller = null;

        if($singular = $registered['singular'] ?? null) {
            $prefix = Str::camelize($singular);
            $controller = $registered['controller'] ?? \TypeRocket\Utility\Helper::appNamespace("Controllers\\{$prefix}Controller");
        }

        $controller = apply_filters('typerocket_posts_responder_controller', $controller);

        $resource = $registered['singular'] ?? 'post';
        $response = \TypeRocket\Http\Response::getFromContainer()->blockFlash();
        $middlewareGroup = [ $resource ,'post'];
        $model = null;

        if (! class_exists( $controller ) ) {
            $controller = WPPostController::class;
        }

        $this->handler
            ->setArgs($args)
            ->setController([new $controller, 'update'])
            ->setMiddlewareGroups($middlewareGroup);

        $this->runKernel(new Request, $response, $this->handler);
    }

}