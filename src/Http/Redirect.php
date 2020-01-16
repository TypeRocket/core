<?php

namespace TypeRocket\Http;

use TypeRocket\Core\Injector;
use TypeRocket\Models\Model;

class Redirect
{
    public $url;

    /**
     * @param array $data
     *
     * @return Redirect $this
     */
    public function with( $data ) {

        if( !empty( $data ) ) {
            $cookie = new Cookie();
            $cookie->setTransient('tr_redirect_data', $data);
        }

        return $this;
    }

    /**
     * @param array|Fields $fields
     * @param array $notFields
     *
     * @return \TypeRocket\Http\Redirect $this
     */
    public function withFields( $fields, $notFields = [] ) {

        if( $fields instanceof Fields) {
            $fields = $fields->getArrayCopy();
        }

        if( !empty( $fields ) ) {
            $cookie = new Cookie();
            $send = array_diff_key($fields, array_flip($notFields));
            $cookie->setTransient('tr_old_fields', $send);
        }

        return $this;
    }

    /**
     * Redirect to Home URL
     *
     * @param string $path
     *
     * @param null|string $schema
     * @return Redirect $this
     */
    public function toHome( $path = '', $schema = null)
    {
        $this->url = esc_url_raw( home_url( $path ), $schema ?: (is_ssl() ? 'https' : 'http') );

        return $this;
    }

    /**
     * To Home URL
     *
     * @param string $path
     * @return Redirect
     * @deprecated 4.0.46
     */
    public function onHome( $path = '') {
        return $this->toHome($path);
    }

    /**
     * To Site URL
     *
     * @param string $path
     * @param null|string $schema
     * @return $this
     */
    public function toSite($path = '', $schema = null)
    {
        $this->url = get_site_url(null, $path, $schema ?: (is_ssl() ? 'https' : 'http') );

        return $this;
    }

    /**
     * @param string $resource
     * @param string $action
     * @param null $item_id
     *
     * @return Redirect $this
     */
    public function toPage($resource, $action, $item_id = null)
    {
        $query = [];
        $query['page'] = $resource . '_' . $action;

        if($item_id) {
            $query['route_id'] = (int) $item_id;
        }

        $this->url = admin_url('/') . 'admin.php?' . http_build_query($query);

        return $this;
    }

    /**
     * To Admin
     *
     * @param string $path
     * @param array $query
     * @return Redirect
     */
    public function toAdmin($path, $query = [])
    {
        $this->url = admin_url('/') . $path;

        if(!empty($query)) {
            $this->url .= '?' . http_build_query($query);
        }

        return $this;
    }

    /**
     * Redirect to URL
     *
     * @param string $url
     *
     * @return Redirect $this
     */
    public function toUrl( $url ) {
        $this->url = esc_url_raw($url);

        return $this;
    }

    /**
     * To Named Route
     *
     * @param string $name
     * @param array|Model $values
     * @param bool $site
     * @param null|RouteCollection $routes
     *
     * @return $this
     */
    public function toRoute($name, $values, $site = true, $routes = null)
    {
        /** @var ApplicationRoutes $routes */
        $routes = $routes ?? Injector::resolve(RouteCollection::class);
        $located = $routes->getNamedRoute($name);

        if($values instanceof Model) {
            $values = $values->getProperties();
        }

        $this->url = $located->buildUrlFromPattern($values, $site);

        return $this;
    }

    /**
     * Redirect back to referrer
     *
     * Must be the same host
     *
     * @return Redirect $this
     */
    public function back()
    {
        $ref = $_SERVER['HTTP_REFERER'];
        $scheme = is_ssl() ? 'https' : 'http';
        $same_host = home_url( '/', $scheme );
        if( substr($ref, 0, strlen($same_host)) === $same_host ) {
            $this->url = $ref;
        } else {
            $this->url = home_url('/', $scheme);
        }

        return $this;
    }

    /**
     * Run the redirect
     */
    public function now() {
        wp_redirect( $this->url );
        exit();
    }
}