<?php


namespace TypeRocket\Database;


class ResultsPaged implements \Iterator
{

    /** @var Results */
    protected $results;
    protected $page;
    protected $count;
    protected $pages;

    private $position = 0;

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

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->results[$this->position];
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->results[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->position = 0;
    }
}