<?php
namespace TypeRocket\Elements\Fields;

use TypeRocket\Html\Html;
use TypeRocket\Models\Model;

class Search extends Field implements ScriptField
{
    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'search' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts()
    {
        wp_enqueue_script('jquery-ui-sortable', ['jquery'], false, true );
    }

    /**
     * Covert Test to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        if($this->getSetting('multiple'))
            return $this->getMultipleString();
        else
            return $this->getSingleString();
    }

    /**
     * Covert Test to HTML string
     */
    public function getSingleString()
    {
        $input = new Html();
        $name = $this->getNameAttributeString();
        $value = $this->setCast('string')->getValue();
        $title = __('No selection... Search and click on a result', 'typerocket-domain');
        $this->setAttribute('data-tr-field', $this->getContextId());

        $search_attributes = $this->getSearchAttributes();

        if($value) {
            $title = $this->getSearchItem($value, $name);
        }

        $field = '<div class="tr-search-single">';
        $field .= $input->input('search', null, null, $search_attributes);
        $field .= $input->input( 'hidden', $name, $value, $this->getAttributes() );
        $field .= '<div class="tr-search-selected">'.$title.'</div>';
        $field .= '<ol class="tr-search-results"></ol>';
        $field .= '</div>';

        return $field;
    }

    /**
     * Covert Test to HTML string
     */
    public function getMultipleString()
    {
        $input = new Html();
        $name = $this->getNameAttributeString();
        $results_title = __('No selection... Search and click on a result', 'typerocket-domain');
        $data = $this->getValue() ?? [];
        $field = '';

        $search_attributes = $this->getSearchAttributes();

        $links = [];
        if(is_array($data)) {
            $values = array_map('trim', $data);
            foreach ($values as $value) {
                $links[] = '<li tabindex="0" class="tr-search-chosen-item">' . $this->getSearchItem($value, $name, true) . '</li>';
            }
        }

        $field .= '<div class="tr-search-multiple">';
        $field .= '<div class="tr-search-controls">';
        $field .= $input->input('hidden', $name, '0', $this->attrClass( 'tr-field-hidden-input')->getAttributes() );
        $field .= $input->input('search', null, null,  $search_attributes);
        $field .= '<ol class="tr-search-results"></ol>';
        $field .= '</div>';
        $field .= '<ol data-placeholder="'.$results_title.'" class="tr-search-selected tr-search-selected-multiple">';

        if($links) {
            foreach ($links as $link) {
                $field .= $link;
            }
        }

        $field .= '</ol>';
        $field .= '</div>';

        return $field;
    }

    /**
     * @return array
     */
    protected function getSearchAttributes() {
        $type = $this->getSetting('post_type', 'any');
        $taxonomy = $this->getSetting('taxonomy');
        $model = $this->getSetting('model');
        $url = $this->getSetting('url_endpoint');
        $config = $this->getSetting('search-config');

        $search_attributes = [
            'placeholder' => __('Type to search...', 'typerocket-domain'),
            'class' => 'tr-search-input'
        ];

        if($url) {
            $search_attributes['data-endpoint'] = $url;
            if($map = $this->getSetting('url_map')) {
                $search_attributes['data-map'] = json_encode($map);
            }
        } if(!empty($taxonomy)) {
            $search_attributes['data-taxonomy'] = $taxonomy;
        } elseif(!empty($model)) {
            $search_attributes['data-model'] = $model;
        } else {
            $search_attributes['data-posttype'] = $type;
        }

        if(is_array($config)) {
            $search_attributes['data-search-config'] = json_encode($config);
        }

        return $search_attributes;
    }

    /**
     * Get Search Item
     *
     * @param mixed $value
     * @param string $name
     * @param bool $input
     *
     * @return string
     */
    protected function getSearchItem($value, $name, $input = false) {
        $remove = esc_attr(__('remove', 'typerocket-domain'));
        $remove_classes = 'tr-control-icon tr-control-icon-remove tr-search-chosen-item-remove';

        $flat = $this->getSetting('model_flat', false);
        $taxonomy = $this->getSetting('taxonomy');
        $model = $this->getSetting('model');
        $post_type = $this->getSetting('post_type', 'any');
        $url = $this->getSetting('url_endpoint');
        $selection = '';

        if($value && $input) {
            $selection = Html::input('hidden', $name . '[]', $value );
        }

        if($url) {
            $type = 'url';
            $flat = true;
            $registered = $url;
        } elseif( $taxonomy && $value ) {
            $type = 'taxonomy';
            $registered = $taxonomy;
        }
        elseif ($model && $value ) {
            $type = 'model';
            $registered = $model;
        }
        elseif( $value ) {
            $type = 'post_type';
            $registered = $post_type;
        }

        $title = static::getSearchTitle($value, ['id' => $type ?? null , 'registered' => $registered ?? null], $flat);

        return '<span class="tr-search-selection-option">' . $selection . ' ' . $title . ' </span><button aria-label="Close" type="button" tabindex="0" title="'.$remove.'" class="'.$remove_classes.'"><span class="tr-sr-only" aria-hidden="true">×</span></button>';
    }

    /**
     * Set Multiple
     *
     * @return Search
     */
    public function multiple()
    {
        return $this->setSetting('multiple', true);
    }

    /**
     * Search by post type only
     *
     * @param string $type
     *
     * @return $this
     */
    public function setPostTypeOptions($type)
    {
        $this->setSetting('post_type', $type);

        return $this;
    }

    /**
     * Search by taxonomy only
     *
     * @param string $taxonomy
     *
     * @return $this
     */
    public function setTaxonomyOptions($taxonomy)
    {
        $this->setSetting('taxonomy', $taxonomy);

        return $this;
    }

    /**
     * @param array $array
     *
     * @return $this
     */
    public function setSearchConfig(array $array)
    {
        return $this->setSetting('search-config', $array);
    }

    /**
     * Search URL Endpoint
     *
     * @param string $url
     * @param null|array $map
     *
     * Endpoint format must follow this pattern if map is not set:
     *
     * {
     *   "search_type":"post_type",
     *   "items": [ { "title":"<b>Hello world!</b> (post)", "id":1 } ],
     *   "count": "1 in limit of 10"
     * }
     *
     * @return $this
     */
    public function setUrlOptions($url, $map = null)
    {
        $this->setSetting('url_endpoint', $url);
        $this->setSetting('url_map', $map);

        return $this;
    }

    /**
     * Search by model only
     *
     * @param string $model class as string
     *
     * @return $this
     */
    public function setModelOptions($model)
    {
        $this->setSetting('model', $model);

        return $this;
    }

    /**
     * Set Model Flat
     *
     * Do not look up value when returned. This should only be used if the value
     * is also the title of the selection.
     *
     * @return Search
     */
    public function setModelFlat()
    {
        return $this->setSetting('model_flat', true);
    }

    /**
     * @param array|mixed $value
     * @param array $options
     * @param bool $flat
     *
     * @return mixed|void
     */
    public static function getSearchTitle($value, $options, $flat = false)
    {
        $deleted = '<em>' . __('item was deleted or is missing.', 'typerocket-domain') . '</em> ID:';
        $title = $deleted;
        $id = $value;
        $error = false;

        if(is_array($value)) {
            $id = $value['id'] ?? $value;
        }

        try {
            if( $options['id'] == 'taxonomy' && $value ) {

                if(is_numeric($value)) {
                    $value = get_term( $value, $options['registered'] );
                }

                $title = $value->name ?? $deleted . ' ' . $id;
                $title = empty($title) ? $value->term_id : $title;
                $title = "<b>{$title}</b>";
                $title .= ' (' . esc_html($options['registered']) . ')';
            }
            elseif (($options['id'] == 'model' || $options['id']  == 'url') && $value ) {
                $title = "<b>{$id}</b>";

                if(!$flat) {
                    if(!is_array($value)) {
                        /** @var Model $db */
                        $db = new $options['registered'];
                        $item = $db->findForSearch($value);

                        if($item) {
                            $value = $item->getSearchResult();
                        }
                    }

                    $title = $value['title'] ?? $deleted . ' ' . $id;
                    $title = empty($title) ? $value['id'] : $title;
                    $title = "<b>{$title}</b>";
                }
            }
            elseif( $value ) {
                if(is_numeric($value)) {
                    $value = get_post($value);
                }

                $status = '';
                $pt = $value->post_type ?? '';
                $p_stat = $value->post_status ?? null;
                $title = $value->post_title ?? $deleted . ' ' . $id;
                $title = empty($title) ? $value->ID : $title;
                $title = "<b>{$title}</b>";
                if( $value && $p_stat == 'draft' ) { $status = 'draft '; }

                $title .= ' (' . esc_html($status) . ' ' . esc_html($pt) . ')';
            }
        } catch(\Throwable $e) {
            $error = true;
            $title = $deleted . ' ' . (is_string($value) ? esc_html($value) : $value['id'] ?? '?');
            $title = "<b class='tr-search-model-error'>{$title}</b>";
        }

        return apply_filters('typerocket_search_field_result', $title, $value, $options, $error);
    }

    /**
     * @param array|mixed $value
     * @param array $options
     *
     * @return mixed|void
     */
    public static function getSearchUrl($value, $options)
    {
        $url = null;
        $error = false;
        $is_id = false;

        if(is_array($value)) {
            $value = $value['id'] ?? $value;
        }

        if(is_numeric($value)) {
            $is_id = true;
        }

        try {
            if( $is_id && $options['id'] == 'taxonomy' && $value ) {
                $url = get_term_link( $value, $options['registered'] );
            }
            elseif ( $is_id && ($options['id'] == 'model' || $options['id'] == 'url') && $value ) {
                /** @var Model $db */
                $db = new $options['registered'];
                $item = $db->findForSearch($value);

                if($item) {
                    $url = $item->getSearchUrl();
                }
            }
            elseif( $is_id && $value ) {
                $url = get_permalink($value);
            }
        } catch(\Throwable $e) {
            $error = true;
        }

        return apply_filters('typerocket_search_field_result_url', $url, $value, $options, $error);
    }
}