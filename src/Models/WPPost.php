<?php
namespace TypeRocket\Models;

use TypeRocket\Core\Config;
use TypeRocket\Database\Query;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Models\Meta\WPPostMeta;
use TypeRocket\Models\Traits\MetaData;
use WP_Post;

class WPPost extends Model
{
    use MetaData;

    protected $idColumn = 'ID';
    protected $resource = 'posts';
    protected $postType = 'post';
    protected $searchColumn = 'post_title';
    protected $wp_post = null;

    protected $builtin = [
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
        'comment_count',
        'post_password',
        'id'
    ];

    protected $guard = [
        'post_type',
        'id'
    ];

    public function __construct($postType = null)
    {
        if($postType) { $this->postType = $postType; }
        parent::__construct();
    }

    /**
     * Get Meta Model Class
     *
     * @return string
     */
    protected function getMetaModelClass()
    {
        return WPPostMeta::class;
    }

    /**
     * Get ID Columns
     *
     * @return array
     */
    protected function getMetaIdColumns()
    {
        return [
            'local' => 'ID',
            'foreign' => 'post_id',
        ];
    }

    /**
     * After Properties Are Cast
     *
     * Create an Instance of WP_Post
     *
     * @return Model
     */
    protected function afterCastProperties()
    {
        if(!$this->wp_post) {
            $_post = sanitize_post( (object) $this->properties, 'raw' );
            wp_cache_add( $_post->ID, $_post, 'posts' );
            $this->wp_post = new WP_Post($_post);
        }

        return parent::afterCastProperties();
    }

    /**
     * Get WP_Post Instance
     *
     * @return WP_Post
     */
    public function WP_Post() {
        return $this->wp_post;
    }

    /**
     * Posts Meta Fields
     *
     * @param bool $withoutPrivate
     *
     * @return null|\TypeRocket\Models\Model
     */
    public function meta( $withoutPrivate = false )
    {
        $meta = $this->hasMany( WPPostMeta::class, 'post_id', function($rel) use ($withoutPrivate) {
            if( $withoutPrivate ) {
                $rel->notPrivate();
            }
        } );

        return $meta;
    }

    /**
     * Posts Meta Fields Without Private
     *
     * @return null|\TypeRocket\Models\Model
     */
    public function metaWithoutPrivate()
    {
        return $this->meta( true );
    }

    /**
     * Belongs To Taxonomy
     *
     * @param string $modelClass
     * @param string $taxonomy_id the registered taxonomy id: category, post_tag, etc.
     * @param null|callable $scope
     *
     * @return Model|null
     */
    public function belongsToTaxonomy($modelClass, $taxonomy_id, $scope = null)
    {
        global $wpdb;

        return $this->belongsToMany($modelClass, $wpdb->term_relationships, 'object_id', 'term_taxonomy_id', function($rel) use ($scope, $taxonomy_id) {
            global $wpdb;
            $rel->where($wpdb->term_taxonomy .'.taxonomy', $taxonomy_id);

            if(is_callable($scope)) {
                $scope($rel);
            }
        });
    }

    /**
     * Author
     *
     * @return $this|null
     */
    public function author() {
        $user = Config::locate('app.class.user', WPUser::class);
        return $this->belongsTo( $user, 'post_author' );
    }

    /**
     * Published
     *
     * @return $this
     */
    public function published()
    {
        return $this->where('post_status', 'publish');
    }

    /**
     * Status
     *
     * @param string $type
     *
     * @return $this
     */
    public function status($type)
    {
        return $this->where('post_status', $type);
    }

    /**
     * Get Post Type
     *
     * @return string
     */
    public function getPostType()
    {
        return $this->postType;
    }

    /**
     * Return table name in constructor
     *
     * @param \wpdb $wpdb
     *
     * @return string
     */
    public function initTable( $wpdb )
    {
        return $wpdb->prefix . 'posts';
    }

    /**
     * Init Post Type
     *
     * @param Query $query
     *
     * @return Query
     */
    protected function initQuery( Query $query )
    {
        return $query->where('post_type', $this->getPostType());
    }

    /**
     * Find post by ID
     *
     * @param string $id
     *
     * @return $this
     */
    public function getPost($id) {
        return $this->fetchResult(get_post( $id, ARRAY_A ));
    }

    /**
     * Find by ID with Where
     *
     * @param string $id
     * @return mixed|object|\TypeRocket\Database\Results|Model|null
     * @throws \Exception
     */
    public function findByIdWithWhere($id)
    {
        $results = $this->query->findById($id)->get();

        return $this->getQueryResult($results);
    }

    /**
     * Find by ID and Remove Where
     *
     * @param string $id
     * @return mixed|object|\TypeRocket\Database\Results|Model|null
     */
    public function findById($id)
    {
        $results = $this->query->removeWhere()->findById($id)->get();

        return $this->getQueryResult($results);
    }

    /**
     * Create post from TypeRocket fields
     *
     * Set the post type property on extended model so they
     * are saved to the correct type. See the PagesModel
     * as example.
     *
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return null|mixed|WPPost
     * @throws \TypeRocket\Exceptions\ModelException
     */
    public function create( $fields = [])
    {
        $fields = $this->provisionFields($fields);
        $builtin = $this->getFilteredBuiltinFields($fields);
        $new_post = null;

        if ( ! empty( $builtin )) {
            $builtin = $this->slashBuiltinFields($builtin);
            remove_action('save_post', 'TypeRocket\Http\Responders\Hook::posts');

            if(!empty($this->postType)) {
                $builtin['post_type'] = $this->postType;
            }

            if( empty($builtin['post_title']) ) {
                $error = 'WPPost not created: post_title is required';
                throw new ModelException( $error );
            }

            $post      = wp_insert_post( $builtin );
            add_action('save_post', 'TypeRocket\Http\Responders\Hook::posts');

            if ( $post instanceof \WP_Error || $post === 0 ) {
                $error = 'WPPost not created: An error accrued during wp_insert_post.';
                throw new ModelException( $error );
            } else {
                $new_post = $this->findById($post);
            }
        }

        $this->saveMeta( $fields );

        return $new_post;
    }

    /**
     * Update post from TypeRocket fields
     *
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return $this
     * @throws \TypeRocket\Exceptions\ModelException
     */
    public function update( $fields = [] )
    {
        $id = $this->getID();
        if( $id != null && ! wp_is_post_revision( $id ) ) {
            $fields = $this->provisionFields( $fields );
            $builtin = $this->getFilteredBuiltinFields($fields);

            if ( ! empty( $builtin ) ) {
                $builtin = $this->slashBuiltinFields($builtin);
                remove_action('save_post', 'TypeRocket\Http\Responders\Hook::posts');
                $builtin['ID'] = $id;
                $builtin['post_type'] = $this->properties['post_type'];
                $updated = wp_update_post( $builtin );
                add_action('save_post', 'TypeRocket\Http\Responders\Hook::posts');

                if ( $updated instanceof \WP_Error || $updated === 0 ) {
                    $error = 'WPPost not updated: post_name (slug), post_title and post_content are required';
                    throw new ModelException( $error );
                } else {
                    $this->findById($id);
                }

            }

            $this->saveMeta( $fields );

        } else {
            $this->errors = ['No item to update'];
        }

        return $this;
    }

    /**
     * Save post meta fields from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    private function saveMeta( $fields )
    {
        $fields = $this->getFilteredMetaFields($fields);
        $id = $this->getID();
        if ( ! empty($fields) && ! empty( $id )) :
            if ($parent_id = wp_is_post_revision( $id )) {
                $id = $parent_id;
            }

            foreach ($fields as $key => $value) :
                if (is_string( $value )) {
                    $value = trim( $value );
                }

                $current_value = get_post_meta( $id, $key, true );

                if (( isset( $value ) && $value !== "" ) && $value !== $current_value) :
                    update_post_meta( $id, $key, wp_slash($value) );
                elseif ( ! isset( $value ) || $value === "" && ( isset( $current_value ) || $current_value === "" )) :
                    delete_post_meta( $id, $key );
                endif;

            endforeach;
        endif;

        return $this;
    }

    /**
     * Get base field value
     *
     * Some fields need to be saved as serialized arrays. Getting
     * the field by the base value is used by Fields to populate
     * their values.
     *
     * @param string $field_name
     *
     * @return null
     */
    public function getBaseFieldValue( $field_name )
    {
        $id = $this->getID();

        $field_name = $field_name === 'ID' ? 'id' : $field_name;

        if($field_name == 'post_password') {
            $data = '';
        } else {
            $data = $this->getProperty($field_name);
        }

        if( is_null($data) ) {
            $data = get_metadata( 'post', $id, $field_name, true );
        }

        return $this->getValueOrNull($data);
    }

    /**
     * Slash Builtin Fields
     *
     * @param array $builtin
     * @return mixed
     */
    public function slashBuiltinFields( $builtin ) {

        $fields = [
            'post_content',
            'post_excerpt',
            'post_title',
        ];

        foreach ($fields as $field) {
            if(!empty($builtin[$field])) {
                $builtin[$field] = wp_slash( $builtin[$field] );
            }
        }

        return $builtin;
    }
}
