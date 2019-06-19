<?php
namespace TypeRocket\Database;

class Query
{
    public static $numberQueriesRun = 0;

    public $idColumn = 'id';
    public $lastCompiledSQL = null;
    public $returnOne = false;
    public $useResultsClass = false;
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
     * Get the ID Column
     *
     * @return string
     */
    public function getIdColumn()
    {
        return $this->idColumn;
    }

    /**
     * Set the ID Column
     *
     * @param $id
     *
     * @return Query $this
     */
    public function setIdColumn($id)
    {
        $this->idColumn = $id;

        return $this;
    }

    /**
     * Set table
     *
     * @param $name
     *
     * @return Query $this
     */
    public function table( $name )
    {
        $this->query['table'] = $name;

        return $this;
    }

    /**
     * Find all
     *
     * @param array|\ArrayObject $ids
     *
     * @param null $table
     * @return Query $this
     */
    public function findAll( $ids = [], $table = null )
    {
        if(!empty($ids)) {
            if(!$table) { $table = $this->query['table']; }
            $this->where( $table . '.' .$this->idColumn , 'IN', $ids);
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
            $whereQuery['condition'] = null;
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
     * Append Raw Where
     *
     * This method is not sanitized before it is run. Do not
     * use this method with user provided input.
     *
     * @param $condition string|null
     * @param $sql string
     * @return $this
     */
    public function appendRawWhere($condition, $sql)
    {
        $this->query['raw']['where'][] = [$condition, $sql];

        return $this;
    }

    /**
     * Remove Where
     *
     * Remove raw and standard where clauses.
     *
     * @return $this
     */
    public function removeWhere()
    {
        if(!empty($this->query['raw']['where'])) {
            unset($this->query['raw']['where']);
        }

        if(!empty($this->query['where'])) {
            unset($this->query['where']);
        }

        return $this;
    }

    /**
     * Remove Take
     *
     * Remove take from query
     *
     * @return $this
     */
    public function removeTake()
    {
        unset($this->query['take']);
        $this->returnOne = false;

        return $this;
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
        $this->query['order_by'][] = [ 'column' => $column, 'direction' => $direction];

        return $this;
    }

    /**
     * Group By
     *
     * @param $column
     *
     * @return $this
     */
    public function groupBy($column)
    {
        $this->query['group_by']['column'] = $column;

        return $this;
    }

    /**
     * Distinct
     *
     * @return $this
     */
    public function distinct()
    {
        $this->query['distinct'] = true;

        return $this;
    }

    /**
     * Take only a select group
     *
     * @param int $limit limit
     * @param int $offset offset
     * @param bool $returnOne if taking one return direct object
     *
     * @return $this
     */
    public function take( $limit, $offset = 0, $returnOne = true ) {
        $this->query['take']['limit'] = (int) $limit;
        $this->query['take']['offset'] = (int) $offset;

        if( $limit === 1 && $returnOne ) {
            $this->returnOne = true;
        }

        return $this;
    }

    /**
     * Get First
     *
     * Get first and return one but not as a collection.
     *
     * @return array|bool|false|int|null|object
     */
    public function first() {
        $this->returnOne = true;
        $this->take(1);
        return $this->get();
    }

    /**
     * Always Wrap In Results Class
     *
     * @return $this
     */
    public function useResultsClass()
    {
        $this->useResultsClass = true;
        return $this;
    }

    /**
     * Create resource by TypeRocket fields
     *
     * When a resource is created the Model ID should be set to the
     * resource's ID.
     *
     * @param array|\ArrayObject $fields
     * @param array $multiple optional
     *
     * @return mixed
     */
    public function create( $fields, $multiple = [] )
    {
        $this->setQueryType('create');
        $this->query['data'] = $fields;
        $this->query['data_values'] = $multiple;

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
        return $this->where( $this->query['table'] . '.' . $this->idColumn, $id)->take(1)->findAll();
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
     * Find first where or die
     *
     * @param $column
     * @param $arg1
     * @param null $arg2
     * @param string $condition
     *
     * @return object
     * @internal param $id
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
     * Select only specific columns
     *
     * @param $args
     *
     * @return $this
     */
    public function select($args)
    {
        if( is_array($args) ) {
            $select = $args;
        } else {
            $select = func_get_args();
        }

        $this->query['select'] = array_merge($this->query['select'] ?? [], $select);

        return $this;
    }

    /**
     * Reset Select
     *
     * @param $args
     * @return Query
     */
    public function reselect($args)
    {
        if( ! is_array($args) ) {
            $args = func_get_args();
        }

        unset($this->query['select']);

        return $this->select($args);
    }

    /**
     * Count results
     *
     * @param string $column
     *
     * @return array|bool|false|int|null|object
     */
    public function count( $column = '*' )
    {
        $this->setQueryType('function', [ 'count' => $column]);

        return $this->runQuery();
    }

    /**
     * Sum
     *
     * @param string $column
     *
     * @return array|bool|false|int|null|object
     */
    public function sum( $column )
    {
        $this->setQueryType('function', [ 'sum' => $column]);

        return $this->runQuery();
    }

    /**
     * Min
     *
     * @param string $column
     *
     * @return array|bool|false|int|null|object
     */
    public function min( $column )
    {
        $this->setQueryType('function', [ 'min' => $column]);

        return $this->runQuery();
    }

    /**
     * Max
     *
     * @param string $column
     *
     * @return array|bool|false|int|null|object
     */
    public function max( $column )
    {
        $this->setQueryType('function', [ 'max' => $column]);

        return $this->runQuery();
    }

    /**
     * Average
     *
     * @param string $column
     *
     * @return array|bool|false|int|null|object
     */
    public function avg( $column )
    {
        $this->setQueryType('function', [ 'avg' => $column]);

        return $this->runQuery();
    }

    /**
     * Join
     *
     * @param string $table
     * @param string $column
     * @param string $arg1 column or operator
     * @param null|string $arg2 column if arg1 is set to operator
     * @param string $type INNER (default), LEFT, RIGHT
     *
     * @return $this
     */
    public function join($table, $column, $arg1, $arg2 = null, $type = 'INNER')
    {
        $joinQuery = [];
        $joinQuery['type'] = strtoupper($type) . ' JOIN';
        $joinQuery['table'] = $table;
        $joinQuery['on'] = 'ON';
        $joinQuery['column1'] = $column;
        $joinQuery['operator'] = '=';
        $joinQuery['column2'] = $arg1;

        if( isset($arg2) ) {
            $joinQuery['operator'] = $arg1;
            $joinQuery['column2'] = $arg2;
        }

        $this->query['joins'][] = $joinQuery;

        return $this;
    }

    /**
     * Left Join
     *
     * @param string $table
     * @param string $column
     * @param string $arg1
     * @param null|string $arg2
     *
     * @return \TypeRocket\Database\Query
     */
    public function leftJoin($table, $column, $arg1, $arg2 = null)
    {
        return $this->join($table, $column, $arg1, $arg2, 'LEFT');
    }

    /**
     * Right Join
     *
     * @param string $table
     * @param string $column
     * @param string $arg1
     * @param null|string $arg2
     *
     * @return \TypeRocket\Database\Query
     */
    public function rightJoin($table, $column, $arg1, $arg2 = null)
    {
        return $this->join($table, $column, $arg1, $arg2, 'RIGHT');
    }

    /**
     * Union
     *
     * @param \TypeRocket\Database\Query $query
     *
     * @return $this
     */
    public function union( Query $query)
    {
        $this->query['union'] = $query;

        return $this;
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
          'function', 'update', 'delete', 'create'
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
        } elseif( array_key_exists('function', $query) ) {
            $result = $wpdb->get_var( $sql );
        } else {
            $results = $wpdb->get_results( $sql, ARRAY_A );
            if($results && $this->returnOne && !$this->useResultsClass) {
                $result = $results[0];
            } elseif( $results ) {
                /** @var Results $result */
                $result = new $this->resultsClass;
                foreach ($results as $object) {
                    $result->append( (object) $object );
                }
            } else {
                $result = false;
            }
        }

        self::$numberQueriesRun++;

        return $result;
    }

    /**
     * Compile Full Query
     *
     * @return string|null
     */
    public function compileFullQuery() {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $table = $this->query['table'];
        $sql_insert_columns = $sql_union = $sql_insert_values = $distinct = '';

        // compilers
        $sql_where = $this->compileWhere();
        extract( $this->compileInsert() );
        $sql_update = $this->compileUpdate();
        $sql_select_columns = $this->compileSelectColumns();
        $sql_limit = $this->compileTake();
        $sql_order = $this->compileOrder();
        $sql_function = $this->compileFunction();
        $sql_grouping = $this->compileGrouping();
        $sql_join = $this->compileJoins();
        $sql_union = $this->compileUnion();

        if( array_key_exists('distinct', $this->query) ) {
            $distinct = 'DISTINCT ';
        }

        $sql_select = $sql_join . $sql_where . $sql_grouping . $sql_order . $sql_limit . $sql_union;

        if( array_key_exists('delete', $this->query) ) {
            $sql = 'DELETE FROM ' . $table . $sql_where;
        } elseif( array_key_exists('create', $this->query) ) {
            $sql = 'INSERT INTO ' . $table . $sql_insert_columns . ' VALUES ' . $sql_insert_values;
        } elseif( array_key_exists('update', $this->query) ) {
            $sql = 'UPDATE ' . $table . ' SET ' . $sql_update . $sql_where;
        } elseif( array_key_exists('function', $this->query) ) {
            $sql = 'SELECT ' . $distinct . $sql_function . 'FROM '. $table . $sql_select;
        } else {
            $sql = 'SELECT ' . $distinct . $sql_select_columns .' FROM '. $table . $sql_select;
        }

        $this->lastCompiledSQL = $sql;

        return $this->lastCompiledSQL;
    }

    /**
     * Compile Select Columns
     *
     * @return string
     */
    protected function compileSelectColumns() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql = '*';

        if( !empty($query['select']) && is_array($query['select']) ) {
            $sql = implode(',',$query['select']);
        }

        return $sql;
    }

    /**
     * Compile Union
     *
     * @return string
     */
    protected function compileUnion()
    {
        $query = $this->query;
        $sql = '';

        if( array_key_exists('union', $query) ) {
            $sql .= ' UNION ';
            /** @var Query $union_query */
            $union_query = $this->query['union'];
            $sql .= $union_query->compileFullQuery();
        }

        return $sql;
    }

    /**
     * Compile Function
     *
     * @return string
     */
    protected function compileFunction() {
        $query = $this->query;
        $sql = '';

        if( array_key_exists('function', $query) ) {
            $key = key($query['function']);
            $func = strtoupper( $key );
            $column = $this->query['function'][$key];
            $sql = $func.'('.$column.') ';
        }

        return $sql;
    }

    /**
     * Compile Group By
     *
     * @return string
     */
    protected function compileGrouping() {
        $query = $this->query;
        $sql = '';

        if( array_key_exists('group_by', $query) ) {
            $column = $query['group_by']['column'];
            $sql = ' GROUP BY '.$column.' ';
        }

        return $sql;
    }

    /**
     * Compile Take
     *
     * @return string
     */
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

    /**
     * Compile Order
     *
     * @return string
     */
    protected function compileOrder() {
        $query = $this->query;
        $sql = '';

        if( !empty($query['order_by']) ) {
            $sql .= " ORDER BY ";

            $order = array_map(function($ordering) {
                $order_column = preg_replace($this->columnPattern, '', $ordering['column']);
                $order_direction = $ordering['direction'] == 'ASC' ? 'ASC' : 'DESC';
                return "{$order_column} {$order_direction}";
            }, $query['order_by']);

            $sql .= implode(' , ', $order);
        }

        return $sql;
    }

    /**
     * Compile Insert
     *
     * @return string|array
     */
    protected function compileInsert() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql_insert = [ 'sql_insert_columns' => '', 'sql_insert_values' => '' ];

        if( !empty($query['create']) && !empty($query['data']) ) {
            $inserts = $columns = [];

            if( !empty($query['data_values']) ) {
                foreach( $query['data'] as $column ) {
                    $columns[] =  preg_replace($this->columnPattern, '', $column);
                }

                $sql_insert['sql_insert_columns'] = ' (' . implode(',', $columns) . ') ';
                $sql_insert_values_container = [];

                foreach ($query['data_values'] as $multiples ) {
                    $inserts = [];

                    foreach ($multiples as $data ) {
                        $this->setupInserts($data, $inserts);
                    }

                    $sql_insert_values_container[] = ' ( ' . implode(',', $inserts) . ' ) ';
                }

                $sql_insert['sql_insert_values'] .= implode(',', $sql_insert_values_container);

            } else {
                foreach( $query['data'] as $column => $data ) {
                    $columns[] =  preg_replace($this->columnPattern, '', $column);
                    $this->setupInserts($data, $inserts);
                }

                $sql_insert['sql_insert_columns'] = ' (' . implode(',', $columns) . ') ';
                $sql_insert['sql_insert_values'] .= ' ( ' . implode(',', $inserts) . ' ) ';
            }
        }

        return $sql_insert;
    }

    /**
     * Compile Update
     *
     * @return string
     */
    protected function compileUpdate() {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = $this->query;
        $sql = '';

        if( !empty($query['update']) && !empty($query['data']) ) {
            $inserts = $columns = [];
            foreach( $query['data'] as $column => $data ) {
                $columns[] = preg_replace($this->columnPattern, '', $column);
                $this->setupInserts($data, $inserts);
            }

            $sql = implode(', ', array_map(
                function ($v, $k) { return sprintf("%s=%s", $k, $v); },
                $inserts,
                $columns
            ));
        }

        return $sql;
    }

    /**
     * Setup the Inserts
     *
     * @param $data
     * @param $inserts
     *
     * @return $this
     */
    protected function setupInserts( $data, &$inserts ) {
        $inserts[] = $this->prepareValue($data);
        return $this;
    }

    /**
     * Prepare Value
     *
     * @param $value
     *
     * @return int|null|string
     */
    protected function prepareValue( $value ) {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $prepared = null;

        if( is_array($value) ) {
            $prepared = $wpdb->prepare( '%s', serialize($value) );
        } elseif( $value === null ) {
            $prepared = 'NULL';
        } elseif( is_int($value) ) {
            $prepared = (int) $value;
        } elseif (is_object($value)) {
            $value = (string) $value;
            $prepared = $wpdb->prepare( '%s', $value );
        } else {
            $prepared = $wpdb->prepare( '%s', $value );
        }

        $prepared = $wpdb->remove_placeholder_escape($prepared);

        return $prepared;
    }

    /**
     * Compile Where
     *
     * @return string
     */
    protected function compileWhere() {
        $query = $this->query;
        $sql = '';

        if( !empty($query['where']) ) {
            foreach( $query['where'] as $where ) {

                if( is_array($where['value']) ) {
                    $where['value'] = array_map(\Closure::bind(function($value) {
                        return $this->prepareValue($value);
                    }, $this), $where['value']);

                    $where['value'] = '(' . implode(',', $where['value']) . ')';
                } else {
                    $where['value'] = $this->prepareValue($where['value']);
                }

                if($where['condition'] === null) {
                    unset($where['condition']);
                }

                $sql .= ' ' . implode(' ', $where);
            }
        }

        if(!empty($this->query['raw']['where']) && is_array($this->query['raw']['where'])) {

            foreach ($this->query['raw']['where'] as $rawWhere) {
                if(!$sql) { unset($rawWhere[0]); }
                $sql .= ' ' . implode(' ', $rawWhere);
            }
        }

        if($sql) {
            $sql = ' WHERE' . $sql;
        }

        return $sql;
    }

    /**
     * Compile Joins
     *
     * @return string
     */
    protected function compileJoins() {
        $query = $this->query;
        $sql = '';

        if( !empty($query['joins']) ) {
            foreach( $query['joins'] as $join ) {

                if( is_callable($join['table']) ) {
                    $joinQuery = new static();
                    $joinQuery->run = false;
                    $as = '';
                    $join['table'] = call_user_func_array( $join['table'], [$joinQuery, &$as]);
                    $join['table'] = trim('( ' . $joinQuery->compileFullQuery() . ' ) ' . $as);
                }

                $sql .= ' ' . implode(' ', $join);
            }
        }

        return $sql;
    }

}
