<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Html\Html;

/**
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file
 */
class FileUpload extends Field
{
    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'file' );
    }

    /**
     * Covert Test to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $this->setupInputId();
        $this->setAttribute('data-tr-field', $this->getContextId());
        $name = $this->getNameAttributeString();
        $this->setupInputId();
        $cb = $this->getSetting('doBefore');
        $before = is_callable($cb) ? $cb($this, $this->getValue()) : '';

        return '<div class="tr-text-input">' . $before . Html::input('file', $name, null, $this->getAttributes()) . '</div>';
    }

    /**
     * @param callable $callback a callback that returns a string. Will be used before input field output
     *
     * @return $this
     */
    public function doBefore(callable $callback)
    {
        return $this->setSetting('doBefore', $callback);
    }

    /**
     * @return $this
     */
    public function multiple()
    {
        return $this->setAttribute('multiple', 'multiple');
    }

    /**
     * @param string|array $accepts
     *
     * @return $this
     */
    public function accepts($accepts)
    {
        return $this->setAttribute('accept', implode(',', (array) $accepts));
    }
}