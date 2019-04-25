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
     * @param $args
     * @throws \ReflectionException
     */
    public function respond( $args )
    {
        $postId = $args['id'];

        if ( ! $id = wp_is_post_revision( $postId ) ) {
            $id = $postId;
        }

        $type = get_post_type($id);
        $registered = Registry::getPostTypeResource($type);
        $prefix = Str::camelize($registered[0]);
        $controller = $registered[3] ?? tr_app("Controllers\\{$prefix}Controller");
        $controller = apply_filters('tr_posts_responder_controller', $controller);
        $model = $registered[2] ?? tr_app("Models\\{$prefix}");
        $resource = $registered[0] ?? null;
        $response = (new Response())->blockFlash();
        $request = new Request('PUT', true);
        $middlewareGroup = [ $resource ,'post'];

        if(! class_exists( $model )) {
            $model = new WPPost($type);
        }

        if (! class_exists( $controller ) ) {
            $controller = new WPPostController($request, $response, $model);
        }

        if ( empty($resource) ) {
            $resource = 'post';
            $middlewareGroup = 'post';
        }

        $handler = (new Handler())
            ->setAction('update')
            ->setArgs($args)
            ->setHandler($controller)
            ->setHook($this->hook)
            ->setResource($resource)
            ->setMiddlewareGroup($middlewareGroup);

        $this->runKernel($request, $response, $handler);
    }

}