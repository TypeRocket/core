<?php

namespace TypeRocket\Models\Traits;

use TypeRocket\Models\WPPost;

trait Searchable
{
    protected $searchable = true;

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

    public function getSearchColumn()
    {
        if($this instanceof WPPost) {
            return 'post_title';
        }

        return $this->getIdColumn();
    }

    public function getSearchResult() {
        return [
            'title' => $this->{$this->getSearchColumn()},
            'id' => $this->{$this->getIdColumn()}
        ];
    }
}