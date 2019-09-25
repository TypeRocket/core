<?php


namespace TypeRocket\Database;


class ResultsPaged
{

    /** @var Results */
    protected $results;
    protected $page;
    protected $count;
    protected $pages;

    public function __construct(Results $results, $page, $count)
    {
        $this->results = $results;
        $this->count = $count;
        $this->page = $page;
        $this->pages = ceil($count / $page);
    }

    /**
     * Get Results
     *
     * @return Results
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Get Current Page
     *
     * @return mixed
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get Number of Pages
     *
     * @return float
     */
    public function getNumberOfPages()
    {
        return $this->pages;
    }

    /**
     * Get Total Count
     *
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * To Array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'items' => $this->results->toArray(),
            'page' => $this->page,
            'pages' => $this->pages,
            'count' => $this->count,
        ];
    }

    /**
     * To JSON
     */
    public function toJson()
    {
        return json_encode($this->toArray());
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

}