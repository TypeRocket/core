<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\BeforeAfterSetting;
use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;

class Input extends Field
{
    use DefaultSetting, RequiredTrait, BeforeAfterSetting;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'text' );
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
        $value = $this->setCast('string')->getValue();
        $default = $this->getDefault();
        $this->setupInputId();

        $value = !empty($value) || $value == '0' ? $value : $default;
        $value = $this->sanitize($value, 'raw');

        if($before = $this->getBefore()) {
            $this->attrClass('with-before');
            $before = '<div class="before"><span>' . $before . '</span></div>';
        }

        if($after = $this->getAfter()) {
            $this->attrClass('with-after');
            $after = '<div class="after"><span>' . $after . '</span></div>';
        }

        return '<div class="tr-text-input">' . $before . Html::input($this->getType(), $name, $value, $this->getAttributes()) . $after . '</div>';
    }

    /**
     * Spellcheck
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/email#spellcheck
     *
     * @param bool $use
     * @return Input
     */
    public function spellcheck($use = true)
    {
        return $this->setAttribute('spellcheck', $use ? 'true' : 'false');
    }

    /**
     * Number
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/number
     *
     * @param int|null $min
     * @param int|null $max
     * @return Input
     */
    public function setTypeNumber($min = null, $max = null)
    {
        return $this->setType('number')
            ->setAttribute('min', $min)
            ->setAttribute('max', $max);
    }

    /**
     * Time
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/time
     *
     * @param string|null $min
     * @param string|null $max
     * @return Input
     */
    public function setTypeTime($min = null, $max = null)
    {
        return $this->setType('time')
            ->setAttribute('min', $min)
            ->setAttribute('max', $max);
    }

    /**
     * Date
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date
     *
     * @param string|null $min
     * @param string|null $max
     * @return Input
     */
    public function setTypeDate($min = null, $max = null)
    {
        return $this->setType('date')
            ->setAttribute('min', $min)
            ->setAttribute('max', $max);
    }

    /**
     * Datetime
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local
     *
     * @param string|null $min
     * @param string|null $max
     * @return Input
     */
    public function setTypeDateTime($min = null, $max = null)
    {
        return $this->setType('datetime-local')
            ->setAttribute('min', $min)
            ->setAttribute('max', $max);
    }

    /**
     * Range
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/range
     *
     * @param int $min
     * @param int $max
     * @param int $step
     * @return Input
     */
    public function setTypeRange($min, $max, $step = 1)
    {
        return $this->setType('range')
            ->setAttribute('min', $min)
            ->setAttribute('step', $step)
            ->setAttribute('max', $max);
    }

    /**
     * URL
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/url
     *
     * @param null|string $pattern
     * @return Input
     */
    public function setTypeUrl($pattern = null)
    {
        return $this->setType('url')->setAttribute('pattern', $pattern);
    }

    /**
     * Email
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/email
     *
     * @param string $pattern regex example .+@globex.com
     * @return Input
     */
    public function setTypeEmail($pattern = null)
    {
        $this->setType('email')->setAttribute('pattern', $pattern);

        return $this;
    }

    /**
     * Tel
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/tel
     *
     * @param string|null $pattern regex example [0-9]{3}-[0-9]{3}-[0-9]{4}
     * @param null|string $help
     * @return Input|Field
     */
    public function setTypeTel($pattern = null, $help = null)
    {
        return $this->setType('Tel')->setAttribute('pattern', $pattern)->setHelp($help);
    }
}