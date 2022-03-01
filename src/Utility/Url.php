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
     * @param string $menu_slug
     * @param array|string $query
     *
     * @return $this
     */
    public function adminPage(string $menu_slug, $query = null)
    {
        global $_parent_pages;

        if(!$_parent_pages) {
            throw new \Exception('TypeRocket\Utility::adminPage() can not be called yet.');
        }

        $url = \menu_page_url($menu_slug, false);

        if($query) {
            $url .= '&' . (is_array($query) ? http_build_query($query) : (string) $query);
        }

        $this->setRoot($url);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $base = $this->root . ($this->path ? '/' . $this->path : '');
        $query = $this->query ? '?' . $this->query : '';

        return $base . $query;
    }

    /**
     * Get URL with Query Params
     *
     * @param string|Request $url
     * @param array $request_params
     *
     * @return string
     */
    public static function withQuery($url, $request_params = [])
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        $query = http_build_query(array_merge($query, $request_params));

        $map = [
            $parts['scheme'],
            '://',
            $parts['host'],
            !empty($parts['port']) ? ':'.$parts['port'] : null,
            $parts['path'],
            $query ? '?' : '',
            $query,
        ];

        return implode('', $map);
    }

    /**
     * Build Instance
     * @return Url
     */
    public static function build()
    {
        return new static(new Request);
    }
}