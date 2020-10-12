<?php
namespace TypeRocket\Elements\Traits;

trait RequiredTrait
{

    /**
     * Set Field As Required
     *
     * This method only applies HTML5 required attribute
     *
     * @return $this
     */
    public function setRequired()
    {
        return $this->markLabelRequired()->setAttribute('required', 'required');
    }

}