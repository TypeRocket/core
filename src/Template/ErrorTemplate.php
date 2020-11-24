<?php
namespace TypeRocket\Template;

class ErrorTemplate
{
    /**
     * Error Template
     *
     * ErrorTemplate constructor.
     *
     * @param $code
     * @param bool $templates
     */
    public function __construct($code, $templates = false)
    {
        $caller = function($template) use ($code) {
            global $wp_query;
            $wp_query->set_404();

            $new = get_query_template( $code );

            if(file_exists($new)) {
                $template = $new;
            } else {
                $template = get_query_template( 404 );
            }

            $title = get_status_header_desc($code);

            add_filter( 'document_title_parts', function($parts) use ($title) {
                $parts['title'] = $title;
                return $parts;
            }, 101, 3 );

            add_filter('body_class', function($classes) use ($code) {
                array_push($classes, 'tr-error'.$code); return $classes;
            });
            return $template;
        };

        if($templates) {
            $temp = $caller(null);
            $temp = apply_filters( 'template_include', $temp );
            /** @noinspection PhpIncludeInspection */
            include $temp;
        } else {
            add_action( 'template_include', $caller, 100);
        }
    }
}