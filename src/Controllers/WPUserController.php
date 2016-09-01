<?php
namespace TypeRocket\Controllers;

use TypeRocket\Exceptions\ModelException;
use TypeRocket\Models\WPUser;

class WPUserController extends Controller
{

    protected $modelClass = WPUser::class;

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

        try {
            $user->findById( $id )->update( $this->request->getFields() );
            $this->response->flashNext( 'User updated', 'success' );
            $this->response->setData('resourceId', $user->getID());
        } catch ( ModelException $e ) {
            $this->response->flashNext($e->getMessage(), 'error' );
            $this->response->setError( 'model', $e->getMessage() );
        }
    }

    /**
     * Create user
     */
    public function create()
    {
        /** @var \TypeRocket\Models\Model $user */
        $user   = new $this->modelClass;

        try {
            $user->create( $this->request->getFields() );
            $this->response->flashNext( 'User created', 'success' );
            $this->response->setStatus(201);
            $this->response->setData('resourceId', $user->getID());
        } catch ( ModelException $e ) {
            $this->response->flashNext($e->getMessage(), 'error' );
            $this->response->setError( 'model', $e->getMessage() );
        }
    }

}
