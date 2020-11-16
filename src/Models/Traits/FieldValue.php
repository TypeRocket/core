<?php
namespace TypeRocket\Models\Traits;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Http\Cookie;
use TypeRocket\Http\Redirect;
use TypeRocket\Utility\Data;

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
            $data = Data::walk($field, $this->old);
            $data = wp_unslash($data);
        }
        elseif( ! $this->onlyOld ) {
            $data = Data::walk($field, $data);
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
        if( !empty($_COOKIE[Redirect::KEY_OLD]) ) {
            $this->old = (new Cookie)->getTransient(Redirect::KEY_OLD);
        }

        $this->onlyOld = $load_only_old;
    }

}