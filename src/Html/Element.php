<?php
namespace TypeRocket\Html;

use TypeRocket\Elements\Dashicons;

class Element
{
    /**
     * Create new headline
     *
     * @param string $title
     * @param string $icon
     * @param array $attributes
     * @param string $tag
     *
     * @return Tag
     */
    public static function title($title, $icon = null, array $attributes = [], $tag = 'h3')
    {
        if($icon) {
            $icon = Dashicons::getIconHtml($icon);
        }

        $tag = new Tag($tag, array_merge(['class' => 'tr-headline'], $attributes));
        $tag->nest([
            $icon ? "<span class='icon'>{$icon}</span>" : null,
            "<span class='text'>" . esc_html($title) . '</span>'
        ]);

        return $tag;
    }

    /**
     * Accessible Close Button
     *
     * @param array $attributes
     * @param string $text close button symbol
     *
     * @return Tag
     */
    public static function controlButton(array $attributes = [], $text = '×') {
        $static = ['type' => 'button'];
        $tag = new Tag( 'button', array_merge(['aria-label' => __('Close', 'typerocket-domain')], $attributes, $static) );

        $tag->nest("<span aria-hidden=\"true\">{$text}</span>");

        return $tag;
    }
}