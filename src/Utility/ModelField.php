<?php
namespace TypeRocket\Utility;

use Exception;
use TypeRocket\Elements\Fields\Matrix;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPComment;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\WPUser;
use WP_Post;

class ModelField
{
    /**
     * Modify Model Value
     *
     * @param Model $model use dot notation
     * @param string|array $args
     *
     * @return array|mixed|null|string
     */
    public static function model(Model $model, $args)
    {
        if(!empty($args[0]) && $args[0] != ':' && !is_array($args)) {
            return $model->getFieldValue($args);
        }

        if(is_array($args)) {
            $value = $model->getFieldValue($args['name']);
            $callback_args = array_merge([$value], $args['args']);
            return call_user_func_array($args['callback'], $callback_args);
        }

        [$modifier, $arg1, $arg2] = array_pad(explode(':', ltrim($args, ':'), 3), 3, null);
        $name = $arg2 ? $arg2 : $arg1 ;
        $value = $model->getFieldValue($name);

        switch($modifier) {
            case 'img';
                $size = $arg2 ? $arg1 : 'thumbnail' ;
                $modified = wp_get_attachment_image( (int) $value, $size);
                break;
            case 'plaintext';
                $modified = wpautop(esc_html($value));
                break;
            case 'html';
                $modified = Sanitize::editor($value, true);
                break;
            case 'img_src';
                $size = $arg2 ? $arg1 : 'thumbnail';
                $modified = wp_get_attachment_image_src( (int) $value, $size);
                break;
            case 'background';
                $size = $arg2 ? $arg1 : 'full';
                $img_src = wp_get_attachment_image_src( (int) $value['id'] ?? 0, $size)[0];
                $img_px = $value['x'] ?? 50;
                $img_py = $value['y'] ?? 50;
                $img_p = "{$img_px}% {$img_py}%";
                $modified = "background-image: url({$img_src}); background-position: {$img_p};";
                break;
            case 'post';
                $modified = get_post( (int) $value);
                break;
            case 'term';
                $taxonomy = $arg2 ? $arg1 : 'category' ;
                $modified = get_term($value, $taxonomy);
                break;
            default:
                $callback_args = array_merge([$value], $arg2 ? [$arg1] : []);
                $modified = call_user_func_array($modifier, $callback_args);
                break;
        }

        return $modified;
    }

    /**
     * Get the post's field
     *
     * @param string|array $name use dot notation
     * @param null|int|WP_Post|WPPost $item_id
     *
     * @return array|mixed|null|string
     */
    public static function post($name, $item_id = null)
    {
        global $post;

        if($item_id instanceof WPPost) {
            $model = $item_id;
        } else {
            if (is_null($item_id) && isset($post->ID)) {
                $item_id = $post->ID;
            }

            try {
                $model = new WPPost();
                $model->wpPost($item_id);
            } catch (Exception $e) {
                return null;
            }
        }

        return static::model($model, $name);
    }

    /**
     * Get components
     *
     * Auto binding only for post types
     *
     * @param string $name use dot notation
     * @param null|int|Model $item_id
     *
     * @param null|string $modelClass
     *
     * @return array|mixed|null|string
     * @throws Exception
     */
    public static function components($name, $item_id = null, $modelClass = null)
    {
        global $post;

        if($item_id instanceof Model) {
            $model = $item_id;
        } else {
            if (isset($post->ID) && is_null($item_id)) {
                $item_id = $post->ID;
            }

            try {
                /** @var Model $model */
                $modelClass = $modelClass ?? \TypeRocket\Models\WPPost::class;
                $model = new $modelClass;
                $model->findById($item_id);
            } catch (Exception $e) {
                return null;
            }
        }

        $builder_data = $model->getFieldValue($name);
        if(is_array($builder_data)) {
            Matrix::componentsLoop($builder_data, compact('name', 'item_id', 'model'));
        }

        return $builder_data;
    }

    /**
     * Get users field
     *
     * @param string|array $name use dot notation
     * @param null|int|WPUser $item_id
     *
     * @return array|mixed|null|string
     */
    public static function user($name, $item_id = null)
    {
        global $user_id, $post;

        if ($item_id instanceof WPUser) {
            $model = $item_id;
        } else {
            if (isset($user_id) && is_null($item_id)) {
                $item_id = $user_id;
            } elseif (is_null($item_id) && isset($post->ID)) {
                $item_id = $post->post_author;
            } elseif (is_null($item_id)) {
                $item_id = get_current_user_id();
            }

            try {
                /** @var WPUser $model */
                $model = Helper::modelClass('User');
                $model->wpUser($item_id);
            } catch (Exception $e) {
                return null;
            }
        }

        return static::model($model, $name);
    }

    /**
     * Get options
     *
     * @param string|array $name use dot notation
     *
     * @return array|mixed|null|string
     */
    public static function option($name)
    {
        $model = Helper::modelClass('Option');

        return static::model($model, $name);
    }

    /**
     * Get comments field
     *
     * @param string|array $name use dot notation
     * @param null|int $item_id
     *
     * @return array|mixed|null|string
     */
    public static function comment($name, $item_id = null)
    {
        global $comment;

        if ($item_id instanceof WPComment) {
            $model = $item_id;
        } else {
            if (isset($comment->comment_ID) && is_null($item_id)) {
                $item_id = $comment->comment_ID;
            }

            try {
                /** @var WPComment $model */
                $model = Helper::modelClass('Comment');
                $model->wpComment($item_id);
            } catch (Exception $e) {
                return null;
            }
        }

        return static::model($model, $name);
    }

    /**
     *  Get taxonomy field
     *
     * @param string|array $name use dot notation
     * @param string|null|WPTerm $taxonomy taxonomy model class
     * @param int|null $item_id taxonomy id
     *
     * @return array|mixed|null|string
     */
    public static function term($name, $taxonomy = null, $item_id = null)
    {
        if ($taxonomy instanceof WPTerm && !$item_id) {
            $model = $taxonomy;
        } else {
            try {
                /** @var WPTerm $model */
                $model = $taxonomy ? Helper::modelClass($taxonomy) : new WPTerm;
                $model->wpTerm($item_id);
            } catch (Exception $e) {
                return null;
            }
        }

        return static::model($model, $name);
    }

    /**
     * Get resource
     *
     * @param string|array $name use dot notation
     * @param string $resource
     * @param null|int $item_id
     *
     * @return array|mixed|null|string
     */
    public static function resource($name, $resource, $item_id = null)
    {
        if ($resource instanceof Model && !$item_id) {
            $model = $resource;
        } else {
            try {
                /** @var Model $model */
                $model = Helper::modelClass($resource);
                $model->findById($item_id);
            } catch (Exception $e) {
                return null;
            }
        }

        return static::model($model, $name);
    }
}