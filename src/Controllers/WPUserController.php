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
                throw new ModelException('ID not found.');
            }

            $model->wpUser($id);

            do_action('typerocket_controller_update', $this, $model, $authUser);

            if(!$model->can('update', $authUser)) {
                throw new ModelException('Policy does not give the current user access to write.');
            }

            $model->update( $this->getFields() );
            $response->flashNext( 'User updated', 'success' );
            $response->setData('resourceId', $model->getID());
        } catch ( ModelException $e ) {
            $response->flashNext($e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
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
            if(!$model->can('create', $authUser)) {
                throw new ModelException('Policy does not give the current user access to write.');
            }

            $model->create( $this->getFields() );
            $response->flashNext( 'User created', 'success' );
            $response->setStatus(201);
            $response->setData('resourceId', $model->getID());
        } catch ( ModelException $e ) {
            $response->flashNext($e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
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
                throw new ModelException('ID not found.');
            }

            $model->wpUser( $id );

            if(!$model->can('destroy', $authUser)) {
                throw new ModelException('Policy does not give the current user access to write.');
            }

            $model->delete();
            $response->flashNext( 'Term deleted', 'success' );
            $response->setStatus(200);
            $response->setData('resourceId', $model->getID() );
        } catch( ModelException $e ) {
            $response->flashNext( $e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
        }

        return $this->returnJsonOrGoBack();
    }

}
