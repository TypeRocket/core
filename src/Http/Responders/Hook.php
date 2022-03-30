<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Http\Request;
use TypeRocket\Models\WPMenu;

/**
 * Class Hook
 *
 * Used by core to hook into WordPress API
 *
 * @package TypeRocket\Http\Responders
 */
class Hook
{
    /**
     * Verify Nonce
     *
     * @param string $context
     * @param mixed $id
     *
     * @return bool
     */
    static public function verified($context, $id)
    {
        $verified = false;

        if( isset($_REQUEST['_tr_nonce_formhook']) && Request::new()->checkNonce('hook') ) {
            $verified = true;
        }

        return apply_filters('typerocket_hook_verified', $verified, $context, $id);
    }

    /**
     * Respond to posts hook
     *
     * @param string $id
     * @throws \Exception
     */
    static public function posts($id)
    {
        if(!static::verified('posts', $id)) {
            return;
        }

        $responder = new PostsResponder;
        $responder->getHandler()->setHook();
        $responder->respond([ '@first' => $id ]);
    }

    /**
     * Respond to attachments hook
     *
     * @param string $id
     * @throws \Exception
     */
    static public function attachments($id)
    {
        if(!static::verified('attachments', $id)) {
            return;
        }

        $responder = new PostsResponder;
        $responder->getHandler()->setHook();
        $responder->respond([ '@first' => $id ]);
    }

    /**
     * Respond to comments hook
     *
     * @param string $id
     * @throws \Exception
     */
    static public function comments($id)
    {
        if(!static::verified('comments', $id)) {
            return;
        }

        $responder = new CommentsResponder;
        $responder->getHandler()->setHook();
        $responder->respond([ '@first' => $id ]);
    }

    /**
     * Respond to users hook
     *
     * @param string $id
     * @throws \Exception
     */
    static public function users($id)
    {
        if(!static::verified('users', $id)) {
            return;
        }

        $responder = new UsersResponder;
        $responder->getHandler()->setHook();
        $responder->respond([ '@first' => $id ]);
    }

    /**
     * Respond to taxonomies hook
     *
     * @param string $term_id
     * @param string|null $term_taxonomy_id
     * @param string|null $taxonomy
     * @throws \Exception
     */
    static public function taxonomies($term_id, $term_taxonomy_id = null, $taxonomy = null)
    {
        if(is_null($taxonomy) || $taxonomy == 'nav_menu' || !static::verified('taxonomies', $term_id)) {
            return;
        }

        $responder = new TaxonomiesResponder;
        $responder->setTaxonomy($taxonomy);
        $responder->getHandler()->setHook();
        $responder->respond([ '@first' => $term_id ]);
    }

    /**
     * Respond to menu item hook
     *
     * This hook does not use the responder, kernel, and controller system
     * because it would be a performance hog.
     *
     * @param $menu_id
     * @param $menu_item_db_id
     * @param $args
     *
     * @throws \Exception
     */
    public static function menus( $menu_id, $menu_item_db_id, $args )
    {
        if (current_user_can('edit_theme_options') || !static::verified('menus', $menu_item_db_id)) {
            $fields = Request::new()->getDataPost('tr-menu-'.$menu_item_db_id);
            $menu = (new WPMenu)->wpPost($menu_item_db_id, true);
            $menu->saveMeta($fields);
        }
    }

}