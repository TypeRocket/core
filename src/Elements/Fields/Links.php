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

        $search_attributes = [
            'placeholder' => __('Type to search...'),
            'class' => 'tr-link-links-input'
        ];

        if( empty($taxonomy) ) {
            $search_attributes['data-posttype'] = $type;
        } else {
            $search_attributes['data-taxonomy'] = $taxonomy;
        }

        $links = [];
        if(is_array($data)) {
            $values = array_map('intval', $data);
            foreach ($values as $value) {
                if( empty($taxonomy) && $value ) {
                    $post = get_post($value);
                    $status = '';

                    if( $post->post_status == 'draft' ) {
                        $status = 'draft ';
                    }

                    $selection = new Generator();
                    $selection->newInput('hidden', $name . '[]', $value, $this->getAttributes() )->getString();
                    $links[] = '<li class="tr-link-chosen-item">' . $selection . $post->post_title . ' (' . $status . $post->post_type . ')</li>';

                } elseif( $value ) {
                    $term = get_term( $value, $taxonomy );
                    $selection = new Generator();
                    $selection->newInput('hidden', $name . '[]', $value, $this->getAttributes() )->getString();
                    $links[] = '<li class="tr-link-chosen-item">' . $selection . $term->name . ' (' . $taxonomy . ')</li>';
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
     * @param $type
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
     * @param $taxonomy
     *
     * @return $this
     */
    public function setTaxonomy($taxonomy)
    {
        $this->setSetting('taxonomy', $taxonomy);

        return $this;
    }

}