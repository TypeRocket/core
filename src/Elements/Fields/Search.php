<?php

namespace TypeRocket\Elements\Fields;

use \TypeRocket\Html\Generator;
use TypeRocket\Models\Model;

class Search extends Field
{

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
        $input = new Generator();
        $name = $this->getNameAttributeString();
        $value = (int) $this->getValue();
        $title = 'No selection... Search and click on a result';
        $type = $this->getSetting('post_type', 'any');
        $taxonomy = $this->getSetting('taxonomy', '');
        $model = $this->getSetting('model', '');

        $search_attributes = [
            'placeholder' => 'Type to search...',
            'class' => 'tr-link-search-input'
        ];

        if($value < 1) {
            $value = null;
        }

        if(!empty($taxonomy)) {
            $search_attributes['data-taxonomy'] = $taxonomy;
        } elseif(!empty($model)) {
            $search_attributes['data-model'] = $model;
        } else {
            $search_attributes['data-posttype'] = $type;
        }

        if( !empty($taxonomy) && $value ) {
            $term = get_term( $value, $taxonomy );
            $title = 'Selection: <b>' . $term->name . '</b> <a class="tr-link-search-remove-selection" href="#remove-selection">remove</a>';
        }
        elseif ($value && !empty($model)) {
            /** @var Model $db */
            $db = new $model;

            $item = $db->findById($value)->getSearchResult();
            $title = 'Selection: <b>' . $item['title'] . '</b> <a class="tr-link-search-remove-selection" href="#remove-selection">remove</a>';
        }
        elseif( $value ) {
            $post = get_post($value);
            $status = '';

            if( $post->post_status == 'draft' ) {
                $status = 'draft ';
            }

            $title = 'Selection: <b>' . $post->post_title . ' (' . $status . $post->post_type . ')</b> <a class="tr-link-search-remove-selection" href="#remove-selection">remove</a>';
        }

        $field = $input->newInput($this->getType(), null, null,  $search_attributes)->getString();
        $field .= $input->newInput( 'hidden', $name, $value, $this->getAttributes() )->getString();
        $field .= '<div class="tr-link-search-page">'.$title.'</div>';
        $field .= '<div class="tr-link-search-results"></div>';

        return $field;
    }

    /**
     * Search by post type only
     *
     * @param string $type
     *
     * @return $this
     */
    public function setPostType($type)
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
    public function setTaxonomy($taxonomy)
    {
        $this->setSetting('taxonomy', $taxonomy);

        return $this;
    }

    /**
     * Search by model only
     *
     * @param string $model clas as string
     *
     * @return $this
     */
    public function setModel($model)
    {
        $this->setSetting('model', $model);

        return $this;
    }
}