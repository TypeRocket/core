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
        $controller = "\\" . TR_APP_NAMESPACE . "\\Controllers\\{$prefix}Controller";
        $model      = "\\" . TR_APP_NAMESPACE . "\\Models\\{$prefix}";
        $resource = $resource[0];

        if ( empty($prefix) || ! class_exists( $controller ) || ! class_exists( $model )) {
            $resource = 'post';
        }

        $request  = new Request( $resource, 'PUT', $args, 'update', $this->hook );
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);

    }

}