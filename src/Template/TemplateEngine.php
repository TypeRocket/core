<?php
namespace TypeRocket\Template;

class TemplateEngine
{
    protected $file;
    /** @var string  */
    protected $ext;
    protected $view;
    /** @var string  */
    protected $context;
    /** @var array  */
    protected $data;
    /**  @var array */
    protected $sections = [];
    protected $currentSection;
    protected $layout;

    /**
     * TemplateEngine constructor.
     *
     * @param $file
     * @param array $data
     * @param string $context
     * @param null|View $view
     */
    public function __construct($file, array $data, $context = 'views', $view = null)
    {
        $this->file = $file;
        $this->data = $data;
        $this->view = $view;
        $this->ext = $view->getExtension();
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string pages or views
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return View|null
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @return string|null
     */
    public function getExtension()
    {
        return $this->ext;
    }

    /**
     * Load Template
     */
    public function load()
    {
        extract( $this->data );
        /** @noinspection PhpIncludeInspection */
        include ( $this->file );
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
        $this->load();
        return ob_get_clean();
    }
}