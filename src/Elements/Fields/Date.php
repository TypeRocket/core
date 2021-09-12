<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;

/**
 * Class Date
 *
 * Safari Browser does not support HTML5 date
 *
 * @package TypeRocket\Elements\Fields
 */
class Date extends Field implements ScriptField
{
    use DefaultSetting, RequiredTrait;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'date' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts() {
        wp_enqueue_script( 'jquery-ui-datepicker', ['jquery'], false, true );
    }

    /**
     * Covert Date to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $this->setupInputId();
        $this->setAttribute('data-tr-field', $this->getContextId());
        $name  = $this->getNameAttributeString();
        $this->removeAttribute( 'name' );
        $value = $this->setCast('string')->getValue();
        $default = $this->getDefault();
        $value = !empty($value) ? $value : $default;
        $value = esc_attr( $this->sanitize($value, 'raw') );
        $this->attrClass('tr-date-picker');

        return (string) Html::input( 'text', $name, $value, $this->getAttributes() );
    }

    /**
     * Set Format Year-Month-Day
     *
     * @return $this
     */
    public function setFormatYearMonthDay()
    {
        return $this->setFormat('yy-mm-dd');
    }

    /**
     * Set Format
     *
     * The format should correspond to the jQuery datepicker format.
     * @link http://api.jqueryui.com/datepicker/#option-dateFormat
     *
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->setAttribute('data-format', $format);

        return $this;
    }

}
