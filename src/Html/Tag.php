<?php
namespace TypeRocket\Html;

use TypeRocket\Elements\Traits\Attributes;

class Tag
{
    use Attributes;

    protected $tag;
    protected $nest = [];
    protected $closed = false;

    /**
     * Html constructor.
     *
     * @param string $tag
     * @param array|null $attributes
     * @param string|Tag|Html|array|null $nest
     */
    public function __construct( string $tag, $attributes = null, $nest = null)
    {
        if(is_string($attributes) || is_numeric($attributes) || $attributes instanceof Tag || $attributes instanceof Html) {
            $nest = $nest ?? $attributes;
            $attributes = [];
        }

        $attributes = $attributes ?? [];

        $this->tag = $tag;
        $this->attrReset( $attributes );
        $this->nest($nest);

        if( in_array($this->tag, ['img', 'br', 'hr', 'input']) ) {
            $this->closed = true;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getString();
    }

    /**
     * Get string
     *
     * @return string
     */
    public function getString(): string {
        return $this->open().$this->inner().$this->close();
    }

    /**
     * Create Tag
     *
     * @param string $tag
     * @param array|string|null $attributes
     * @param string|array|null $nest
     *
     * @return $this
     */
    public static function el($tag, $attributes = null, $nest = null )
    {
        return new static( $tag, $attributes, $nest );
    }

    /**
     * Append Inner Tag
     *
     * @param string|Tag|Html|array $tag
     *
     * @return $this
     */
    public function nest($tag)
    {
        if(is_array($tag)) {
            foreach ($tag as $t) {
                array_push($this->nest, $t);
            }
        } else {
            array_push($this->nest, $tag);
        }

        return $this;
    }

    /**
     * Prepend inner tag
     *
     * @param Tag|Html|string|array $tag
     *
     * @return $this
     */
    public function nestAtTop( $tag )
    {
        if(is_array($tag)) {
            foreach ($tag as $t) {
                array_unshift($this->nest, $t);
            }
        } else {
            array_unshift($this->nest, $tag);
        }

        return $this;
    }

    /**
     * Get the opening tag in string form
     *
     * @return string
     */
    public function open() {
        $openTag = "<{$this->tag}";

        foreach($this->attr as $attribute => $value) {
            $value = esc_attr($value);
            $value = $value !== '' ? "=\"{$value}\"" : '';
            $openTag .= " {$attribute}{$value}";
        }

        $openTag .= $this->closed ? " />" : ">";

        return $openTag;
    }

    /**
     * Get the closing tag as string
     *
     * @return string
     */
    public function close() {
        return $this->closed ? '' : "</{$this->tag}>";
    }

    /**
     * Get the string with inner HTML
     *
     * @return string
     */
    public function inner() {
        $html = '';

        if( ! $this->closed ) {
            foreach($this->nest as $tag) {
                $html .= (string) $tag;
            }
        }

        return $html;
    }

    /**
     * @param string $tag
     * @param null|array $attributes
     * @param string|Tag|Html|array $nest
     *
     * @return static
     */
    public static function new(string $tag, $attributes = null, $nest = null)
    {
        return new static(...func_get_args());
    }

}
