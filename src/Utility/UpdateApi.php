<?php


namespace TypeRocket\Utility;


class UpdateApi
{
    /**
     * @var string
     */
    public $slug;
    /**
     * @var array
     */
    public $settings;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    public $url;
    /**
     * @var string
     */
    public $slug_underscored;
    /**
     * @var string
     */
    public $locate;

    /**
     * UpdateApi
     *
     * @param string $slug plugin of theme slug / id
     * @param string $url API info url
     * @param string $locate my-plugin.php or my-plugin/my-plugin.php or my-theme
     * @param array $settings
     * @param string $type set to plugin or theme
     */
    public function __construct($slug, $url, $locate, $settings = [], $type = 'plugin')
    {
        $this->slug = $slug;
        $this->slug_underscored = Sanitize::underscore($slug);
        $this->settings = $settings;
        $this->type = $type;
        $this->url = $url;
        $this->locate = $locate;
    }
}