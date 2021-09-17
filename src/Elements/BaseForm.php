<?php
namespace TypeRocket\Elements;

use TypeRocket\Elements\Components\Fieldset;
use TypeRocket\Elements\Traits\Attributes;
use TypeRocket\Elements\Traits\DisplayPermissions;
use TypeRocket\Elements\Traits\Fieldable;
use TypeRocket\Elements\Traits\MacroTrait;
use TypeRocket\Html\Html;
use TypeRocket\Html\Tag;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Http\ErrorCollection;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Http\RouteCollection;
use TypeRocket\Interfaces\Formable;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\Model;
use TypeRocket\Elements\Traits\FormConnectorTrait;
use TypeRocket\Register\PostType;
use TypeRocket\Register\Registry;
use TypeRocket\Register\Taxonomy;
use TypeRocket\Utility\Data;
use TypeRocket\Utility\DataCollection;
use TypeRocket\Utility\Str;
use TypeRocket\Utility\Url;

class BaseForm
{
    use FormConnectorTrait, MacroTrait, Attributes, Fieldable, BaseFields, DisplayPermissions;

    protected $resource = null;
    protected $action = null;
    protected $useAjax = null;
    protected $useConfirm;
    protected $formUrl;
    protected $method = null;
    protected $errors = null;
    protected $submitButton = 'Save';
    protected $uploads = null;

    /**
     * Instance the From
     *
     * @param string|Formable|array|null $resource posts, users, comments or options
     * @param string|null $action update, delete, patch, or create
     * @param null|int $itemId you can set this to null or an integer
     * @param null|Model|string $model
     * @throws \Exception
     */
    public function __construct($resource = null, $action = null, $itemId = null, $model = null)
    {
        if( (is_int($action) || is_numeric($action)) && ! $itemId ) {
            $itemId = $action;
            $action = 'update';
        }

        switch (strtolower($action)) {
            case 'put' :
                $action = 'update';
                break;
            case 'post' :
                $action = 'create';
                break;
        }

        if($itemId && !$action) {
            $action = 'update';
        }

        $this->itemId = $itemId;

        if(is_array($resource)) {
            $this->resource = null;
            $this->action = $action ?? 'update';
            $this->setModel(new DataCollection($resource));
        }
        elseif($resource instanceof Model) {
            $this->resource = null;
            $this->action = $action ?? ( $resource->hasProperties() ? 'update' : 'create' );
            $this->itemId = $itemId ?? ( $resource->hasProperties() ? $resource->getId() : null );
            $this->setModel($resource);
        }
        elseif($resource instanceof Formable) {
            $this->resource = null;
            $this->action = $action ?? 'update';
            $this->setModel($resource);
        }
        elseif(is_object($resource)) {
            $this->resource = null;
            $this->action = $action ?? 'update';

            if($resource instanceof \WP_Post) {
                $this->setModel( (new WPPost)->wpPost($resource, true) );
            } else {
                $this->setModel(new DataCollection($resource));
            }
        }
        elseif(class_exists($resource)) {
            $this->resource = null;
            $this->action = $action ?? 'create';
            $this->setModel($resource);
        }
        else {
            $this->resource = $resource;
            $this->autoConfigModel($model, $action);
        }

        if( !$this->resource && method_exists($this->model, 'getRouteResource') ) {
            $this->resource = $this->model->getRouteResource();
        }

        do_action('typerocket_from', $this);
    }

    /**
     * Auto config form if no Model is set.
     *
     * These global vars can impact the results of auto
     * config of the form: $post, $comment, $user_id,
     * $taxonomy, $tag_ID, and $screen
     *
     * @param mixed|null $model
     * @param null|string $action
     *
     * @return static
     * @throws \Exception
     */
    protected function autoConfigModel($model = null, $action = null)
    {
        if (!$this->resource && !$model) {
            global $post, $comment, $user_id, $taxonomy, $tag_ID, $screen;

            if ( isset( $post->ID ) && empty($taxonomy) && empty($screen) ) {
                $item_id  = $post->ID;

                $reg = Registry::getPostTypeResource($post->post_type);

                if(!empty($reg['object']) && $reg['object'] instanceof PostType) {
                    $model = $reg['object']->getResource('model');
                }

                if(!$model) {
                    $Resource = Str::camelize($reg['singular'] ?? null);
                    $model = \TypeRocket\Utility\Helper::appNamespace("Models\\{$Resource}");
                }

                if( !class_exists($model) ) {
                    $model = new WPPost($post->post_type);
                }

            }
            elseif ( isset($comment->comment_ID ) ) {
                $item_id  = $comment->comment_ID;
                $model = \TypeRocket\Utility\Helper::appNamespace("Models\Comment");
            }
            elseif ( isset( $user_id ) ) {
                $item_id  = $user_id;
                $model = \TypeRocket\Utility\Helper::appNamespace('Models\User');
            }
            elseif ( isset( $taxonomy ) || isset($tag_ID) ) {
                $item_id  = $tag_ID;
                $reg = Registry::getTaxonomyResource($taxonomy);

                if(!empty($reg['object']) && $reg['object'] instanceof Taxonomy) {
                    $model = $reg['object']->getResource('model');
                }

                if(!$model) {
                    $Resource = Str::camelize($reg['singular'] ?? null);
                    $model = \TypeRocket\Utility\Helper::appNamespace("Models\\{$Resource}");
                }

                if( !class_exists($model) ) {
                    $model = new WPTerm($taxonomy);
                }
            }
            else {
                $item_id  = null;
                $model = \TypeRocket\Utility\Helper::appNamespace("Models\\Option");
            }

            $this->itemId = $item_id;
        }
        elseif($this->resource && !$model) {
            $action = $action ?? 'create';
            $model = \TypeRocket\Utility\Helper::modelClass($this->resource);
        }

        $this->action = $action ?? 'update';
        $this->setModel( $model );

        return $this;
    }

    /**
     * Get controller
     *
     * @return null|string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set Resource
     *
     * @param string $resource
     * @return static
     */
    public function setResource(string $resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Set Action
     *
     * @return null|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set Action
     *
     * @param string $action
     *
     * @return static
     */
    public function setAction(string $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Set Form method
     *
     * @param string $method POST, PUT, DELETE
     *
     * @return static
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
        return $this;
    }
    
    /**
     * Get Form method
     *
     * @return static
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Disable Ajax
     *
     * @return static
     */
    public function disableAjax() {
        $this->useAjax = null;

        return $this;
    }

    /**
     * Use Ajax
     *
     * @return $this
     */
    public function useAjax() {
        $this->useAjax = true;

        return $this;
    }

    /**
     * Use Confirm
     *
     * Only works with form's open HTML tag
     *
     * @return static
     */
    public function useConfirm()
    {
        $this->useConfirm = true;

        return $this;
    }

    /**
     * Use TypeRocket Rest API to submit form
     *
     * @param null|string $resource override resource name useful for setting custom post type IDs etc.
     * @param null|int|string $item_id
     *
     * @return static
     */
    public function useRest($resource = null, $item_id = null)
    {
        $this->useAjax();
        $this->resource = $resource ?? $this->resource;
        $this->itemId = $item_id ?? $this->itemId ?? '';

        $this->formUrl = get_site_url(null, '/tr-api/rest/' . $this->resource . '/' . $this->itemId);

        return $this;
    }

    /**
     * Return old data if missing
     *
     * @param bool $load_only_old
     *
     * @return static
     */
    public function useOld($load_only_old = false)
    {
        $this->model->oldStore($load_only_old);

        return $this;
    }

    /**
     * Set Errors
     *
     * @param string|null $key
     * @param null|array $override
     *
     * @return static
     */
    public function useErrors($key = 'fields', $override = null)
    {
        if($override) {
            $this->errors = Data::walk($key ?? 'fields', $override);
        }
        elseif ($errors = ErrorCollection::getFromRuntimeCache())    {
            /** @var null|ErrorCollection $errors */
            $fields = $errors->errors();
            if(is_array($fields)) {
                $errors = $override ?? $fields;
                $this->errors = Data::walk($key ?? 'fields', $errors);
            }
        }

        return $this;
    }

    /**
     * @param $key
     *
     * @return null|string
     */
    public function getError($key)
    {
        return $this->errors[$key] ?? null;
    }

    /**
     * Use Form for Widget
     *
     * @param \WP_Widget $widget
     * @param array $data
     *
     * @return static
     * @throws \ReflectionException
     */
    public function useWidget(\WP_Widget $widget, $data = [])
    {
        $data = new DataCollection($data);
        return $this->setWidgetPrefix($widget)->setModel($data);
    }

    /**
     * Use Menu
     *
     * @param $menu_id
     *
     * @return static
     */
    public function useMenu($menu_id)
    {
        return $this->setMenuPrefix($menu_id);
    }

    /**
     * Use a URL
     *
     * @param string $url
     * @param string $method
     * @param bool $site
     *
     * @return static
     */
    public function toUrl($url, $method = null, $site = null)
    {
        if(strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            $site = $site ?? false;
        }

        $site = $site ?? true;

        switch($method) {
            case 'create' :
                $method = 'POST';
                break;
            case 'update' :
                $method = 'PUT';
                break;
            case 'destroy' :
                $method = 'DELETE';
                break;
        }

        $this->method  = $method ? strtoupper($method) : $this->method;
        $this->formUrl = $site ? get_site_url( null, ltrim($url, '/') ) : $url;

        return $this;
    }

    /**
     * @param string $name
     * @param null|array $values
     * @param null|string $method
     *
     * @return static
     */
    public function toRoute($name, $values = null, $method = null)
    {
        $routes = RouteCollection::getFromContainer();
        $located = $routes->getNamedRoute($name);

        $url = $located->buildUrlFromPattern($values ?? $this->model->getProperties());
        return $this->toUrl($url, $method, false);
    }

    /**
     * To Admin URL
     *
     * @param string $path
     * @param array $query
     *
     * @return static
     */
    public function toAdmin($path, $query = [])
    {
        $this->formUrl = admin_url() . $path;

        if(!empty($query)) {
            $this->formUrl .= '?' . http_build_query($query);
        }

        return $this;
    }

    /**
     * Use TypeRocket Rest to submit form
     *
     * @param null|string $resource
     * @param null|string $action common options include add, edit, index, show and delete (reserved: create, update, destroy)
     * @param null|int $item_id
     * @param string $root_path examples admin.php, tools.php, ect.
     *
     * @return static
     */
    public function toPage($resource = null, $action = null, $item_id = null, $root_path = 'admin.php')
    {
        $params = [];

        if($this->itemId || $item_id) {
            $params = ['route_' => [$item_id ?? $this->itemId]];
        }

        $action = $action ?? $this->action;
        $resource = $resource ?? $this->resource;

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

        $query = http_build_query( array_merge(
            [ 'page' => $resource . ( $action ? '_' . $action : null) ],
            $params
        ) );

        $this->formUrl = admin_url() . $root_path . '?' . $query;

        return $this;
    }

    /**
     * Get Form URL
     *
     * @return mixed
     */
    public function getFormUrl()
    {
        return $this->formUrl;
    }

    /**
     * TypeRocket Nonce Field
     *
     * @param string $action
     *
     * @return string
     */
    public static function nonceInput($action = '')
    {
        return wp_nonce_field('form_' . $action . \TypeRocket\Core\Config::get('app.seed'), '_tr_nonce_form' . $action, false, false);
    }

    /**
     * TypeRocket Nonce Field
     *
     * @param string $method GET, POST, PUT, PATCH, DELETE
     *
     * @return string
     */
    public static function methodInput($method = 'POST')
    {
        return "<input type=\"hidden\" name=\"_method\" value=\"{$method}\"  />";
    }

    /**
     * Prefix Field
     *
     * @param string $prefix prefix fields will be grouped under
     *
     * @return string
     */
    public static function prefixInput($prefix = 'tr')
    {
        return "<input type=\"hidden\" name=\"_tr_form_prefix\" value=\"{$prefix}\"  />";
    }

    /**
     * TypeRocket Nonce Field
     *
     * @param string $method GET, POST, PUT, PATCH, DELETE
     * @param string $prefix prefix fields will be grouped under
     *
     * @return string
     */
    public static function hiddenInputs($method = 'POST', $prefix = 'tr')
    {
        return static::methodInput($method) . static::nonceInput() . static::prefixInput($prefix);
    }

    /**
     * Check Spam Honeypot
     *
     * @param null|string $name
     *
     * @return Html
     */
    public static function honeypotInputs($name = null)
    {
        $name = $name ?? 'my_name_' . md5(time());
        $fields = "<input type='text' name='__hny[{$name}]' /><input type='checkbox' name='__hny[send_message]' />";
        return Html::div(['style' => 'display: none', 'class' => 'tr-bot-tasty-treat'], $fields);
    }

    /**
     * Add Multipart Form Data
     *
     * @return BaseForm
     */
    public function allowFileUploads()
    {
        $this->uploads = true;
        return $this->setAttribute('enctype', 'multipart/form-data');
    }

    /**
     * Open Form Element
     *
     * Not needed post types, for example, since WordPress already opens this for you.
     *
     * @param array $attr
     * @param array $request_params append query params to action
     * @param null|string $method PUT, POST, and DELETE
     *
     * @return string
     */
    public function open( $attr = [], $request_params = [], $method = null)
    {
        $r = '';

        if($method) {
            $this->method = $method;
        }

        if( ! $this->method ) {
            switch ($this->action) {
                case 'create' :
                case 'post' :
                    $this->method = 'POST';
                    break;
                case 'destroy' :
                case 'delete' :
                    $this->method = 'DELETE';
                    break;
                case 'patch' :
                    $this->method = 'PATCH';
                    break;
                default :
                    $this->method = 'PUT';
                    break;
            }
        }

        $url = $this->formUrl ? $this->formUrl : (new Request)->getUriFull();

        $defaults = [
            'action'      => Url::withQuery($url, $request_params),
            'method'      => 'POST',
            'accept-charset' => 'UTF-8',
        ];

        if ($this->useAjax === true) {
            $this->attrClass('tr-ajax-form');
        }

        if ($this->useConfirm) {
            $this->attrClass('tr-form-confirm');
        }

        $this->attrClass('tr-form-container');
        $this->attrClass($attr['class'] ?? '');

        $attr['class'] = $this->attr['class'];

        $attr = array_merge( $defaults, $this->attr, $attr );

        $r .= Tag::el('form', $attr)->open();

        if ($this->useAjax === true) {
            $r .= Html::input( 'hidden', '_tr_ajax_request', '1' );
        }

        $r .= static::hiddenInputs($this->method, $this->getPrefix());

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

        if ($value) {
            $value = (string) $value;

            $html .= '<div class="tr-form-action">';
            $html .= Html::input( 'submit', '_tr_submit_form', esc_attr($value), ['class' => 'button button-primary']);
            $html .= '</div>';
        }

        $html .= '</form>';

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
    public function row(...$fields) {
        return (new FieldRow(...$fields))->configureToForm($this);
    }

    /**
     * Get fields as grouped section
     *
     * Array of fields or args of fields
     *
     * @param array|Field|\TypeRocket\Elements\FieldColumn $fields
     *
     * @return FieldSection
     */
    public function section(...$fields) {
        return (new FieldSection(...$fields))->configureToForm($this);
    }

    /**
     * Get Fieldset
     *
     * @param string $title
     * @param string $description
     * @param mixed ...$arg
     *
     * @return Fieldset
     */
    public function fieldset($title, $description, ...$arg)
    {
        return (new Fieldset($title, $description, ...$arg))->configureToForm($this);
    }

	/**
	 * Add text between fields in row
	 *
	 * @param string $content
	 *
	 * @return Html
	 */
    public function textContent($content) {
    	return Html::div(['class' => 'tr-control-section tr-divide'], $content);
    }

    /**
     * @param null|string $name
     *
     * @return Html
     */
    public function honeypot($name = null)
    {
        return static::honeypotInputs(...func_get_args());
    }

    /**
     * Field object into input
     *
     * @param Fields\Field $field
     *
     * @return Field $field
     */
    public function custom( Field $field )
    {
        return $field->configureToForm($this);
    }

    /**
     * Set Submit Button Text
     *
     * @param string $submit
     *
     * @return static
     */
    public function save($submit = 'Save')
    {
        $this->submitButton = $submit;

        return $this;
    }

    /**
     * Render Form with fields
     *
     * Not needed post types, for example, since WordPress already opens this for you.
     *
     * @param array $attr
     * @param array $request_params append query params to action
     * @param null|string $method PUT, POST, and DELETE
     * @param string|null $submit
     */
    public function render($attr = [], $request_params = [], $method = null, $submit = null)
    {
        echo $this->toString($attr, $request_params, $method, $submit);
    }

    /**
     * Get String
     *
     * @param array $attr
     * @param array $request_params append query params to action
     * @param null|string $method PUT, POST, and DELETE
     * @param string|null $submit
     *
     * @return string
     */
    public function toString($attr = [], $request_params = [], $method = null, $submit = null)
    {
        if(!$this->canDisplay()) { return ''; }
        $html = '';
        $html .= $this->open($attr, $request_params, $method);
        $html .= $this->fieldsWrapperString();
        $html .= $this->close($submit ?? $this->submitButton);

        return $html;
    }

    /**
     * @return string
     */
    public function fieldsWrapperString()
    {
        if(!$this->canDisplay()) { return ''; }
        $html = '';
        $html .= '<div class="tr-form-fields">';
        $html .= $this->getFieldsString();
        $html .= '</div>';

        return $html;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if(!$this->canDisplay()) {
            return '';
        }

        return $this->toString();
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     * @throws \Exception
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }
}
