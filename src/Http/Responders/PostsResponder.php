<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Controllers\Controller;
use TypeRocket\Controllers\WPPostController;
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
     * @param $args
     * @throws \ReflectionException
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
        $resource   = $resource[0] ?? null;
        $response = new Response();

        if(! class_exists( $model )) {
            $model = new WPPost($type);
        }

        if (! class_exists( $controller ) ) {
            $controller = new WPPostController(new Request(), $response, $model);
        }

        if ( empty($resource) ) {
            $resource = 'post';
        }

        $request  = new Request( $resource, 'PUT', $args, 'update', $this->hook, $controller );

        if($controller instanceof Controller) {
            $controller->setRequest($request);
        }

        $response->blockFlash();

        $this->runKernel($request, $response);
    }

}