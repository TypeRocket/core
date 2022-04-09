<?php
namespace TypeRocket\Template;

use TypeRocket\Core\Config;
use TypeRocket\Http\Request;
use TypeRocket\Utility\PersistentCache;

class View
{
    protected $data = [];
    protected $title = null;
    protected $ext = null;
    protected $file = null;
    protected $location = null;
    protected $viewsEngine = null;
    protected $context = null;
    protected $folder = null;
    protected $name = null;

    /**
     * View constructor.
     *
     * Take a custom file location or dot notation of view location.
     *
     * @param string $dots dot syntax or specific file path
     * @param array $data
     * @param string $ext file extension
     * @param null|string $folder
     */
    public function __construct( $dots, array $data = [], $ext = null, $folder = null )
    {
        if( file_exists( $dots ) ) {
            $this->file = $dots;
        } else {
            $this->ext = $ext ?? '.php';
            $this->location = str_replace('.', '/', $dots) . $this->ext;
        }

        if( !empty( $data ) ) {
            $this->data = $data;
        }

        $this->name = $dots;
        $this->init();

        $this->setContext();
        $this->setFolder($folder);
    }

    protected function init() {}

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }

    /**
     * Get View Name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the file
     *
     * @return null|string
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Get the Location
     *
     * @return null|string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Get the data attached to a view.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get file extension
     *
     * @return string|null
     */
    public function getExtension()
    {
        return $this->ext ?? '.php';
    }

    /**
     * Set the title attached to a view.
     *
     * Requires https://codex.wordpress.org/Title_Tag support AND
     * override SEO Meta when used on a template.
     *
     * @param string $title
     *
     * @return \TypeRocket\Template\View
     */
    public function setTitle( $title )
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title attached to a view.
     *
     * @return array
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set SEO Meta Data
     *
     * Requires SEO plugin
     *
     * @param array $meta
     * @param null|string $url URL for the current page
     *
     * @return View
     * @throws \Exception
     */
    public function setSeoMeta(array $meta, $url = null)
    {
        if(!defined('TYPEROCKET_SEO_EXTENSION')) {
            throw new \Exception('TypeRocket SEO Extension required for the `setMeta()` view method.');
        }

        add_filter('typerocket_seo_meta', function($old_meta) use ($meta) {
            return $meta;
        });

        add_filter('typerocket_seo_url', function($old_url) use ($url) {
            return $url ?? (new Request)->getUriFull();
        });

        return $this;
    }

    /**
     * Set Templating Engine
     *
     * @param $engine_class
     *
     * @return View
     */
    public function setEngine($engine_class)
    {
        return $this->setViewsEngine($engine_class);
    }

    /**
     * Set Views Templating Engine
     *
     * @param $engine_class
     *
     * @return View
     */
    public function setViewsEngine($engine_class)
    {
        $this->viewsEngine = $engine_class;

        return $this;
    }

    /**
     * @return array|mixed|null
     */
    public function getComposedEngine()
    {
        return $this->viewsEngine ?? Config::get('app.templates.' . $this->getContext());
    }

    /**
     * Load Other Context
     *
     * @param null|string $context
     */
    protected function load($context = null)
    {
        $view_title = $this->getTitle();
        $this->setContext($context);

        if($view_title) {
            add_filter('document_title_parts', function( $title ) use ($view_title) {
                if( is_string($view_title) ) {
                    $title = [];
                    $title['title'] = $view_title;
                } elseif ( is_array($view_title) ) {
                    $title = $view_title;
                }
                return $title;
            }, 101);
        }

        $this->setFolder($this->getFolderPath());
        $file = $this->getComposedFilePath();
        $templateEngine = $this->viewsEngine ?? Config::get('app.templates.' . $context) ?? Config::get('app.templates.views');
        (new $templateEngine($file, $this->getData(), $context, $this))->load();
    }

    /**
     * Render View
     *
     * @param string|null $context the views context to use
     */
    public function render($context = null)
    {
        $context = $context ?? $this->getContext() ?? 'views';

        $this->load($context);
    }

    /**
     * Set Context
     *
     * Can be a key from config/paths like `views`.
     *
     * @param null|string $context the template engine context to use
     *
     * @return $this
     */
    public function setContext($context = null)
    {
        $this->context = $context ?? $this->getContext();

        return $this;
    }

    /**
     * @return null|string
     */
    public function getContext()
    {
        return $this->context ?? 'views';
    }

    /**
     * @param $folder
     *
     * @return $this
     */
    public function setFolder($folder) {
        $this->folder = $folder ?? $this->folder;

        return $this;
    }

    /**
     * @return null
     */
    public function getFolder() {
        return $this->folder;
    }

    /**
     * @return string
     */
    public function getComposedFilePath()
    {
        return $this->getFile() ?? $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getLocation();
    }

    /**
     * @return null|string
     */
    public function getFolderPath()
    {
        if(is_dir($this->folder)) {
            $folder = rtrim($this->folder, DIRECTORY_SEPARATOR);
        } else {
            $folder = rtrim(Config::get('paths.' . $this->getContext()), DIRECTORY_SEPARATOR);
        }

        return $folder;
    }

    /**
     * @param string $key
     * @param int $time cache forever by default
     * @param string $folder
     *
     * @return string|null
     */
    public function cache($key, $time = 9999999999, $folder = 'views')
    {
        return PersistentCache::new($folder)->getOtherwisePut($key, function() {
            return $this->toString();
        }, $time);
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return false|string
     */
    public function toString()
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }

}