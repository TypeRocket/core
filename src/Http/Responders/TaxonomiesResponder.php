<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Controllers\Controller;
use TypeRocket\Controllers\WPTermController;
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
     * @param $args
     * @throws \ReflectionException
     */
    public function respond( $args )
    {
        $taxonomy   = $this->taxonomy;
        $resource   = Registry::getTaxonomyResource( $taxonomy );
        $prefix     = Str::camelize( $resource[0] );
        $controller = $resource[3] ?? tr_app("Controllers\\{$prefix}Controller");
        $controller  = apply_filters('tr_taxonomies_responder_controller', $controller);
        $model      = $resource[2] ?? tr_app("Models\\{$prefix}");
        $resource = $resource[0] ?? null;
        $response = new Response();

        if(! class_exists( $model )) {
           $model = new WPTerm($taxonomy);
        }

        if (! class_exists( $controller ) ) {
            $controller = new WPTermController(new Request(), $response, $model);
        }

        if(empty($resource)) {
            $resource = 'category';
        }

        $request = new Request( $resource, 'PUT', $args, 'update', $this->hook, $controller );

        if($controller instanceof Controller) {
            $controller->setRequest($request);
        }

        $response->blockFlash();

        $this->runKernel($request, $response);
    }

}