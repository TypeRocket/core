<?php
namespace TypeRocket\Elements\Traits;

trait Limits
{
    protected $limit = 99999;

    /**
     * Limit Number of Items
     *
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Get Item Limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }
}