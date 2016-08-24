<?php

namespace TypeRocket\Template;

use TypeRocket\Core\Config;

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
            $dots = explode('.', $dots);
            self::$page = Config::getPaths()['pages'] . '/' . implode('/', $dots) . '.php';
            self::$view =  Config::getPaths()['views'] . '/' . implode('/', $dots) . '.php';
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
     * @param $title
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

}