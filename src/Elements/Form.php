<?php
namespace TypeRocket\Elements;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Fields\Submit;
use TypeRocket\Elements\Traits\MacroTrait;
use TypeRocket\Html\Generator;
use TypeRocket\Html\Tag;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Models\WPComment;
use TypeRocket\Models\WPOption;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\Model;
use TypeRocket\Elements\Traits\FormConnectorTrait;
use TypeRocket\Models\WPUser;
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
    protected $translateLabelDomain = null;

    /**
     * Instance the From
     *
     * @param string $resource posts, users, comments or options
     * @param string $action update or create
     * @param null|int $itemId you can set this to null or an integer
     * @param null|Model|string $model
     * @throws \Exception
     */
    public function __construct( $resource = 'auto', $action = 'update', $itemId = null, $model = null )
    {
        if( is_int($action) && ! $itemId ) {
            $itemId = $action;
            $action = 'update';
        }

        $this->resource = strtolower($resource);
        $this->action = $action;
        $this->itemId = $itemId;
        $this->autoConfig($model);

        $Resource = Str::camelize($this->resource);
        $this->setModel($this->model ?? $model ?? tr_app("Models\\{$Resource}"));

        do_action('tr_after_form_element_init', $this);
    }

    /**
     * Set the model for the form
     *
     * @param Model|string $model
     *
     * @return $this
     * @throws \Exception
     */
    public function setModel( $model )
    {
        if ( !is_string($model) && $model instanceof Model) {
            $this->model = $model;
        } elseif(class_exists($model)) {
            $this->model = new $model();
        }

        if( !empty($this->itemId) ) {
            $this->model->findById($this->itemId);
        }

        return $this;
    }

    /**
     * Auto config form if no Model is set.
     *
     * These global vars can impact the results of auto
     * config of the form: $post, $comment, $user_id,
     * $taxonomy, $tag_ID, and $screen
     *
     * @param string $model
     * @return $this
     */
    protected function autoConfig($model)
    {
        if ($this->resource === 'auto' && is_null($model)) {
            global $post, $comment, $user_id, $taxonomy, $tag_ID, $screen;

            if ( isset( $post->ID ) && empty($taxonomy) && empty($screen) ) {
                $item_id  = $post->ID;
                $resource_data = Registry::getPostTypeResource($post->post_type);

                $Resource = Str::camelize($resource_data[0] ?? '');
                $model = $resource_data[2] ?? tr_app("Models\\{$Resource}");
                $resource = $resource_data[0] ?? null;

                if(! class_exists($model) ) {
                    $this->model = new WPPost($post->post_type);
                } else {
                    $this->model = $model;
                }

                if( empty($resource) ) {
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
                $resources = Registry::getTaxonomyResource($taxonomy);
                $Resource = Str::camelize($resources[0] ?? '');
                $resource = $resources[0] ?? null;
                $model = $resource[2] ?? tr_app("Models\\{$Resource}");

                if(! class_exists($model) ) {
                    $this->model = new WPTerm($taxonomy);
                } else {
                    $this->model = $model;
                }

                if( empty($resource) ) {
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
     * Set Form method
     *
     * @return Form $this
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
        return $this;
    }
    
    /**
     * Get Form method
     *
     * @return Form $this
     */
    public function getMethod()
    {
        return $this->method;
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
     * @param null|string $resource override resource name useful for setting custom post type IDs etc.
     * @return Form $this
     */
    public function useJson($resource = null)
    {
        if( $this->useAjax === null ) {
            $this->useAjax();
        }

        $the_resource = $this->resource;

        if($this->model instanceof WPPost) {
            $pt = $this->model->getPostType();
            $the_resource = $the_resource != $pt ? $pt : $the_resource;
        }

        if($this->model instanceof WPTerm) {
            $tx = $this->model->getTaxonomy();
            $the_resource = $the_resource != $tx ? $tx : $the_resource;
        }

        $the_resource = $resource ?? $the_resource;

        $this->formUrl = home_url('/', get_http_protocol() ) . 'tr_json_api/v1/' . $the_resource . '/' . $this->itemId;

        return $this;
    }

    /**
     * Use a URL
     *
     * @param string $method
     * @param string $url
     *
     * @return $this
     */
    public function useUrl($method, $url)
    {
        $url_parts     = explode('/', trim($url, '/') );
        $this->formUrl = home_url(implode('/', $url_parts ) . '/', get_http_protocol() );
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
     * Return old data if missing
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
     * @throws \ReflectionException
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
     * @throws \ReflectionException
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
        $r .= wp_nonce_field( 'form_' .  Config::locate('app.seed') , '_tr_nonce_form', false, false );

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
        $label_tag = $this->currentField->getLabelTag();
        $label_for = $this->currentField->getInputId();
        $label_for_spoof = $this->currentField->getSpoofInputId();
        $open_html  = "<div class=\"control-label\"><{$label_tag} data-trfor=\"{$label_for_spoof}\" for=\"{$label_for}\" class=\"label\">";
        $close_html = "</{$label_tag}></div>";
        $debug      = $this->getDebug();
        $html       = '';
        $label      = $this->currentField->getLabelOption();
        $required   = $this->currentField->getRequired() ? '<span class="tr-required">*</span>' : '';

        if ($label) {
            $label = $this->currentField->getSetting( 'label' );

            $label_translate = $this->getLabelTranslationDomain();
            if($label_translate) {
                $label = __($label, $label_translate);
            } else {
                $label = __($label);
            }

            $html  = "{$open_html}{$label} {$required} {$debug}{$close_html}";
        } elseif ($debug !== '') {
            $html = "{$open_html}{$debug}{$close_html}";
        }

        return $html;
    }

    /**
     * Get Translate Label Domain
     *
     * @return bool
     */
    public function getLabelTranslationDomain()
    {
        return $this->translateLabelDomain;
    }

    /**
     * Set Translate Label Domain
     *
     * @param string $string
     * @return $this
     */
    public function setLabelTranslationDomain($string)
    {
        $this->translateLabelDomain = (bool) $string;

        return $this;
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
        $mod = $field->getDebugHelperFunctionModifier() ?? '';

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
            } elseif($this->model instanceof WPUser) {
                $controller = 'users';
                $param = '';
            }  elseif($this->model instanceof WPComment) {
                $controller = 'comments';
                $param = '';
            } elseif($this->model instanceof Model) {
                $controller = 'resource';
                $param = ", '{$resource}'";
                $id = $field->getItemId() ? $field->getItemId() : '$id';
                $param .= ', '.$id;
            }

            $function   = "tr_{$controller}_field('{$mod}{$dots}'{$param});";
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
        return ( $this->debugStatus === false ) ? $this->debugStatus : Config::locate('app.debug');
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

        $id        = $field->getSetting( 'id' );
        $help      = $field->getSetting( 'help' );
        $section_class = $field->getSetting( 'classes', '' );
        $fieldHtml = $field->getSetting( 'render' );
        $formHtml  = $this->getSetting( 'render' );
        $fieldString     = $field->getString();

        $id   = $id ? "id=\"{$id}\"" : '';
        $help = $help ? "<div class=\"tr-form-field-help\"><p>{$help}</p></div>" : '';

        if ($fieldHtml == 'raw' || $formHtml == 'raw') {
            $html = apply_filters( 'tr_from_field_html_raw', $fieldString, $field );
        } else {
            $type = strtolower( str_ireplace( '\\', '-', get_class( $field ) ) );
            $html = "<div class=\"control-section {$section_class} {$type}\" {$id}>{$label}<div class=\"control\">{$fieldString}{$help}</div></div>";
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
        $obj = $this;

        $config_field = function($field) use ($obj) {
            $clone_field = clone $field;
            $clone_field->configureToForm($obj);
            return $clone_field;
        };

        $config_column = function($column) use ($config_field) {
            $clone_column = clone $column;
            foreach ($clone_column->fields as $key => $field) {
                if($field instanceof Field) {
                    $clone_column->fields[$key] = $config_field($field);
                }
            }

            return $clone_column;
        };

        $config_row = function($row) use ($config_field, $config_column) {
            $clone_row = clone $row;
            foreach ($clone_row->fields as $key => $field) {
                if($field instanceof Field) {
                    $clone_row->fields[$key] = $config_field($field);
                } elseif($field instanceof FieldColumn) {
                    $clone_row->fields[$key] = $config_column($field);
                }
            }

            return $clone_row;
        };

        foreach ($fields as $field) {

            if($field instanceof Field) {
                $html .= (string) $config_field($field);
            } elseif($field instanceof FieldRow) {
                $html .= (string) $config_row($field);
            } elseif($field instanceof Tabs) {
                $tab = clone $field;
                $buf = tr_buffer()->startBuffer();
                $tabs = $tab->setForm($this);
                $_tabs = $tabs->getTabs();
                foreach ($_tabs as $key => $tab) {
                    if(!empty($tab['fields'])) {
                        foreach ($tab['fields'] as $i_key => $option) {
                            if($option instanceof Field) {
                                $_tabs[$key]['fields'][$i_key] = $config_field($option);
                            } elseif( $option instanceof FieldRow) {
                                $_tabs[$key]['fields'][$i_key] = $config_row($option);
                            }
                        }
                    }
                }
                $tabs->setTabs($_tabs)->uidTabs()->render();
                $html .= (string) $buf->getCurrent();
                $buf = $field = null;
            }
        }

        return $html;
    }

    /**
     * Get fields as row
     *
     * Array of fields or args of fields
     *
     * @param array|Field|\TypeRocket\Elements\FieldColumn $fields
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
	 * Add text between fields in row
	 *
	 * @param string $content
	 *
	 * @return Generator
	 */
    public function rowText( $content ) {
    	$generator = new Generator;
    	$generator->newElement( 'div', ['class' => 'control-section'], $content );

    	return $generator;
    }

    /**
     * Get fields as row
     *
     * Array of fields or args of fields
     *
     * @param array|Field $fields
     *
     * @return FieldColumn
     */
    public function column( $fields ) {
        if( ! is_array( $fields) ) {
            $fields = func_get_args();
        }

        return new FieldColumn( $fields );
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
     * Links Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Links
     */
    public function links( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Links( $name, $attr, $settings, $label, $this );
    }

    /**
     * Toggle Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Toggle
     */
    public function toggle( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Toggle( $name, $attr, $settings, $label, $this );
    }

    /**
     * Location Inputs
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool|true $label
     *
     * @return Fields\Location
     */
    public function location( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Fields\Location( $name, $attr, $settings, $label, $this );
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
     * @return Fields\Builder
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
