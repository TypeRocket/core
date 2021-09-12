<?php
namespace TypeRocket\Models\Traits;

trait ArrayReplaceRecursiveValues
{
    protected $arrayReplaceRecursiveKeys = [];

    /**
     * @param string $key
     *
     * @return $this
     */
    public function addArrayReplaceRecursiveKey(string $key)
    {
        $this->arrayReplaceRecursiveKeys[$key] = $key;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function removeArrayReplaceRecursiveKey(string $key)
    {
        unset($this->arrayReplaceRecursiveKeys[$key]);
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $current_value
     * @param mixed $new_value
     *
     * @return array|mixed
     */
    public function getNewArrayReplaceRecursiveValue(string $key, $current_value, $new_value)
    {
        if(!array_key_exists($key, $this->arrayReplaceRecursiveKeys) || !is_array($new_value) || !is_array($current_value) ) {
            return $new_value;
        }

        return array_replace_recursive($current_value, $new_value);
    }
}