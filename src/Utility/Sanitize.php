<?php
namespace TypeRocket\Utility;

class Sanitize
{

    /**
     * Sanitize a textarea input field. Removes bad html like <script> and <html>.
     *
     * @param $input
     *
     * @return string
     */
    public static function textarea( $input )
    {
        global $allowedposttags;
        $output = wp_kses( $input, $allowedposttags );

        return $output;
    }

    /**
     * Sanitize nothing.
     *
     * @param $input
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
     * @param $input
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
     * @param $input
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
     * @param $input
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
     * @param $input
     *
     * @return string
     */
    public static function plaintext( $input )
    {
        $output = wp_kses( $input, array() );

        return $output;
    }

    /**
     * Sanitize editor data. Much like textarea remove <script> and <html>.
     * However, if the user can create unfiltered HTML allow it.
     *
     * @param $input
     *
     * @return string
     */
    public static function editor( $input )
    {
        if (current_user_can( 'unfiltered_html' )) {
            $output = $input;
        } else {
            global $allowedtags;
            $output = wpautop( wp_kses( $input, $allowedtags ) );
        }

        return $output;
    }

    /**
     * Sanitize Hex Color Value
     *
     * If the hex does not validate return a default instead.
     *
     * @param $hex
     * @param string $default
     *
     * @return string
     */
    public static function hex( $hex, $default = '#000000' )
    {
        if ( preg_match("/^\#?([a-f0-9]{3}){1,2}$/", $hex ) ) {
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
     * @param $name
     *
     * @return mixed|string
     */
    public static function underscore( $name )
    {
        if (is_string( $name )) {
            $name = preg_replace( '/[\.]+/', '_', $name );
            $name = preg_replace("/[^A-Za-z0-9\\s\\-\\_?]/",'', strtolower(trim($name)) );
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
     * @param $name
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