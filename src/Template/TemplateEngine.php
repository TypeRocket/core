<?php


namespace TypeRocket\Template;


class TemplateEngine
{
    protected $file;
    protected $context;
    /**
     * @var array
     */
    protected $data;

    /**
     * TemplateEngine constructor.
     *
     * @param $file
     * @param array $data
     * @param string $context
     */
    public function __construct($file, array $data, $context = 'front')
    {
        $this->file = $file;
        $this->data = $data;
        $this->context = $context;
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
}