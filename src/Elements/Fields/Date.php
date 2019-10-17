<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use \TypeRocket\Html\Generator;

class Date extends Field implements ScriptField
{
    use DefaultSetting;

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
        wp_enqueue_script( 'jquery-ui-datepicker', ['jquery'], '1.0', true );
    }

    /**
     * Covert Date to HTML string
     */
    public function getString()
    {
        $name  = $this->getNameAttributeString();
        $this->removeAttribute( 'name' );
        $value = $this->getValue();
        $default = $this->getDefault();
        $this->setupInputId();
        $value = !empty($value) ? $value : $default;

        $value = esc_attr( $this->sanitize($value, 'raw') );

        $this->appendStringToAttribute( 'class', ' date-picker' );
        $input = new Generator();

        return $input->newInput( 'text', $name, $value, $this->getAttributes() )->getString();
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
