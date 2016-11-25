<?php
namespace TypeRocket\Controllers;

use TypeRocket\Exceptions\ModelException;
use TypeRocket\Models\WPOption;

class WPCommentController extends Controller
{
    protected $modelClass = WPOption::class;

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
        $comments = new $this->modelClass;

        try {
            $comments->findById( $id )->update( $this->request->getFields() );
            $this->response->flashNext( 'Comment updated', 'success' );
            $this->response->setData('resourceId', $comments->getID() );
        } catch( ModelException $e ) {
            $this->response->flashNext( $e->getMessage(), 'error' );
            $this->response->setError( 'model', $e->getMessage() );
        }

    }

    /**
     * Create Comment
     */
    public function create()
    {
        /** @var \TypeRocket\Models\Model $comments */
        $comments = new $this->modelClass;
        try {
            $comments->create( $this->request->getFields() );
            $this->response->flashNext( 'Comment created', 'success' );
            $this->response->setStatus(201);
            $this->response->setData('resourceId', $comments->getID() );
        } catch( ModelException $e ) {
            $this->response->flashNext( $e->getMessage(), 'error' );
            $this->response->setError( 'model', $e->getMessage() );
        }

    }
}
