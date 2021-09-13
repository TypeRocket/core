<?php
namespace TypeRocket\Models\Traits;

use TypeRocket\Utility\Arr;

trait ArrayReplaceRecursiveValues
{
    protected $arrayReplaceRecursiveKeys = [];
    protected $arrayReplaceRecursiveStops = [];

    /**
     * @param string $key
     * @param null|callable $callback
     *
     * @return $this
     */
    public function setArrayReplaceRecursiveKey(string $key, ?callable $callback = null)
    {
        $this->arrayReplaceRecursiveKeys[$key] = $callback;

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
     * @param array $stops
     *
     * @return $this
     */
    public function extendArrayReplaceRecursiveStops(string $key, array $stops)
    {
        if(!array_key_exists($key, $this->arrayReplaceRecursiveKeys)) {
            $this->arrayReplaceRecursiveKeys[$key] = null;
        }

        $stops = array_merge($this->arrayReplaceRecursiveStops[$key] ?? [], $stops);
        $this->arrayReplaceRecursiveStops[$key] = array_unique(array_values($stops));

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setArrayReplaceRecursiveStops(string $key, array $stops)
    {
        if(!array_key_exists($key, $this->arrayReplaceRecursiveKeys)) {
            $this->arrayReplaceRecursiveKeys[$key] = null;
        }

        $this->arrayReplaceRecursiveStops[$key] = array_unique(array_values($stops));

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function removeArrayReplaceRecursiveStops(string $key)
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

        if(is_callable($this->arrayReplaceRecursiveKeys[$key])) {
            $new_value = call_user_func($this->arrayReplaceRecursiveKeys[$key], $new_value, $current_value, $key);
        }

        return Arr::replaceRecursivePreferNew($current_value, $new_value, $this->arrayReplaceRecursiveStops[$key] ?? []);
    }
}