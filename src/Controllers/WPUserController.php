<?php
namespace TypeRocket\Controllers;

abstract class WPUserController extends Controller
{

    /**
     * Update user by ID
     *
     * @param null $id
     *
     * @return mixed|void
     */
    public function update( $id = null )
    {
        /** @var \TypeRocket\Models\Model $user */
        $user   = new $this->modelClass;
        $errors = $user->findById( $id )->update( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext( 'User not updated', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext( 'User updated', 'success' );
            $this->response->setData('resourceId', $user->getID());
        }
    }

    /**
     * Create user
     */
    public function create()
    {
        /** @var \TypeRocket\Models\Model $user */
        $user   = new $this->modelClass;
        $errors = $user->create( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext( 'User not created', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext( 'User created', 'success' );
            $this->response->setStatus(201);
            $this->response->setData('resourceId', $user->getID());
        }
    }

}
