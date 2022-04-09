<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Elements\BaseForm;
use TypeRocket\Elements\Traits\Attributes;
use TypeRocket\Elements\Traits\Conditional;
use TypeRocket\Elements\Traits\DisplayPermissions;
use TypeRocket\Elements\Traits\MacroTrait;
use TypeRocket\Html\Html;
use TypeRocket\Html\Tag;
use TypeRocket\Interfaces\Formable;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPComment;
use TypeRocket\Models\WPOption;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\WPUser;
use TypeRocket\Elements\Traits\Settings;
use TypeRocket\Utility\Data;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Elements\Traits\FormConnectorTrait;

abstract class Field
{
    use FormConnectorTrait, Attributes, MacroTrait, Conditional, Settings, DisplayPermissions;

    protected $name = null;
    protected $type = null;
    protected $required = false;
    protected $cast = null;
    protected $id = null;
    protected $rawHtml = null;
    protected $error = null;

    /** @var BaseForm */
    protected $form = null;
    protected $label = false;
    protected $labelTag = 'span';

    /**
     * When instancing a Field use reflection to connect the Form
     *
     * A Form must be passed for the field to work
     *
     * @param string $name the name of the field
     * @param array|BaseForm $attr the html attributes
     * @param array|BaseForm $settings the settings of the field
     * @param bool|true|BaseForm $label show the label
     * @param array|BaseForm $params
     */
    public function __construct( $name, $attr = [], $settings = [], $label = true, ...$params )
    {
        $args = func_get_args();
        $this->init();

        foreach ($args as $key => $arg) {
            if ($arg instanceof BaseForm) {
                $this->configureToForm( $arg );
                unset( $args[$key] );
            }
        }

        $this->configure(...$args);

        if ($this instanceof ScriptField) {
            $this->enqueueScripts();
        }

        do_action('typerocket_field', $this);
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
    protected function configure( $name, array $attr = [], array $settings = [], $label = true )
    {
        $this->attrExtend($attr);
        $this->setName( $name );
        $this->settingsExtend($settings);
        $this->label    = $label;

        if ( empty($this->settings['label']) ) {
            $this->setLabel($name);
        }

        return $this;
    }

    /**
     * Get the debug mode helper content
     *
     * @return string
     */
    protected function getHelpFunction()
    {
        $helper = $this->getDebugHelperFunction();
        $mod = $this->getDebugHelperFunctionModifier() ?? '';

        if($helper) {
            $function = $helper;
        } else {
            $dots   = $this->getDots();
            $resource = $this->getForm()->getResource();
            $access = $resource;
            $param = '';

            if($this->model instanceof WPPost) {
                $access = '';
            } elseif($this->model instanceof WPTerm) {
                $access = 'term_';
                $param = ", '{$resource}'";
                $id = $this->getForm()->getItemId() ?? '$id';
                $param .= ', '.$id;
            } elseif($this->model instanceof WPOption) {
                $access = 'option_';
                $param = '';
            } elseif($this->model instanceof WPUser) {
                $access = 'user_';
                $param = '';
            }  elseif($this->model instanceof WPComment) {
                $access = 'comment_';
                $param = '';
            } elseif($this->model instanceof Model) {
                $access = 'resource_';
                $param = ", '{$resource}'";
                $id = $this->getForm()->getItemId() ?? '$id';
                $param .= ', '.$id;
            }

            $function   = "tr_{$access}field('{$mod}{$dots}'{$param});";
        }

        return $function;
    }

    /**
     * Get the debug HTML for the From Field Label
     *
     * @return string
     */
    protected function getDebugString()
    {
        $generator = new Html();
        $html      = '';
        if ($this->getDebugStatus() === true && ! $this instanceof Submit ) {
            $dev_html = $this->getHelpFunction();
            $fillable = $guard = $builtin = [];

            if($this->model instanceof Model) {
                $fillable = $this->model->getFillableFields();
                $guard = $this->model->getGuardFields();
                $builtin = $this->model->getBuiltinFields();
            }

            $icon = '<i class="dashicons dashicons-editor-code"></i>';

            if(in_array($this->getName(), $builtin)) {
                $icon = '<i class="dashicons dashicons-database"></i> ' . $icon;
            }

            if(in_array($this->getName(), $fillable )) {
                $icon = '<i class="dashicons dashicons-edit"></i> ' . $icon;
            } elseif(in_array($this->getName(), $guard )) {
                $icon = '<i class="dashicons dashicons-shield-alt"></i> ' . $icon;
            }

            $generator->div([ 'class' => 'tr-dev-field-helper' ], $icon );
            $navTag       = new Tag( 'span', [ 'class' => 'nav' ] );
            $fieldCopyTag = new Tag( 'span', [ 'class' => 'tr-dev-field-function' ], $dev_html );
            $navTag->nest( $fieldCopyTag );
            $html = $generator->nest( $navTag )->getString();
        }

        return $html;
    }

    /**
     * Get the Form Field Label
     *
     * @return string
     */
    protected function getControlLabel()
    {
        $label_tag = $this->getLabelTag();
        $label_for = $this->getInputId();

        $helpRef = $this->getSetting('help') ? "aria-describedby=\"{$label_for}--help\"":'';

        $open_html  = "<div class=\"tr-control-label\"><{$label_tag} {$helpRef} for=\"{$label_for}\" class=\"tr-label\" tabindex=\"-1\">";
        $close_html = "</{$label_tag}></div>";
        $debug      = $this->getDebugString();
        $html       = '';
        $label      = $this->getLabelOption();
        $required   = $this->getRequired() ? '<span class="tr-field-required">*</span>' : '';

        if ($label) {
            $label = $this->getLabel();

            if($error = $this->error) {
                $error = "<span class=\"tr-field-error\">{$error}</span>";
            }

            $html  = "{$open_html}{$label} {$required} {$debug} {$close_html}{$error}";
        } elseif ($debug !== '') {
            $html = "{$open_html}{$debug}{$close_html}";
        }

        return $html;
    }

    /**
     * @deprecated
     * @return string
     */
    public function getFromString()
    {
        return $this->getFormString();
    }

    /**
     * Get Form Field string
     *
     * @return string
     */
    public function getFormString() : string
    {
        // converting to string must happen first
        $fieldString     = $this->getString();
        $before_form_string = $this->getSetting('before_form_string');
        $field_cb_value = $this->getValue();

        if(is_callable($before_form_string)) {
            $before_form_string($field_cb_value, $this);
        }

        if(!$fieldString) {
            return '';
        }

        // now the rest
        $contextId     = $this->getContextId();
        $error         = $this->error = $this->getForm()->getError($this->getDots());
        $label         = $this->getControlLabel();
        $id            = $this->getSetting('id');
        $idInput       = $this->getInputId();
        $section_class = $this->getSetting( 'classes', '' );

        if($error) {
            $section_class .= ' tr-field-has-error';
        }

        $help = $this->getSetting( 'help' );

        if(is_callable($help)) {
            $help = $help($field_cb_value, $this);
        }

        $id     = $id ? "id=\"{$id}\"" : '';
        $idHelp = $idInput ? "id=\"{$idInput}--help\"" : '';
        $help   = $help ? "<div {$idHelp} class=\"tr-form-field-help\"><p>{$help}</p></div>" : '';

        if ($this->rawHtml) {
            $html = apply_filters('typerocket_field_html_raw', $fieldString, $this, $contextId );
        } else {
            $type = strtolower( str_ireplace( '\\', '-', get_class( $this ) ) );
            $condition = $this->getConditionalAttribute();
            $html = "<div {$condition} data-tr-context=\"{$contextId}\" class=\"tr-control-section tr-divide {$section_class} {$type}\" {$id}>{$label}<div class=\"control\">{$fieldString}{$help}</div></div>";
        }

        return apply_filters('typerocket_field_html', $html, $this );
    }

    /**
     * Get Field object as string
     *
     * @return string
     */
    public function __toString()
    {
        if(!$this->canDisplay()) {
            return '';
        }

        $this->beforeEcho();
        $form = $this->getForm();
        if($form instanceof BaseForm) {
            $string = $this->getFormString();
        } else {
            $string = $this->getString();
        }

        return $string;
    }

    /**
     * Set Before Form String
     *
     * @param callable $callable
     * @return Field
     */
    public function setBeforeFormString(callable $callable)
    {
        return $this->setSetting('before_form_string', $callable);
    }

    /**
     * Append to Section Classes
     *
     * @param string $classes
     *
     * @return $this
     */
    public function appendToSectionClasses(string $classes)
    {
        return $this->appendToStringSetting('classes', $classes);
    }

    /**
     * Clone Field
     *
     * @param null|BaseForm|Formable $form
     *
     * @return Field
     */
    public function cloneToForm($form = null)
    {
        $clone = clone $this;

        if($form instanceof BaseForm) {
            $clone->configureToForm($form);
        }

        return $clone;
    }

    /**
     * Set Cast
     *
     * @param callable|string $cast
     * @param bool $soft
     *
     * @return $this
     */
    public function setCast($cast, $soft = true)
    {
        if($soft) {
            $this->cast = $this->cast ?? $cast;
        } else {
            $this->cast = $cast;
        }

        return $this;
    }

    /**
     * Get Cast
     *
     * @param mixed $value
     * @param bool $cast_skip_null
     *
     * @return mixed
     */
    public function getCast($value, $cast_skip_null = false)
    {
        if($cast_skip_null && is_null($value)) {
            return $value;
        }

        if( !empty($this->cast) ) {
            $value = Data::cast($value, $this->cast);
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
     * Init is normally used to setup initial configuration like a
     * constructor does.
     *
     * @return void
     */
    abstract protected function init();

    /**
     * Optional for running code just before the field is printed
     * to the screen.
     *
     * @return void
     */
     protected function beforeEcho() {}

    /**
     * Setup to use with a Form.
     *
     * @param BaseForm $form
     *
     * @return $this
     */
    public function configureToForm(BaseForm $form )
    {
        $this->form = clone $form;
        $this->model = $this->form->getModel();
        $this->prefix = $this->form->getPrefix();
        $this->debugStatus = $this->form->getDebugStatus();
        $this->translateLabelDomain = $this->form->getLabelTranslationDomain();
        $this->setPopulate( $this->form->getPopulate() );

        return $this;
    }

    /**
     * @return BaseForm
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Set Help Text
     *
     * @param string|callable $value help text
     *
     * @return $this
     */
    public function setHelp( $value )
    {
        if(is_callable($value)) {
            $this->settings['help'] = $value;
        } elseif($value) {
            $this->settings['help'] = $value;
        } else {
            unset($this->settings['help']);
        }

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
        return $this->settings['help'] ?? null;
    }

    /**
     * Generate the string needed for the html name attribute. This
     * does not set the name attribute.
     *
     * @return string
     */
    public function getNameAttributeString()
    {
        return $this->prefix.$this->getBrackets();
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
     * @return null|string
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
        $label = strrev($name_parts[0]);
        $this->name = Sanitize::underscore( $label );

        if(!empty($name_parts[1])) {
            $this->setLabel($label);
            $this->appendToGroup( strrev($name_parts[1]), true );
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
        $this->settings['label'] = $this->translateLabelDomain ? _x($value, 'field', $this->translateLabelDomain) : $value;

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
        return $this->settings['label'] ?? null;
    }

    /**
     * Set whether label should be displayed
     *
     * @param bool $label
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
     * Get or Set Label (Option)
     *
     * @param null|bool|string $label
     *
     * @return $this|bool|null
     */
    public function label($label = null)
    {
        if(func_num_args() === 0) {
            return $this->getLabel();
        }

        if(is_bool($label)) {
            $this->setLabelOption($label);
        }
        else {
            $this->setLabel($label);
        }

        return $this;
    }

    /**
     * @internal
     * @param bool $bool
     *
     * @return $this
     */
    public function raw($bool = true)
    {
        $this->rawHtml = $bool;

        return $this;
    }

    /**
     * Get Input ID
     *
     * @return mixed|string
     */
    public function getInputId()
    {
        return $this->getAttribute('id') ??  wp_unique_id('tr_field_') . '_' . $this->getDots(true,true);
    }

    /**
     * Set Input ID
     *
     * @return $this
     */
    public function setupInputId()
    {
        return $this->setAttribute('id',  wp_unique_id('tr_field_') . '_' . $this->getDots(true,true));
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
     * @param bool $cast_skip_null
     *
     * @return null|string return the fields value
     */
    public function getValue($cast_skip_null = false)
    {
        if ($this->populate == false) {
            return null;
        }

        $model = $value = $this->getModel();

        if($model instanceof Formable) {
            $value = $model->getFieldValue($this);
        }

        return $this->getCast($value, $cast_skip_null);
    }

    /**
     * Get Value As String
     *
     * @return string|null
     */
    public function getValueAsString()
    {
        $value = $this->getValue();

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        } elseif(is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        return $value;
    }

    /**
     * Sanitize field value
     *
     * @param string|null $value
     * @param null|string $default
     *
     * @return mixed
     */
    protected function sanitize( $value, $default = null )
    {
        $sanitize = "\\TypeRocket\\Utility\\Sanitize::" . $this->getSetting('sanitize', $default );

        if ( is_callable($sanitize)) {
            $value = call_user_func($sanitize, $value);
        }

        return $value;
    }

    /**
     * @param $value
     *
     * @return Field
     */
    public function setSanitize($value)
    {
        return $this->setSetting('sanitize', $value);
    }

    /**
     * Make field required
     *
     * Adds a * to the field and that is all.
     *
     * @return $this
     */
    public function markLabelRequired()
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
     * Get Group With Form
     *
     * @return string
     */
    public function getGroupWithFrom()
    {
        $group = '';

        if(!empty($this->group)) {
            $group = $this->group;
        }

        if($this->form) {
            if($fromGroup = $this->form->getGroup()) {
                $group = trim($fromGroup . '.' . $group, '.');
            }
        }

        return $group;
    }

    /**
     * Get the dot syntax
     *
     * @param bool $underscore underscore the dots
     * @param bool $prefix include prefix
     *
     * @return string
     */
    public function getDots($underscore = false, $prefix = false)
    {
        $dots = $this->name;

        if($group = $this->getGroupWithFrom()) {
            $dots = $group . '.' . $dots;
        }

        if(!empty($this->sub)) {
            $dots .= '.' . $this->sub;
        }

        if($prefix) {
            $dots = $this->prefix . '.' . $dots;
        }

        return !$underscore ? $dots : str_replace('.', '_', $dots);
    }

    /**
     * Get Context ID
     *
     * This is used for conditional fields
     *
     * @return string
     */
    public function getContextId()
    {
        return $this->getDots(false, true);
    }

    /**
     * Get Brackets
     *
     * @return string
     */
    public function getBracketsFromDots()
    {
        $array = explode('.', $this->getDots());
        return '[' . implode('][', $array) . ']';
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
     * Add Modifier To Helper Function
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

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }
}
