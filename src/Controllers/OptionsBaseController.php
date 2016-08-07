<?php
namespace TypeRocket\Controllers;

abstract class OptionsBaseController extends Controller
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
        $errors  = $options->create( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext( 'Options not updated', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext( 'Updated', 'success' );
        }

    }

    /**
     * Create option
     */
    public function create()
    {
        /** @var \TypeRocket\Models\Model $options */
        $options = new $this->modelClass;
        $errors  = $options->create( $this->request->getFields() )->getErrors();

        if ( ! empty( $errors ) ) {
            $this->response->flashNext( 'Options not created', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext( 'Options created', 'success' );
        }

    }

}
