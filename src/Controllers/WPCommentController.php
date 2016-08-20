<?php
namespace TypeRocket\Controllers;

abstract class WPCommentController extends Controller
{

    /**
     * Update comment based on ID
     *
     * @param null $id
     *
     * @return mixed|void
     */
    public function update( $id = null )
    {
        /** @var \TypeRocket\Models\Model $comments */
        $comments = new $this->$modelClass;
        $errors   = $comments->findById( $id )->update( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext( 'Comment not updated', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext( 'Comment updated', 'success' );
            $this->response->setData('resourceId', $comments->getID() );
        }

    }

    /**
     * Create Comment
     */
    public function create()
    {
        /** @var \TypeRocket\Models\Model $comments */
        $comments = new $this->modelClass;
        $errors   = $comments->create( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext( 'Comment not created', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext( 'Comment created', 'success' );
            $this->response->setStatus(201);
            $this->response->setData('resourceId', $comments->getID() );
        }

    }
}
