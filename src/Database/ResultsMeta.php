<?php


namespace TypeRocket\Database;


class ResultsMeta extends Results
{
    protected $storedValues = [];

    /**
     * Get Meta Sorted
     *
     * @return array
     */
    public function initKeyStore()
    {
        if(!empty($this->storedValues)) {
            return $this->storedValues;
        }

        $data = $this->getArrayCopy();

        foreach ($data as $meta) {
            $this->storedValues[$meta->meta_key] = maybe_unserialize($meta->meta_value);
        }

        return $this->storedValues;
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getProperty($key)
    {
        $this->initKeyStore();

        if (array_key_exists($key, $this->storedValues)) {
            return $this->storedValues[$key];
        }

        return null;
    }

    /**
     * Get attribute as property
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getProperty($key);
    }

    /**
     * Property Exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getProperty($key));
    }

}