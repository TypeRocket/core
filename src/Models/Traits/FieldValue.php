<?php
namespace TypeRocket\Models\Traits;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Http\Cookie;

trait FieldValue
{
    protected $old;
    protected $onlyOld;

    public function getFieldValue($field)
    {
        if($field instanceof Field) {
            $field = $field->getDots();
        }

        $data = $this->getFormFields();

        if( $this->old ) {
            $data = tr_dots_walk($field, $this->old);
            $data = wp_unslash($data);
        }
        elseif( ! $this->onlyOld ) {
            $data = tr_dots_walk($field, $data);
        }
        else {
            return null;
        }

        return $data;
    }

    public function getFormFields()
    {
        return [];
    }

    /**
     * Get old stored fields
     *
     * @param bool $load_only_old
     */
    public function oldStore( $load_only_old = false)
    {
        if( !empty($_COOKIE['tr_old_fields']) ) {
            $this->old = (new Cookie)->getTransient('tr_old_fields');
        }

        $this->onlyOld = $load_only_old;
    }

}