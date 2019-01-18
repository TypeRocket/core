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
	public function update( $id = null ) {
		$fields          = $this->request->getFields();
		$has_many_fields = null;

		foreach ( $fields as $name => $data ) {
			if ( $name == 'hasmany' ) {
				$has_many_fields = $data;
				unset( $fields['hasmany'] );
			}
		}

		$post = $this->model->findById( $id );

		try {
			$post->update( $fields );
			$this->response->flashNext( $this->type . ' updated', 'success' );
			$this->response->setData( 'resourceId', $id );
			if( $has_many_fields ) {
				$this->saveRelated( $has_many_fields, $id );
			}
		} catch ( ModelException $e ) {
			$this->response->flashNext( $e->getMessage(), 'error' );
			$this->response->setError( 'model', $e->getMessage() );
		}

	}

    /**
     * Create Post
     */
	public function create() {
		$fields          = $this->request->getFields();
		$has_many_fields = null;

		foreach ( $fields as $name => $data ) {
			if ( $name == 'hasmany' ) {
				$has_many_fields = $data;
				unset( $fields['hasmany'] );
			}
		}

		try {
			$this->model->create( $fields );
			$this->response->flashNext( $this->type . ' created', 'success' );
			$this->response->setStatus( 201 );
			$this->response->setData( 'resourceId', $this->model->getID() );
			if( $has_many_fields ) {
				$this->saveRelated( $has_many_fields, $this->model->getID() );
			}
		} catch ( ModelException $e ) {
			$this->response->flashNext( $e->getMessage(), 'error' );
			$this->response->setError( 'model', $e->getMessage() );
		}

	}

	/**
	 * Iterate through related records and create, update, or delete them
	 * 
	 * @param $models
	 * @param $foreign_key
	 */
	private function saveRelated( $models, $foreign_key ) {
		if ( $models ) {
			foreach ( $models as $model => $records ) {
				$model             = "\\App\\Models\\" . $model;
				$foreign_key_field = ( $records['foreignkey'] );
				unset( $records['foreignkey'] );
				$sort_field = null;
				if ( isset( $records['sortfield'] ) ) {
					$sort_field = $records['sortfield'];
					unset( $records['sortfield'] );
				}
				$i = 0;
				foreach ( $records as $id => $fields ) {
					$newmodel = new $model();
					if ( isset( $fields['delete'] ) && $fields['delete'] == 1 ) {
						$newmodel->delete( [ $id ] );
					} else {
						unset( $fields['delete'] );
						if ( strlen( (string) $id ) < 13 ) {
							$newmodel->setProperty( 'id', $id );
						}
						$fields[ $foreign_key_field ] = $foreign_key;
						if ( $sort_field ) {
							$fields[ $sort_field ] = $i;
						}
						$newmodel->save( $fields );
						$i ++;

					}
				}
			}
		}
	}
}
