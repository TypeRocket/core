<?php
namespace TypeRocket\Controllers;

use TypeRocket\Exceptions\ModelException;

abstract class WPOptionController extends Controller
{

    /**
     * Update option
     *
     * @param $id
     *
     * @return mixed|void
     */
    public function update( $id )
    {
        /** @var \TypeRocket\Models\Model $options */
        $options = new $this->modelClass;
        try {
            $options->update( $this->request->getFields() );
            $this->response->flashNext( 'Updated', 'success' );
        } catch ( ModelException $e ) {
            $this->response->flashNext( $e->getMessage(), 'error' );
            $this->response->setError( 'model', $e->getMessage() );
        }

    }

    /**
     * Create option
     */
    public function create()
    {
        /** @var \TypeRocket\Models\Model $options */
        $options = new $this->modelClass;

        try {
            $options->create( $this->request->getFields() );
            $this->response->flashNext( 'Options created', 'success' );
        } catch ( ModelException $e ) {
            $this->response->flashNext( $e->getMessage(), 'error' );
            $this->response->setError( 'model', $e->getMessage() );
        }

    }

}
