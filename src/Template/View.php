<?php

namespace TypeRocket\Template;

use TypeRocket\Core\Config;
use TypeRocket\Register\Page;

class View
{
    static public $data = [];
    static public $title = null;
    static public $page = null;
    static public $view = null;

    /**
     * View constructor.
     *
     * Take a custom file location or dot notation of view location.
     *
     * @param string $dots dot syntax or specific file path
     * @param array $data
     */
    public function __construct( $dots , array $data = [] )
    {
        if( file_exists( $dots ) ) {
            self::$page = $dots;
            self::$view = $dots;
        } else {
            $location = implode('/', explode('.', $dots) );
            self::$page = Config::locate('paths.pages') . '/' . $location . '.php';
            self::$view =  Config::locate('paths.views') . '/' . $location . '.php';
        }

        if( !empty( $data ) ) {
            self::$data = $data;
        }
    }

    /**
     * Get the file
     *
     * This is used for admin pages
     *
     * @return null|string
     */
    public function getPage() {
        return self::$page;
    }

    /**
     * Get the template
     *
     * This is used for front-end views
     *
     * @return null|string
     */
    public function getView() {
        return self::$view;
    }

    /**
     * Get the data attached to a view.
     *
     * @return array
     */
    public function getData()
    {
        return self::$data;
    }

    /**
     * Set the title attached to a view.
     *
     * Requires https://codex.wordpress.org/Title_Tag support
     *
     * @param string $title
     *
     * @return \TypeRocket\Template\View
     */
    public function setTitle( $title )
    {
        self::$title = $title;

        return $this;
    }

    /**
     * Get the title attached to a view.
     *
     * @return array
     */
    public function getTitle()
    {
        return self::$title;
    }

    /**
     * View Is Ready
     * @param string $context
     *
     * @return bool
     */
    public static function isReady($context = 'front')
    {
        if($context == 'front' && file_exists( self::$view ) ) {
            return true;
        }

        return $context == 'admin' && file_exists( View::$page );
    }

    /**
     *  Load the template for the front-end without globals
     */
    public static function load() {
        add_filter('document_title_parts', function( $title ) {
            if( is_string(self::$title) ) {
                $title = [];
                $title['title'] = self::$title;
            } elseif ( is_array(self::$title) ) {
                $title = self::$title;
            }
            return $title;
        }, 101);

        if(is_admin()) {
            // not yet
            return;
        }

        $templateEngine = Config::locate('app.template_engine.front') ?? TemplateEngine::class;
        (new $templateEngine(self::$view, self::$data))->load();
    }

    /**
     * Load Page
     */
    public static function loadPage()
    {
        $templateEngine = Config::locate('app.template_engine.admin') ??  TemplateEngine::class;
        (new $templateEngine(self::$page, self::$data, 'admin'))->load();
    }


  /**
   * Render a given view template
   *
   * @return string
   */
  public function renderView()
  {
    $buffer = tr_buffer()->startBuffer();
    extract( $this->getData() );
    include $this->getView();
    $view   = $buffer->getCurrent();
    $buffer->cleanBuffer();

    return $view;
  }
}
