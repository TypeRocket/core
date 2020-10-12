<?php
namespace TypeRocket\Controllers\Traits;

use TypeRocket\Models\Model;

trait LoadsModel
{

    /**
     * Set Model
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->modelClass = $model;

        return $this;
    }

    /**
     * Get Model
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->modelClass;
    }

}