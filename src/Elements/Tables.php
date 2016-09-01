<?php

namespace TypeRocket\Elements;

use TypeRocket\Core\Config;
use TypeRocket\Html\Generator;
use TypeRocket\Models\Model;
use TypeRocket\Register\Page;
use TypeRocket\Utility\Sanitize;

class Tables
{
    public $results;
    public $columns;
    public $count;

    /** @var Model|null $model */
    public $model;
    public $primary = 'id';

    /** @var null|Page  */
    public $page = null;
    public $paged = 1;
    public $limit;
    public $offset = 0;
    public $settings = ['update_column' => 'id'];

    /**
     * Tables constructor.
     *
     * @param int $limit
     * @param \TypeRocket\Models\Model $model
     *
     */
    public function __construct( $limit = 25, $model = null )
    {
        global $_tr_page, $_tr_resource;

        if(!empty($_tr_page) && $_tr_page instanceof Page ) {
            $this->page = $_tr_page;
        }

        if( $model instanceof Model) {
            $this->setModel($model);
        } elseif(!empty($_tr_resource) && $_tr_resource instanceof Model ) {
            $this->setModel($_tr_resource);
        }

        $this->paged = !empty($_GET['paged']) ? (int) $_GET['paged'] : 1;
        $this->setLimit($limit);
    }

    /**
     * Set table limit
     *
     * @param $limit
     *
     * @return $this
     */
    public function setLimit( $limit ) {
        $this->limit = (int) $limit;
        $this->offset = ( $this->paged - 1 ) * $this->limit;

        return $this;
    }

    /**
     * Set table sorting
     *
     * @param $column
     * @param string $direction
     *
     * @return $this
     * @internal param $ $
     *
     */
    public function setOrder( $column, $direction = 'ASC' ) {
        $this->model->orderBy($column, $direction);

        return $this;
    }

    /**
     * Set the tables columns
     *
     * @param string $primary set the main column
     * @param array $columns
     *
     * @return \TypeRocket\Elements\Tables
     */
    public function setColumns( $primary, array $columns)
    {
        $this->primary = $primary;
        $this->columns = $columns;

        return $this;
    }

    /**
     * Set the page the table is connected to.
     *
     * @param \TypeRocket\Register\Page $page
     *
     * @return $this
     */
    public function setPage( Page $page) {
        $this->page = $page;

        return $this;
    }

    /**
     * Set Model
     *
     * @param \TypeRocket\Models\Model $model
     *
     * @return $this
     */
    public function setModel( Model $model )
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $this->model = clone $model;

        if( !empty($_GET['s']) && is_string($_GET['s']) && is_string($_GET['on']) ) {
            $condition = $_GET['condition'] == 'like' ? 'LIKE' : '=';

            $search = wp_unslash($_GET['s']);
            if($condition == 'LIKE') {
                $search = '%' . $wpdb->esc_like($search) . '%';
            } else {
                $search = $wpdb->_real_escape($search);
            }
            $model->where( Sanitize::underscore($_GET['on']) , $condition, $search );
        }

        $this->count = $model->findAll()->count();
        if( !empty( $_GET['order'] ) && !empty( $_GET['orderby'] ) ) {
            $this->model->orderBy($_GET['orderby'], $_GET['order']);
        }

        return $this;
    }

    /**
     * Render table
     */
    public function render()
    {
        $results = $this->model->findAll()->take($this->limit, $this->offset)->get();
        $columns = $this->columns;
        $table = new Generator();
        $head = new Generator();
        $body = new Generator();
        $foot = new Generator();

        if( empty($columns) ) {
            $columns = array_keys(get_object_vars($results[0]));
        }

        $table->newElement('table', ['class' => 'tr-list-table wp-list-table widefat striped']);
        $head->newElement('thead');
        $body->newElement('tbody', ['class' => 'the-list']);
        $foot->newElement('tfoot');

        $th_row = new Generator();
        $th_row->newElement('tr', ['class' => 'manage-column']);
        foreach ( $columns as $column => $data ) {
            $th = new Generator();
            $classes = null;
            if($this->primary == $column) {
                $classes = 'column-primary';
            }

            if( ! is_string($column) ) {
                $th->newElement('th', ['class' => $classes], ucfirst($data));
            } else {
                $label = $data['label'];
                if( !empty($data['sort']) && $this->page ) {
                    $order_direction = !empty( $_GET['order'] ) && $_GET['order'] == 'ASC' ? 'DESC' : 'ASC';
                    $order_link = $this->page->getUrl(['orderby' => $column, 'order' => $order_direction]);
                    $label = "<a href=\"{$order_link}\">$label</a>";
                }

                $th->newElement('th', ['class' => $classes],$label);
            }

            $th_row->appendInside($th);
        }
        $head->appendInside($th_row);
        $foot->appendInside($th_row);

        if( !empty($results)) {
            foreach ($results as $result) {
                $td_row = new Generator();
                $row_id = 'result-row-' . $result->id;
                $td_row->newElement('tr', ['class' => 'manage-column', 'id' => $row_id]);
                foreach ($columns as $column => $data) {
                    $show_url = $edit_url = $delete_url = '';

                    // get columns if none set
                    if ( ! is_string($column)) {
                        $column = $data;
                    }

                    $text = $result->$column;

                    if( !empty($data['callback']) ) {
                        $text = call_user_func($data['callback'], $text);
                    }

                    if ($this->page instanceof Page && ! empty($this->page->pages)) {
                        foreach ($this->page->pages as $page) {
                            /** @var Page $page */
                            if ($page->action == 'edit') {
                                $edit_url = $page->getUrl(['route_id' => (int)$result->id]);
                            }

                            if ($page->action == 'show') {
                                $show_url = $page->getUrl(['route_id' => (int)$result->id]);
                            }

                            if ($page->action == 'delete') {
                                $delete_url = $page->getUrl(['route_id' => (int)$result->id]);
                            }
                        }

                        if ( ! empty($data['actions'])) {
                            $text = "<strong><a href=\"{$edit_url}\">{$text}</a></strong>";
                            $text .= "<div class=\"row-actions\">";
                            foreach ($data['actions'] as $index => $action) {

                                if ($index > 0) {
                                    $text .= ' | ';
                                }

                                switch ($action) {
                                    case 'edit' :
                                        $text .= "<span class=\"edit\"><a href=\"{$edit_url}\">Edit</a></span>";
                                        break;
                                    case 'delete' :
                                        $delete_url = wp_nonce_url($delete_url, 'form_' . Config::getSeed(),
                                            '_tr_nonce_form');
                                        $text .= "<span class=\"delete\"><a data-target=\"#{$row_id}\" class=\"tr-delete-row-rest-button\" href=\"{$delete_url}\">Delete</a></span>";
                                        break;
                                    case 'view' :
                                        $text .= "<span class=\"view\"><a href=\"{$show_url}\">View</a></span>";
                                }
                            }
                            $text .= "</div>";
                        }
                    }

                    $classes = null;
                    if($this->primary == $column) {
                        $classes = 'column-primary';
                        $text .= "<button type=\"button\" class=\"toggle-row\"><span class=\"screen-reader-text\">Show more details</span></button>";
                    }

                    $td = new Generator();
                    $td->newElement('td', ['class' => $classes], $text);
                    $td_row->appendInside($td);
                }
                $body->appendInside($td_row);
            }
        } else {
            $td_row = new Generator();
            $td_row->newElement('tr', [], '<td>No results.</td>');
            $body->appendInside($td_row);
        }

        $table->appendInside('thead', [], $head );
        $table->appendInside('tbody', [], $body );
        $table->appendInside('tfoot', [], $foot );

        // Pagination
        $pages = ceil($this->count / $this->limit);
        $item_word = 'items';

        if($this->count < 2) {
            $item_word = 'item';
        }

        $page = $this->paged;
        $previous_page = $this->paged - 1;
        $next_page = $this->paged + 1;

        $current = $this->page->getUrlWithParams(['paged' => (int) $page]);
        $get_page = !empty($_GET['page']) ? $_GET['page']: '';

        if($this->page instanceof Page) {
            $next = $this->page->getUrlWithParams(['paged' => (int) $next_page]);
            $prev = $this->page->getUrlWithParams(['paged' => (int) $previous_page]);
        } else {
            parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $query);
            $query_next = array_merge($query, ['paged' => (int) $next_page]);
            $query_prev = array_merge($query, ['paged' => (int) $previous_page]);
            $next = $_SERVER['PHP_SELF'] . '?' . http_build_query($query_next);
            $prev = $_SERVER['PHP_SELF'] . '?' . http_build_query($query_prev);
        }

        ?>
        <form action="<?php echo $current; ?>">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select class="alignleft" name="on">
                        <?php foreach ($this->columns as $column_name => $column) : ?>
                            <option value="<?php echo esc_attr($column_name); ?>"><?php echo $column['label']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="alignleft actions">
                    <select class="alignleft" name="condition">
                        <option value="like">Contains</option>
                        <option value="equals">Is Exactly</option>
                    </select>
                </div>
                <div class="alignleft actions">
                    <label class="screen-reader-text" for="post-search-input">Search Pages:</label>
                    <input type="hidden" id="post-search-input" name="page" value="<?php echo esc_attr($get_page); ?>">
                    <input type="hidden" id="post-search-input" name="paged" value="<?php echo (int) $page; ?>">
                    <input type="search" id="post-search-input" name="s" value="">
                    <button id="search-submit" class="button">Search <?php echo ucfirst($item_word); ?></button>
                </div>

                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo $this->count; ?> <?php echo $item_word; ?></span>
                    <?php $this->paginationLinks($page, $prev, $next, $pages); ?>
                </div>
                <br class="clear">
            </div>
        </form>

        <?php  echo $table; ?>

        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo $this->count; ?> <?php echo $item_word; ?></span>
                <?php $this->paginationLinks($page, $prev, $next, $pages); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Pagination Links
     *
     * @param $page
     * @param $prev
     * @param $next
     * @param $pages
     */
    protected function paginationLinks($page, $prev, $next, $pages) {
        echo "<span class=\"pagination-links\">";
        if( $page < 2 ) {
            echo "<span class=\"tablenav-pages-navspan\" aria-hidden=\"true\">&lsaquo;</span>";
        } else {
            echo "<a class=\"prev-page\" href=\"{$prev}\" aria-hidden=\"true\">&lsaquo;</a>";
        }
        echo " <span id=\"table-paging\" class=\"paging-input\">{$page} of <span class=\"total-pages\">{$pages}</span></span> ";
        if( $page < $pages ) {
            echo "<a class=\"next-page\" href=\"{$next}\"><span class=\"screen-reader-text\">Next page</span><span aria-hidden=\"true\">&rsaquo;</span></a>";
        } else {
            echo "<span class=\"tablenav-pages-navspan\" aria-hidden=\"true\">&rsaquo;</span>";
        }
        echo "</span>";
    }

}