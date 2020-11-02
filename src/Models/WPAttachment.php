<?php
namespace TypeRocket\Models;

use TypeRocket\Html\Html;

class WPAttachment extends WPPost
{
    public const POST_TYPE = 'attachment';

    /**
     * Get Attachment URL
     *
     * @return false|string
     */
    public function getUrl()
    {
        if ( !empty($this->dataCache['url_sizes']['full']) ) {
            return $this->dataCache['url_sizes']['full'];
        }

        return $this->dataCache['url_sizes']['full'] = wp_get_attachment_url($this->wpPost()->ID);
    }

    /**
     * Get URL by Size
     *
     * @param string $size
     * @return string|mixed
     */
    public function getUrlSize($size)
    {
        if ( !empty($this->dataCache['url_sizes'][$size]) ) {
            return $this->dataCache['url_sizes'][$size];
        }

        $meta = maybe_unserialize($this->meta->_wp_attachment_metadata ?? null);
        $file = $meta['sizes'][$size]['file'] ?? wp_basename($meta['file']) ?? null;
        $url = $this->getUrl();

        if(!$file) {
            return $file;
        }

        return $this->dataCache['url_sizes'][$size] = str_replace( wp_basename( $url ), $file, $url );
    }

    /**
     * Get Image Tag
     *
     * @param $size
     * @param array $attr
     *
     * @return Html
     */
    public function getImage($size, $attr = [])
    {
        $src = $this->getUrlSize($size);
        return Html::img($src, array_merge(['alt' => $this->post_excerpt ?? 'Photo'], $attr));
    }
}