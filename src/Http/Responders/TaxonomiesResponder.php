<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;
use \TypeRocket\Register\Registry;

class TaxonomiesResponder extends Responder
{

    public $taxonomy = null;

    /**
     * Respond to posts hook
     *
     * Detect the post types registered resource and run the Kernel
     * against that resource.
     *
     * @param $id
     */
    public function respond( $id )
    {
        $taxonomy   = $this->taxonomy;
        $resource   = Registry::getTaxonomyResource( $taxonomy );
        $prefix     = ucfirst( $resource );
        $controller = "\\" . TR_APP_NAMESPACE . "\\Controllers\\{$prefix}Controller";
        $model      = "\\" . TR_APP_NAMESPACE . "\\Models\\{$prefix}";

        if ( empty($prefix) || ! class_exists( $controller ) || ! class_exists( $model )) {
            $resource = 'categories';
        }

        if( ! is_array($id) ) {
            $id = [ 'id' => $id ];
        }

        $request  = new Request( $resource, 'PUT', $id, 'update' );
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);

    }

}