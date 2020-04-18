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
    protected $searchColumns;
    public $paged = 1;
    public $checkboxes = false;
    public $searchFormFilters = false;
    public $limit;
    public $offset = 0;
    public $formWrapTable = false;
    public $settings = ['update_column' => 'id'];

    /**
     * Tables constructor.
     *
     * @param int $limit
     * @param Model $model
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

        do_action('tr_after_table_element_init', $this);
    }

    /**
     * Set table limit
     *
     * @param string $limit
     *
     * @return $this
     */
    public function setLimit( $limit ) {
        $this->limit = (int) $limit;
        $this->offset = ( $this->paged - 1 ) * $this->limit;

        return $this;
    }

    /**
     * Set table search columns
     *
     * @param array $columns
     *
     * @return $this
     */
    public function setSearchColumns( $columns ) {
        $this->searchColumns = $columns;

        return $this;
    }

    /**
     * Set table sorting
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     * @internal param $ $
     *
     */
    public function setOrder( $column, $direction = 'ASC' ) {
        if( empty( $_GET['order'] ) && empty( $_GET['orderby'] ) ) {
            $this->model->orderBy($column, $direction);
        }

        return $this;
    }

    /**
     * Set the tables columns
     *
     * @param string $primary set the main column
     * @param array $columns
     *
     * @return Tables
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
     * @param Page $page
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
     * @param Model $model
     *
     * @return $this
     */
    public function setModel( Model $model )
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $this->model = clone $model;

        if( $this->isValidSearch() ) {
            $condition = $_GET['condition'] == 'like' ? 'LIKE' : '=';

            $search = wp_unslash($_GET['s']);
            if($condition == 'LIKE') {
                $search = '%' . $wpdb->esc_like($search) . '%';
            } else {
                $search = $wpdb->_real_escape($search);
            }
            $model->where( Sanitize::underscore($_GET['on']) , $condition, $search );
        }

        $this->count = null;
        if( !empty( $_GET['order'] ) && !empty( $_GET['orderby'] ) ) {
            $this->model->orderBy($_GET['orderby'], $_GET['order']);
        }

        return $this;
    }

	/**
     * Add Checkboxes
     *
	 * @return $this
	 */
	public function addCheckboxes() {
        $this->checkboxes = true;

		return $this;
    }

	/**
     * Remove Checkboxes
     *
	 * @return $this
	 */
	public function removeCheckboxes() {
		$this->checkboxes = false;

		return $this;
    }

    /**
     * Form Wrap Table
     *
     * Wrap the tables in the <form> tag
     *
     * @param bool $wrap
     *
     * @return $this
     */
    public function formWrapTable( $wrap = true)
    {
        $this->formWrapTable = $wrap;
        return $this;
    }

	/**
     * Append Search From Filters
     *
     * Add the ability to append other input fields and HTML
     * inside the search section of the filter table area.
     *
	 * @param string $callback
	 *
	 * @return $this
	 */
	public function appendSearchFormFilters( $callback ) {
        $this->searchFormFilters = $callback;
        return $this;
    }

    /**
     * Render table
     *
     * @param string $action_key a key to customize hooks by
     * @throws \Exception
     */
    public function render($action_key = '')
    {
        if($action_key) {
            $action_key = '_' . $action_key;
        }

        do_action('tr_table_search_model'.$action_key, $this->model, $this);
        $count_model = clone $this->model;
        $results = $this->results = $this->model->findAll()->useResultsClass()->take($this->limit, $this->offset)->get();
        $count = $this->count = $count_model->removeTake()->count();
        $columns = $this->columns;
        $this_table = $this;
        $table = new Generator();
        $head = new Generator();
        $body = new Generator();
        $foot = new Generator();
        $columnId = $this->model->getIdColumn();
        $addCheckbox = $this->checkboxes ? $columnId : false;

        if( empty($columns) ) {
            $columns = array_keys(get_object_vars($results[0]));
        }

        $table->newElement('table', ['class' => 'tr-list-table wp-list-table widefat striped']);
        $head->newElement('thead');
        $body->newElement('tbody', ['class' => 'the-list']);
        $foot->newElement('tfoot');

        $th_row = new Generator();
        $th_row->newElement('tr', ['class' => 'manage-column']);

        if($addCheckbox) {
            $th = new Generator();
            $th->newElement('td', ['class' => 'manage-column column-cb check-column'], '<input type="checkbox" class="check-all" />');
            $th_row->appendInside($th);
        }

        foreach ( $columns as $column => $data ) {
            $th = new Generator();
            $classes = 'manage-column';
            if($this->primary == $column) {
                $classes .= ' column-primary';
            }

            if( ! is_string($column) ) {
                $th->newElement('th', ['class' => $classes], ucfirst($data));
            } else {
                $label = $data['label'];
                if( !empty($data['sort']) && $this->page && strpos($column, '.') === false) {
                    $order_direction = !empty( $_GET['order'] ) && $_GET['order'] == 'ASC' ? 'DESC' : 'ASC';
                    $order_direction_now = !empty( $_GET['order'] ) && $_GET['order'] == 'ASC' ? 'ASC' : 'DESC';

                    $url_params = ['orderby' => $column, 'order' => $order_direction];

                    if( $this->isValidSearch() ) {
                        $url_params = array_merge($url_params, [
                            's' => wp_unslash($_GET['s']),
                            'condition' => wp_unslash($_GET['condition']),
                            'on' => wp_unslash($_GET['on']),
                        ]);
                    }

                    $order_link = $this->page->getUrl($url_params);
                    if( !empty($_GET['orderby']) &&  $column == $_GET['orderby']) {
                        $classes .= ' sorted ' . strtolower($order_direction_now);
                    } else {
                        $classes .= ' sortable ' . strtolower($order_direction_now);
                    }

                    $label = "<a href=\"{$order_link}\"><span>$label</span><span class=\"sorting-indicator\"></span></a>";
                }

                $th->newElement('th', ['class' => $classes],$label);
            }

            $th_row->appendInside($th);
        }
        $head->appendInside($th_row);
        $foot->appendInside($th_row);

        if( !empty($results)) {
            /** @var Model $result */
            foreach ($results as $result) {
                $td_row = new Generator();
                $columnValue = Sanitize::dash($result->getProperty($columnId));
                $row_id = 'result-row-' . $columnValue;
                $td_row->newElement('tr', ['class' => 'manage-column', 'id' => $row_id]);

                if($addCheckbox) {
                    $td = new Generator();
                    $td->newElement('th', ['class' => 'check-column'], '<input type="checkbox" name="bulk[]" value="'.$columnValue.'" />');
                    $td_row->appendInside($td);
                }

                foreach ($columns as $column => $data) {
                    $show_url = $edit_url = $delete_url = '';

                    // get columns if none set
                    if ( ! is_string($column)) {
                        $column = $data;
                    }

                    $text = $result->getDeepValue($column);

                    if( !empty($data['callback']) && is_callable($data['callback']) ) {
                        $text = call_user_func_array($data['callback'], [$text, $result] );
                    }

                    if ($this->page instanceof Page && ! empty($this->page->pages)) {
                        foreach ($this->page->pages as $page) {
                            /** @var Page $page */
                            if ($page->action == 'edit') {
                                $edit_url = $page->getUrl(['route_id' => (int)$result->{$columnId}]);
                            }

                            if ($page->action == 'show') {
                                $show_url = $page->getUrl(['route_id' => (int)$result->{$columnId}]);
                            }

                            if ($page->action == 'delete') {
                                $delete_url = $page->getUrl(['route_id' => (int)$result->{$columnId}]);
                            }
                        }

                        if ( ! empty($data['actions'])) {
                            $text = "<strong><a href=\"{$edit_url}\">{$text}</a></strong>";
                            $text .= "<div class=\"row-actions\">";
                            $delete_ajax = true;
                            $delete_class = '';
                            if( isset($data['delete_ajax']) && $data['delete_ajax'] === false ) {
                                $delete_ajax = false;
                            }
                            foreach ($data['actions'] as $index => $action) {

                                if ($index > 0) {
                                    $text .= ' | ';
                                }
                                switch ($action) {
                                    case 'edit' :
                                        $edit_text = __('Edit', 'typerocket-domain');
                                        $text .= "<span class=\"edit\"><a href=\"{$edit_url}\">{$edit_text}</a></span>";
                                        break;
                                    case 'delete' :
                                        if( $delete_ajax ) {
                                            $delete_url = wp_nonce_url($delete_url, 'form_' . tr_config('app.seed'), '_tr_nonce_form');
                                            $delete_class = 'class="tr-delete-row-rest-button"';
                                        }
                                        $del_text = __('Delete', 'typerocket-domain');
                                        $text .= "<span class=\"delete\"><a data-target=\"#{$row_id}\" {$delete_class} href=\"{$delete_url}\">{$del_text}</a></span>";
                                        break;
                                    case 'view' :
                                        $view_text = __('View', 'typerocket-domain');
                                        if( !empty($data['view_url']) && is_callable($data['view_url']) ) {
                                            $show_url = call_user_func_array($data['view_url'], [$show_url, $result]);
                                        }
                                        $text .= "<span class=\"view\"><a href=\"{$show_url}\">{$view_text}</a></span>";
                                }
                            }
                            $text .= "</div>";
                        }
                    }

                    $classes = null;
                    if($this->primary == $column) {
                        $classes = 'column-primary';
                        $details_text = __('Show more details', 'typerocket-domain');
                        $text .= "<button type=\"button\" class=\"toggle-row\"><span class=\"screen-reader-text\">{$details_text}</span></button>";
                    }

                    $td = new Generator();
                    $td->newElement('td', ['class' => $classes], $text);
                    $td_row->appendInside($td);
                }
                $body->appendInside($td_row);
            }
        } else {
            $td_row = new Generator();
            $results_text = __('No results.', 'typerocket-domain');
            $td_row->newElement('tr', [], "<td>{$results_text}</td>");
            $body->appendInside($td_row);
        }

        $table->appendInside('thead', [], $head );
        $table->appendInside('tbody', [], $body );
        $table->appendInside('tfoot', [], $foot );

        // Pagination
        $pages = ceil($count / $this->limit);
        $item_word = __('items', 'typerocket-domain');

        if($count < 2) {
            $item_word = __('item', 'typerocket-domain');
        }

        $page = $this->paged;
        $previous_page = $this->paged - 1;
        $next_page = $this->paged + 1;

        $current = $this->page->getUrlWithParams(['paged' => (int) $page]);
        $get_page = !empty($_GET['page']) ? $_GET['page']: '';

        if($this->page instanceof Page) {
            $next = $this->page->getUrlWithParams(['paged' => (int) $next_page]);
            $prev = $this->page->getUrlWithParams(['paged' => (int) $previous_page]);
            $last = $this->page->getUrlWithParams(['paged' => (int) $pages]);
            $first = $this->page->getUrlWithParams(['paged' => 1]);
        } else {
            parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $query);
            $query_next = array_merge($query, ['paged' => (int) $next_page]);
            $query_prev = array_merge($query, ['paged' => (int) $previous_page]);
            $query_last = array_merge($query, ['paged' => (int) $pages]);
            $query_first = array_merge($query, ['paged' => (int) 1]);
            $next = $_SERVER['PHP_SELF'] . '?' . http_build_query($query_next);
            $prev = $_SERVER['PHP_SELF'] . '?' . http_build_query($query_prev);
            $last = $_SERVER['PHP_SELF'] . '?' . http_build_query($query_last);
            $first = $_SERVER['PHP_SELF'] . '?' . http_build_query($query_first);
        }

        $get_search_current = !empty($_GET['s']) ? wp_unslash($_GET['s']) : '';
        $get_condition_current = !empty($_GET['condition']) ? wp_unslash($_GET['condition']) : '';
        $get_on_current = !empty($_GET['on']) ? wp_unslash($_GET['on']) : '';
        $select_condition = [
            'like' => __('Contains', 'typerocket-domain'),
            'equals' => __('Is Exactly', 'typerocket-domain')
        ];

        $searchColumns = $this->searchColumns ? $this->searchColumns : $this->columns;

        ?>
        <form action="<?php echo $current; ?>">
            <div class="tablenav top">
                <?php if(is_callable($this->searchFormFilters )) {
                    echo '<div class="alignleft actions">';
                    call_user_func($this->searchFormFilters);
                    echo '</div>';
                } ?>
                <?php do_action('tr_table_search_form'.$action_key, $this_table); ?>
                <div class="alignleft actions">
                    <select class="alignleft" name="on">
                        <?php foreach ($searchColumns as $column_name => $column) :

                            if(strpos($column_name, '.') !== false) {
                                continue;
                            }

                            $selected = $get_on_current == $column_name ? 'selected="selected"' : '';
                            ?>
                            <option <?php echo $selected; ?> value="<?php echo esc_attr($column_name); ?>">
                                <?php echo !empty($column['label']) ? $column['label'] : $column; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="alignleft actions">
                    <select class="alignleft" name="condition">
                        <?php foreach ($select_condition as $column_name => $label) :
                            $selected = $get_condition_current == $column_name ? 'selected="selected"' : '';
                            ?>
                            <option <?php echo $selected; ?> value="<?php echo esc_attr($column_name); ?>">
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="alignleft actions">
                    <label class="screen-reader-text" for="post-search-input"><?php _e('Search Pages:'); ?></label>
                    <input type="hidden" name="page" value="<?php echo esc_attr($get_page); ?>">
                    <input type="hidden" name="paged" value="1">
                    <?php if (!empty($_GET['orderby'])) : ?>
                        <input type="hidden" name="orderby" value="<?php echo esc_attr($_GET['orderby']); ?>">
                    <?php endif; ?>
                    <?php if (!empty($_GET['order'])) : ?>
                        <input type="hidden" name="order" value="<?php echo esc_attr($_GET['order']); ?>">
                    <?php endif; ?>
                    <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($get_search_current); ?>">
                    <button id="search-submit" class="button"><?php _e('Search') ?></button>
                </div>

                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo $count; ?> <?php echo $item_word; ?></span>
                    <?php $this->paginationLinks($page, $prev, $next, $first, $last, $pages); ?>
                </div>
                <br class="clear">
            </div>

        <?php
        if($this->formWrapTable == false) {
            echo '</form>';
        }
        echo $table;
        if($this->formWrapTable == true) {
            echo '</form>';
        }
        ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo $count; ?> <?php echo $item_word; ?></span>
                <?php $this->paginationLinks($page, $prev, $next, $first, $last, $pages); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Pagination Links
     *
     * @param string|int $page
     * @param string $prev
     * @param string $next
     * @param string $first
     * @param string $last
     * @param string|int $pages
     */
    protected function paginationLinks($page, $prev, $next, $first, $last, $pages) {
        echo "<span class=\"pagination-links\">";
        $last_text = __('Last page', 'typerocket-domain');
        $next_text = __('Next page', 'typerocket-domain');

        if($first && $pages > 2) {
            if( (int) $page === 1 ) {
                echo ' <span class="tablenav-pages-navspan  button disabled" aria-hidden="true">&laquo;</span> ';
            } else {
                echo " <a class=\"last-page button\" href=\"{$first}\"><span class=\"screen-reader-text\">{$last_text}</span><span aria-hidden=\"true\">&laquo;</span></a> ";
            }
        }

        if( $page < 2 ) {
            echo " <span class=\"tablenav-pages-navspan button disabled\" aria-hidden=\"true\">&lsaquo;</span> ";
        } else {
            echo " <a class=\"prev-page button\" href=\"{$prev}\" aria-hidden=\"true\">&lsaquo;</a> ";
        }
        echo " <span id=\"table-paging\" class=\"paging-input\">{$page} ".__('of', 'typerocket-domain')." <span class=\"total-pages\">{$pages}</span></span> ";
        if( $page < $pages ) {
            echo " <a class=\"next-page button\" href=\"{$next}\"><span class=\"screen-reader-text\">{$next_text}</span><span aria-hidden=\"true\">&rsaquo;</span></a> ";
        } else {
            echo " <span class=\"tablenav-pages-navspan button disabled\" aria-hidden=\"true\">&rsaquo;</span> ";
        }

        if($last && $pages > 2) {
            if( (int) $pages === $page  ) {
                echo ' <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span> ';
            } else {
                echo " <a class=\"last-page button\" href=\"{$last}\"><span class=\"screen-reader-text\">{$last_text}</span><span aria-hidden=\"true\">&raquo;</span></a> ";
            }

        }

        echo "</span>";
    }

    /**
     * Is Valid Search
     *
     * @return bool
     */
    protected function isValidSearch() {

        if( !empty($_GET['s']) && !empty($_GET['on'] && !empty($_GET['condition'])) ) {
            if(is_string($_GET['s']) && is_string($_GET['on']) && is_string($_GET['condition'])) {
                return true;
            }
        }

        return false;

    }

}