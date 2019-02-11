<?php
namespace TypeRocket\Controllers;

use TypeRocket\Exceptions\ModelException;
use TypeRocket\Models\WPPost;

class WPPostController extends Controller
{
    protected $modelClass = WPPost::class;

    /** @var \TypeRocket\Models\WPPost */
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
     * Update Post by ID
     *
     * @param null $id
     *
     * @return mixed|void
     */
    public function update( $id = null )
    {
        $post = $this->model->findById( $id );

        try {
            $post->update( $this->request->getFields() );
            $this->response->flashNext($this->type . ' updated', 'success' );
            $this->response->setData('resourceId', $id );
        } catch ( ModelException $e ) {
            $this->response->flashNext($e->getMessage(), 'error' );
            $this->response->setError( 'model', $e->getMessage() );
        }

    }

    /**
     * Create Post
     */
    public function create()
    {
        try {
            $this->model->create( $this->request->getFields() );
            $this->response->flashNext($this->type . ' created', 'success' );
            $this->response->setStatus(201);
            $this->response->setData('resourceId', $this->model->getID());
        } catch ( ModelException $e ) {
            $this->response->flashNext($e->getMessage(), 'error' );
            $this->response->setError( 'model', $e->getMessage() );
        }

    }

	/**
	 * Delete Post
	 */
    public function destroy()
    {
    	//override this method to spoof delete_post action
    }

}
