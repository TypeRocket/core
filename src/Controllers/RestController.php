<?php
namespace TypeRocket\Controllers;

use TypeRocket\Http\Request;
use TypeRocket\Http\Responders\HttpResponder;
use TypeRocket\Register\Registry;

class RestController extends Controller
{

    /**
     * @param $resource
     * @param Request $request
     * @param null|string|int $id
     * @throws \Exception
     */
    public function rest($resource, Request $request, $id = null)
    {
        $action = $id ? 'showRest' : 'indexRest';
        $id = (int) $id;

        // Using @ tells the controller to look in the App namespace
        $handler = "@{$resource}";
        $middleware = 'rest';

        if( $request->isPut() ) {
            $action = 'update';
        }
        elseif( $request->isDelete() ) {
            $action = 'destroy';
        }
        elseif( $request->isPost() ) {
            $action = 'create';
        }

        if($array = Registry::getPostTypeResource($resource)) {
            $middleware = 'post';
            $handler = $array['controller'] ?? $handler;
        }
        elseif($array = Registry::getTaxonomyResource($resource)) {
            $middleware = 'term';
            $handler = $array['controller'] ?? $handler;
        }
        elseif($array = Registry::getCustomResource($resource)) {
            $handler = $array['controller'] ?? $handler;
        }
        $responder = new HttpResponder;

        $responder->getHandler()
            ->setController([$handler, $action])
            ->setMiddlewareGroups([$resource, $middleware]);

        do_action('typerocket_rest', $responder, $id, $action, $resource);

        $responder->respond(['@first' => (int) $id]);
        $rest = $request->isMarkedAjax();

        \TypeRocket\Http\Response::getFromContainer()->finish($rest);
    }

}