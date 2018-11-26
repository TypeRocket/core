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
            if($this instanceof WPPost) {
                $return[] = [
                    'title' => $result->post_title,
                    'id' => $result->{$this->getIdColumn()}
                ];
            } else {
                $return[] = [
                    'title' => $result->{$this->getIdColumn()},
                    'id' => $result->{$this->getIdColumn()}
                ];
            }
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
        if($this instanceof WPPost) {
            return [
                'title' => $this->post_title,
                'id' => $this->{$this->getIdColumn()}
            ];
        } else {
            return [
                'title' => $this->{$this->getIdColumn()},
                'id' => $this->{$this->getIdColumn()}
            ];
        }
    }
}