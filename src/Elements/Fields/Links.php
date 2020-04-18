<?php

namespace TypeRocket\Elements\Fields;

use \TypeRocket\Html\Generator;

class Links extends Field
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
        $data = $this->getValue() ?? [];
        $type = $this->getSetting('post_type', 'any');
        $taxonomy = $this->getSetting('taxonomy', '');
        $model = $this->getSetting('model', '');
        $field = '';

        $search_attributes = [
            'placeholder' => __('Type to search...', 'typerocket-domain'),
            'class' => 'tr-link-links-input'
        ];

        if(!empty($taxonomy)) {
            $search_attributes['data-taxonomy'] = $taxonomy;
        } elseif(!empty($model)) {
            $search_attributes['data-model'] = $model;
        } else {
            $search_attributes['data-posttype'] = $type;
        }

        $links = [];
        if(is_array($data)) {
            $values = array_map('intval', $data);
            foreach ($values as $value) {

                if($value) {
                    $selection = new Generator();
                    $selection->newInput('hidden', $name . '[]', $value, $this->getAttributes() )->getString();
                }

                if( !empty($taxonomy) && $value ) {
                    $term = get_term( $value, $taxonomy );
                    $links[] = '<li class="tr-link-chosen-item">' . $selection . $term->name . ' (' . $taxonomy . ')<a title="remove" class="tr-control-icon tr-control-icon-remove tr-link-chosen-item-remove"></a></li>';
                }
                elseif ($value && !empty($model) ) {
                    /** @var \TypeRocket\Models\Model $db */
                    $db = new $model;
                    $item = $db->findById($value)->getSearchResult();
                    $links[] = '<li class="tr-link-chosen-item">' . $selection . $item['title'] . '<a title="remove" class="tr-control-icon tr-control-icon-remove tr-link-chosen-item-remove"></a></li>';
                }
                elseif( $value ) {
                    $post = get_post($value);
                    $status = '';
                    if( $post->post_status == 'draft' ) { $status = 'draft '; }

                    $links[] = '<li class="tr-link-chosen-item">' . $selection . $post->post_title . ' (' . $status . $post->post_type . ')<a title="remove" class="tr-control-icon tr-control-icon-remove tr-link-chosen-item-remove"></a></li>';
                }
            }
        }

        $field .= '<div class="tr-links-group">';
        $field .= $input->newInput('hidden', $name, '0', $this->getAttributes() )->getString();
        $field .= '<div class="tr-links-controls">';
        $field .= $input->newInput($this->getType(), null, null,  $search_attributes)->getString();
        $field .= '<div class="tr-link-links-results"></div>';
        $field .= '</div>';
        $field .= '<ol data-input="'.$name.'" class="tr-links-selected">';

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