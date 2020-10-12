<?php
namespace TypeRocket\Database;

class ResultsMeta extends Results
{
    protected $storedValues = [];
    protected $loadStoredValues = true;

    /**
     * @var bool|string $cache false, 'post_meta', 'term_meta', 'comment_meta', 'user_meta'
     */
    protected $cache_key = false;

    /**
     * @var bool|string
     */
    protected $cache_column = false;

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

            if($this->cache_key && $this->cache_column) {
                $cache_id = $meta->{$this->cache_column};
                $cache = wp_cache_get($cache_id, $this->cache_key);
                $cache[$meta->meta_key][0] = $meta->meta_value;
                wp_cache_set($cache_id, $cache, $this->cache_key );
            }

            $this->storedValues[$meta->meta_key] = maybe_unserialize($meta->meta_value);
        }

        return $this->storedValues;
    }

    /**
     * Get Form Fields
     */
    public function getFormFields()
    {
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