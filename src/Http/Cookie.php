<?php
namespace TypeRocket\Http;

use TypeRocket\Utility\Sanitize;

class Cookie
{
    /**
     * Set a transient with cookie to persist across page loads
     *
     * @param string $name name of transient
     * @param string|array $data
     * @param int $time
     *
     * @return $this
     */
    public function setTransient( $name, $data, $time = MINUTE_IN_SECONDS ) {
        $cookie_id = Sanitize::underscore( uniqid() . time() . uniqid() );
        $this->set($name, $cookie_id);
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
        setcookie($name, $data, $time === null ? 0 : time() + $time, $path, null, is_ssl());

        return $this;
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
     *
     * @return null
     */
    public function get( $name ) {
        $data = null;

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
        if( !empty($_COOKIE['tr_old_fields']) ) {
            return (new Cookie)->getTransient('tr_old_fields', $delete);
        }

        return null;
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