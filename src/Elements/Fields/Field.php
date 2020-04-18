<?php
namespace TypeRocket\Elements\Fields;

use \TypeRocket\Elements\Form;
use TypeRocket\Elements\Traits\AttributesTrait;
use TypeRocket\Elements\Traits\MacroTrait;
use \TypeRocket\Utility\Sanitize;
use \TypeRocket\Elements\Traits\FormConnectorTrait;

abstract class Field
{
    use FormConnectorTrait, AttributesTrait, MacroTrait;

    protected $name = null;
    protected $type = null;
    protected $required = false;
    protected $cast = null;

    /** @var Form */
    protected $form = null;
    protected $label = false;
    protected $labelTag = 'span';

    /**
     * When instancing a Field use reflection to connect the Form
     *
     * @param string $name the name of the field
     * @param array $attr the html attributes
     * @param array $settings the settings of the field
     * @param bool|true $label show the label
     *
     * @internal A Form must be passed for the field to work
     */
    public function __construct( $name, $attr = [], $settings = [], $label = true )
    {
        $args = func_get_args();
        $this->init();

        try {
            $setup = new \ReflectionMethod( $this, 'setup' );
            $setup->setAccessible( true );
            $args = $this->assignAutoArgs($args);

            if ($this instanceof ScriptField) {
                $this->enqueueScripts();
            }

            $setup->invokeArgs( $this, $args );
            $setup->setAccessible( false );

        } catch(\ReflectionException $e) {
            wp_die($e->getMessage());
        }

        do_action('tr_after_field_element_init', $this);
    }

    /**
     * Get Field object as string
     *
     * @return string
     */
    public function __toString()
    {
        $this->beforeEcho();
        $form = $this->getForm();
        if($form instanceof Form) {
            $string = $this->getForm()->getFromFieldString($this);
        } else {
            $string = $this->getString();
        }

        return $string;
    }

    /**
     * Set Cast
     *
     * @param callable $callback
     * @param array $args
     * @return $this
     */
    public function setCast($callback, array $args = [])
    {
        $this->cast = [
            'callback' => $callback,
            'args' => $args,
        ];

        return $this;
    }

    /**
     * Get Cast
     *
     * @param $value
     * @return mixed
     */
    public function getCast($value)
    {
        if( is_array($this->cast) && is_callable($this->cast['callback']) ) {
            $args = array_merge([$value], $this->cast['args']);
            $value = call_user_func_array($this->cast['callback'], $args);
        }

        return $value;
    }

    /**
     * Print Field to Screen
     *
     * @return $this
     */
    public function echo()
    {
        echo $this;
        return $this;
    }

    /**
     * Require Form
     *
     * @param array $args
     *
     * @return mixed
     */
    private function assignAutoArgs($args) {
        foreach ($args as $key => $arg) {
            if ($arg instanceof Form) {
                $this->configureToForm( $arg );
                unset( $args[$key] );
                return $args;
            }
        }

        die('TypeRocket: A field does not have a Form connected to it.');
    }

    /**
     * Invoked by Reflection in constructor
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return $this
     */
    protected function setup( $name, array $attr = [], array $settings = [], $label = true )
    {
        $this->settings = $settings;
        $this->label    = $label;
        $this->attr     = $attr;
        $this->setName( $name );

        if (empty( $settings['label'] )) {
            $this->settings['label'] = __($name, 'typerocket-profile');
        }

        return $this;

    }

    /**
     * Init is normally used to setup initial configuration like a
     * constructor does.
     *
     * @return mixed
     */
    abstract protected function init();

    /**
     * Optional for running code just before the field is printed
     * to the screen.
     *
     * @return mixed
     */
     protected function beforeEcho() {}

    /**
     * Setup to use with a Form.
     *
     * @param Form $form
     *
     * @return $this
     */
    public function configureToForm( Form $form )
    {
        $this->form = clone $form;
        $this->setGroup( $this->form->getGroup() );
        $this->itemId = $this->form->getItemId();
        $this->resource = $this->form->getResource();
        $this->action = $this->form->getAction();
        $this->model = $this->form->getModel();
        $this->setPopulate( $this->form->getPopulate() );
        $this->prefix = $this->form->getPrefix();

        return $this;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Set Help Text
     *
     * @param string $value help text
     *
     * @return Field $this
     */
    public function setHelp( $value )
    {
        $this->settings['help'] = (string) $value;

        return $this;
    }

    /**
     *
     * Get Help Text
     *
     * @return string help text
     */
    public function getHelp()
    {
        return $this->settings['help'];
    }

    /**
     * Generate the string needed for the html name attribute. This
     * does not set the name attribute.
     *
     * @return string
     */
    public function getNameAttributeString()
    {
        return $this->prefix .$this->getBrackets();
    }

    /**
     * Set the type of Field. This is not always the input type. For
     * example in custom fields. Text Field is the only that does.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType( $type )
    {
        $this->type = (string) $type;

        return $this;
    }

    /**
     * Get Type
     *
     * @return null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name of field. Not the same as the html name attribute.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName( $name )
    {
        $name_parts = explode('.', strrev($name), 2);
        $this->name = Sanitize::underscore( strrev($name_parts[0]) );

        if(!empty($name_parts[1])) {
            $this->appendToGroup( strrev($name_parts[1]) );
        }

        return $this;
    }

    /**
     * Get name of field. Not the same as the html name attribute.
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Label Text
     *
     * Set the label text to be used
     *
     * @param string $value
     *
     * @return $this
     */
    public function setLabel( $value )
    {

        $this->settings['label'] = __($value, 'typerocket-profile');

        return $this;
    }

    /**
     * Get Label
     *
     * This is not the label text but the label setting. Whether it
     * should be displayed.
     *
     * @return bool
     */
    public function getLabel()
    {
        if ( ! array_key_exists( 'label', $this->settings )) {
            return null;
        }

        return $this->settings['label'];
    }

    /**
     * Set whether label should be displayed
     *
     * @param string $label
     *
     * @return $this
     */
    public function setLabelOption( $label )
    {
        $this->label = (bool) $label;

        return $this;
    }

    /**
     * Get Label Option
     *
     * This is not the label text but the label setting. Whether it
     * should be displayed.
     *
     * @return bool
     */
    function getLabelOption()
    {
        return $this->label;
    }

    /**
     * Tag to use for Label Element
     *
     * @return string
     */
    public function getLabelTag() {
        return $this->labelTag ?? 'span';
    }

    /**
     * Get Input ID
     *
     * @return mixed|string
     */
    public function getInputId()
    {
        $default = 'tr_field_' . Sanitize::underscore($this->getDots());
        return $this->getAttribute('id', $default );
    }

    /**
     * Get Input Spoof ID
     *
     * Required for repeaters to work.
     *
     * @return mixed|string
     */
    public function getSpoofInputId()
    {
        return $this->getAttribute('data-trid', 'tr_field_' . $this->getDots());
    }

    /**
     * Set Input ID
     *
     * @return $this
     */
    public function setupInputId()
    {
        $dots = $this->getDots();
        $this->setAttribute('data-trid', 'tr_field_' . $dots);
        $this->setAttribute('id', $this->getInputId() );

        return $this;
    }

    /**
     * Get the prefix
     *
     * @return null
     */
    public function getPrefix()
    {
        return $this->prefix;
    }


    /**
     * Get the value from the database
     *
     * @return null|string return the fields value
     */
    public function getValue()
    {

        if ($this->populate == false) {
            return null;
        }

        $value = $this->form->getModel()->getFieldValue($this);

        return $this->getCast($value);
    }

    /**
     * Sanitize field value
     *
     * @param string $value
     * @param null $default
     *
     * @return mixed
     */
    protected function sanitize( $value, $default = null )
    {
        $sanitize = "\\TypeRocket\\Sanitize::" . $this->getSetting('sanitize', $default );

        if ( is_callable($sanitize)) {
            $value = call_user_func($sanitize, $value);
        }

        return $value;
    }

    /**
     * Make field required
     *
     * Adds a * to the field and that is all.
     *
     * @return $this
     */
    public function required()
    {
        $this->required = true;

        return $this;
    }

    /**
     * Get Required
     *
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Get bracket syntax used to name the input and get the
     * value from the database using the GetValue class.
     *
     * @return string format [group][name][sub]
     */
    public function getBrackets()
    {
        return $this->getBracketsFromDots();
    }

    /**
     * Get the dot syntax
     *
     * @return null|string
     */
    public function getDots()
    {

        $dots = $this->name;

        if(!empty($this->group)) {
            $dots = $this->group . '.' . $dots;
        }

        if(!empty($this->sub)) {
            $dots .= '.' . $this->sub;
        }

        return $dots;
    }

    public function getBracketsFromDots()
    {
        $dots = $this->getDots();
        $array = explode('.', $dots);
        $brackets = array_map(function($item) { return "[{$item}]"; }, $array);

        return implode('', $brackets);
    }

    /**
     * Define the field debugger helper for the front-end
     *
     * @return mixed
     */
    protected function debugHelperFunction() {
        return false;
    }

    /**
     * Get the field debugger helper for the front-end
     *
     * @return null
     */
    public function getDebugHelperFunction() {
        return $this->debugHelperFunction();
    }

    /**
     * Add Modifyer To Helper Function
     *
     * @return string
     */
    public function getDebugHelperFunctionModifier()
    {
        return '';
    }

    /**
     * Configure in all concrete Field classes
     *
     * @return string
     */
    abstract public function getString();

}
