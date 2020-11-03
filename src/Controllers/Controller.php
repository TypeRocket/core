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
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * @param string|array $group Kernel named group or array of middleware
     * @param array $actions ['only' => ['show', 'index']]
     *
     * @return $this
     */
    public function addMiddleware( $group, $actions = [])
    {
        $this->middleware[] = [
            'group' => $group,
            'except' => $actions['except'] ?? null,
            'only' => $actions['only'] ?? null,
        ];

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
