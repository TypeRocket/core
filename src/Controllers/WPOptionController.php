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
        try {
            if(!$this->onValidate('save', 'update', $model)) {
                throw new ModelException(__('Validation for update failed.', 'typrocket-domain'));
            }

            do_action('typerocket_controller_update', $this, $model, $user);

            if(!$model->can('update', $user)) {
                throw new ModelException(__('Policy does not give the current user access to update values.', 'typrocket-domain'));
            }

            $model->update( $this->getFields() );
            $this->onAction('save', 'update', $model);

            do_action('typerocket_controller_after_update', $this, $model, $user);

            $response->flashNext( 'Updated', 'success' );
        } catch ( ModelException $e ) {
            $response->allowFlash();
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
            if(!$this->onValidate('save', 'create', $model)) {
                throw new ModelException(__('Validation for create failed.', 'typrocket-domain'));
            }

            if(!$model->can('create', $user)) {
                throw new ModelException(__('Policy does not give the current user access to create.', 'typrocket-domain'));
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
