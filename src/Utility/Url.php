<?php


namespace TypeRocket\Utility;


use TypeRocket\Http\Request;

class Url
{
    /**
     * @var Request
     */
    protected $request;
    protected $root;
    protected $path;
    protected $query;

    /**
     * Url constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Admin as Root
     *
     * @param string $schema
     * @return $this
     */
    public function admin($schema = 'admin')
    {
        $this->root = admin_url('', $schema);

        return $this;
    }

    /**
     * Front as Root
     *
     * @param null|string $schema
     * @return $this
     */
    public function front($schema = null)
    {
        $this->root = get_site_url(null, '', $schema ?: (is_ssl() ? 'https' : 'http') );

        return $this;
    }

    /**
     * Home as Root
     *
     * @param null|string $schema
     * @return $this
     */
    public function home($schema = null)
    {
        $this->root = get_home_url(null, '', $schema ?: (is_ssl() ? 'https' : 'http') );

        return $this;
    }

    /**
     * Set Query
     *
     * @param array $params
     * @param bool $existing
     * @return $this
     */
    public function setQuery(array $params, $existing = true)
    {
        if($existing) {
            $params = array_merge( $this->request->getQueryAsArray(), $params );
        }

        $this->query = http_build_query($params);

        return $this;
    }

    /**
     * Set Path
     *
     * @param string $path
     * @param bool $existing
     * @return $this
     */
    public function setPath($path, $existing = true)
    {
        if($existing) {
            $path = $this->request->getPath() . '/' . ltrim($path, '/');
        }

        $this->path = ltrim($path, '/');

        return $this;
    }

    /**
     * Set Root
     *
     * @param $root
     * @return $this
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $base = $this->root . '/' . $this->path;
        $query = $this->query ? '?' . $this->query : '';

        return $base . $query;
    }

    /**
     * Build Instance
     * @return Url
     */
    public static function build()
    {
        return new static(new Request());
    }
}