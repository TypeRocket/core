<?php
namespace TypeRocket\Register;

use TypeRocket\Core\Config;
use TypeRocket\Utility\Sanitize;

class MetaBox extends Registrable
{

    protected $label = null;
    protected $callback = null;
    protected $context = null;
    protected $priority = null;
    protected $screens = [];

    /**
     * Make Meta Box
     *
     * @param string $name
     * @param null|string|array $screen
     * @param array $settings
     */
    public function __construct( $name, $screen = null, array $settings = [])
    {
        $this->label = $this->id = $name;
        $this->id    = Sanitize::underscore( $this->id );

        if ( ! empty( $screen )) {
            $screen        = (array) $screen;
            $this->screens = array_merge( $this->screens, $screen );
        }

        if ( ! empty( $settings['callback'] )) {
            $this->callback = $settings['callback'];
        }
        if ( ! empty( $settings['label'] )) {
            $this->label = $settings['label'];
        }

        unset( $settings['label'] );

        $defaults = [
            'context'  => 'normal', // 'normal', 'advanced', or 'side'
            'priority' => 'default', // 'high', 'core', 'default' or 'low'
            'args'     => []
        ]; // arguments to pass into your callback function.

        $settings = array_merge( $defaults, $settings );

        $this->context  = $settings['context'];
        $this->priority = $settings['priority'];
        $this->args     = $settings['args'];
    }

    /**
     * Set the meta box label
     *
     * @param string $label
     *
     * @return MetaBox $this
     */
    public function setLabel( $label )
    {

        $this->label = (string) $label;

        return $this;
    }

    /**
     * Set the meta box label
     *
     * @return null|string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Add meta box to a screen
     *
     * @param string|array $screen
     *
     * @return MetaBox $this
     */
    public function addScreen( $screen )
    {
        $this->screens = array_merge( $this->screens, (array) $screen );

        return $this;
    }

    /**
     * Add meta box to post type
     *
     * @param string|array|PostType $s
     *
     * @return MetaBox $this
     */
    public function addPostType( $s )
    {
        if ($s instanceof PostType) {
            $s = $s->getId();
        } elseif (is_array( $s )) {
            foreach ($s as $n) {
                $this->addPostType( $n );
            }
        }

        if ( ! in_array( $s, $this->screens )) {
            $this->screens[] = $s;
        }

        return $this;

    }

    /**
     * Register meta box with WordPress
     *
     * @return MetaBox $this
     */
    public function register()
    {
        global $post, $comment;

        $postType = null;

        if(!empty($post)) {
            $postType = get_post_type( $post->ID );
            $pageTemplate = get_post_meta( $post->ID, '_wp_page_template', true );
        }

        if (!empty($post) && post_type_supports( $postType, $this->id )) {
            $this->addPostType( $postType );
        }

        foreach ($this->screens as $screen) {
            $isPageTemplate = $isFrontPage = $isPostsPage = false;
            if(isset($post)) {
              $isPageTemplate = $pageTemplate == $screen
                && $post->ID != get_option( 'page_on_front' )
                && $post->ID != get_option( 'page_for_posts' );
              $isFrontPage = $post->ID == get_option( 'page_on_front' ) && $screen == 'front_page';
              $isPostsPage = $post->ID == get_option( 'page_for_posts' ) && $screen == 'posts_page';
            }

            if ( $postType == $screen ||
                $isPageTemplate ||
                $isFrontPage ||
                $isPostsPage ||
                ( $screen == 'comment' && isset( $comment ) ) ||
                ( $screen == 'dashboard' && ! isset( $post ) )
            ) {
                $obj = $this;

                $callback = function () use ( $obj ) {
                    $func     = 'add_meta_content_' . $obj->getId();
                    $callback = $obj->getCallback();

                    echo '<div class="typerocket-container">';
                    if (is_callable( $callback )) :
                        call_user_func_array( $callback, [$obj]);
                    elseif (function_exists( $func )) :
                        $func( $obj );
                    elseif ( Config::locate('app.debug') == true) :
                        echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> Add content here by defining: <code>function {$func}() {}</code></div>";
                    endif;
                    echo '</div>';
                };

                add_meta_box(
                    $this->id,
                    $this->label,
                    $callback,
                    $isPageTemplate || $isFrontPage || $isPostsPage ? $postType : $screen,
                    $this->context,
                    $this->priority
                );
            }
        }

        return $this;
    }

    /**
     * Set Priority
     *
     * @return null|string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set Priority
     *
     * @param null $priority 'high', 'core', 'default' or 'low'
     *
     * @return MetaBox $this
     */
    public function setPriority( $priority )
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Set Context
     *
     * @param null $context 'normal', 'advanced', or 'side'
     *
     * @return MetaBox $this
     */
    public function setContext( $context )
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get Context
     *
     * @return null|string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set Callback
     *
     * @param string $callback
     * @return MetaBox $this
     */
    public function setCallback( $callback )
    {

        if (is_callable( $callback )) {
            $this->callback = $callback;
        } else {
            $this->callback = null;
        }

        return $this;
    }

    /**
     * Set Callback
     *
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }


}