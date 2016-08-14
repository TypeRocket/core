<?php
namespace TypeRocket\Models;


class Object
{

    public $schemaClass = SchemaModel::class;
    public $properties = [];

    /**
     * Load attributes from database
     *
     * @param array|null|object $id
     *
     * @return $this
     */
    public function populate( $id ) {

        /** @var SchemaModel $model */
        $model = new $this->schemaClass();
        $attributes = $model->findFirstWhereOrDie($model->getRouterInjectionColumn(), $id);

        $this->properties = $attributes;

        return $this;
    }

    public function save() {
        /** @var SchemaModel $model */
        $model = new $this->schemaClass();

        return $model
            ->setGuardFields([])
            ->setFillableFields([])
            ->findById($this->properties[$model->idColumn])
            ->update($this->properties);
    }

    /**
     * Get attribute as property
     *
     * @param $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->properties[$key];
    }

    /**
     * Set attribute as property
     *
     * @param $key
     * @param null $value
     */
    public function __set($key, $value = null)
    {
        $this->properties[$key] = $value;
    }

}