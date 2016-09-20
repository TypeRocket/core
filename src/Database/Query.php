<?php
namespace TypeRocket\Database;

class Query
{
    public $table = null;
    public $idColumn = 'id';

    public $lastCompiledSQL = null;
    public $returnOne = false;
    public $resultsClass = Results::class;
    public $run = true;
    protected $columnPattern = "/[^a-zA-Z0-9\\\\_]+/";
    protected $query = [];

    /**
     * Get Date Time
     *
     * @return bool|string
     */
    public function getDateTime()
    {
        return date('Y-m-d H:i:s', time());
    }

    /**
     * Find all
     *
     * @param array|\ArrayObject $ids
     *
     * @return Query $this
     */
    public function findAll( $ids = [] )
    {
        if(!empty($ids)) {
            $this->where( $this->idColumn , 'IN', $ids);
        }

        return $this;
    }

    /**
     * Get results from find methods
     *
     * @return array|null|object
     */
    public function get() {
        $this->setQueryType();
        return $this->runQuery();
    }

    /**
     * Where
     *
     * @param string $column
     * @param string $arg1
     * @param null|string $arg2
     * @param string $condition
     *
     * @return $this
     */
    public function where($column, $arg1, $arg2 = null, $condition = 'AND')
    {
        $whereQuery = [];

        if( !empty($this->query['where']) ) {
            $whereQuery['condition'] = strtoupper($condition);
        } else {
            $whereQuery['condition'] = 'WHERE';
        }

        $whereQuery['column'] = $column;
        $whereQuery['operator'] = '=';
        $whereQuery['value'] = $arg1;

        if( isset($arg2) ) {
            $whereQuery['operator'] = $arg1;
            $whereQuery['value'] = $arg2;
        }

        $this->query['where'][] = $whereQuery;

        return $this;
    }

    /**
     * Or Where
     *
     * @param string $column
     * @param string $arg1
     * @param null|string $arg2
     *
     * @return Query
     */
    public function orWhere($column, $arg1, $arg2 = null)
    {
        return $this->where($column, $arg1, $arg2, 'OR');
    }

    /**
     * Order by
     *
     * @param string $column name of column
     * @param string $direction default ASC other DESC
     *
     * @return $this
     */
    public function orderBy($column = 'id', $direction = 'ASC')
    {
        $this->query['order_by']['column'] = $column;
        $this->query['order_by']['direction'] = $direction;

        return $this;
    }

    /**
     * Take only a select group
     *
     * @param $limit
     *
     * @param int $offset
     *
     * @return $this
     */
    public function take( $limit, $offset = 0 ) {
        $this->query['take']['limit'] = (int) $limit;
        $this->query['take']['offset'] = (int) $offset;

        return $this;
    }

    /**
     * @return array|bool|false|int|null|object
     */
    public function first() {
        $this->returnOne = true;
        $this->take(1);
        return $this->get();
    }

    /**
     * Create resource by TypeRocket fields
     *
     * When a resource is created the Model ID should be set to the
     * resource's ID.
     *
     * @param array|\ArrayObject $fields
     *
     * @return mixed
     */
    public function create( $fields)
    {
        $this->setQueryType('create');
        $this->query['data'] = $fields;

        return $this->runQuery();
    }

    /**
     * Update resource by TypeRocket fields
     *
     * @param array|\ArrayObject $fields
     *
     * @return mixed
     */
    public function update( $fields = [])
    {
        $this->setQueryType('update');
        $this->query['data'] = $fields;

        return $this->runQuery();
    }

    /**
     * Find resource by ID
     *
     * @param $id
     *
     * @return $this
     */
    public function findById($id)
    {
        $this->returnOne = true;
        return $this->where( $this->idColumn, $id)->take(1)->findAll();
    }

    /**
     * Find by ID or die
     *
     * @param $id
     *
     * @return object
     */
    public function findOrDie($id) {
        if( ! $data = $this->findById($id)->get() ) {
            wp_die('Something went wrong');
        }

        return $data;
    }

    /**
     * Find first where of die
     *
     * @param $column
     * @param $arg1
     * @param null $arg2
     * @param string $condition
     *
     * @return object
     * @internal param $id
     *
     */
    public function findFirstWhereOrDie($column, $arg1, $arg2 = null, $condition = 'AND') {
        if( ! $data = $this->where( $column, $arg1, $arg2, $condition)->first() ) {
            wp_die('Something went wrong');
        }

        return $data;
    }

    /**
     * Delete
     *
     * @param array|\ArrayObject $ids
     *
     * @return array|false|int|null|object
     */
    public function delete( $ids = [] ) {
        $this->setQueryType('delete');

        if(!empty($ids)) {
            $this->where( $this->idColumn , 'IN', $ids);
        }

        return $this->runQuery();
    }

    /**
     * Count results
     *
     * @return array|bool|false|int|null|object
     */
    public function count()
    {
        $this->setQueryType('count');

        return $this->runQuery();
    }

    /**
     * Set Query Type
     *
     * @param string|null $type
     *
     * @param bool|array $args
     *
     * @return $this
     */
    protected function setQueryType( $type = null , $args = true ) {

        $actions = [
          'count', 'update', 'delete', 'create'
        ];

        foreach ($actions as $action ) {
            unset($this->query[$action]);
        }

        if( in_array($type, $actions) ) {
            unset($this->query['select']);
        }

        if($type) {
            $this->query[$type] = $args;
        }

        return $this;
    }

    /**
     * Select only specific columns
     *
     * @param $args
     *
     * @return $this
     */
    public function select($args)
    {
        if( is_array($args) ) {
            $this->query['select'] = $args;
        } else {
            $this->query['select'] = func_get_args();
        }

        return $this;
    }

    /**
     * Run the SQL query from the query property
     *
     * @param array|\ArrayObject $query
     *
     * @return array|bool|false|int|null|object
     */
    protected function runQuery( $query = [] ) {
        /** @var \wpdb $wpdb */
        global $wpdb;

        if( empty($query) ) {
            $query = $this->query;
        }

        $sql = $this->compileFullQuery();

        if( ! $this->run ) {
            return false;
        }

        if( array_key_exists('delete', $query) ) {
            $result = $wpdb->query( $sql );
        } elseif( array_key_exists('create', $query) ) {
            $result = false;
            if( $wpdb->query( $sql ) ) {
                $result = $wpdb->insert_id;
            };
        } elseif( array_key_exists('update', $query) ) {
            $result = $wpdb->query( $sql );
        } elseif( array_key_exists('count', $query) ) {
            $result = (int) $wpdb->get_var( $sql );
        } else {
            $results = $wpdb->get_results( $sql, ARRAY_A );
            if($results && $this->returnOne) {
                $result = $results[0];
            } elseif( $results ) {
                $result = new $this->resultsClass;
                foreach ($results as $object) {
                    $result->append( (object) $object );
                }
            } else {
                $result = false;
            }
        }

        return $result;
    }

    public function compileFullQuery() {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $table = $this->table;
        $join_sql = '';

        // compilers
        $sql_where = $this->compileWhere();
        extract( $this->compileInset() );
        $sql_update = $this->compileUpdate();
        $sql_select_columns = $this->compileSelectColumns();
        $sql_limit = $this->compileTake();
        $sql_order = $this->compileOrder();

        if( array_key_exists('delete', $this->query) ) {
            $sql = 'DELETE FROM `' . $table . '`' . $join_sql . $sql_where;
        } elseif( array_key_exists('create', $this->query) ) {
            $sql = 'INSERT INTO `' . $table . '`' . $sql_insert_columns . ' VALUES ' . $sql_insert_values;
        } elseif( array_key_exists('update', $this->query) ) {
            $sql = 'UPDATE `' . $table . '` SET ' . $sql_update . $join_sql . $sql_where;
        } elseif( array_key_exists('count', $this->query) ) {
            $sql = 'SELECT COUNT(*) FROM `'. $table . '`' . $join_sql . $sql_where . $sql_order . $sql_limit;
        } else {
            $sql = 'SELECT ' . $sql_select_columns .' FROM `'. $table . '`' . $join_sql . $sql_where . $sql_order . $sql_limit;
        }

        $this->lastCompiledSQL = $sql;

        return $this->lastCompiledSQL;
    }

    protected function compileSelectColumns() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql = '*';
        $table = $this->table;
        $pattern = $this->columnPattern;

        if( !empty($query['select']) && is_array($query['select']) ) {
            $sql_select_columns = array_reduce(
                $query['select'],
                function( $carry = '', $value ) use ( $table, $pattern ) {
                    return $carry . ',`'.$table.'`.`' . preg_replace($pattern, '', $value) . '`';
                }
            );

            $sql = trim($sql_select_columns, ',');
        }

        return $sql;
    }

    protected function compileTake() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql = '';

        if( !empty($query['take']) ) {
            $sql = ' ' . $wpdb->prepare('LIMIT %d OFFSET %d', $query['take']);
        }

        return $sql;
    }

    protected function compileOrder() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql = '';
        $table = $this->table;

        if( !empty($query['order_by']) ) {
            $order_column = preg_replace($this->columnPattern, '', $query['order_by']['column']);
            $order_direction = $query['order_by']['direction'] == 'ASC' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY `{$table}`.`{$order_column}` {$order_direction}";
        }

        return $sql;
    }

    protected function compileInset() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql_insert = [ 'sql_insert_columns' => '', 'sql_insert_values' => '' ];
        $table = $this->table;

        if( !empty($query['create']) && !empty($query['data']) ) {
            $inserts = $columns = [];
            foreach( $query['data'] as $column => $data ) {
                $columns[] = '`' . $table . '`.`' . preg_replace($this->columnPattern, '', $column) . '`';

                if( is_array($data) ) {
                    $inserts[] = $wpdb->prepare( '%s', serialize($data) );
                } else {
                    $inserts[] = $wpdb->prepare( '%s', $data );
                }
            }

            $sql_insert['sql_insert_columns'] = ' (' . implode(',', $columns) . ') ';
            $sql_insert['sql_insert_values'] .= ' ( ' . implode(',', $inserts) . ' ) ';
        }

        return $sql_insert;
    }

    protected function compileUpdate() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql = '';
        $table = $this->table;

        if( !empty($query['update']) && !empty($query['data']) ) {
            $inserts = $columns = [];
            foreach( $query['data'] as $column => $data ) {
                $columns[] = '`' . $table . '`.`' . preg_replace($this->columnPattern, '', $column) . '`';

                if( is_array($data) ) {
                    $inserts[] = $wpdb->prepare( '%s', serialize($data) );
                } else {
                    $inserts[] = $wpdb->prepare( '%s', $data );
                }
            }

            $sql = implode(', ', array_map(
                function ($v, $k) { return sprintf("%s=%s", $k, $v); },
                $inserts,
                $columns
            ));
        }

        return $sql;
    }

    protected function compileWhere() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql = '';
        $table = $this->table;

        if( !empty($query['where']) ) {
            foreach( $query['where'] as $where ) {

                if( is_array($where['value']) ) {

                    $where['value'] = array_map(function($value) use ($wpdb, $table) {
                        return $wpdb->prepare( '%s', $value );
                    }, $where['value']);

                    $where['value'] = '(' . implode(',', $where['value']) . ')';
                } else {
                    $where['value'] = $wpdb->prepare( '%s', $where['value'] );
                }

                $where['column'] = '`' . $table . '`.`' . $where['column'] . '`';

                $sql .= ' ' . implode(' ', $where);
            }
        }

        return $sql;
    }

}