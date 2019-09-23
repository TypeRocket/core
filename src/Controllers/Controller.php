<?php
namespace TypeRocket\Controllers;

use \TypeRocket\Models\Model;
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

    /** @var Response */
    protected $response = null;
    /** @var Request */
    protected $request = null;

    protected $fields = [];
    protected $middleware = [];
    protected $model = null;
    protected $modelClass = Model::class;
    protected $validation = [];

    /**
     * Construct Controller
     *
     * @param Request $request
     * @param Response $response
     */
    public function __construct( Request $request, Response $response )
    {
        $this->response = $response;
        $this->request  = $request;
        $this->fields = $this->request->getFields();
        $this->model = is_object($this->modelClass) ? $this->modelClass : new $this->modelClass;
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
     * Set Model
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get Model
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get Response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get Request
     *
     * @return $this
     */
    public function getRequest()
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
     * @param string $group
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

    /**
     * invalid
     * if($this->invalid()) return tr_redirect()
     * @return bool whether validation is passed
     */
    protected function invalid()
    {
        $validator = tr_validator($this->validation, $this->fields);
        if($validator->getErrors()) {
            $validator->flashErrors($this->response);
            return true;
        }
        return false;
    }

}
