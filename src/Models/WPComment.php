<?php
namespace TypeRocket\Models;

use TypeRocket\Exceptions\ModelException;
use TypeRocket\Http\Fields;
use TypeRocket\Models\Meta\WPCommentMeta;
use TypeRocket\Models\Traits\MetaData;

class WPComment extends Model
{
    use MetaData;

    protected $idColumn = 'comment_ID';
    protected $resource = 'comments';
    protected $routeResource = 'comment';
    protected $wpComment;
    protected $fieldOptions = [
        'key' => 'comment_date',
        'value' => 'comment_ID',
    ];


    protected $builtin = [
        'comment_author',
        'comment_author_email',
        'comment_author_url',
        'comment_type',
        'comment_parent',
        'user_id',
        'comment_date',
        'comment_date_gmt',
        'comment_content',
        'comment_karma',
        'comment_approved',
        'comment_agent',
        'comment_author_ip',
        'comment_post_id',
        'comment_id'
    ];

    protected $guard = [
        'comment_id'
    ];

    /**
     * Return table name in constructor
     *
     * @param \wpdb $wpdb
     *
     * @return string
     */
    public function initTable( $wpdb )
    {
        return $wpdb->comments;
    }

    /**
     * Get Meta Model Class
     *
     * @return string
     */
    protected function getMetaModelClass()
    {
        return WPCommentMeta::class;
    }

    /**
     * Get ID Columns
     *
     * @return array
     */
    protected function getMetaIdColumns()
    {
        return [
            'local' => 'comment_ID',
            'foreign' => 'comment_id',
        ];
    }

    /**
     * Get User ID
     *
     * @return string|int|null
     */
    public function getUserID()
    {
        return $this->properties['user_id'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getRestMetaType()
    {
        return 'comment';
    }

    /**
     * Get WP_Comment Instance
     *
     * @param \WP_Comment|null|int $comment
     * @param bool $returnModel
     *
     * @return \WP_Comment|$this|null
     */
    public function wpComment($comment = null, $returnModel = false)
    {
        if( !$comment && $this->wpComment instanceof \WP_Comment ) {
            return $this->wpComment;
        }

        if( !$comment && $this->getID() ) {
            return $this->wpComment($this->getID());
        }

        if(!$comment) {
            return $this->wpComment;
        }

        $comment = get_comment($comment);

        if($comment instanceof \WP_Comment) {
            $this->wpComment = $comment;

            $this->castProperties( $comment->to_array() );
        }

        return $returnModel ? $this : $this->wpComment;
    }

    /**
     * Create comment from TypeRocket fields
     *
     * @param array|Fields $fields
     *
     * @return $this
     * @throws ModelException
     */
    public function create( $fields = [] )
    {
        $fields  = $this->provisionFields($fields);
        $builtin = $this->getFilteredBuiltinFields( $fields );
        $comment = null;

        do_action('typerocket_model_create', $this, $fields);

        if ( ! empty( $builtin['comment_post_id'] ) &&
             ! empty( $builtin['comment_content'] )
        ) {
            remove_action( 'wp_insert_comment', 'TypeRocket\Http\Responders\Hook::comments' );
            $comment   = wp_new_comment( $this->caseFieldColumns( wp_slash($builtin) ) );
            add_action( 'wp_insert_comment', 'TypeRocket\Http\Responders\Hook::comments' );

            if ( empty( $comment ) ) {
                throw new ModelException('WPComments not created');
            } else {
                $comment = $this->findById($comment);
            }
        } else {
            $this->errors = [
                'Missing post ID `comment_post_id`.',
                'Missing comment content `comment_content`.'
            ];
        }

        $this->saveMeta( $fields );

        do_action('typerocket_model_after_create', $this, $fields, $comment);

        return $comment;
    }

    /**
     * Update comment from TypeRocket fields
     *
     * @param array|Fields $fields
     *
     * @return $this
     * @throws ModelException
     */
    public function update( $fields = [] )
    {
        $id = $this->getID();
        if ($id != null) {
            $fields  = $this->provisionFields($fields);
            $builtin = $this->getFilteredBuiltinFields( $fields );
            $comment = null;

            do_action('typerocket_model_update', $this, $fields);

            if ( ! empty( $builtin )) {
                remove_action( 'edit_comment', 'TypeRocket\Http\Responders\Hook::comments' );
                $builtin['comment_id'] = $id;
                $builtin = $this->caseFieldColumns( $builtin );
                $comment = wp_update_comment(  wp_slash($builtin) );
                add_action( 'edit_comment', 'TypeRocket\Http\Responders\Hook::comments' );

                if (empty( $comment )) {
                    throw new ModelException('WPComments not updated');
                }

                $this->findById($id);
            }

            $this->saveMeta( $fields );

            do_action('typerocket_model_after_update', $this, $fields, $comment);

        } else {
            $this->errors = ['No item to update'];
        }

        return $this;
    }

    /**
     * Delete Comment
     *
     * This will delete the comment
     *
     * @param null|int $ids
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

        $delete = wp_delete_comment($ids);

        if ( !$delete ) {
            throw new ModelException('WPComment not deleted');
        }

        do_action('typerocket_model_after_delete', $this, $ids, $delete);

        return $this;
    }

    /**
     * Delete Comment Forever
     *
     * This will delete the comment
     *
     * @param null|int $ids
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

        $delete = wp_delete_comment($ids, true);

        if ( !$delete ) {
            throw new ModelException('WPComment not deleted');
        }

        do_action('typerocket_model_after_delete', $this, $ids, $delete);

        return $this;
    }

    /**
     * Save comment meta fields from TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return $this
     */
    public function saveMeta( $fields )
    {
        $fields = $this->getFilteredMetaFields( $fields );
        $id = $this->getID();
        if ( ! empty( $fields ) && ! empty( $id )) :
            foreach ($fields as $key => $value) :
                if (is_string( $value )) {
                    $value = trim( $value );
                }

                $current_value = get_comment_meta( $id, $key, true );
                $value = $this->getNewArrayReplaceRecursiveValue($key, $current_value, $value);

                if (( isset( $value ) && $value !== "" ) && $value !== $current_value) :
                    $value = wp_slash($value);
                    update_comment_meta( $id, $key, $value );
                    do_action('typerocket_after_save_meta_comment', $id, $key, $value, $current_value, $this);
                elseif ( ! isset( $value ) || $value === "" && ( isset( $current_value ) || $current_value === "" )) :
                    delete_comment_meta( $id, $key );
                endif;

            endforeach;
        endif;

        return $this;
    }

    /**
     * Format irregular fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return array
     */
    protected function caseFieldColumns( $fields )
    {
        if ( ! empty( $fields['comment_post_id'] )) {
            $fields['comment_post_ID'] = (int) $fields['comment_post_id'];
            unset( $fields['comment_post_id'] );
        }

        if ( ! empty( $fields['comment_id'] )) {
            $fields['comment_ID'] = (int) $fields['comment_id'];
            unset( $fields['comment_id'] );
        }

        if ( ! empty( $fields['comment_author_ip'] )) {
            $fields['comment_author_IP'] = $fields['comment_author_ip'];
            unset( $fields['comment_author_ip'] );
        }

        return $fields;
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
        $data = $this->getProperty($field_name);

        if (is_null($data) && in_array($field_name, $this->builtin)) {
            switch ($field_name) {
                case 'comment_author_ip' :
                    $data = $this->properties['comment_author_IP'];
                    break;
                case 'comment_post_id' :
                    $data = $this->properties['comment_post_ID'];
                    break;
                case 'comment_id' :
                    $data = $this->properties['comment_ID'];
                    break;
            }
        } elseif(is_null($data)) {
            $data = get_metadata('comment', $this->getID(), $field_name, true);
        }

        return $this->getValueOrNull($data);
    }

}
