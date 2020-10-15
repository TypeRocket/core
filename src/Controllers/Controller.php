<?php
namespace TypeRocket\Controllers;

use TypeRocket\Http\Redirect;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;

/**
 * Class Controller
 *
 * Be sure to validate by https://codex.wordpress.org/Roles_and_Capabilities
 * when building your own controllers. You can do this with Middleware and
 * XKernel.
 *
 * @package TypeRocket\Controllers
 */
class Controller
{
    /** @var array  */
    protected $middleware = [];

    /**
     * Maybe return response
     *
     * @param mixed $return
     *
     * @return Response|mixed
     */
    public function onAjaxReturnResponseOr($return)
    {
        if((new Request)->isAjax()) {
            return \TypeRocket\Http\Response::getFromContainer();
        }

        return $return;
    }

    /**
     * Return Response json or go back with redirect data
     *
     * @param bool $withFields
     *
     * @return Redirect|Response
     */
    public function returnJsonOrGoBack($withFields = true)
    {
        $response = \TypeRocket\Http\Response::getFromContainer();
        $request = new Request;

        if($request->isAjax() || $request->isGet()) {
            return $response;
        }

        $redirect = \TypeRocket\Http\Redirect::new()->back();
        $request = new Request;

        $response->withRedirectData();
        $response->withRedirectMessage();
        $response->withRedirectErrors();

        if($response->hasErrors() && $withFields) {
            $redirect->withOldFields($request->getFields());
        }

        return $redirect;
    }

    /**
     * @return array
     */
    public function getMiddleware() {
        return $this->middleware;
    }

    /**
     * @param string $group Kernel named group
     * @param array $actions ['show', 'index']
     *
     * @return $this
     */
    public function addMiddleware( $group, $actions = []) {
        $middleware['group'] = $group;

        if(array_key_exists('except', $actions)) {
            $middleware['except'] = $actions['except'];
        }

        if(array_key_exists('only', $actions)) {
            $middleware['only'] = $actions['only'];
        }

        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Get Fields
     *
     * @param null $field
     *
     * @return array|null
     */
    public function getFields($field = null)
    {
        return apply_filters('typerocket_controller_fields', Request::new()->getFields($field), $this);
    }

}
