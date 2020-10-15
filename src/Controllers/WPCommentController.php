<?php
namespace TypeRocket\Controllers;

use TypeRocket\Controllers\Traits\LoadsModel;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Http\Redirect;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Models\AuthUser;
use TypeRocket\Models\WPComment;

class WPCommentController extends Controller
{
    use LoadsModel;

    protected $modelClass = WPComment::class;

    /**
     * Update comment based on ID
     *
     * @param int $id
     * @param Request $request
     * @param Response $response
     *
     * @param AuthUser $user
     * @return Redirect|Response
     * @throws \Exception
     */
    public function update($id, Request $request, Response $response, AuthUser $user)
    {
        /** @var WPComment $model */
        $model = new $this->modelClass;

        try {
            if(!$id) {
                throw new ModelException('ID not found.');
            }

            $model = $model->findById( $id );

            do_action('typerocket_controller_update', $this, $model, $user);

            if(!$model->can('update', $user)) {
                throw new ModelException('Policy does not give the current user access to write.');
            }

            $model->findById( $id )->update( $this->getFields() );
            $response->flashNext( 'Comment updated', 'success' );
            $response->setData('resourceId', $model->getID() );
        } catch( ModelException $e ) {
            $response->flashNext( $e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
        }

        return $this->returnJsonOrGoBack();
    }

    /**
     * Create Comment
     *
     * This method is not triggered by a WordPress core action.
     *
     * @param Request $request
     * @param Response $response
     * @param AuthUser $user
     *
     * @return Redirect|Response
     * @throws \Exception
     */
    public function create(Request $request, Response $response, AuthUser $user)
    {
        /** @var WPComment $model */
        $model = new $this->modelClass;

        try {
            if(!$model->can('create', $user)) {
                throw new ModelException('Policy does not give the current user access to write.');
            }

            $model->create( $this->getFields() );
            $response->flashNext( 'Comment created', 'success' );
            $response->setStatus(201);
            $response->setData('resourceId', $model->getID() );
        } catch( ModelException $e ) {
            $response->flashNext( $e->getMessage(), 'error' );
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
     * @param AuthUser $user
     *
     * @return Redirect|Response
     * @throws \Exception
     */
    public function destroy($id, Request $request, Response $response, AuthUser $user)
    {
        /** @var WPComment $model */
        $model = new $this->modelClass;

        try {
            if(!$id) {
                throw new ModelException('ID not found.');
            }

            $model = $model->findById( $id );

            if(!$model->can('destroy', $user)) {
                throw new ModelException('Policy does not give the current user access to write.');
            }

            $model->delete();
            $response->flashNext( 'Comment deleted', 'success' );
            $response->setStatus(200);
            $response->setData('resourceId', $model->getID() );
        } catch( ModelException $e ) {
            $response->flashNext( $e->getMessage(), 'error' );
            $response->setError( 'model', $e->getMessage() );
        }

        return $this->returnJsonOrGoBack();
    }
}
