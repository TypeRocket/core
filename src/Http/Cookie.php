<?php

namespace TypeRocket\Http;

use TypeRocket\Utility\Sanitize;

class Cookie
{

    /**
     * Set a transient with cookie to persist across page loads
     *
     * @param string $name
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
     * @param string $name
     * @param bool $delete
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
            }

            if (!headers_sent()) {
                $this->delete($name);
            }
        }

        return $data;
    }

    /**
     * Set a cookie
     *
     * @param string $name
     * @param string $data
     * @param int $time
     *
     * @return $this
     */
    public function set( $name, $data, $time = MINUTE_IN_SECONDS ) {
        setcookie($name, $data, time() + $time, '/', null, is_ssl());

        return $this;
    }

    /**
     * Delete a cookie
     *
     * Only call if headers are not sent yet
     *
     * @param string $name
     *
     * @return $this
     */
    public function delete( $name ) {
        setcookie($name, "", time() - 36000);

        return $this;
    }

    /**
     * Get a cookie
     *
     * @param string $name
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
     * @return string|null
     */
    public function oldFields() {
        if( !empty($_COOKIE['tr_old_fields']) ) {
            return (new Cookie())->getTransient('tr_old_fields');
        }

        return null;
    }
}