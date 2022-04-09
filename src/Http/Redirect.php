<?php
namespace TypeRocket\Http;

use TypeRocket\Core\Container;
use TypeRocket\Models\Model;
use TypeRocket\Utility\Str;

class Redirect
{
    public $url;
    public const KEY_DATA = 'typerocket_redirect_data';
    public const KEY_ERROR = 'typerocket_redirect_errors';
    public const KEY_MESSAGE = 'typerocket_redirect_message';
    public const KEY_OLD = 'typerocket_old_fields';
    public const KEY_ADMIN = 'typerocket_redirect_flash';

    /**
     * Redirect with data
     *
     * @param array $data
     *
     * @return Redirect
     */
    public function withData($data)
    {
        if( !empty($data) ) {
            (new Cookie)->setTransient(static::KEY_DATA, $data);
        }

        return $this;
    }

    /**
     * With with data
     *
     * @param array|null $errors
     *
     * @return Redirect $this
     */
    public function withErrors( $errors = null)
    {
        if( !empty( $errors ) ) {
            (new Cookie)->setTransient(ErrorCollection::KEY, $errors);
        }

        return $this;
    }

    /**
     * With Message
     *
     * @param string $message
     * @param string $type options: success, error, warning, and info
     *
     * @return $this
     */
    public function withMessage($message, $type = 'success')
    {
        if(!empty($message) && is_string($message)) {
            (new Cookie)->setTransient(static::KEY_MESSAGE, ['message' => $message, 'type' => $type]);
        }

        return $this;
    }

    /**
     * Redirect with old fields
     *
     * @param array|Fields $fields
     * @param array $notFields
     *
     * @return Redirect
     */
    public function withOldFields($fields = null, $notFields = [])
    {
        $fields = $fields ?? (new Request)->getFields();

        if($fields instanceof Fields) {
            $fields = $fields->getArrayCopy();
        }

        if( !empty($fields) ) {
            $send = array_diff_key($fields, array_flip($notFields));
            (new Cookie)->setTransient(static::KEY_OLD, $send);
        }

        return $this;
    }

    /**
     * With Field Errors
     *
     * @param array $fields array of inline field errors that match field names
     * @param string $key
     *
     * @return $this
     */
    public function withFieldErrors(array $fields, $key = 'fields')
    {
        return $this->withErrors([$key => $fields]);
    }

    /**
     * Redirect to Home URL
     *
     * @param string $path
     *
     * @param null|string $schema
     * @return Redirect
     */
    public function toHome( $path = '', $schema = null)
    {
        $this->url = get_home_url( null, $path, $schema ?: (is_ssl() ? 'https' : 'http') );

        return $this;
    }

    /**
     * To Site URL
     *
     * @param string $path
     * @param null|string $schema
     * @return Redirect
     */
    public function toSite($path = '', $schema = null)
    {
        $this->url = get_site_url(null, $path, $schema ?: (is_ssl() ? 'https' : 'http') );

        return $this;
    }

    /**
     * @param string $resource
     * @param string $action
     * @param null|string|int $item_id
     * @param string $root_path
     *
     * @return Redirect $this
     */
    public function toPage($resource, $action = null, $item_id = null, $root_path = 'admin.php')
    {
        $query = [];
        $query['page'] = $resource . ( $action ? '_' . $action : null);

        if(is_array($item_id)) {
            $query = array_merge($query, $item_id);
        } elseif($item_id) {
            $query['route_args'] = [$item_id];
        }

        $this->url = admin_url('/') . $root_path . '?' . http_build_query($query);

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
     * @return Redirect
     */
    public function toUrl( $url )
    {
        $this->url = $url;

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
     * @return Redirect
     */
    public function toRoute($name, $values = [], $site = true, $routes = null)
    {
        /** @var RouteCollection $routes */
        $routes = $routes ?? Container::resolve(RouteCollection::class);
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
     * @param bool $self
     * @param bool $force
     *
     * @return Redirect
     */
    public function back($self = false, $force = false)
    {
        $ref = (new Request)->getReferer($self);

        if($force) {
            $this->url = $ref;
        }
        else {
            $scheme = is_ssl() ? 'https' : 'http';
            $same_host = home_url( '/', $scheme );
            if( Str::starts($same_host, $ref) ) {
                $this->url = $ref;
            } else {
                $this->url = get_site_url(null, '/', $scheme);
            }
        }

        return $this;
    }

    /**
     * Redirect back to referrer if no URL is set
     *
     * Must be the same host
     *
     * @param false $self
     * @param false $force
     *
     * @return $this
     */
    public function maybeBack($self = false, $force = false)
    {
        if(!$this->url) {
            return $this->back($self, $force);
        }

        return $this;
    }

    /**
     * Flash message on next request
     *
     * When the request is marked as _tr_ajax_request transient is not
     * set by default.
     *
     * @param string $message
     * @param string $type options: success, error, warning, and info
     * @param bool $force_transient
     *
     * @return $this
     */
    public function withFlash($message, $type = 'success', $force_transient = false)
    {
        \TypeRocket\Http\Response::getFromContainer()->flashNext($message, $type, $force_transient);

        return $this;
    }

    /**
     * Run the redirect
     */
    public function now()
    {
        wp_redirect( $this->url );
        exit();
    }

    /**
     *
     * Get Url
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }
}