<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Controllers\Controller;
use TypeRocket\Controllers\WPPostController;
use TypeRocket\Http\Handler;
use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;
use TypeRocket\Models\WPPost;
use \TypeRocket\Register\Registry;
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
     */
    public function respond( $args )
    {
        $postId = $args['id'];

        if ( ! $id = wp_is_post_revision( $postId ) ) {
            $id = $postId;
        }

        $post_type = get_post_type($id);

        if($post_type == 'nav_menu_item') {
            return;
        }

        $post_type = get_post_type($id);
        $registered = Registry::getPostTypeResource($post_type);
        $prefix = Str::camelize($registered[0]);

        $controller = $registered[3] ?? tr_app("Controllers\\{$prefix}Controller");
        $controller = apply_filters('tr_posts_responder_controller', $controller);

        $resource = $registered[0] ?? 'post';
        $response = tr_response()->blockFlash();
        $request = new Request('PUT', $this->hook);
        $middlewareGroup = [ $resource ,'post'];

        if (! class_exists( $controller ) ) {
            $model = $registered[2] ?? tr_app("Models\\{$prefix}");

            if(! class_exists( $model )) {
                $model = new WPPost($post_type);
            }

            $controller = new WPPostController($request, $response, $model);
        }

        $handler = (new Handler())
            ->setAction('update')
            ->setArgs($args)
            ->setHandler($controller)
            ->setHook($this->hook)
            ->setResource($resource)
            ->setMiddlewareGroups($middlewareGroup);

        $this->runKernel($request, $response, $handler);
    }

}