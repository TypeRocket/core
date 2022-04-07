<?php
namespace TypeRocket\Database;

use JsonSerializable;
use TypeRocket\Html\Html;
use TypeRocket\Http\Request;

class ResultsPaged implements \Iterator, JsonSerializable
{
    /** @var Results|array */
    protected $results;
    protected $number;
    protected $page;
    protected $count;
    protected $pages;

    private $position = 0;

    public function __construct($results, $page, $count, $number)
    {
        $this->results = $results;
        $this->number = $number;
        $this->count = $count;
        $this->page = $page;
        $this->pages = ceil($count / $number);
    }

    /**
     * Get Results
     *
     * @return Results|array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Get Number
     *
     * @return mixed
     */
    public function getNumberPerPage()
    {
        return $this->number;
    }

    /**
     * Get Current Page
     *
     * @return mixed
     */
    public function getCurrentPage()
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
     * Get Next
     *
     * @return int|null
     */
    public function getNextPage()
    {
        $next = $this->page + 1;
        return $next > $this->pages ? null : $next;
    }

    /**
     * Get Previous
     *
     * @return int|null
     */
    public function getPreviousPage()
    {
        $prev = $this->page - 1;
        return $prev < 1 ? null : $prev;
    }

    /**
     * Get Last
     *
     * @return float
     */
    public function getLastPage()
    {
        return $this->pages;
    }

    /**
     * Get First
     *
     * @return int
     */
    public function getFirstPage()
    {
        return 1;
    }

    /**
     * Get links
     *
     * @return array
     */
    public function getLinks()
    {
        $request = (new Request);
        $current = $this->getCurrentPage();
        $next = $this->getNextPage();
        $prev = $this->getPreviousPage();
        $last = $this->getLastPage();
        $first = $this->getFirstPage();

        return [
            'current' => $next ? $request->getModifiedUri(['paged' => $current]) : null,
            'next' => $next ? $request->getModifiedUri(['paged' => $next]) : null,
            'previous' => $prev ? $request->getModifiedUri(['paged' => $prev]) : null,
            'first' => $prev || $next ? $request->getModifiedUri(['paged' => $first]) : null,
            'last' => $prev || $next ? $request->getModifiedUri(['paged' => $last]) : null,
        ];
    }

    /**
     * Get Next Link
     *
     * Get <a> tag with correct URL
     *
     * @param string|null $label
     * @param array $attributes
     *
     * @return string|null
     */
    public function linkNext($label = null, $attributes = [])
    {
        $label = $label ?: __( 'Next Page &raquo;', 'typerocket-domain' );

        if($next = $this->getNextPage()) {
            $url = (new Request)->getModifiedUri(['paged' => $next]);

            return (string) Html::a($label, $url, $attributes);
        }

        return null;
    }

    /**
     * Get Previous Link
     *
     * Get <a> tag with correct URL
     *
     * @param string|null $label
     * @param array $attributes
     *
     * @return string|null
     */
    public function linkPrevious($label = null, $attributes = [])
    {
        $label = $label ?: __( '&laquo; Previous Page', 'typerocket-domain'  );

        if($next = $this->getPreviousPage()) {
            $url = (new Request)->getModifiedUri(['paged' => $next]);

            return (string) Html::a($label, $url, $attributes);
        }

        return null;
    }

    /**
     * To Array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'items' => is_array($this->results) ? $this->results : $this->results->toArray(),
            'number' => $this->number,
            'current' => $this->page,
            'pages' => $this->pages,
            'count' => $this->count,
            'links' => $this->getLinks(),
        ];
    }

    /**
     * To JSON
     */
    public function toJson()
    {
        return json_encode($this);
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

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}