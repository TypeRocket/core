<?php
namespace TypeRocket\Models;

class WPAttachment extends WPPost
{
    protected $postType = 'attachment';

    /**
     * Get Attachment URL
     *
     * @return false|string
     */
    public function getUrl()
    {
        if ( !empty($this->dataCache['url_sizes']['full']) ) {
            return $this->dataCache['url_sizes']['full'];
        };

        return $this->dataCache['url_sizes']['full'] = wp_get_attachment_url($this->WP_Post()->ID);
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
        };

        $meta = maybe_unserialize($this->meta->_wp_attachment_metadata ?? null);
        $file = $meta['sizes'][$size]['file'] ?? null;
        $url = $this->getUrl();

        if(!$file) {
            return $file;
        }

        return $this->dataCache['url_sizes'][$size] = str_replace( wp_basename( $url ), $file, $url );
    }
}