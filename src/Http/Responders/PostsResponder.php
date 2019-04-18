<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;
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
     * @param $args
     */
    public function respond( $args )
    {
        $postId = $args['id'];

        if ( ! $id = wp_is_post_revision( $postId ) ) {
            $id = $postId;
        }

        $type       = get_post_type( $id );
        $resource   = Registry::getPostTypeResource( $type );
        $prefix     = Str::camelize( $resource[0] );
        $controller = $resource[3] ?? tr_app("Controllers\\{$prefix}Controller");
        $controller  = apply_filters('tr_posts_responder_controller', $controller);
        $model      = $resource[2] ?? tr_app("Models\\{$prefix}");
        $resource   = $resource[0];

        if ( empty($prefix) || ! class_exists( $controller ) || ! class_exists( $model )) {
            $resource = 'post';
            $controller = tr_app("Controllers\\PostController");
        }

        $request  = new Request( $resource, 'PUT', $args, 'update', $this->hook, $controller );
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);
    }

}