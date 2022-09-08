<?php
namespace TypeRocket\Database;

use TypeRocket\Utility\Str;

class Query
{
    public static $numberQueriesRun = 0;

    public $idColumn = 'id';
    public $lastCompiledSQL = null;
    public $returnOne = false;
    public $useResultsClass = false;
    public $resultsClass = Results::class;
    public $run = true;
    protected $columnPattern = "/[^a-zA-Z0-9\\\\_\\.\\`\\*\\s]+/";
    protected $query = [];
    protected $selectTable = null;
    protected $joinAs = null;
    protected $tableAs = null;
    protected ?string $connection = null;
    protected ?\wpdb $wpdb = null;

    /**
     * Query constructor.
     *
     * @param null|string $table
     * @param bool|null|string $selectTable
     * @param null|string $idColumn
     */
    public function __construct($table = null, $selectTable = null, $idColumn = null)
    {
        $wpdb = $this->establishConnection();

        if(is_string($table)) {
            $this->query['table'] = $table;
        }

        $this->setSelectTable($selectTable);

        if($idColumn) {
            $this->idColumn = $idColumn;
        }

        if(!$this->wpdb) {
            $this->setWpdb($wpdb);
        }
    }

    /**
     * @return \wpdb
     */
    protected function establishConnection()
    {
        $connection = Connection::getFromContainer();

        if(!$name = $this->connection) {
            return $connection->default();
        }

        return $connection->get($name);
    }

    /**
     * Merge
     *
     * Create a new query from other queries but keeps list (see code)
     *
     * @param Query $query
     *
     * @return $this
     */
    public function merge(Query $query)
    {
        $list = [];
        $select = $this->query['select'] ?? null;
        $table = $this->query['table'] ?? null;

        $list[] = ['joins' => [[
            'type' => 'INNER JOIN',
            'table' => $query->query['table']
        ]]];
        $list[] = $query->query;
        $list[] = ['where' => 'AND'];

        $list[] = $this->query;
        $new = array_merge_recursive(...$list);
        $new['select'] = $select ?? ($table . '.*');

        $keeps = [
            'data', 'data_values', 'table', 'select',
            'create', 'update', 'take', 'order_by', 'function',
            'distinct', 'union', 'group_by'
        ];
        foreach ($keeps as $keep) {
            $new[$keep] = $this->query[$keep] ?? null;
        }

        $new = array_filter($new);
        $this->query = $new;

        return $this;
    }

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
     * @return string
     */
    public function getIdColumWithTable($idColumn = null)
    {
        $idColumn ??= $this->idColumn;
        $table = $this->query['table'] ? "`{$this->query['table']}`." : '';
        return "{$table}`{$idColumn}`";
    }

    /**
     * Set the ID Column
     *
     * @param string $id
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
     * @param string $name
     * @param null|string $as
     *
     * @return Query $this
     */
    public function table($name, $as = null)
    {
        $this->query['table'] = $name;
        $this->tableAs = $as;

        return $this;
    }

    /**
     * @param \wpdb|null $wpdb
     * @return $this
     */
    public function setWpdb(?\wpdb $wpdb = null)
    {
        $this->wpdb = $wpdb;

        return $this;
    }

    /**
     * @return \wpdb
     */
    public function getWpdb() : \wpdb
    {
        return $this->wpdb;
    }

    /**
     * Set Select Table
     *
     * @param string|null|true $table
     * @return $this
     */
    public function setSelectTable($table = true)
    {
        $this->selectTable = $table === true ? $this->query['table'] : $table;

        return $this;
    }

    /**
     * Get Select Table
     *
     * @return null|string
     */
    public function getSelectTable()
    {
        return $this->selectTable;
    }

    /**
     * Find all
     *
     * @param array|\ArrayObject $ids
     *
     * @param null|string $table
     * @param null|int $num
     *
     * @return Query $this
     */
    public function findAll( $ids = [], $table = null, $num = null )
    {
        $num = $num ?? func_num_args();

        if(!empty($ids)) {
            if(!$table) { $table = $this->query['table']; }
            $this->where( $table . '.' .$this->idColumn , 'IN', $ids);
        } elseif($num) {
            // block query from getting results if values provided is empty
            $this->where( 1 , 0);
        }

        return $this;
    }

    /**
     * Get results from find methods
     *
     * @return array|null|object|Results
     */
    public function get()
    {
        $this->setQueryType();
        return $this->runQuery();
    }

    /**
     * Where
     *
     * @param string|array $column
     * @param string|null $arg1
     * @param null|string|array $arg2
     * @param string $condition
     * @param null|int $num
     * @return $this
     */
    public function where($column, $arg1 = null, $arg2 = null, $condition = 'AND', $num = null)
    {
        if(is_array($column)) {

            if( !empty($this->query['where']) ) {
                $this->query['where'][] = $arg1 ? strtoupper($arg1) : 'AND';
            }

            $this->query['where'][] = $column;

            return $this;
        }

        $whereQuery = [];

        if( !empty($this->query['where']) ) {
            $whereQuery['condition'] = strtoupper($condition);
        } else {
            $whereQuery['condition'] = null;
        }

        $num = $num ?? func_num_args();
        $whereQuery['column'] = $column;

        if( $num < 3 ) {
            $whereQuery['operator'] = '=';
            $whereQuery['value'] = $arg1;
        } else {
            $whereQuery['operator'] = $arg1;
            $whereQuery['value'] = $arg2;
        }

        $this->query['where'][] = $whereQuery;

        return $this;
    }

    /**
     * Last Where
     *
     * @return array|null
     */
    public function lastWhere() : ?array
    {
        $key = array_key_last($this->query['where']);

        if($key === null) {
            return null;
        }

        return ['key' => $key, 'value' => $this->query['where'][$key]];
    }

    /**
     * Modify Where
     *
     * @param int $index
     * @param array $args
     * @param bool $merge
     *
     * @return $this
     */
    public function modifyWhere($index, $args, $merge = true, $callback = null)
    {
        if(empty($this->query['where'])) {
            return $this;
        }

        if($index === -1) {
            $index = array_key_last($this->query['where']);
        }

        $where = $merge ? array_merge($this->query['where'][$index], $args) : $args;

        if(is_callable($callback)) {
            $callback($where, $this, $index);
        }

        $this->query['where'][$index] = $where;

        return $this;
    }

    /**
     * Or Where
     *
     * @param string $column
     * @param string $arg1
     * @param null|string|array $arg2
     * @param null|string|int $num
     * @return Query
     */
    public function orWhere($column, $arg1, $arg2 = null, $num = null)
    {
        $num = $num ?? func_num_args();
        return $this->where($column, $arg1, $arg2, 'OR', $num);
    }

    /**
     * Append Raw Where
     *
     * This method is not sanitized before it is run. Do not
     * use this method with user provided input.
     *
     * @param string $condition string|null
     * @param string $sql string
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
     * Append Raw Order By
     *
     * This method is not sanitized before it is run. Do not
     * use this method with user provided input.
     *
     * @param string $sql string
     * @return $this
     */
    public function appendRawOrderBy($sql)
    {
        $this->query['raw']['order_by'][] = $sql;

        return $this;
    }

    /**
     * Reorder
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function reorder($column = 'id', $direction = 'ASC')
    {
        $num = func_num_args();
        unset($this->query['order_by']);

        if($num > 0) {
            $this->orderBy($column, $direction);
        }

        return $this;
    }

    /**
     * Group By
     *
     * @param string|string[] $column
     *
     * @return $this
     */
    public function groupBy($column)
    {
        $this->query['group_by']['column'] = (array) $column;

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
    public function first()
    {
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
     * @param string $id
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
     * @param string $id
     *
     * @return object
     * @throws \Exception
     */
    public function findOrDie($id)
    {
        if( ! $data = $this->findById($id)->get() ) {
            throw new \Exception("Model not found: {$id} " . get_class($this));
        }

        return $data;
    }

    /**
     * Find first where or die
     *
     * @param string $column
     * @param string $arg1
     * @param null|array|string $arg2
     * @param string $condition
     * @param null|int $num
     *
     * @return object
     * @throws \Exception
     * @internal param $id
     */
    public function findFirstWhereOrDie($column, $arg1, $arg2 = null, $condition = 'AND', $num = null)
    {
        if( ! $data = $this->where( $column, $arg1, $arg2, $condition, $num ?? func_num_args())->first() ) {
            throw new \Exception("Model not found: on {$column} " . get_class($this));
        }

        return $data;
    }

    /**
     * Delete
     *
     * @param array|int $ids
     *
     * @return array|false|int|null|object
     */
    public function delete( $ids = null )
    {
        $this->setQueryType('delete');
        $idColumnWhere = $this->query['table'] . '.' . $this->idColumn;

        if(is_numeric($ids)) {
            $this->where( $idColumnWhere , $ids);
        } elseif (is_array($ids)) {
            $this->where( $idColumnWhere , 'IN', $ids);
        } elseif(defined('TYPEROCKET_QUERY_DELETE_ANY') && constant('TYPEROCKET_QUERY_DELETE_ANY') === true) {
            $this->where( $idColumnWhere , $ids);
        }

        return $this->runQuery();
    }

    /**
     * Select only specific columns
     *
     * @param string $args
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
     * @param string $args
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
     * Count
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
     * Count Derived
     *
     * @return array|bool|false|int|null
     */
    public function countDerived()
    {
        $this->setQueryType('function', [ 'countDerived' => '*']);

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
     * Join As
     *
     * @param $as
     *
     * @return $this;
     */
    public function setJoinAs($as)
    {
        $this->joinAs = $as;

        return $this;
    }

    /**
     * Get Join As
     *
     * @return null|string
     */
    public function getJoinAs()
    {
        return $this->joinAs;
    }

    /**
     * From As
     *
     * @param $as
     *
     * @return $this;
     */
    public function setTableAs($as)
    {
        $this->tableAs = $as;

        return $this;
    }

    /**
     * Get From As
     *
     * @return null|string
     */
    public function getTableAs()
    {
        return $this->tableAs;
    }

    /**
     * Join
     *
     * @param string|callable|Query $table
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
        $joinQuery['column1'] = $this->tickSqlName($column);
        $joinQuery['operator'] = '=';
        $joinQuery['column2'] = $arg1;

        if( isset($arg2) ) {
            $joinQuery['operator'] = $arg1;
            $joinQuery['column2'] = $arg2;
        }

        $joinQuery['column2'] = $this->tickSqlName($joinQuery['column2']);

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
     * Paginate
     *
     * @param int $number
     * @param int|null $page automatically set to $_GET['paged'] or $_GET['page']
     * @param callable|null $callback
     * @return ResultsPaged|null
     */
    public function paginate($number = 25, $page = null, $callback = null)
    {
        $count_clone = clone $this;
        $page = $page ?? $_GET['paged'] ?? $_GET['page'] ?? 1;

        if(!is_numeric($page)) { $page = 1; }
        $page = (int) $page;

        $this->take($number, $page < 2 ? 0 : $number * ( $page - 1), false);
        $this->returnOne = false;

        $results = $this->get();

        if(is_callable($callback, true)) {
            $results = $callback($results);
        }

        if($results) {
            return new ResultsPaged($results, $page < 2 ? 1 : $page, $count_clone->removeTake()->count(), $number);
        }

        return null;
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
    protected function setQueryType( $type = null , $args = true )
    {
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
     * @return array|bool|false|int|null|Results|object
     */
    protected function runQuery( $query = [] )
    {
        $wpdb = $this->wpdb;

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

                if($result === 0 && $wpdb->rows_affected === 1)
                {
                    $column_id = $this->getIdColumn();

                    if($column_id_value = $this->query['data'][$column_id] ?? null) {
                        return $column_id_value;
                    }
                }
            }
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
    public function compileFullQuery()
    {
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
        $sql_table = $this->compileTable();

        if( array_key_exists('distinct', $this->query) ) {
            $distinct = 'DISTINCT ';
        }

        $sql_select = $sql_table . $sql_join . $sql_where . $sql_grouping . $sql_order . $sql_limit . $sql_union;

        if( array_key_exists('delete', $this->query) ) {
            $sql = 'DELETE FROM ' . $sql_table . $sql_where;
        } elseif( array_key_exists('create', $this->query) ) {
            $sql = 'INSERT INTO ' . $sql_table . $sql_insert_columns . ' VALUES ' . $sql_insert_values;
        } elseif( array_key_exists('update', $this->query) ) {
            $sql = 'UPDATE ' . $sql_table . ' SET ' . $sql_update . $sql_where;
        } elseif( $this->query['function']['countDerived'] ?? null ) {
            $sql = $this->compileCountDerived('SELECT ' . $distinct . $sql_select_columns . 'FROM ' . $sql_select);
        } elseif( array_key_exists('function', $this->query) ) {
            $sql = 'SELECT ' . $distinct . $sql_function . 'FROM '. $sql_select;
        } else {
            $sql = 'SELECT ' . $distinct . $sql_select_columns .' FROM '. $sql_select;
        }

        $this->lastCompiledSQL = $sql;

        return $this->lastCompiledSQL;
    }

    /**
     * Compile Count Derived
     *
     * @param $sql
     *
     * @return string
     */
    public function compileCountDerived($sql)
    {
        static $counter = 1;

        return "SELECT COUNT(*) FROM ({$sql}) as tr_count_derived" . $counter++;
    }

    /**
     * Compile Select Columns
     *
     * @return string
     */
    protected function compileSelectColumns()
    {
        $query = $this->query;
        $sql = '*';
        $selectTable = $this->selectTable;

        if($selectTable) {
            $sql = "`{$selectTable}`.*";
        }

        if( !empty($query['select']) && is_array($query['select']) ) {

            if($selectTable) {
                $query['select'] = array_map(function($value) use ($selectTable) {
                   return mb_strpos( (string) $value, '.' ) !== false ? $value : "{$selectTable}.{$value}";
                }, $query['select']);
            }

            $query['select'] = array_map(function($value) {
                return $this->tickSqlName($value);
            }, $query['select']);

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
    protected function compileFunction()
    {
        $query = $this->query;
        $sql = '';

        if( array_key_exists('function', $query) ) {
            $key = key($query['function']);
            $func = strtoupper( $key );
            $column = $this->tickSqlName($this->query['function'][$key]);
            $sql = $func.'('.$column.') ';
        }

        return $sql;
    }

    /**
     * Compile Group By
     *
     * @return string
     */
    protected function compileGrouping()
    {
        $query = $this->query;
        $sql = '';

        if( array_key_exists('group_by', $query) ) {

            $columns = (array) $query['group_by']['column'];
            $columns = array_map(function($column) {
                return $this->tickSqlName($column);
            }, $columns);

            $sql = ' GROUP BY '.implode(', ', $columns).' ';
        }

        return $sql;
    }

    /**
     * Compile Take
     *
     * @return string
     */
    protected function compileTake()
    {
        $wpdb = $this->wpdb;
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
    protected function compileOrder()
    {
        $query = $this->query;
        $sql = '';
        $order_basic_tapped = false;

        if( !empty($query['order_by']) || !empty($query['raw']['order_by']) ) {
            $sql .= " ORDER BY ";
        }

        if( !empty($query['order_by']) ) {
            $order_basic_tapped = true;

            $order = array_map(function($ordering) {
                $order_column = $this->tickSqlName($ordering['column']);
                $order_direction = $ordering['direction'] == 'ASC' ? 'ASC' : 'DESC';
                return "{$order_column} {$order_direction}";
            }, $query['order_by']);

            $sql .= implode(' , ', $order);
        }

        if( !empty($query['raw']['order_by']) ) {
            $sql .= $order_basic_tapped ? ' , ' : '';
            $sql .= implode(' , ', $query['raw']['order_by']);
        }

        return $sql;
    }

    /**
     * Compile Insert
     *
     * @return string|array
     */
    protected function compileInsert()
    {
        $query = $this->query;
        $sql_insert = [ 'sql_insert_columns' => '', 'sql_insert_values' => '' ];

        if( !empty($query['create']) && !empty($query['data']) ) {
            $inserts = $columns = [];

            if( !empty($query['data_values']) ) {
                foreach( $query['data'] as $column ) {
                    $columns[] = $this->tickSqlName($column);
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
                    $columns[] =  $this->tickSqlName($column);
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
    protected function compileUpdate()
    {
        $query = $this->query;
        $sql = '';

        if( !empty($query['update']) && !empty($query['data']) ) {
            $inserts = $columns = [];
            foreach( $query['data'] as $column => $data ) {
                $columns[] = $this->tickSqlName($column);
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
     * @param string|array|object $data
     * @param array $inserts
     *
     * @return $this
     */
    protected function setupInserts( $data, &$inserts )
    {
        $inserts[] = $this->prepareValue($data);
        return $this;
    }

    /**
     * Prepare Value
     *
     * @param string|array|object $value
     *
     * @return int|null|string
     */
    protected function prepareValue( $value )
    {
        $wpdb = $this->wpdb;
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
     * Compose Where SQL
     *
     * @param array|null $query
     * @return string
     */
    public function composeWhereSql($query = null)
    {
        $sql = '';

        if( !empty($query) ) {
            foreach( $query as $where ) {
                if( is_array($where) && ( isset($where[0]['column']) || isset($where[0][0]) ) )
                {
                    $sql .= ' ( ' . $this->composeWhereSql($where) . ' ) ';
                }
                elseif( is_array($where) )
                {
                    $where = [
                        'condition' => $where['condition'] ?? null,
                        'column' => $this->tickSqlName($where['column']),
                        'operator' => $where['operator'] ?? '=',
                        'value' => $where['value'] ?? null,
                        'raw' => $where['raw'] ?? false,
                    ];

                    if($where['value'] === null) {
                        if($where['operator'] == '=') {
                            $where['operator'] = 'IS';
                        }

                        if($where['operator'] == '!=') {
                            $where['operator'] = 'IS NOT';
                        }
                    }

                    if( is_array($where['value']) ) {
                        $where['value'] = array_map(\Closure::bind(function($value) {
                            return $this->prepareValue($value);
                        }, $this), $where['value']);

                        $where['value'] = '(' . implode(',', $where['value']) . ')';
                    } else {
                        $where['value'] = $where['raw'] ? $where['value'] : $this->prepareValue($where['value']);
                        // $where['value'] = $this->prepareValue($where['value']);
                    }

                    if(array_key_exists('condition', $where) && $where['condition'] === null) {
                        unset($where['condition']);
                    }

                    if(array_key_exists('raw', $where)) { unset($where['raw']); }

                    $sql .= ' ' . implode(' ', $where);
                }
                elseif (in_array($where, ['AND', 'OR', '&&', '||']))
                {
                    $sql .= ' ' . $where;
                }
            }
        }

        return $sql;
    }

    /**
     * Compile Where
     *
     * @return string
     */
    protected function compileWhere()
    {
        $sql = $this->composeWhereSql($this->query['where'] ?? null);

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
    protected function compileJoins()
    {
        $query = $this->query;
        $sql = '';

        if( !empty($query['joins']) ) {
            $joins = array_unique($query['joins'], SORT_REGULAR);
            foreach( $joins as $join ) {

                if( is_callable($join['table']) ) {
                    $joinQuery = new static();
                    $joinQuery->run = false;
                    $as = '';
                    $join['table'] = call_user_func_array( $join['table'], [$joinQuery, &$as]);
                    $join['table'] = trim('( ' . $joinQuery->compileFullQuery() . ' ) ' . $as);
                } elseif ($join['table'] instanceof Query) {
                    $as = $join['table']->getJoinAs();
                    $join['table'] = trim('( ' . $join['table'] . ' ) `' . $as . '`' );
                } else {
                    $join['table'] = $this->tickSqlName($join['table']);
                }

                $sql .= ' ' . implode(' ', $join);
            }
        }

        return $sql;
    }

    /**
     * Compile Table
     *
     * @return string
     */
    protected function compileTable()
    {
        $table = $this->query['table'];
        $as = $this->tableAs ? ' AS ' . $this->tickSqlName($this->tableAs) . ' ' : '';
        return $this->tickSqlName($table) . $as;
    }

    /**
     * Tick Names
     *
     * Escapes keyword names in columns and tables.
     *
     * @param string $column
     */
    protected function tickSqlName($column)
    {
        if($column instanceof SqlRaw) {
            return $column;
        }

        $c = preg_replace($this->columnPattern, '', $column);

        if(!Str::contains('`', $column)) {
            $c = '`' . str_replace('.', '`.`', $c) . '`';
            $c = str_replace('`*`', '*', $c);
            $c = preg_replace('/\s+(as)\s+/i', '` AS `', $c);
        }

        return $c;
    }

    /**
     * To String
     *
     * @return string|null
     */
    public function __toString()
    {
        return $this->compileFullQuery();
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }

}
