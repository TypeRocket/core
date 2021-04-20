<?php
namespace TypeRocket\Controllers;

use TypeRocket\Controllers\Traits\LoadsModel;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Models\AuthUser;
use TypeRocket\Models\WPOption;

class WPOptionController extends Controller
{
    use LoadsModel;

    protected $modelClass = WPOption::class;

    /**
     * Update option
     *
     * @param Request $request
     * @param Response $response
     * @param AuthUser $user
     *
     * @return \TypeRocket\Http\Redirect|Response
     * @throws \Exception
     */
    public function update(Request $request, Response $response, AuthUser $user)
    {
        /** @var WPOption $model */
        $model = new $this->modelClass;

        do_action('typerocket_controller_update', $this, $model, $user);

        try {
            if(!$model->can('update', $user)) {
                throw new ModelException('Policy does not give the current user access to write.');
            }

            $model->update( $this->getFields() );
            $this->onAction('save', 'update', $model);
            $response->flashNext( 'Updated', 'success' );
        } catch ( ModelException $e ) {
            $response->flashNext( $e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
            $this->onAction('error', 'update', $e, $model);
        }

        return $this->returnJsonOrGoBack();
    }

    /**
     * Create Option
     *
     * @param Request $request
     * @param Response $response
     * @param AuthUser $user
     *
     * @return \TypeRocket\Http\Redirect|Response
     * @throws \Exception
     */
    public function create(Request $request, Response $response, AuthUser $user)
    {
        /** @var WPOption $model */
        $model = new $this->modelClass;

        try {
            if(!$model->can('create', $user)) {
                throw new ModelException('Policy does not give the current user access to write.');
            }

            $model->create( $this->getFields() );
            $this->onAction('save', 'create', $model);
            $response->flashNext( 'Options created', 'success' );
        } catch ( ModelException $e ) {
            $response->flashNext( $e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
            $this->onAction('error', 'create', $e, $model);
        }

        return $this->returnJsonOrGoBack();
    }

}
