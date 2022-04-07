<?php
namespace TypeRocket\Database;

use JsonSerializable;
use TypeRocket\Interfaces\Formable;
use TypeRocket\Models\Traits\FieldValue;
use TypeRocket\Models\Model;

class Results extends \ArrayObject implements Formable, JsonSerializable, ResultsCollection
{
    use FieldValue;

    public $class = null;
    public $property = 'properties';
    public $cache = null;

    /**
     * @param bool $bool
     *
     * @return $this
     */
    public function setCache($bool)
    {
        $this->cache = (bool) $bool;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Add item to top of collection
     *
     * @param string $value
     */
    public function prepend( $value )
    {
        $array = $this->getArrayCopy();
        array_unshift( $array, $value );
        $this->exchangeArray( $array );
    }

    /**
     * Pop
     *
     * @return mixed|Model
     */
    public function pop()
    {
        $array = $this->getArrayCopy();
        $value = array_pop($array);
        $this->exchangeArray( $array );

        return $value;
    }

    /**
     * Shift
     *
     * @return mixed|Model
     */
    public function shift()
    {
        $array = $this->getArrayCopy();
        $value = array_shift($array);
        $this->exchangeArray( $array );

        return $value;
    }

    /**
     * First Item
     *
     * @return mixed
     */
    public function first()
    {
        return $this->offsetExists(0) ? $this->offsetGet(0) : null;
    }

    /**
     * Last Item
     *
     * @return mixed|null
     */
    public function last()
    {
        $offset = $this->count() - 1;

        return $this->offsetExists($offset) ? $this->offsetGet($offset) : null;
    }

    /**
     * Eager Load Results.
     *
     * @param string|array $with
     *
     * @return mixed|Results|null
     */
    public function load($with)
    {
        if($this->offsetExists(0)) {
            $first = $this->offsetGet(0);
            if($first instanceof Model) {
                return $first->clone()->load($with, $this);
            }
        }

        return null;
    }

    /**
     * Exchange and Cast
     *
     * @param array $results
     * @param null|string $class
     *
     * @return $this
     */
    public function exchangeAndCast($results, $class = null)
    {
        $this->exchangeArray( $results );
        $this->castResults($class);

        return $this;
    }

    /**
     * Cast results
     *
     * Casting is normally to a Model class
     *
     * @param null|string $class
     *
     * @return $this
     */
    public function castResults($class = null)
    {
        $this->class = $class ?? $this->class;

        if( ! $this->class ) {
            return null;
        }

        if($this->count() > 0) {
            foreach ( $this as &$item ) {
                $model = new $this->class;

                if( $model instanceof Model ) {
                    $model->setCache($this->getCache())->castProperties( (array) $item );
                } else {
                    $property = $this->property;
                    $model->$property = (array) $item;
                }

                $item = $model;
            }
        }

        return $this;
    }

    /**
     * Index Results
     *
     * @param $column
     *
     * @return $this
     */
    public function indexWith(string $column)
    {
        $data = $this->getArrayCopy();
        $result = [];

        foreach ($data as $item) {
            $result[$item->{$column}] = $item;
        }

        $this->exchangeArray( $result );

        return $this;
    }

    /**
     * Has Results
     *
     * @return bool
     */
    public function hasResults() {
        return $this->count() > 0 ? true : false;
    }

    /**
     * Get Form Fields
     */
    public function getFormFields()
    {
        $data = $this->getArrayCopy();
        $result = [];

        foreach ($data as $i => $item) {
            if($item instanceof Formable) {
                $result[$i] = $item->getFormFields();
            } else {
                $result[$i] = $item;
            }
        }

        return $result;
    }

    /**
     * To Array
     *
     * Get array of model and loaded relationships
     *
     * @return array
     */
    public function toArray()
    {
        $results = [];
        $items = $this->getArrayCopy();

        if(property_exists($this, 'storedValues')) {
            $this->initKeyStore();

            if($this->loadStoredValues) {
                return $this->storedValues;
            }
        }

        foreach ($items as $i => $item) {
            if( $item instanceof Model) {
                $results[$i] = $item->toArray();
            } else {
                $results[$i] = (array) $item;
            }
        }

        return $results;
    }

    /**
     * To JSON
     */
    public function toJson()
    {
        return json_encode($this);
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}