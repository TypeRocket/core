<?php
namespace TypeRocket\Elements;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Fields\Submit;
use TypeRocket\Elements\Traits\MacroTrait;
use TypeRocket\Html\Generator;
use TypeRocket\Html\Tag;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Models\WPOption;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\Model;
use TypeRocket\Elements\Traits\FormConnectorTrait;
use TypeRocket\Register\Registry;
use TypeRocket\Utility\Str;

class Form
{

    use FormConnectorTrait, MacroTrait;

    /** @var \TypeRocket\Elements\Fields\Field $currentField */
    protected $currentField = '';
    protected $debugStatus = null;
    protected $useAjax = null;
    protected $formUrl;
    protected $method = null;

    /**
     * Instance the From
     *
     * @param string $resource posts, users, comments or options
     * @param string $action update or create
     * @param null|int $itemId you can set this to null or an integer
     */
    public function __construct( $resource = 'auto', $action = 'update', $itemId = null )
    {
        if( is_int($action) && ! $itemId ) {
            $itemId = $action;
            $action = 'update';
        }

        $this->resource = strtolower($resource);
        $this->action = $action;
        $this->itemId = $itemId;
        $this->autoConfig();

        $Resource = Str::camelize($this->resource);
        $model = "\\" . TR_APP_NAMESPACE . "\\Models\\{$Resource}";

        if(class_exists($model)) {
            $this->model = new $model();

            if( !empty($this->itemId) ) {
                $this->model->findById($this->itemId);
            }
        }

        do_action('tr_after_form_element_init', $this);
    }

    /**
     * Set the model for the form
     *
     * @param $modelClass
     *
     * @return $this
     */
    public function setModel( $modelClass )
    {
        if(class_exists($modelClass)) {
            $this->model = new $modelClass();

            if( !empty($this->itemId) ) {
                $this->model->findById($this->itemId);
            }
        }

        return $this;
    }

    /**
     * Auto config form is no Controller etc. is set
     *
     * @return $this
     */
    protected function autoConfig()
    {
        if ($this->resource === 'auto') {
            global $post, $comment, $user_id, $taxonomy, $tag_ID;

            if ( isset( $post->ID ) ) {
                $item_id  = $post->ID;
                $resource = Registry::getPostTypeResource($post->post_type);

                $Resource = Str::camelize($resource[0]);
                $resource = $resource[0];
                $model = "\\" . TR_APP_NAMESPACE . "\\Models\\{$Resource}";
                $controller = "\\" . TR_APP_NAMESPACE . "\\Controllers\\{$Resource}Controller";

                if( empty($resource) || ! class_exists($model) || ! class_exists($controller) ) {
                    $resource = 'post';
                }
            } elseif ( isset($comment->comment_ID ) ) {
                $item_id  = $comment->comment_ID;
                $resource = 'comment';
            } elseif ( isset( $user_id ) ) {
                $item_id  = $user_id;
                $resource = 'user';
            } elseif ( isset( $taxonomy ) || isset($tag_ID) ) {
                $item_id  = $tag_ID;
                $resource = Registry::getTaxonomyResource($taxonomy);

                $Resource = Str::camelize($resource[0]);
                $resource = $resource[0];
                $model = "\\" . TR_APP_NAMESPACE . "\\Models\\{$Resource}";
                $controller = "\\" . TR_APP_NAMESPACE . "\\Controllers\\{$Resource}Controller";

                if( empty($resource) || ! class_exists($model) || ! class_exists($controller) ) {
                    $resource = 'category';
                }

            } else {
                $item_id  = null;
                $resource = 'option';
            }

            $this->itemId = $item_id;
            $this->resource = strtolower($resource);
        }

        return $this;
    }

    /**
     * Disable Ajax
     *
     * @return Form $this
     */
    public function disableAjax() {
        $this->useAjax = false;

        return $this;
    }

    /**
     * Use Ajax
     *
     * @return Form $this
     */
    public function useAjax() {
        $this->useAjax = true;

        return $this;
    }

    /**
     * Use TypeRocket Rest to submit form
     *
     * @return Form $this
     */
    public function useJson()
    {
        if( $this->useAjax === null ) {
            $this->useAjax();
        }

        $scheme        = is_ssl() ? 'https' : 'http';
        $this->formUrl = home_url('/', $scheme ) . 'tr_json_api/v1/' . $this->resource . '/' . $this->itemId;

        return $this;
    }

    /**
     * Use a TypeRocket route
     *
     * @param $method
     * @param $dots
     *
     * @return \TypeRocket\Elements\Form $this
     */
    public function useRoute($method, $dots)
    {
        $dots          = explode('.', $dots);
        $scheme        = is_ssl() ? 'https' : 'http';
        $this->formUrl = home_url(implode('/', $dots ) . '/', $scheme);
        $this->method  = strtoupper($method);

        return $this;
    }

    /**
     * Use a URL
     *
     * @param $method
     * @param $url
     *
     * @return $this
     */
    public function useUrl($method, $url)
    {
        $url_parts     = explode('/', trim($url, '/') );
        $scheme        = is_ssl() ? 'https' : 'http';
        $this->formUrl = home_url(implode('/', $url_parts ) . '/', $scheme);
        $this->method  = strtoupper($method);

        return $this;
    }

    /**
     * Use TypeRocket Rest to submit form
     *
     * @return Form $this
     */
    public function useAdmin()
    {
        $params = [];

        if($this->itemId) {
            $params = ['route_id' => $this->itemId];
        }

        $action = $this->action;
        switch($action) {
            case 'create' :
                $action = 'add';
                break;
            case 'update' :
                $action = 'edit';
                break;
            case 'destroy' :
                $action = 'delete';
                break;
        }

        $query         = http_build_query( array_merge(
            [ 'page' => $this->resource . '_' . $action ],
            $params
        ) );
        $this->formUrl = admin_url() . 'admin.php?' . $query;

        return $this;
    }

    /**
     * Return old data is missing
     *
     * @param bool $load_only_old
     *
     * @return $this
     */
    public function useOld($load_only_old = false)
    {
        $this->model->oldStore($load_only_old);

        return $this;
    }

    /**
     * Use Data
     *
     * @param array $data
     *
     * @return $this
     */
    public function useData( array $data)
    {
        $this->model->dataOverride($data);

        return $this;
    }

    /**
     * Use External Resource for Data
     *
     * This method changes the model class used to a stub and maps the
     * data pool to a standard array to override to fields data map.
     *
     * @param array $data
     *
     * @return $this
     */
    public function useExternal($data = [])
    {
        $this->model = new Model();
        return $this->useOld(true)->useData($data);
    }

    /**
     * Use Form for Widget
     *
     * @param \WP_Widget $widget
     * @param array $data
     *
     * @return $this
     */
    public function useWidget(\WP_Widget $widget, $data = [])
    {
        return $this->setWidgetPrefix($widget)->useExternal($data);
    }

    /**
     * Set the current Field to process
     *
     * @param Field $field
     *
     * @return $this
     */
    public function setCurrentField( Field $field )
    {
        $this->currentField = null;

        if ($field instanceof Field) {
            $this->currentField = $field;
        }

        return $this;
    }

    /**
     * Get the current Field the From is processing
     *
     * @return Field
     */
    public function getCurrentField()
    {
        return $this->currentField;
    }

    /**
     * Open Form Element
     *
     * Not needed post types, for example, since WordPress already opens this for you.
     *
     * @param array $attr
     *
     * @return string
     */
    public function open( $attr = [] )
    {
        $r = '';

        if( ! $this->method ) {
            switch ($this->action) {
                case 'update' :
                    $this->method = 'PUT';
                    break;
                case 'create' :
                    $this->method = 'POST';
                    break;
                case 'destroy' :
                    $this->method = 'DELETE';
                    break;
                default :
                    $this->method = 'PUT';
                    break;
            }
        }

        $ajax     = [];
        $defaults = [
            'action'      => $this->formUrl ? $this->formUrl : $_SERVER['REQUEST_URI'],
            'method'      => 'POST',
            'accept-charset' => 'UTF-8'
        ];

        if ($this->useAjax === true) {
            $ajax = [
                'class'    => 'typerocket-ajax-form'
            ];
        }

        $attr = array_merge( $defaults, $attr, $ajax );

        $form      = new Tag( 'form', $attr );
        $generator = new Generator();
        $r .= $form->getStringOpenTag();

        if ($this->useAjax === true) {
            $r .= $generator->newInput( 'hidden', '_tr_ajax_request', '1' )->getString();
        }

        $r .= $generator->newInput( 'hidden', '_method', $this->method )->getString();
        $r .= wp_nonce_field( 'form_' .  Config::getSeed() , '_tr_nonce_form', false, false );

        return $r;
    }

    /**
     * Close the From Element and add a submit button if value is string
     *
     * @param null|string $value
     *
     * @return string
     */
    public function close( $value = null )
    {
        $html = '';
        if (is_string( $value )) {
            $generator = new Generator();
            $html .= $generator->newInput( 'submit', '_tr_submit_form', $value,
                ['class' => 'button button-primary'])->getString();
        }

        $html .= '</form>';

        return $html;
    }

    /**
     * Get the Form Field Label
     *
     * @return string
     */
    protected function getLabel()
    {
        $open_html  = "<div class=\"control-label\"><span class=\"label\">";
        $close_html = '</span></div>';
        $debug      = $this->getDebug();
        $html       = '';
        $label      = $this->currentField->getLabelOption();
        $required   = $this->currentField->getRequired() ? '<span class="tr-required">*</span>' : '';

        if ($label) {
            $label = $this->currentField->getSetting( 'label' );
            $html  = "{$open_html}{$label} {$required} {$debug}{$close_html}";
        } elseif ($debug !== '') {
            $html = "{$open_html}{$debug}{$close_html}";
        }

        return $html;
    }

    /**
     * Get the debug mode helper content
     *
     * @param Field $field
     *
     * @return string
     */
    protected function getFieldHelpFunction( Fields\Field $field )
    {
        $helper = $field->getDebugHelperFunction();

        if($helper) {
            $function = $helper;
        } else {
            $dots   = $field->getDots();
            $resource = $field->getResource();
            $controller = $resource;
            $param = '';

            if($this->model instanceof WPPost) {
                $controller = 'posts';
            } elseif($this->model instanceof WPTerm) {
                $controller = 'taxonomies';
                $param = ", '{$resource}'";
                $id = $field->getItemId() ? $field->getItemId() : '$id';
                $param .= ', '.$id;
            } elseif($this->model instanceof WPOption) {
                $controller = 'options';
                $param = '';
            } elseif($this->model instanceof Model) {
                $controller = 'resource';
                $param = ", '{$resource}'";
                $id = $field->getItemId() ? $field->getItemId() : '$id';
                $param .= ', '.$id;
            }

            $function   = "tr_{$controller}_field('{$dots}'{$param});";
        }

        return $function;
    }

    /**
     * Get the debug HTML for the From Field Label
     *
     * @return string
     */
    protected function getDebug()
    {
        $generator = new Generator();
        $html      = '';
        if ($this->getDebugStatus() === true && ! $this->currentField instanceof Submit ) {
            $dev_html = $this->getFieldHelpFunction( $this->currentField );
            $fillable = $this->model->getFillableFields();
            $guard = $this->model->getGuardFields();
            $builtin = $this->model->getBuiltinFields();

            $icon = '<i class="tr-icon-bug"></i>';

            if(in_array($this->currentField->getName(), $builtin)) {
                $icon = '<i class="tr-icon-table"></i> ' . $icon;
            }

            if(in_array($this->currentField->getName(), $fillable )) {
                $icon = '<i class="tr-icon-pencil"></i> ' . $icon;
            } elseif(in_array($this->currentField->getName(), $guard )) {
                $icon = '<i class="tr-icon-shield"></i> ' . $icon;
            }

            $generator->newElement( 'div', [ 'class' => 'dev' ], $icon );
            $navTag       = new Tag( 'span', [ 'class' => 'nav' ] );
            $fieldCopyTag = new Tag( 'span', [ 'class' => 'field' ], $dev_html );
            $navTag->appendInnerTag( $fieldCopyTag );
            $html = $generator->appendInside( $navTag )->getString();
        }

        return $html;
    }

    /**
     * Set the form debug status
     *
     * @param bool $status
     *
     * @return $this
     */
    public function setDebugStatus( $status )
    {
        $this->debugStatus = (bool) $status;

        return $this;
    }

    /**
     * Get the From debug status
     *
     * @return bool|null
     */
    public function getDebugStatus()
    {
        return ( $this->debugStatus === false ) ? $this->debugStatus : Config::getDebugStatus();
    }

    /**
     * Get Form Field string
     *
     * @param Field $field
     *
     * @return string
     */
    public function getFromFieldString( Field $field )
    {
        $this->setCurrentField( $field );
        $label     = $this->getLabel();
        $field     = $field->getString();
        $id        = $this->getCurrentField()->getSetting( 'id' );
        $help      = $this->getCurrentField()->getSetting( 'help' );
        $section_class = $this->getCurrentField()->getSetting( 'classes', '' );
        $fieldHtml = $this->getCurrentField()->getSetting( 'render' );
        $formHtml  = $this->getSetting( 'render' );

        $id   = $id ? "id=\"{$id}\"" : '';
        $help = $help ? "<div class=\"help\"><p>{$help}</p></div>" : '';

        if ($fieldHtml == 'raw' || $formHtml == 'raw') {
            $html = $field;
        } else {
            $type = strtolower( str_ireplace( '\\', '-', get_class( $this->getCurrentField() ) ) );
            $html = "<div class=\"control-section {$section_class} {$type}\" {$id}>{$label}<div class=\"control\">{$field}{$help}</div></div>";
        }

        $html = apply_filters( 'tr_from_field_html', $html, $this );
        $this->currentField = null;

        return $html;
    }

    /**
     * Get From fields string from array
     *
     * @param array $fields
     *
     * @return string
     */
    public function getFromFieldsString( $fields = [] )
    {
        $html = '';

        foreach ($fields as $field) {

            if($field instanceof Field) {
                $clone_field = clone $field;
                $html .= (string) $clone_field->configureToForm($this);
            } elseif($field instanceof FieldRow) {
                $row = clone $field;
                foreach ($row->fields as $key => $row_field) {
                    if($row_field instanceof Field) {
                        $row_field = clone $row_field;
                        $row_field->configureToForm($this);
                        $row->fields[$key] = $row_field;
                    }
                }
                $html .= (string) $row;
            } elseif($field instanceof Tabs) {
                $tab = clone $field;
                $buf = tr_buffer()->startBuffer();
                $tabs = $tab->setForm($this);
                $_tabs = $tabs->getTabs();
                foreach ($_tabs as $key => $tab) {
                    if(!empty($tab['fields'])) {
                        foreach ($tab['fields'] as $i_key => $option) {
                            if($option instanceof Field) {
                                $option_field = clone $option;
                                $option_field->configureToForm($this);
                                $_tabs[$key]['fields'][$i_key] = $option_field;
                            } elseif( $option instanceof FieldRow) {
                                $row = clone $option;
                                foreach ($row->fields as $r_key => $row_field) {
                                    if($row_field instanceof Field) {
                                        $row_field = clone $row_field;
                                        $row_field->configureToForm($this);
                                        $row->fields[$r_key] = $row_field;
                                    }
                                }
                                $_tabs[$key]['fields'][$i_key] = $row;
                            }
                        }
                    }
                }
                $tabs->setTabs($_tabs)->uidTabs()->render();
                $html .= (string) $buf->getCurrent();
                $buf = $field = null;
            }  elseif(is_array($field) && count($field) > 1) {
                $function   = array_shift( $field );
                $parameters = array_pop( $field );

                if (method_exists( $this, $function ) && is_array( $parameters )) {
                    $html .= (string) call_user_func_array( [ $this, $function ], $parameters );
                }
            }

        }

        return $html;
    }

    /**
     * Get fields as row
     *
     * Array of fields or args of fields
     *
     * @param array|Field $fields
     *
     * @return FieldRow
     */
    public function row( $fields ) {
        if( ! is_array( $fields) ) {
            $fields = func_get_args();
        }

        return new FieldRow( $fields );
    }

    /**
     * Text Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Text
     */
    public function text( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Text( $name, $attr, $settings, $label, $this );
    }

    /**
     * Password Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Text
     */
    public function password( $name, array $attr = [], array $settings = [], $label = true )
    {
        $field = new Fields\Text( $name, $attr, $settings, $label, $this );
        $field->setType( 'password' )->setPopulate(false);

        return $field;
    }

    /**
     * Hidden Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|false $label
     *
     * @return Fields\Text
     */
    public function hidden( $name, array $attr = [], array $settings = [], $label = false )
    {
        $field = new Fields\Text( $name, $attr, $settings, $label, $this );
        $field->setType( 'hidden' )->setRenderSetting( 'raw' );

        return $field;
    }

    /**
     * Submit Button
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|false $label
     *
     * @return Fields\Submit
     */
    public function submit( $name, array $attr = [], array $settings = [], $label = false )
    {
        $field = new Fields\Submit( $name, $attr, $settings, $label, $this );
        $field->setAttribute( 'value', $name );

        return $field;
    }

    /**
     * Textarea Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Textarea
     */
    public function textarea( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Textarea( $name, $attr, $settings, $label, $this );
    }

    /**
     * Editor Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Editor
     */
    public function editor( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Editor( $name, $attr, $settings, $label, $this );
    }

    /**
     * Radio Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Radio
     */
    public function radio( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Radio( $name, $attr, $settings, $label, $this );
    }

    /**
     * Checkbox Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Checkbox
     */
    public function checkbox( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Checkbox( $name, $attr, $settings, $label, $this );
    }

    /**
     * Select Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Select
     */
    public function select( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Select( $name, $attr, $settings, $label, $this );
    }

    /**
     * WordPress Editor
     *
     * Use this only once per page. The WordPress Editor is very buggy. You cannot use
     * this in Meta boxes and repeatable sections.
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\WordPressEditor
     */
    public function wpEditor( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\WordPressEditor( $name, $attr, $settings, $label, $this );
    }

    /**
     * Color Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Color
     */
    public function color( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Color( $name, $attr, $settings, $label, $this );
    }

    /**
     * Date Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Date
     */
    public function date( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Date( $name, $attr, $settings, $label, $this );
    }

    /**
     * Image Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Image
     */
    public function image( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Image( $name, $attr, $settings, $label, $this );
    }

    /**
     * File Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\File
     */
    public function file( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\File( $name, $attr, $settings, $label, $this );
    }

    /**
     * Search Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Search
     */
    public function search( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Search( $name, $attr, $settings, $label, $this );
    }

    /**
     * Gallery Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Gallery
     */
    public function gallery( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Gallery( $name, $attr, $settings, $label, $this );
    }

    /**
     * Items Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Items
     */
    public function items( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Items( $name, $attr, $settings, $label, $this );
    }

    /**
     * Matrix Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Matrix
     */
    public function matrix( $name, array $attr = [], array $settings = [], $label = true ) {
        return new Fields\Matrix( $name, $attr, $settings, $label, $this );
    }

    /**
     * Builder Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Matrix
     */
    public function builder( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Builder( $name, $attr, $settings, $label, $this );
    }

    /**
     * Repeater Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Repeater
     */
    public function repeater( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Repeater( $name, $attr, $settings, $label, $this );
    }

    /**
     * Field object into input
     *
     * @param Fields\Field $field
     *
     * @return Field $field
     */
    public function field( Field $field )
    {
        return $field;
    }

}
