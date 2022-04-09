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
     * @param null|array $allowed_tags
     *
     * @return string
     */
    public static function editor( $input, $force_filter = false, $auto_p = false, $allowed_tags = null )
    {
        if (current_user_can( 'unfiltered_html' ) && !$force_filter) {
            $output = trim($input);
        } else {
            global $allowedtags;
            $output =  wp_kses( trim($input), apply_filters('typerocket_sanitize_editor_tags', $allowed_tags ?? $allowedtags) );
        }

        if($auto_p) {
            $output = wpautop($output);
        }

        return $output;
    }

    /**
     * Sanitizes content for allowed HTML tags for post content.
     *
     * @param string $input Post content to filter.
     * @return string Filtered post content with allowed HTML tags and attributes intact.
     */
    public static function post($input)
    {
        return wp_kses_post($input);
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
    public static function htmlGuest($input, $allowed_tags = null, $namespace = null, $auto_p = false) {
        $tags = apply_filters('typerocket_sanitize_html_guest_tags_' . ($namespace ?? 'default'), $allowed_tags ?? [
                'em' => [],
                'strong' => [],
                'small' => [],
                'sub' => [],
                'sup' => [],
                'b' => [],
                'i' => [],
                'ul' => [],
                'ol' => [],
                'hgroup' => [],
                'h1' => [],
                'h2' => [],
                'h3' => [],
                'h4' => [],
                'h5' => [],
                'h6' => [],
                'table' => [],
                'tbody' => [],
                'tfoot' => [],
                'thead' => [],
                'dd' => [],
                'dt' => [],
                'dl' => [],
                'tr' => [],
                'th' => [],
                'td' => [],
                'li' => [],
                'blockquote' => [],
                'cite' => [],
                'code' => [],
                'hr' => [],
                'p' => [],
                'br' => [],
            ]);
        $output = trim(wp_kses(trim($input), $tags));

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
        $tags = apply_filters('typerocket_sanitize_html_tags_' . ($namespace ?? 'default'), $allowed_tags ?? [
            'em' => ['class' => true],
            'strong' => ['class' => true],
            'small' => [],
            'sub' => ['class' => true],
            'sup' => ['class' => true],
            'b' => [],
            'i' => [],
            'ul' => ['class' => true],
            'ol' => ['class' => true],
            'hgroup' => ['class' => true],
            'h1' => ['class' => true, 'id' => true],
            'h2' => ['class' => true, 'id' => true],
            'h3' => ['class' => true, 'id' => true],
            'h4' => ['class' => true, 'id' => true],
            'h5' => ['class' => true, 'id' => true],
            'h6' => ['class' => true, 'id' => true],
            'table' => ['class' => true],
            'tbody' => ['class' => true],
            'tfoot' => ['class' => true],
            'thead' => ['class' => true],
            'dd' => ['class' => true],
            'dt' => ['class' => true],
            'dl' => ['class' => true],
            'tr' => ['class' => true],
            'th' => ['class' => true],
            'td' => ['class' => true],
            'figure' => ['class' => true],
            'figcaption' => ['class' => true],
            'caption' => ['class' => true],
            'img' => [
                'src' => true,
                'alt' => true,
                'class' => true,
            ],
            'video'      => [
                'autoplay'    => true,
                'controls'    => true,
                'height'      => true,
                'loop'        => true,
                'muted'       => true,
                'playsinline' => true,
                'poster'      => true,
                'preload'     => true,
                'src'         => true,
                'width'       => true,
                'class'       => true
            ],
            'a' => [
                'href'      => true,
                'title'     => true,
                'rev'       => true,
                'rel'       => true,
                'target'    => true,
                'class'     => true,
                'download'  => ['valueless' => 'y'],
            ],
            'li' => ['class' => true],
            'blockquote' => ['class' => true],
            'cite' => ['class' => true],
            'code' => ['class' => true],
            'hr' => [],
            'p' => ['class' => true],
            'br' => [],
        ]);
        $output =  trim(wp_kses(trim($input), $tags));

        if($auto_p) {
            $output = wpautop($output);
        }

        return $output;
    }

    /**
     * Sanitize Html with Encoding
     *
     * @param string $input
     *
     * @return string
     */
    public static function encodeHtml($input)
    {
        return esc_html($input);
    }

    /**
     * Sanitize Attribute with Encoding
     *
     * @param string $input
     *
     * @return string
     */
    public static function encodeAttribute($input)
    {
        return esc_attr($input);
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
    public static function dash( $name, $keep_dots = false )
    {
        if (is_string( $name )) {

            if($keep_dots) {
                $name = preg_replace( '/[\.]+/', '.', $name );
                $name = preg_replace("/[^A-Za-z0-9\.\\s\\-\\_?]/",'', strtolower(trim($name)) );
            } else {
                $name = preg_replace( '/[\.]+/', '_', $name );
                $name = preg_replace("/[^A-Za-z0-9\\s\\-\\_?]/",'', strtolower(trim($name)) );
            }

            $name = preg_replace( '/[_\\s]+/', '-', $name );
            $name = preg_replace( '/-+/', '-', $name );
        }

        return $name;
    }

}