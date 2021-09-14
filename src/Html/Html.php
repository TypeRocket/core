<?php
namespace TypeRocket\Html;

/**
 * Class Html
 *
 * @method static Html div(mixed|array $attributes = null, mixed $text = null)
 * @method static Html sup(mixed|array $attributes = null, mixed $text = null)
 * @method static Html sub(mixed|array $attributes = null, mixed $text = null)
 * @method static Html abbr(mixed|array $attributes = null, mixed $text = null)
 * @method static Html address(mixed|array $attributes = null, mixed $text = null)
 * @method static Html area(mixed|array $attributes = null, mixed $text = null)
 * @method static Html audio(mixed|array $attributes = null, mixed $text = null)
 * @method static Html base(mixed|array $attributes = null, mixed $text = null)
 * @method static Html canvas(mixed|array $attributes = null, mixed $text = null)
 * @method static Html header(mixed|array $attributes = null, mixed $text = null)
 * @method static Html main(mixed|array $attributes = null, mixed $text = null)
 * @method static Html menu(mixed|array $attributes = null, mixed $text = null)
 * @method static Html menuitem(mixed|array $attributes = null, mixed $text = null)
 * @method static Html code(mixed|array $attributes = null, mixed $text = null)
 * @method static Html em(mixed|array $attributes = null, mixed $text = null)
 * @method static Html label(mixed|array $attributes = null, mixed $text = null)
 * @method static Html legend(mixed|array $attributes = null, mixed $text = null)
 * @method static Html i(mixed|array $attributes = null, mixed $text = null)
 * @method static Html strong(mixed|array $attributes = null, mixed $text = null)
 * @method static Html b(mixed|array $attributes = null, mixed $text = null)
 * @method static Html pre(mixed|array $attributes = null, mixed $text = null)
 * @method static Html section(mixed|array $attributes = null, mixed $text = null)
 * @method static Html nav(mixed|array $attributes = null, mixed $text = null)
 * @method static Html head(mixed|array $attributes = null, mixed $text = null)
 * @method static Html data(mixed|array $attributes = null, mixed $text = null)
 * @method static Html title(mixed|array $attributes = null, mixed $text = null)
 * @method static Html col(mixed|array $attributes = null, mixed $text = null)
 * @method static Html colgroup(mixed|array $attributes = null, mixed $text = null)
 * @method static Html source(mixed|array $attributes = null, mixed $text = null)
 * @method static Html article(mixed|array $attributes = null, mixed $text = null)
 * @method static Html datalist(mixed|array $attributes = null, mixed $text = null)
 * @method static Html dd(mixed|array $attributes = null, mixed $text = null)
 * @method static Html dl(mixed|array $attributes = null, mixed $text = null)
 * @method static Html dt(mixed|array $attributes = null, mixed $text = null)
 * @method static Html span(mixed|array $attributes = null, mixed $text = null)
 * @method static Html button(mixed|array $attributes = null, mixed $text = null)
 * @method static Html li(mixed|array $attributes = null, mixed $text = null)
 * @method static Html ul(mixed|array $attributes = null, mixed $text = null)
 * @method static Html ol(mixed|array $attributes = null, mixed $text = null)
 * @method static Html select(mixed|array $attributes = null, mixed $text = null)
 * @method static Html option(mixed|array $attributes = null, mixed $text = null)
 * @method static Html optgroup(mixed|array $attributes = null, mixed $text = null)
 * @method static Html textarea(mixed|array $attributes = null, mixed $text = null)
 * @method static Html fieldset(mixed|array $attributes = null, mixed $text = null)
 * @method static Html hgroup(mixed|array $attributes = null, mixed $text = null)
 * @method static Html h6(mixed|array $attributes = null, mixed $text = null)
 * @method static Html h5(mixed|array $attributes = null, mixed $text = null)
 * @method static Html h4(mixed|array $attributes = null, mixed $text = null)
 * @method static Html h3(mixed|array $attributes = null, mixed $text = null)
 * @method static Html h2(mixed|array $attributes = null, mixed $text = null)
 * @method static Html h1(mixed|array $attributes = null, mixed $text = null)
 * @method static Html p(mixed|array $attributes = null, mixed $text = null)
 * @method static Html style(mixed|array $attributes = null, mixed $text = null)
 * @method static Html script(mixed|array $attributes = null, mixed $text = null)
 * @method static Html noscript(mixed|array $attributes = null, mixed $text = null)
 * @method static Html link(mixed|array $attributes = null, mixed $text = null)
 * @method static Html meta(mixed|array $attributes = null, mixed $text = null)
 * @method static Html html(mixed|array $attributes = null, mixed $text = null)
 * @method static Html body(mixed|array $attributes = null, mixed $text = null)
 * @method static Html iframe(mixed|array $attributes = null, mixed $text = null)
 * @method static Html embed(mixed|array $attributes = null, mixed $text = null)
 * @method static Html object(mixed|array $attributes = null, mixed $text = null)
 * @method static Html aside(mixed|array $attributes = null, mixed $text = null)
 * @method static Html details(mixed|array $attributes = null, mixed $text = null)
 * @method static Html figcaption(mixed|array $attributes = null, mixed $text = null)
 * @method static Html figure(mixed|array $attributes = null, mixed $text = null)
 * @method static Html picture(mixed|array $attributes = null, mixed $text = null)
 * @method static Html map(mixed|array $attributes = null, mixed $text = null)
 * @method static Html caption(mixed|array $attributes = null, mixed $text = null)
 * @method static Html mark(mixed|array $attributes = null, mixed $text = null)
 * @method static Html summary(mixed|array $attributes = null, mixed $text = null)
 * @method static Html time(mixed|array $attributes = null, mixed $text = null)
 * @method static Html blockquote(mixed|array $attributes = null, mixed $text = null)
 * @method static Html cite(mixed|array $attributes = null, mixed $text = null)
 * @method static Html dialog(mixed|array $attributes = null, mixed $text = null)
 * @method static Html table(mixed|array $attributes = null, mixed $text = null)
 * @method static Html tr(mixed|array $attributes = null, mixed $text = null)
 * @method static Html td(mixed|array $attributes = null, mixed $text = null)
 * @method static Html tbody(mixed|array $attributes = null, mixed $text = null)
 * @method static Html tfoot(mixed|array $attributes = null, mixed $text = null)
 * @method static Html thead(mixed|array $attributes = null, mixed $text = null)
 * @method static Html th(mixed|array $attributes = null, mixed $text = null)
 * @method static Html video(mixed|array $attributes = null, mixed $text = null)
 *
 * @package TypeRocket\Html
 */
class Html
{
    /** @var Tag */
    protected $tag;

    /**
     * To String
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getString();
    }

    /**
     * Get string of tag
     *
     * @return string
     */
    protected function getString(): string
    {
        return $this->tag->getString();
    }

    /**
     * Create new Tag
     *
     * @param string $tag
     * @param array|string|null $attributes
     * @param string|array|null $text
     *
     * @return $this
     */
    protected function el($tag, $attributes = null, $text = null )
    {
        $this->tag = new Tag( $tag, $attributes, $text );

        return $this;
    }

    /**
     * Create Form
     *
     *
     * @param $action
     * @param string $method
     * @param array|string $attributes
     * @param null|string $text
     *
     * @return $this
     */
    protected function form($action, $method = 'GET', $attributes = null, $text = null)
    {
        if(is_string($attributes) || is_numeric($attributes)) {
            $text = $text ?? $attributes;
            $attributes = [];
        }

        $attributes = $attributes ?? [];

        $attributes = array_merge( ['action' => $action, 'method' => $method], $attributes );
        $this->tag = new Tag( 'form', $attributes, $text );

        return $this;
    }


    /**
     * Create new link
     *
     * @param string|array $text
     * @param string $url
     * @param array $attributes
     *
     * @return $this
     */
    protected function a($text = '', $url = '#', array $attributes = [])
    {
        $attributes = array_merge( array_filter(['href' => $url], '\TypeRocket\Utility\Str::notBlank'), $attributes );
        $this->tag = new Tag( 'a', $attributes, $text );

        return $this;
    }

    /**
     * Create new image
     *
     * @param string $src
     * @param array $attributes
     *
     * @return $this
     */
    protected function img($src = '', array $attributes = [])
    {
        $attributes = array_merge( ['src' => $src], $attributes );
        $this->tag = new Tag( 'img', $attributes );

        return $this;
    }

    /**
     * Create new input
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param array $attributes
     *
     * @return $this
     */
    protected function input($type, $name, $value, array $attributes = [])
    {
        $defaults = array_filter(['type' => $type, 'name' => $name, 'value' => $value], '\TypeRocket\Utility\Str::notBlank');
        $this->tag = new Tag( 'input', array_merge( $defaults, $attributes ) );

        return $this;
    }

    /**
     * Append inside of tag
     *
     * @param string|Tag|Html|array $tag
     *
     * @return $this
     */
    protected function nest($tag)
    {
        $this->tag->nest( $tag );

        return $this;
    }

    /**
     * Prepend Inside of tag
     *
     * @param string|Tag|Html $tag
     *
     * @return $this
     */
    protected function nestAtTop($tag)
    {
        $this->tag->nestAtTop( $tag );

        return $this;
    }

    /**
     * Tag
     *
     * @return Tag
     */
    protected function tag()
    {
        return $this->tag;
    }

    /**
     * @param $name
     * @param $arguments
     * @return Html|string
     */
    public function __call($name, $arguments)
    {
        if(method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }

        return $this->el($name, ...$arguments);
    }

    /**
     * @param $name
     * @param $arguments
     * @return Html
     */
    public static function __callStatic($name, $arguments)
    {
        return (new static)->{$name}(...$arguments);
    }

}
