<?php
namespace TypeRocket\Controllers;

use TypeRocket\Controllers\Traits\LoadsModel;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Models\AuthUser;
use TypeRocket\Models\WPUser;

class WPUserController extends Controller
{
    use LoadsModel;

    /** @var string|WPUser  */
    protected $modelClass = WPUser::class;

    /**
     * Update user by ID
     *
     * @param null|int $id
     * @param Request $request
     * @param Response $response
     * @param AuthUser $authUser
     *
     * @return mixed|void
     * @throws \Exception
     */
    public function update($id, Request $request, Response $response, AuthUser $authUser )
    {
        /** @var WPUser $model */
        $model = new $this->modelClass;

        try {
            if(!$id) {
                throw new ModelException(__('ID not found.', 'typrocket-domain'));
            }

            $model->wpUser($id);

            if(!$this->onValidate('save', 'update', $model)) {
                throw new ModelException(__('Validation for update failed.', 'typrocket-domain'));
            }

            do_action('typerocket_controller_update', $this, $model, $authUser);

            if(!$model->can('update', $authUser)) {
                throw new ModelException(__('Policy does not give the current user access to update custom fields.', 'typrocket-domain'));
            }

            $model->update( $this->getFields() );
            $this->onAction('save', 'update', $model);

            do_action('typerocket_controller_after_update', $this, $model, $authUser);

            $response->flashNext( 'User updated', 'success' );
            $response->setData('resourceId', $model->getID());
        } catch ( ModelException $e ) {
            $response->allowFlash();
            $response->flashNext($e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
            $this->onAction('error', 'update', $e, $model);
        }

        return $this->returnJsonOrGoBack();
    }

    /**
     * Create User
     *
     * This method is not triggered by a WordPress core action.
     *
     * @param Request $request
     * @param Response $response
     * @param AuthUser $authUser
     *
     * @return \TypeRocket\Http\Redirect|Response
     * @throws \Exception
     */
    public function create(Request $request, Response $response, AuthUser $authUser)
    {
        /** @var WPUser $model */
        $model = new $this->modelClass;

        try {
            if(!$this->onValidate('save', 'create', $model)) {
                throw new ModelException(__('Validation for create failed.', 'typrocket-domain'));
            }

            if(!$model->can('create', $authUser)) {
                throw new ModelException(__('Policy does not give the current user access to create.', 'typrocket-domain'));
            }

            $new = $model->create( $this->getFields() );

            if($new) {
                $this->onAction('save', 'create', $new);
            }

            $response->flashNext( 'User created', 'success' );
            $response->setStatus(201);
            $response->setData('resourceId', $model->getID());
        } catch ( ModelException $e ) {
            $response->flashNext($e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
            $this->onAction('error', 'create', $e, $model);
        }

        return $this->returnJsonOrGoBack();
    }

    /**
     * Destroy
     *
     * @param null|int $id
     * @param Request $request
     * @param Response $response
     * @param AuthUser $authUser
     *
     * @return \TypeRocket\Http\Redirect|Response
     * @throws \Exception
     */
    public function destroy($id, Request $request, Response $response, AuthUser $authUser)
    {
        /** @var WPUser $model */
        $model = new $this->modelClass;

        try {
            if(!$id) {
                throw new ModelException(__('ID not found.', 'typrocket-domain'));
            }

            $model->wpUser( $id );

            if(!$model->can('destroy', $authUser)) {
                throw new ModelException(__('Policy does not give the current user access to destroy.', 'typrocket-domain'));
            }

            $model->delete();
            $this->onAction('destroy', $model);
            $response->flashNext( 'Term deleted', 'success' );
            $response->setStatus(200);
            $response->setData('resourceId', $model->getID() );
        } catch( ModelException $e ) {
            $response->flashNext( $e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
            $this->onAction('error', 'destroy', $e, $model);
        }

        return $this->returnJsonOrGoBack();
    }

}
