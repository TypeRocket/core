<?php
namespace TypeRocket\Controllers;

use \TypeRocket\Http\Request;
use \TypeRocket\Http\Response;

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

    /** @var \TypeRocket\Http\Response */
    protected $response = null;
    /** @var \TypeRocket\Http\Request */
    protected $request = null;

    protected $middleware = [];
    protected $modelClass = null;

    /*
     * Construct Controller
     */
    public function __construct( Request $request, Response $response )
    {
        $this->response = $response;
        $this->request  = $request;
        $this->init();
        $this->routing();
    }

    /**
     * Run when object is created
     *
     * @return $this
     */
    protected function init()
    {
        return $this;
    }

    /**
     * Run just before middleware is run
     *
     * @return $this
     */
    protected function routing()
    {
        return $this;
    }

    /**
     * @return array
     */
    public function getMiddleware() {
        return $this->middleware;
    }

    /**
     * @param $group
     *
     * @param array $settings
     *
     * @return $this
     */
    public function setMiddleware( $group, $settings = []) {
        $middleware['group'] = $group;

        if(array_key_exists('except', $settings)) {
            $middleware['except'] = $settings['except'];
        }

        if(array_key_exists('only', $settings)) {
            $middleware['only'] = $settings['only'];
        }

        $this->middleware[] = $middleware;

        return $this;
    }

}