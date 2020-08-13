<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Controllers\WPTermController;
use TypeRocket\Http\Handler;
use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;
use TypeRocket\Models\WPTerm;
use \TypeRocket\Register\Registry;
use TypeRocket\Utility\Str;

class TaxonomiesResponder extends Responder
{

    public $taxonomy = null;

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
        if('nav_menu' == $this->taxonomy) {
            return;
        }

        $registered = Registry::getTaxonomyResource($this->taxonomy);
        $prefix = Str::camelize( $registered[0] );

        $controller = $registered[3] ?? tr_app("Controllers\\{$prefix}Controller");
        $controller  = apply_filters('tr_taxonomies_responder_controller', $controller);

        $resource = $registered[0] ?? 'category';
        $response = tr_response()->blockFlash();
        $request = new Request( 'PUT', $this->hook );
        $middlewareGroup = [$resource, 'term', 'category', 'tag'];

        if (! class_exists( $controller ) ) {
            $model = $registered[2] ?? tr_app("Models\\{$prefix}");

            if(! class_exists( $model )) {
                $model = new WPTerm($this->taxonomy);
            }

            $controller = new WPTermController($request, $response, $model);
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