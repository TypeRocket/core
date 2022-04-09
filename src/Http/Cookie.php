<?php
namespace TypeRocket\Http;

use TypeRocket\Utility\Data;
use TypeRocket\Utility\Sanitize;

class Cookie
{
    /**
     * Set a transient with cookie to persist across page loads
     *
     * @param string $name name of transient
     * @param string|array $data value of transient
     * @param int $time expire time in seconds
     * @param string $path path cookie can be access from. `/` == all paths
     *
     * @return $this
     */
    public function setTransient( $name, $data, $time = MINUTE_IN_SECONDS, $path = '/' ) {
        $cookie_id = Sanitize::underscore( uniqid() . time() . uniqid() );

        if($path !== null) {
            $this->set($name, $cookie_id, $time, $path);
        }

        set_transient( $name . '_' . $cookie_id, $data, $time );

        return $this;
    }

    /**
     * Get the transient and delete it
     *
     * @param string $name name of transient
     * @param bool $delete delete cookie and transient
     *
     * @return mixed|null
     */
    public function getTransient( $name, $delete = true ) {
        $data = null;

        if( !empty($_COOKIE[$name]) ) {
            $id   = Sanitize::underscore($_COOKIE[$name]);
            $data = get_transient($name . '_' . $id);

            if($delete) {
                delete_transient($name . '_' . $id);

                if(!headers_sent()) {
                    $this->delete($name);
                }
            }
        }

        return $data;
    }

    /**
     * Get the transient and delete it
     *
     * @param string $name name of transient
     *
     * @return null|true
     */
    public function deleteTransient( $name ) {
        $deleted = null;

        if( !empty($_COOKIE[$name]) ) {
            $id   = Sanitize::underscore($_COOKIE[$name]);
            delete_transient($name . '_' . $id);

            if(!headers_sent()) {
                $this->delete($name);
            }

            $deleted = true;
        }

        return $deleted;
    }

    /**
     * Set a cookie
     *
     * @param string $name name of cookie
     * @param string $data value of cookie
     * @param int|null $time expire time in seconds
     * @param string $path path cookie can be access from. `/` == all paths
     *
     * @return $this
     */
    public function set( $name, $data, $time = MINUTE_IN_SECONDS, $path = '/' ) {
        if(\PHP_VERSION_ID >= 70300) {
            setcookie($name, $data, apply_filters('typerocket_cookie_options', [
                'expires' => $time === null ? 0 : time() + $time,
                'path' => $path,
                'domain' => null,
                'secure' => is_ssl()
            ], $name, $data, $this));
        } else {
            setcookie($name, $data, $time === null ? 0 : time() + $time, $path, null, is_ssl());
        }

        return $this;
    }

    /**
     * Get a cookie otherwise set one cookie
     *
     * @param string $name name of cookie
     * @param string $data value of cookie
     * @param int|null $time expire time in seconds
     * @param string $path path cookie can be access from. `/` == all paths
     *
     * @return string|null
     */
    public function getOtherwiseSet($name, $data, $time = MINUTE_IN_SECONDS, $path = '/')
    {
        if($content = $this->get($name)) {
            return $content;
        }

        $this->set(...func_get_args());
    }

    /**
     * Delete a cookie
     *
     * Only call if headers are not sent yet
     *
     * @param string $name name of cookie
     * @param string $path path cookie can be access from. `/` == all paths
     *
     * @return $this
     */
    public function delete( $name, $path = '/' ) {
        setcookie($name, "", time() - 36000, $path, null, is_ssl());

        return $this;
    }

    /**
     * Get a cookie
     *
     * @param string $name name of cookie
     * @param null|string $default
     *
     * @return null
     */
    public function get( $name, $default = null ) {
        $data = $default;

        if( !empty($_COOKIE[$name]) ) {
            $data = wp_unslash($_COOKIE[$name]);
        }

        return $data;
    }

    /**
     * Get old stored fields
     *
     * @param bool $delete
     *
     * @return string|null
     */
    public function oldFields($delete = true) {
        if( !empty($_COOKIE[Redirect::KEY_OLD]) ) {
            return $this->getTransient(Redirect::KEY_OLD, $delete);
        }

        return null;
    }

    /**
     * @param string $name the name of the field
     * @param string $default a default value
     * @param bool $delete should delete old data when getting the last field
     *
     * @return string
     */
    function oldField($name, $default = '', $delete = false)
    {
        return Data::walk($name, $this->getTransient(Redirect::KEY_OLD, $delete), $default);
    }

    /**
     * @return bool
     */
    function oldFieldsRemove()
    {
        $this->getTransient(Redirect::KEY_OLD, true);

        return ! (bool) $this->getTransient(Redirect::KEY_OLD);
    }

    /**
     * @param null|array $default
     * @param bool $delete
     *
     * @return array
     */
    public function redirectMessage($default = null, $delete = true)
    {
        $data = $this->getTransient(Redirect::KEY_MESSAGE, $delete);
        return ! is_null($data) ? $data : $default;
    }

    /**
     * @param null|array $default
     *
     * @return array
     */
    function redirectErrors($default = null)
    {
        $errors = \TypeRocket\Http\ErrorCollection::getFromRuntimeCache();
        return !is_null($errors) ? $errors->errors() : $default;
    }

    /**
     * @param null|array $default
     * @param bool $delete
     *
     * @return array
     */
    function redirectData($default = null, $delete = true)
    {
        $data = $this->getTransient(Redirect::KEY_DATA, $delete);
        return ! is_null($data) ? $data : $default;
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