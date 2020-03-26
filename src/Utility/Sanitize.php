<?php
namespace TypeRocket\Utility;

class Sanitize
{

    /**
     * Sanitize a textarea input field. Removes bad html like <script> and <html>.
     *
     * @param string $input
     *
     * @return string
     */
    public static function textarea( $input )
    {
        global $allowedposttags;
        return wp_kses( $input, $allowedposttags );
    }

    /**
     * Sanitize nothing.
     *
     * @param string $input
     *
     * @return string
     */
    public static function raw( $input )
    {
        return $input;
    }

    /**
     * Sanitize Attribute.
     *
     * @param string $input
     *
     * @return string
     */
    public static function attribute( $input )
    {
        return esc_attr($input);
    }

    /**
     * Sanitize URL
     *
     * @param string $input
     *
     * @return string
     */
    public static function url( $input )
    {
        return esc_url($input);
    }

    /**
     * Sanitize SQL
     *
     * @param string $input
     *
     * @return string
     */
    public static function sql( $input )
    {
        return esc_sql($input);
    }

    /**
     * Sanitize text as plaintext.
     *
     * @param string $input
     *
     * @return string
     */
    public static function plaintext( $input )
    {
        return wp_kses( $input, [] );
    }

    /**
     * Sanitize editor data. Much like textarea remove <script> and <html>.
     * However, if the user can create unfiltered HTML allow it.
     *
     * @param string $input
     * @param bool $force_filter
     * @param bool $auto_p
     * @param null $allowed_tags
     *
     * @return string
     */
    public static function editor( $input, $force_filter = false, $auto_p = false, $allowed_tags = null )
    {
        if (current_user_can( 'unfiltered_html' ) && !$force_filter) {
            $output = trim($input);
        } else {
            global $allowedtags;
            $output =  wp_kses( trim($input), apply_filters('tr_sanitize_editor_tags', $allowed_tags ?? $allowedtags) );
        }

        if($auto_p) {
            $output = wpautop($output);
        }

        return $output;
    }

    /**
     *
     *
     * @param string $input HTML input
     * @param null|array $allowed_tags allowed tags for wp_kses
     * @param null|string $namespace
     * @param bool $auto_p
     *
     * @return string
     */
    public static function html($input, $allowed_tags = null, $namespace = null, $auto_p = false) {
        $tags = apply_filters('tr_sanitize_html_tags_' . ($namespace ?? 'default'), $allowed_tags ?? [
                'em' => [],
                'strong' => [],
                'ul' => [],
                'ol' => [],
                'li' => [],
                'p' => [],
                'br' => [],
            ]);
        $output =  trim(wp_kses(trim($input), $tags));

        if($auto_p) {
            $output = wpautop($output);
        }

        return $output;
    }

    /**
     * Sanitize Hex Color Value
     *
     * If the hex does not validate return a default instead.
     *
     * @param string $hex
     * @param string $default
     *
     * @return string
     */
    public static function hex( $hex, $default = '#000000' )
    {
        if ( preg_match("/^\#?([a-fA-F0-9]{3}){1,2}$/", $hex ) ) {
            return $hex;
        }

        return $default;
    }

    /**
     * Sanitize Underscore
     *
     * Remove all special characters and replace spaces and dashes with underscores
     * allowing only a single underscore after trimming whitespace form string and
     * lower casing
     *
     * ` --"2_ _e''X  AM!pl'e-"-1_@` -> _2_ex_ample_1_
     *
     * @param string $name
     * @param bool $keep_dots
     *
     * @return mixed|string
     */
    public static function underscore( $name, $keep_dots = false )
    {
        if (is_string( $name )) {

            if($keep_dots) {
                $name = preg_replace( '/[\.]+/', '.', $name );
                $name = preg_replace("/[^A-Za-z0-9\.\\s\\-\\_?]/",'', strtolower(trim($name)) );
            } else {
                $name = preg_replace( '/[\.]+/', '_', $name );
                $name = preg_replace("/[^A-Za-z0-9\\s\\-\\_?]/",'', strtolower(trim($name)) );
            }


            $name = preg_replace( '/[-\\s]+/', '_', $name );
            $name = preg_replace( '/_+/', '_', $name );
        }

        return $name;
    }

    /**
     * Sanitize Dash
     *
     * Remove all special characters and replace spaces and underscores with dashes
     * allowing only a single dash after trimming whitespace form string and
     * lower casing
     *
     * ` --"2_ _e\'\'X  AM!pl\'e-"-1_@` -> -2-ex-ample-1-
     *
     * @param string $name
     *
     * @return mixed|string
     */
    public static function dash( $name )
    {
        if (is_string( $name )) {
            $name = preg_replace( '/[\.]+/', '_', $name );
            $name = preg_replace("/[^A-Za-z0-9\\s\\-\\_?]/",'', strtolower(trim($name)) );
            $name = preg_replace( '/[_\\s]+/', '-', $name );
            $name = preg_replace( '/-+/', '-', $name );
        }

        return $name;
    }

}