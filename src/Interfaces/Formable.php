<?php
namespace TypeRocket\Interfaces;

use TypeRocket\Elements\Fields\Field;

interface Formable
{
    /**
     * Get field Value
     *
     * @param string|Field $field
     * @return mixed
     */
    public function getFieldValue($field);

    /**
     * Get Form Fields
     *
     * When a Form or Field tries to assess the object
     * This is the data it returns.
     *
     * @return mixed
     */
    public function getFormFields();

    /**
     * Load Old Data Only
     *
     * @param bool $load_only_old
     * @return mixed
     */
    public function oldStore( $load_only_old );
}