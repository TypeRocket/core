<?php
namespace TypeRocket\Controllers;

use TypeRocket\Exceptions\ModelException;
use TypeRocket\Models\WPOption;

class WPOptionController extends Controller
{

    protected $modelClass = WPOption::class;

    /**
     * Update option
     *
     * @param string $id
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
