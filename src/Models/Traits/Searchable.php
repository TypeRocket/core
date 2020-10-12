<?php
namespace TypeRocket\Models\Traits;

use TypeRocket\Database\ResultsPaged;
use TypeRocket\Models\Model;

trait Searchable
{
    protected $searchable = true;

    /**
     * Get Search Results
     *
     * @param int $limit
     *
     * @return array
     */
    public function getSearchResults($limit = 10)
    {
        /** @var ResultsPaged $results */
        $results = $this->paginate($limit);
        $return = [];
        if($results) {
            foreach($results as $result) {
                $return[] = $this->formatSearchResult($result);
            }
        }

        return ['items' => $return, 'count' => $results ? $results->getCount() : 0];
    }

    /**
     * Format Result
     *
     * @param $result
     *
     * @return array
     */
    protected function formatSearchResult($result)
    {
        if(method_exists($result, 'getFieldOptionKey')) {
            $title = $result->getFieldOptionKey($result->{$this->getSearchColumn()});
        } else {
            $title = $result->{$this->getSearchColumn()};
        }

        return [
            'title' => $title,
            'id' => $result->{$this->getSearchIdColumn()}
        ];
    }

    /**
     * Get Search Column
     *
     * @return string
     */
    public function getSearchColumn()
    {
        return $this->searchColumn ?? $this->getFieldOptions()['key'] ?? $this->getIdColumn();
    }

    /**
     * Get Search ID Column
     *
     * @return string
     */
    public function getSearchIdColumn()
    {
        return $this->searchIdColumn ?? $this->getFieldOptions()['value'] ?? $this->getIdColumn();
    }

    /**
     * Find For Search
     *
     * @param $value
     *
     * @return mixed|Model
     */
    public function findForSearch($value)
    {
        return $this->findById($value);
    }

    /**
     * Get Search Result
     *
     * @return array
     */
    public function getSearchResult() {
        return $this->formatSearchResult($this);
    }

    /**
     * @return null|string
     */
    public function getSearchUrl()
    {
        return null;
    }
}