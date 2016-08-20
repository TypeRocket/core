<?php
namespace TypeRocket\Controllers;

abstract class WPTermController extends Controller
{

    /** @var \TypeRocket\Models\WPTerm */
    protected $model = null;
    protected $type = null;

    /**
     * Dynamically load proper Model based on post type
     */
    protected function init()
    {
        $reflect    = new \ReflectionClass( $this );
        $type       = substr( $reflect->getShortName(), 0, - 10 );
        $this->type = $type;

        $this->model = new $this->modelClass;
    }

    /**
     * Update Taxonomy Term by ID
     *
     * @param null $id
     *
     * @return mixed|void
     */
    public function update( $id = null )
    {
        $errors = $this->model->findById( $id )->update( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext($this->type . ' not updated', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext($this->type . ' updated', 'success' );
            $this->response->setData('resourceId', $this->model->getID());
        }

    }

    /**
     * Create Taxonomy Term
     */
    public function create()
    {
        $errors = $this->model->create( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext($this->type . ' not created', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext($this->type . ' created', 'success' );
            $this->response->setStatus(201);
            $this->response->setData('resourceId', $this->model->getID());
        }

    }
}
