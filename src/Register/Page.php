<?php
namespace TypeRocket\Register;

use Closure;
use TypeRocket\Controllers\Controller;
use TypeRocket\Core\Config;
use TypeRocket\Http\Request;
use TypeRocket\Http\Responders\HttpResponder;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Template\View;
use TypeRocket\Utility\Str;
use WP_Admin_Bar;

class Page extends Registrable
{
    protected $title = 'Admin Page Title';
    protected $menuTitle = null;
    protected $subMenuTitle = null;
    protected $subMenu = null;
    protected $resource = 'admin';
    /** @var string|Controller|null  */
    protected $handler = null;
    protected $middlewareGroups = [];
    protected $action = 'index';
    protected $actionMap = [];
    protected $routeArgs = [];
    protected $routeArgsNamed = [];
    protected $icon = null;
    protected $pages = [];
    /** @var null|Page parent page */
    protected $parent = null;
    protected $showTitle = true;
    protected $showMenu = true;
    protected $showAddNewButton = false;
    protected $builtin = [
        'tools' => 'tools.php',
        'dashboard' => 'index.php',
        'media' => 'upload.php',
        'appearance' => 'themes.php',
        'plugins' => 'plugins.php',
        'users' => 'users.php',
        'settings' => 'options-general.php'
    ];

    /**
     * Page constructor.
     *
     * @param string $resource set the resource or section the page belongs to
     * @param string $action set the action the page is responsible for
     * @param string $title set the title of the page and menu
     * @param array $settings menu, capability, position, view, slug
     * @param null|array|string|callable $handler
     */
    public function __construct(string $resource, string $action, string $title, array $settings = [], $handler = null)
    {
        [$resource, $handle] = array_pad(explode('@', $resource), 2, null);
        $handler = $handler ?? $handle;

        $settings['capability'] = $settings['capability'] ?? $settings['cap'] ?? null;

        $this->setTitle($title);
        $this->resource = Sanitize::underscore( $resource );
        $this->id       = Sanitize::underscore( $this->title );
        $this->action   = Sanitize::underscore( $action );
        $this->args     = array_merge( [
            'menu' => $this->title,
            'sub_menu' => $this->subMenuTitle,
            'inherit_capability' => true,
            'position' => 25,
            'view' => null,
            'slug' => $this->resource . '_' . $this->action,
        ], $settings );

        if($handler) {
            $this->setHandler($handler);
        }
    }

    /**
     * Set View
     *
     * @param View|string|callable $view string value can be a file path or text block
     *
     * @return Page
     */
    public function setView($view)
    {
        return $this->setArgument('view', $view);
    }

    /**
     * Set the page menu icon
     *
     * @link https://developer.wordpress.org/resource/dashicons/
     *
     * @param string $name icon name does not require prefix.
     *
     * @return Page $this
     */
    public function setIcon($name)
    {
        $this->icon = 'dashicons-' . Str::trimStart($name, 'dashicons-');

        return $this;
    }

    /**
     * Get the page icon
     *
     * @return null
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * Set Position
     *
     * @param int $number
     *
     * @return Page
     */
    public function setPosition($number)
    {
        return $this->setArgument('position', $number);
    }

    /**
     * Get Handler
     *
     * @return string|Controller|null
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get Pages
     *
     * @return array
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Is Action
     *
     * @param $action
     * @return bool
     */
    public function isAction(string $action)
    {
        return $this->action == $action;
    }

    /**
     * Get Action
     *
     * @return mixed|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the slug
     *
     * @return mixed
     */
    public function getSlug() {
        return $this->args['slug'];
    }

    /**
     * Set the slug
     *
     * @param string $slug
     *
     * @return Page $this
     */
    public function setSlug( $slug ) {
        $this->args['slug'] = $slug;

        return $this;
    }

    /**
     * Set the parent page
     *
     * @param Page $parent
     *
     * @return Page $this
     */
    public function setParentPage( Page $parent ) {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get the parent page
     *
     * @return null|Page
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set Title
     *
     * The Page title.
     *
     * @param string $title
     *
     * @return Page $this
     */
    public function setTitle( $title )
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set Menu Title
     *
     * The main menu title if used.
     *
     * @param $title
     *
     * @return Page
     */
    public function setMenuTitle($title)
    {
        $this->menuTitle = $title;

        return $this;
    }

    /**
     * Set Sub Menu Title
     *
     * The sub menu title if used.
     *
     * @param $title
     *
     * @return Page
     */
    public function setSubMenuTitle($title)
    {
        $this->subMenuTitle = $title;

        return $this;
    }

    /**
     * Set Sub Menu
     *
     * Make page a submenu of another page.
     *
     * @param string|Page $page
     *
     * @return $this
     */
    public function setParent($page)
    {
        if($page instanceof Page) {
            return $this->setParentPage($page);
        }

        $this->subMenu = $page;

        return $this;
    }


    /**
     * Remove title from page
     *
     * @return Page $this
     */
    public function removeTitle()
    {
        $this->showTitle = false;

        return $this;
    }

    /**
     * Get admin page
     *
     * Get the page such as admin.php tools.php upload.php that Page belongs to
     *
     * @return mixed|string
     */
    public function getAdminPage()
    {
        $resource = $this->resource;
        return !empty($this->builtin[$resource]) ? $this->builtin[$resource] : 'admin.php';
    }

    /**
     * Get URL for admin page
     *
     * @param array $params
     *
     * @return string
     */
    public function getUrl( $params = [] ) {

        $query = http_build_query( array_merge(
            [ 'page' => $this->getSlug() ],
            $this->routeArgsNamed,
            ['route_args' => $this->routeArgs],
            $params
        ) );

        return admin_url() . $this->getAdminPage() . '?' . $query;
    }

    /**
     * Get URL for admin page with existing params in URL
     *
     * @param array $params
     *
     * @return string
     */
    public function getUrlWithParams( $params = [] ) {
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $request_params);

        $query = http_build_query( array_filter( array_merge(
            [ 'page' => $this->getSlug() ],
            $this->routeArgsNamed,
            ['route_args' => $this->routeArgs],
            $request_params,
            $params
        ) ) );

        return admin_url() . $this->getAdminPage() . '?' . $query;
    }

    /**
     * @param string $key
     * @param string|int $value
     *
     * @return Page
     */
    public function setRouteArg($key, $value = null)
    {
        if(func_num_args() == 1) {
            $this->routeArgs[] = $key;
        } else {
            $this->routeArgsNamed['route_' . $key] = $value;
        }

        return $this;
    }

    /**
     * Remove menu
     *
     * @return Page $this
     */
    public function removeMenu()
    {
        $this->showMenu = false;

        return $this;
    }

    /**
     * Show new button
     *
     * @param bool $url
     *
     * @return Page $this
     */
    public function addNewButton( $url = true ) {
        $this->showAddNewButton = $url;

        return $this;
    }

    /**
     * Set Handler
     *
     * The class name of the controller for the page to use.
     *
     * @param string $handler
     * @return Page $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Set Middleware Groups
     *
     * @param array $middlewareGroups
     * @return $this
     */
    public function setMiddlewareGroups(array $middlewareGroups)
    {
        $this->middlewareGroups = $middlewareGroups;

        return $this;
    }

    /**
     * Set Capability
     *
     * @param string $capability
     *
     * @return Page
     */
    public function setCapability($capability)
    {
        return $this->setArgument('capability', $capability);
    }

    /**
     * Get Capability
     *
     * @return string
     */
    public function getCapability() {
        $default_capability = 'administrator';
        $capability = $this->args['capability'] ?? $default_capability;

        if( $this->getParent() && $this->args['inherit_capability'] && ! $this->args['capability'] ) {
            $parent_capability = $this->getParent()->getArgument('capability');
            $capability = $parent_capability ?? $default_capability;
        }

        return $capability;
    }

    /**
     * Register with WordPress
     *
     * Override this in concrete classes
     *
     * @return Page $this
     */
    public function register()
    {
        $menu_title = $this->menuTitle ?? $this->args['menu'];
        $capability = $this->getCapability();
        $slug = $this->getSlug();
        $position = $this->args['position'];

        $callback = function() {

            $url = $action = '';

            if( $this->parent ) {
                $all_pages = $this->parent->getPages();
            } else {
                $all_pages = $this->pages;
            }

            do_action('typerocket_page_start_view_' . $this->id, $this);
            echo '<div id="typerocket-admin-page" class="wrap typerocket-container">';

            foreach ($all_pages as $page) {
                /** @var Page $page */
                if($page->action == 'add') {
                    $url =  $page->getUrl();
                    break;
                }
            }

            if( $url && $this->showAddNewButton ) {
                if( is_string($this->showAddNewButton) ) {
                    $url = $this->showAddNewButton;
                }
                $add_text = __('Add New', 'typerocket-domain');
                $action = ' <a href="'.$url.'" class="page-title-action">'.$add_text.'</a>';
            }

            $action = apply_filters('typerocket_page_title_actions', $action, $this);

            if( $this->showTitle ) {
                echo '<h1 class="tr-admin-page-title">'. $this->title . $action . '</h1>';
            }

            echo '<div class="tr-admin-view">';

            $response = $this->args['view'];

            if( is_callable($response) ) {
                call_user_func($response, $this);
            }
            elseif ( $response instanceof View) {
                $response->render();
            }
            elseif( is_string($response) && strlen( $response ) <= PHP_MAXPATHLEN && file_exists($response) ) {
                /** @noinspection PhpIncludeInspection */
                include( $response );
            }
            elseif ( is_string($response)) {
                echo $response;
            }
            elseif( Config::get('app.debug') ) {
                echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon dashicons dashicons-editor-code\"></i> Add content here by creating or setting a view.</div>";
            }
            echo '</div></div>';
        };

        if( $this->subMenu || array_key_exists( $this->resource, $this->builtin ) ) {
            $subMenu = $this->builtin[$this->subMenu ?? $this->resource] ?? $this->subMenu;
            add_submenu_page($subMenu, $this->title, $menu_title, $capability, $slug, Closure::bind( $callback, $this ) );
        } elseif( ! $this->parent ) {
            add_menu_page( $this->title, $menu_title, $capability, $slug, Closure::bind( $callback, $this ), $this->icon, $position);
            if( $this->hasShownSubPages() ) {
                add_submenu_page( $slug, $this->title, $this->subMenuTitle ?? $menu_title, $capability, $slug );
            }
        } else {
            $parent_slug = $this->parent->getSlug();
            add_submenu_page( $parent_slug, $this->title, $menu_title, $capability, $slug, Closure::bind( $callback, $this ) );

            if( ! $this->showMenu ) {
                add_action( 'admin_head', function() use ($parent_slug, $slug) {
                    remove_submenu_page( $parent_slug, $slug );
                } );
            }
        }

        return $this;
    }

    /**
     * Add Admin Bar Menu Item
     *
     * @param string $id
     * @param null|string $title
     * @param string $parent_id
     *
     * @return Page $this
     */
    public function adminBar( $id, $title = null, $parent_id = 'site-name')
    {
        add_action('admin_bar_menu', Closure::bind(function() use ($parent_id, $title, $id) {
            if( current_user_can( $this->getCapability() ) ) {
                /** @var $wp_admin_bar WP_Admin_Bar */
                global $wp_admin_bar;
                $link = $this->getUrl();
                $wp_admin_bar->add_menu([
                    'id'     => $id,
                    'parent' => $parent_id,
                    'meta'   => [
                        'class' => 'custom-page-admin-bar-item',
                    ],
                    'title'  => $title ? $title : $this->getTitle(),
                    'href'   => $link
                ]);
            }
        }, $this), 80);

        return $this;
    }

    /**
     * Map Action
     *
     * Use to page controller actions for different request methods
     *
     * @param string $method use the string POST, GET, UPDATE, DELETE
     * @param string $action use the action on the controller you want to call
     *
     * @return Page $this
     */
    public function mapAction($method, $action)
    {
        $this->actionMap[strtoupper($method)] = $action;

        return $this;
    }

    /**
     * Map Actions
     *
     * Used to reduce the number of page registrations needed
     * to map REST methods to actions. This allows for each
     * page=? to act as a single route that can respond
     * to any number of HTTP request methods.
     *
     * @param array $map ['POST' => 'create', 'GET' => 'add', 'DELETE' => 'destroy']
     * @return Page $this
     */
    public function mapActions($map)
    {
        $this->actionMap = $map;

        return $this;
    }

    /**
     * Invoked if $handler is set
     * @throws \Exception
     */
    public function respond()
    {
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $request_params);

        if( !empty($request_params['page']) &&  $request_params['page'] == $this->getSlug() ) {

            $args = [];

            if(isset($_GET)) {

                $get = $_GET;

                if(isset($get['route_args']) && is_array($get['route_args'])) {
                    $args = $get['route_args'];
                    unset($get['route_args']);
                }

                foreach ($get as $name => $value) {
                    if( $name != 'route_args' && Str::starts('route_', $name) ) {
                        if($route_arg = mb_substr($name, 6)) {
                            $args[$route_arg] = $value;
                        } else {
                            $args[] = $value;
                        }
                    }
                }
            }

            $this->loadGlobalVars();
            $method = (new Request)->getFormMethod();
            $action = $this->actionMap[$method] ?? $this->action;
            $handler = [$this->handler, $action];

            if(is_array($this->handler) || is_callable($this->handler)) {
                $handler = $this->handler;
            }

            $responder = new HttpResponder;
            $responder->getHandler()
                ->setController( $handler )
                ->setMiddlewareGroups( $this->middlewareGroups );

            $responder->respond( $args );

            $response = \TypeRocket\Http\Response::getFromContainer();
            $returned = $response->getReturn();
            $rest = Request::new()->isMarkedAjax();

            if( !$rest && $returned instanceof View) {
                status_header( $response->getStatus() );
                $this->setArgument('view', $returned);
            } elseif( !$rest && is_string($returned) ) {
                status_header( $response->getStatus() );
                $this->setArgument('view', $returned);
            } else {
                $response->finish($rest);
            }
        }
    }

    /**
     * Has shown sub pages
     *
     * @return bool
     */
    public function hasShownSubPages()
    {
        if( ! empty( $this->pages ) ) {
            foreach($this->pages as $page) {
                if( $page->showMenu ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add page to page
     *
     * @param string|Page $s
     *
     * @return Page $this
     */
    public function addPage( $s )
    {
        if ( $s instanceof Page && ! in_array( $s, $this->pages )) {
            $this->pages[] = $s;
            $s->setParentPage( $this );
        } elseif( is_array($s) ) {
            foreach($s as $n) {
                $this->addPage($n);
            }
        }

        return $this;
    }

    /**
     * Apply post types
     *
     * @param array|PostType $post_type
     *
     * @return Page $this
     */
    public function addPostType( $post_type )
    {
        if ($post_type instanceof PostType) {
            $post_type->setArgument( 'show_in_menu', $this->getSlug() );
        } elseif (is_array( $post_type )) {
            foreach ($post_type as $n) {
                $this->addPostType( $n );
            }
        }

        return $this;
    }

    /**
     * Global Vars
     */
    protected function loadGlobalVars() {
        $GLOBALS['_tr_page'] = $this;
        $class = \TypeRocket\Utility\Helper::appNamespace('Models\\' . Str::camelize($this->resource));
        if( class_exists( $class ) ) {
            $GLOBALS['_tr_resource'] = new $class;
        }
    }

    /**
     * @param string $singular
     * @param string|array|null $plural
     * @param array $settings
     * @param null|string $resource
     * @param null|string $handler
     *
     * @return static
     * @throws \Exception
     */
    public static function addResourcePages($singular, $plural = null, array $settings = [], $resource = null, $handler = null)
    {
        [$singular, $handle] = array_pad(explode('@', $singular), 2, null);
        $handler = $handler ?? $handle;

        if(is_array($plural)) {
            $settings = $plural;

            if(isset($settings['plural'])) {
                $plural = $settings['plural'];
                unset($settings['plural']);
            } else {
                $plural = null;
            }
        }

        if ( ! $plural) {
            $plural = \TypeRocket\Utility\Inflect::pluralize($singular);
        }

        if(!$handler) {
            $handler = \TypeRocket\Utility\Helper::controllerClass($singular, false);
        }

        if( ! $resource) {
            $resource = $singular;
        }

        $menu_id = 'add_resource_' . \TypeRocket\Utility\Sanitize::underscore($singular);

        $add = \TypeRocket\Register\Page::add($resource, 'add', __('Add ' . $singular))
            ->setMenuTitle(__('Add New'))
            ->adminBar($menu_id, $singular, 'new-content')
            ->mapActions([
                'GET' => 'add',
                'POST' => 'create',
            ]);

        $delete = \TypeRocket\Register\Page::add($resource, 'delete', 'Delete ' . $singular)
            ->removeMenu()
            ->mapActions([
                'GET' => 'delete',
                'DELETE' => 'destroy',
            ]);

        $show = \TypeRocket\Register\Page::add($resource, 'show', $singular)
            ->addNewButton()
            ->removeMenu()
            ->mapActions([
                'GET' => 'show'
            ]);

        $edit = \TypeRocket\Register\Page::add($resource, 'edit', __('Edit ' . $singular))
            ->addNewButton()
            ->removeMenu()
            ->mapActions([
                'GET' => 'edit',
                'PUT' => 'update',
            ]);

        $index = \TypeRocket\Register\Page::add($resource, 'index', $plural, $settings)
            ->apply($edit, $show, $delete, $add)
            ->setSubMenuTitle(__('All ' . $plural))
            ->addNewButton();

        foreach ([$add, $edit, $delete, $show, $index] as $page) {
            /** @var \TypeRocket\Register\Page $page */
            $page->setHandler($handler);
        }

        return $index;
    }
}
