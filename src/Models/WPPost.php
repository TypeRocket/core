<?php
namespace TypeRocket\Models;

use TypeRocket\Database\Query;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Models\Meta\WPPostMeta;
use TypeRocket\Models\Traits\MetaData;
use TypeRocket\Template\Composer;
use WP_Post;

/**
 * Class WPPost
 *
 * @property bool $is_published;
 *
 * @property string $post_status;
 * @property int $post_author;
 * @property string $post_date;
 * @property string $post_date_gmt;
 * @property string $post_content;
 * @property string $post_content_filtered;
 * @property string $post_title;
 * @property string $comment_status;
 * @property string $ping_status;
 * @property string $post_name;
 * @property string $to_ping;
 * @property string $pinged;
 * @property string $post_modified;
 * @property string $post_modified_gmt;
 * @property int $post_parent;
 * @property string $guid;
 * @property int $menu_order;
 * @property string $post_type;
 * @property string $post_mime_type;
 * @property int $comment_count;
 * @property string $post_password;
 * @property int $ID;
 *
 * @package TypeRocket\Models
 */
class WPPost extends Model
{
    use MetaData;

    protected $idColumn = 'ID';
    protected $resource = 'posts';
    public const POST_TYPE = null;
    protected $postType = null;
    protected $wpPost = null;
    protected $composer = 'TypeRocket\Template\PostTypeModelComposer';
    protected $fieldOptions = [
        'key' => 'post_title',
        'value' => 'ID',
    ];

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

    protected $private = [
        'post_password'
    ];

    /**
     * WPPost constructor.
     *
     * @param null|string $postType
     *
     * @throws \Exception
     */
    public function __construct($postType = null)
    {
        $this->setPostType($postType ?? static::POST_TYPE);
        parent::__construct();
    }

    /**
     * @param null|string $postType
     * @param bool $init
     *
     * @return $this
     */
    protected function setPostType($postType = null, $init = false)
    {
        if($postType) { $this->postType = $postType; }
        if($init) { $this->initQuery($this->query); }

        return $this;
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
        return $wpdb->posts;
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
        if($pt = $this->getPostType()) {
            $query->where('post_type', $pt);
        }

        return $query;
    }

    /**
     * Get JsonApiResource
     *
     * @return string|null
     */
    public function getRouteResource()
    {
        return $this->getPostType() ?? 'post';
    }

    /**
     * @return string|null
     */
    public function getRestMetaType()
    {
        return 'post';
    }

    /**
     * @return string|null
     */
    public function getRestMetaSubtype()
    {
        return $this->getPostType();
    }

    /**
     * Get WP_Post Instance
     *
     * @param WP_Post|null|int|false $post
     * @param bool $returnModel
     *
     * @return WP_Post|$this|null
     */
    public function wpPost($post = null, $returnModel = false) {

        if( !$post && $this->wpPost instanceof \WP_Post ) {
            return $this->wpPost;
        }

        if( !$post && $this->getID() ) {
            return $this->wpPost($this->getID());
        }

        if(!$post) {
            return $this->wpPost;
        }

        $post = get_post($post);

        if($post instanceof WP_Post) {
            $this->setPostType($post->post_type);
            $this->wpPost = $post;

            $this->castProperties( get_object_vars( $post ) );
        }

        return $returnModel ? $this : $this->wpPost;
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
     * Get User ID
     *
     * @return string|int|null
     */
    public function getUserID()
    {
        return $this->properties['post_author'] ?? null;
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
     * Get Post Permalink
     *
     * @return string|\WP_Error
     */
    public function permalink()
    {
        return get_permalink($this->wpPost());
    }

    /**
     * @param mixed|null $value
     *
     * @return bool
     */
    public function getIsPublishedProperty($value = null)
    {
        return (bool) ( $value ?? $this->post_status == 'publish' );
    }

    /**
     * @return string|\WP_Error
     */
    public function getSearchUrl()
    {
        return $this->permalink();
    }

    /**
     * Limit Field Options
     *
     * @return $this
     */
    public function limitFieldOptions()
    {
        return $this->published();
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
        if(!$this->wpPost && $this->getCache()) {
            $_post = sanitize_post((object) $this->properties, 'raw');
            wp_cache_add($_post->ID, $_post, 'posts');
            $this->wpPost = new WP_Post($_post);
        }

        return parent::afterCastProperties();
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
        return $this->hasMany( WPPostMeta::class, 'post_id', function($rel) use ($withoutPrivate) {
            if( $withoutPrivate ) {
                $rel->notPrivate();
            }
        });
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
     * @param bool $reselect
     *
     * @return Model|null
     */
    public function belongsToTaxonomy($modelClass, $taxonomy_id, $scope = null, $reselect = false)
    {
        $connection = $this->query->getWpdb();

        return $this->belongsToMany([$modelClass, WPTermTaxonomy::class], $connection->term_relationships, 'object_id', 'term_taxonomy_id', function($rel, &$reselect_main = true) use ($scope, $taxonomy_id, $reselect) {
            $connection = $rel->query->getWpdb();
            $rel->where($connection->term_taxonomy .'.taxonomy', $taxonomy_id);
            $reselect_main = $reselect;
            if(is_callable($scope)) {
                $scope($rel, $reselect_main);
            }
        });
    }

    /**
     * Author
     *
     * @return $this|null
     */
    public function author()
    {
        $user = \TypeRocket\Utility\Helper::appNamespace('Models\User');
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
     * @return Composer|\TypeRocket\Template\PostTypeModelComposer
     */
    public function composer()
    {
        return parent::composer();
    }

    /**
     * Find by ID and Remove Where
     *
     * @param string $id
     * @return mixed|object|\TypeRocket\Database\Results|Model|null
     */
    public function findById($id)
    {
        $results = $this->query->findById($id)->get();

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

        do_action('typerocket_model_create', $this, $fields);

        if ( ! empty( $builtin )) {
            $builtin = $this->slashBuiltinFields($builtin);
            remove_action('save_post', 'TypeRocket\Http\Responders\Hook::posts');

            if(!empty($this->getPostType())) {
                $builtin['post_type'] = $this->getPostType();
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
                $modelClass = get_class($this);
                $new_post = (new $modelClass)->findById($post);

                // TODO v6: Remove this line.
                // Kept for breaking changes in v5 for now
                $this->findById($post);
            }
        }

        if($new_post) {
            $new_post->saveMeta( $fields );
        } else {
            $this->saveMeta( $fields );
        }

        do_action('typerocket_model_after_create', $this, $fields, $new_post);

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
            $result = null;

            do_action('typerocket_model_update', $this, $fields);

            if ( ! empty( $builtin ) ) {
                $builtin = $this->slashBuiltinFields($builtin);
                remove_action('save_post', 'TypeRocket\Http\Responders\Hook::posts');
                $builtin['ID'] = $id;
                $builtin['post_type'] = $this->properties['post_type'];
                $result = wp_update_post( $builtin );
                add_action('save_post', 'TypeRocket\Http\Responders\Hook::posts');

                if ( $result instanceof \WP_Error || $result === 0 ) {
                    $error = 'WPPost not updated: post_name (slug), post_title and post_content are required';
                    throw new ModelException( $error );
                } else {
                    $this->findById($id);
                }

            }

            $this->saveMeta( $fields );

            do_action('typerocket_model_after_update', $this, $fields, $result);

        } else {
            $this->errors = ['No item to update'];
        }

        return $this;
    }

    /**
     * Delete Post
     *
     * @param null|int|\WP_Post $ids
     *
     * @return $this
     * @throws ModelException
     */
    public function delete($ids = null)
    {
        if(is_null($ids) && $this->hasProperties()) {
            $ids = $this->getID();
        }

        if(is_array($ids)) {
            throw new ModelException(static::class . ' not deleted: bulk deleting not supported due to WordPress performance issues.');
        }

        do_action('typerocket_model_delete', $this, $ids);

        $delete = wp_delete_post($ids);

        if ( !$delete ) {
            throw new ModelException('WPPost not deleted');
        }

        do_action('typerocket_model_after_delete', $this, $ids, $delete);

        return $this;
    }

    /**
     * Delete Post Forever
     *
     * @param null|int|\WP_Post $ids
     *
     * @return $this
     * @throws ModelException
     */
    public function deleteForever($ids = null)
    {
        if(is_null($ids) && $this->hasProperties()) {
            $ids = $this->getID();
        }

        do_action('typerocket_model_delete', $this, $ids);

        $delete = wp_delete_post($ids, true);

        if ( !$delete ) {
            throw new ModelException('WPPost not deleted');
        }

        do_action('typerocket_model_after_delete', $this, $ids, $delete);

        return $this;
    }

    /**
     * Save post meta fields from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    public function saveMeta( $fields )
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
                $value = $this->getNewArrayReplaceRecursiveValue($key, $current_value, $value);

                if (( isset( $value ) && $value !== "" ) && $value !== $current_value) :
                    $value = wp_slash($value);
                    update_post_meta( $id, $key, $value );
                    do_action('typerocket_after_save_meta_post', $id, $key, $value, $current_value, $this);
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
    public function slashBuiltinFields( $builtin )
    {
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
