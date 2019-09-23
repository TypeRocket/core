<?php

namespace TypeRocket\Models\Traits;

trait Searchable
{
    protected $searchable = true;
    protected $searchColumn = null;

    /**
     * Get Search Results
     *
     * @return array
     */
    public function getSearchResults()
    {
        $results = $this->get();
        $return = [];
        foreach($results as $result) {
            $return[] = [
                'title' => $result->{$this->getSearchColumn()},
                'id' => $result->{$this->getIdColumn()}
            ];
        }

        return $return;
    }

    /**
     * Get Search Column
     *
     * @return string
     */
    public function getSearchColumn()
    {
        return $this->searchColumn ?? $this->getIdColumn();
    }

    /**
     * Get Search Result
     *
     * @return array
     */
    public function getSearchResult() {
        return [
            'title' => $this->{$this->getSearchColumn()},
            'id' => $this->{$this->getIdColumn()}
        ];
    }
}