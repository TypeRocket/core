<?php
namespace TypeRocket\Utility;

use TypeRocket\Interfaces\Formable;
use TypeRocket\Models\Traits\FieldValue;

class DataCollection implements Formable
{
    use FieldValue;

    protected $data;

    /**
     * DataCollection
     *
     * @param array|object $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return array|object
     */
    public function getFormFields()
    {
        return $this->data;
    }
}