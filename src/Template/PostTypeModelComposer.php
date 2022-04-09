<?php
namespace TypeRocket\Template;

use TypeRocket\Html\Html;
use TypeRocket\Models\WPPost;

class PostTypeModelComposer extends Composer
{
    /** @var array|WPPost $data */
    protected $data;

    /**
     * @return string|string[]
     */
    public function content()
    {
        $content = get_the_content(null, false, $this->data->wpPost());
        $content = apply_filters( 'the_content', $content );
        $content = str_replace( ']]>', ']]&gt;', $content );
        return $content;
    }

    /**
     * @return string
     */
    public function permalink()
    {
        return $this->data->permalink();
    }

    /**
     * @return string
     */
    public function excerpt()
    {
        return get_the_excerpt($this->data->wpPost());
    }

    /**
     * @return string
     */
    public function title()
    {
        return get_the_title($this->data->wpPost());
    }

    /**
     * @param string $d
     *
     * @return false|int|string
     */
    public function publishedOn($d = 'F j, Y') {
        return get_post_time($d, false, $this->data->wpPost());
    }

    /**
     * @param string $size
     * @param string $classes
     * @param false $from_cache
     *
     * @return mixed|string
     */
    public function featuredImage($size = 'thumbnail', $classes = '', $from_cache = false)
    {
        if($from_cache && $this->data->meta->_thumbnail_id) {
            $img = \TypeRocketPro\Utility\ImageCache::attachmentSrc($this->data->meta->_thumbnail_id, $size);

            if($img) {
                return $img;
            }
        }

        return get_the_post_thumbnail($this->data->wpPost(), $size, ['class' => $classes]);
    }

    /**
     * @param null|string $name
     * @param bool $url
     *
     * @return string|Html|null
     */
    public function authorLink($name = null, $url = true)
    {
        $author_id = $this->data->post_author;

        if(!$name) {
            $name = get_userdata($author_id)->display_name;
        }

        if(!$url) {
            $url = get_author_posts_url($author_id);
        }

        if(!$url) {
            return $name;
        }

        return Html::a($name, $url);
    }

    /**
     * @param null|string $text
     * @param string $classes
     */
    public function editLink($text = null, $classes = 'post-edit-link')
    {
        return edit_post_link($text, '', '', $this->data->wpPost(), $classes);
    }

    /**
     * @param int $size
     * @param string $alt
     * @param string $classes
     *
     * @return false|mixed|void
     */
    public function authorAvatar($size = 100, $alt = '', $classes = '')
    {
        return get_avatar($this->data->post_author, $size, '', $alt, ['class' => $classes] );
    }

    /**
     * @return false|string
     */
    public function authorDescription()
    {
        ob_start();
        the_author_meta('description', $this->data->post_author);
        return ob_get_clean();
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public function authorMeta($field = '')
    {
        return get_the_author_meta($field, $this->data->post_author);
    }
}