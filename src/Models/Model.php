<?php
namespace TypeRocket\Models;

use ArrayObject;
use Exception;
use LogicException;
use ReflectionClass;
use TypeRocket\Database\EagerLoader;
use TypeRocket\Database\Query;
use TypeRocket\Database\Results;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Http\Cookie;
use TypeRocket\Http\Fields;
use TypeRocket\Models\Contract\Formable;
use TypeRocket\Utility\Inflect;
use TypeRocket\Utility\Str;
use wpdb;

class Model implements Formable
{
    protected $fillable = [];
    protected $closed = false;
    protected $guard = ['id'];
    protected $format = [];
    protected $cast = [];
    protected $static = [];
    protected $builtin = [];
    protected $metaless = [];
    protected $resource = null;
    protected $table = null;
    protected $errors = null;
    /** @var mixed|Query  */
    protected $query;
    protected $old = null;
    protected $onlyOld = false;
    protected $dataOverride = null;
    protected $properties = [];
    protected $propertiesUnaltered = null;
    protected $explicitProperties = [];
    protected $idColumn = 'id';
    protected $resultsClass = Results::class;
    protected $currentRelationshipModel = null;
    protected $relatedBy = null;
    protected $relationships = [];
    protected $junction = null;
    protected $with = null;

    /**
     * Construct Model based on resource
     */
    public function __construct()
    {
        $this->init();
        /** @var wpdb $wpdb */
        global $wpdb;

        $this->table = $this->initTable( $wpdb );
        try {
            $type    = (new ReflectionClass( $this ))->getShortName();
        } catch (\ReflectionException $e) {
            wp_die('Model failed');
        }

        if( ! $this->resource ) {
            $this->resource = strtolower( Inflect::pluralize($type) );
        }

        $this->query = $this->initQuery( new Query() );
        $this->query->resultsClass = $this->resultsClass;
        $table = $this->getTable();
        $this->query->table($table);
        $this->query->setIdColumn($this->idColumn);

        $suffix  = '';

        if ( ! empty( $type ) ) {
            $suffix = '_' . $type;
        }

        $this->fillable = apply_filters( 'tr_model_fillable' . $suffix, $this->fillable, $this );
        $this->guard    = apply_filters( 'tr_model_guard' . $suffix, $this->guard, $this );
        $this->format   = apply_filters( 'tr_model_format' . $suffix, $this->format, $this );
        do_action( 'tr_model', $this );
    }

    /**
     * Init Query
     *
     * @param Query $query
     *
     * @return mixed
     */
    protected function initQuery( Query $query) {
        return $query;
    }

    /**
     * Return table name in constructor
     *
     * @param wpdb $wpdb
     *
     * @return null
     */
    protected function initTable($wpdb)
    {
        return $this->table;
    }

    /**
     * Basic initialization
     *
     * Used on construction in concrete classes
     *
     * @return $this
     */
    protected function init()
    {
        return $this;
    }

    /**
     * Set Static Fields
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array|ArrayObject $static
     *
     * @return $this
     */
    public function setStaticFields( $static )
    {
        $this->static = $static;

        return $this;
    }

    /**
     * Set Fillable
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array|ArrayObject $fillable
     *
     * @return $this
     */
    public function setFillableFields( $fillable )
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Set Format
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array|ArrayObject $format
     *
     * @return $this
     */
    public function setFormatFields( $format )
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set Guard
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array|ArrayObject $guard
     *
     * @return $this
     */
    public function setGuardFields( $guard )
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * Append Fillable
     *
     * Add a fillable field.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function appendFillableField( $field_name )
    {
        if ( ! in_array( $field_name, $this->fillable ) && ! in_array( $field_name, $this->guard ) ) {
            $this->fillable[] = $field_name;
        }

        return $this;
    }

    /**
     * Append Guard
     *
     * Add a field to guard if not set to fillable.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function appendGuardField( $field_name )
    {
        if ( ! in_array( $field_name, $this->fillable ) && ! in_array( $field_name, $this->guard ) ) {
            $this->guard[] = $field_name;
        }

        return $this;
    }

    /**
     * Append Format
     *
     * Add a field to format.
     *
     * @param string $field_name dot notation with support for wild card *
     * @param callable $callback function or method to call on $field_name
     *
     * @return $this
     */
    public function appendFormatField( $field_name, $callback )
    {
        if ( ! array_key_exists( $field_name, $this->format )) {
            $this->format[$field_name] = $callback;
        }

        return $this;
    }

    /**
     * Remove Guard
     *
     * Remove field from guard.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function removeGuardField( $field_name )
    {
        if ( in_array( $field_name, $this->guard ) ) {
            unset($this->guard[array_search($field_name, $this->guard)]);
        }

        return $this;
    }

    /**
     * Remove Fillable
     *
     * Remove field from fillable.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function removeFillableField( $field_name )
    {
        if ( in_array( $field_name, $this->fillable ) ) {
            unset($this->fillable[array_search($field_name, $this->fillable)]);
        }

        return $this;
    }

    /**
     * Remove Format
     *
     * Remove field from format.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function removeFormatField( $field_name )
    {
        if ( array_key_exists( $field_name, $this->format ) ) {
            unset($this->format[$field_name]);
        }

        return $this;
    }

    /**
     * Unlock Field
     *
     * Unlock field by adding it to fillable and removing it from guard.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function unlockField( $field_name )
    {
        if ( in_array( $field_name, $this->guard ) ) {
            unset($this->guard[array_search($field_name, $this->guard)]);
        }

        if ( !empty($this->fillable) && ! in_array( $field_name, $this->fillable ) && ! in_array( $field_name, $this->guard ) ) {
            $this->fillable[] = $field_name;
        }

        return $this;
    }

    /**
     * Append Error
     *
     * Get any errors that have been logged
     *
     * @param $value
     *
     * @return null
     */
    public function appendError( $value )
    {
        return $this->errors[] = $value;
    }

    /**
     * Get Errors
     *
     * Get any errors that have been logged
     *
     * @return null
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get Fillable Fields
     *
     * Get all the fields that can be filled
     *
     * @return array|mixed
     */
    public function getFillableFields()
    {
        return $this->fillable;
    }

    /**
     * Get Guard Fields
     *
     * Get all the fields that have been write protected
     *
     * @return array|mixed
     */
    public function getGuardFields()
    {
        return $this->guard;
    }

    /**
     * Get Builtin Fields
     *
     * Get all the fields that are not saved as meta fields
     *
     * @return array
     */
    public function getBuiltinFields()
    {
        return $this->builtin;
    }

    /**
     * Get Format Fields
     *
     * Get all the fields that have been set for formatting
     *
     * @return array|mixed
     */
    public function getFormatFields()
    {
        return $this->format;
    }

    /**
     * Set Property
     *
     * By key
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setProperty( $key, $value = null )
    {
        $this->properties[$key] = $value;
        $this->explicitProperties[$key] = $value;

        return $this;
    }

    /**
     * Set Properties
     *
     * @param array $properties
     *
     * @return array
     */
    public function setProperties( array $properties )
    {
        return $this->properties = $properties;
    }

    /**
    * Get an attribute from the model.
    *
    * @param  string  $key
    * @return mixed
    */
    public function getProperty($key)
    {
        if (array_key_exists($key, $this->properties) || $this->hasGetMutator($key)) {
            return $this->getPropertyValue($key);
        }

        return $this->getRelationValue($key);
    }

    /**
     * Get Properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get Properties Unaltered
     *
     * @return array
     */
    public function getPropertiesUnaltered()
    {
        return $this->propertiesUnaltered;
    }

    /**
     * Get only the fields that are considered to
     * be meta fields.
     *
     * @param array|ArrayObject $fields
     *
     * @return array
     */
    public function getFilteredMetaFields( $fields )
    {
        $diff = array_flip( array_unique( array_merge($this->builtin, $this->metaless) ) );

        return array_diff_key( $fields, $diff );
    }

    /**
     * Get only the fields that are considered to
     * be builtin fields.
     *
     * @param array|ArrayObject $fields
     *
     * @return array
     */
    public function getFilteredBuiltinFields( $fields )
    {
        $builtin = array_flip( $this->builtin );

        return array_intersect_key( $fields, $builtin );
    }

    /**
     * Provision Fields
     *
     * Get fields that have been checked against fillable and guard.
     * Fillable fields override guarded fields. Format fields to
     * specification. Override given values with static values.
     *
     * @param array|ArrayObject $fields
     *
     * @return mixed
     */
    public function provisionFields( $fields )
    {
        // Unlock fillable fields
        if( $fields instanceof Fields ) {
            foreach ( $fields->getFillable() as $field_name ) {
                $this->unlockField($field_name);
            }
            $fields = $fields->getArrayCopy();
        }

        // Fillable
        $fillable = [];
        if ( ! empty( $this->fillable ) && is_array( $this->fillable )) {
            foreach ($this->fillable as $field_name) {
                if (isset( $fields[$field_name] )) {
                    $fillable[$field_name] = $fields[$field_name];
                }
            }
            $fields = $fillable;
        }

        // Closed
        if($this->closed && empty($fillable)) {
            $fields = [];
        }

        // Guard
        if ( ! empty( $this->guard ) && is_array( $this->guard )) {
            foreach ($this->guard as $field_name) {
                if (isset( $fields[$field_name] ) && ! in_array( $field_name, $this->fillable )) {
                    unset( $fields[$field_name] );
                }
            }
        }

        // Override with static values
        $fields = array_merge( $this->explicitProperties, $fields, $this->static);

        // Format
        if ( ! empty( $this->format ) && is_array( $this->format )) {
            $fields = $this->formatFields($fields);
        }

        return apply_filters( 'tr_model_provision_fields', $fields, $this );
    }

    /**
     * Get value from database from typeRocket bracket syntax
     *
     * @param $field
     *
     * @return array|mixed|null|string
     * @throws Exception
     */
    public function getFieldValue( $field )
    {
        if ($field instanceof Field) {
            $field = $field->getDots();
        }

        if ($this->getID() == null && ! $this->old && empty($this->dataOverride) ) {
            return null;
        }

        $keys = $this->getDotKeys( $field );

        if( $this->old ) {
            if( ! empty($this->old[$keys[0]]) ) {
                $data = wp_unslash( $this->old[$keys[0]] );
            } else {
                $data = null;
            }
        } elseif( !empty($this->dataOverride[$keys[0]]) ) {
            $data = $this->dataOverride[$keys[0]];
        } elseif( !$this->onlyOld ) {
            $data = $this->getBaseFieldValue( $keys[0] );
        } else {
            return null;
        }

        return $this->parseValueData( $data, $keys );
    }

    /**
     * Get old stored fields
     *
     * @param bool $load_only_old
     */
    public function oldStore( $load_only_old = false) {
        if( !empty($_COOKIE['tr_old_fields']) ) {
            $cookie = new Cookie();
            $this->old = $cookie->getTransient('tr_old_fields');
        }

        $this->onlyOld = $load_only_old;
    }

    /**
     * Override Data
     *
     * Use data override over model data. Used mainly but Form class.
     *
     * @param array $data
     *
     * @return $this
     */
    public function dataOverride(array $data)
    {
        $this->dataOverride = $data;
        return $this;
    }

    /**
     * Parse data by walking through keys
     *
     * @param $data
     * @param $keys
     *
     * @return array|mixed|null|string
     */
    private function parseValueData( $data, $keys )
    {
        $mainKey = $keys[0];
        if (isset( $mainKey ) && ! empty( $data )) {

            if ( $data instanceof Formable) {
                $data = $data->getFormFields();
            }

            if ( is_string($data) && tr_is_json($data)  ) {
                $data = json_decode( $data, true );
            }

            if ( is_string($data) && is_serialized( $data ) ) {
                $data = unserialize( $data );
            }

            // unset first key since $data is already set to it
            unset( $keys[0] );

            if ( ! empty( $keys ) && is_array( $keys )) {
                foreach ($keys as $name) {
                    $data = ( isset( $data[$name] ) && $data[$name] !== '' ) ? $data[$name] : null;
                }
            }

        }

        return $data;
    }

    /**
     * Format fields
     *
     * @param array|ArrayObject $fields
     *
     * @return array
     */
    private function formatFields( $fields) {

        foreach ($this->format as $path => $fn) {
            $this->ArrayDots($fields, $path, $fn);
        }

        return $fields;
    }

    /**
     * Used to format fields
     *
     * @param array|ArrayObject $arr
     * @param $path
     * @param $fn
     *
     * @return array|null
     */
    private function ArrayDots( &$arr, $path, $fn) {
        $loc = &$arr;
        $dots = explode('.', $path);
        foreach($dots as $step)
        {
            array_shift($dots);
            if($step === '*' && is_array($loc)) {
                $new_loc = &$loc;
                $indies = array_keys($new_loc);
                foreach($indies as $index) {
                    if(isset($new_loc[$index])) {
                        $this->ArrayDots($new_loc[$index], implode('.', $dots), $fn);
                    }
                }
            } elseif( isset($loc[$step] ) ) {
                $loc = &$loc[$step];
            } else {
                return null;
            }

        }

        if(!isset($indies)) {
            if( is_callable($fn) ) {
                $loc = call_user_func($fn, $loc);
            } elseif( is_callable('\\TypeRocket\\Sanitize::' . $fn ) ) {
                $fn = '\\TypeRocket\\Sanitize::' . $fn;
                $loc = call_user_func($fn, $loc);
            }
        }

        return $loc;
    }

    /**
     * Get column of router injection
     *
     * @return string
     */
    public function getRouterInjectionColumn()
    {
        return strtolower($this->idColumn);
    }

    /**
     * Get keys from TypeRocket brackets
     *
     * @param $str
     *
     * @return mixed
     */
    private function getDotKeys( $str )
    {
        $matches = explode('.', $str);

        return $matches;
    }

    /**
     * Get the value of a field if it is not an empty string or null.
     * If the field is null, undefined or and empty string it will
     * return null.
     *
     * @param $value
     *
     * @return null
     */
    public function getValueOrNull( $value )
    {
        return ( isset( $value ) && $value !== '' ) ? $value : null;
    }

    /**
     * Find all
     *
     * @param array|ArrayObject $ids
     *
     * @return Model $this
     */
    public function findAll( $ids = [] )
    {
        $this->query->findAll($ids);

        return $this;
    }

    /**
     * Get results from find methods
     *
     * @return array|null|object
     * @throws Exception
     */
    public function get() {
        $results = $this->query->get();
        return $this->getQueryResult($results);
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
        $this->query->where($column, $arg1, $arg2, $condition);

        return $this;
    }

    /**
     * Or Where
     *
     * @param string $column
     * @param string $arg1
     * @param null|string $arg2
     *
     * @return Model $this
     */
    public function orWhere($column, $arg1, $arg2 = null)
    {
        $this->query->where($column, $arg1, $arg2, 'OR');

        return $this;
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
        $this->query->appendRawWhere($condition, $sql);

        return $this;
    }

    /**
     * Remove Where
     *
     * Remove raw and standard where clauses.
     *
     * @return $this
     */
    public function removeWhere() {
        $this->query->removeWhere();

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
        $this->query->removeTake();

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
    public function orderBy($column = null, $direction = 'ASC')
    {
        if( ! $column ) {
            $column = $this->idColumn;
        }

        $this->query->orderBy($column, $direction);

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
        $this->query->take($limit, $offset, $returnOne);

        return $this;
    }

    /**
     * Always Wrap In Results Class
     *
     * @return $this
     */
    public function useResultsClass()
    {
        $this->query->useResultsClass();
        return $this;
    }

    /**
     * Find the first record and set properties
     *
     * @return array|bool|false|int|null|object|Model
     * @throws Exception
     */
    public function first() {
        $results = $this->query->first();
        return $this->getQueryResult($results);
    }

    /**
     * Create resource by TypeRocket fields
     *
     * When a resource is created the Model ID should be set to the
     * resource's ID.
     *
     * @param array|Fields $fields
     *
     * @return mixed
     * @throws Exception
     */
    public function create( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );

        return $this->query->create($fields);
    }

    /**
     * Update resource by TypeRocket fields
     *
     * @param array|Fields $fields
     *
     * @return mixed
     * @throws Exception
     */
    public function update( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );

        return $this->query->update($fields);
    }

    /**
     * Find resource by ID
     *
     * @param $id
     *
     * @return mixed
     * @throws Exception
     */
    public function findById($id)
    {
        $results = $this->query->findById($id)->get();

        return $this->getQueryResult($results);
    }

    /**
     * Find by ID or die
     *
     * @param $id
     *
     * @return object
     * @throws Exception
     */
    public function findOrDie($id) {
        $results = $this->query->findOrDie($id);
        return $this->getQueryResult($results);
    }

    /**
     * Find Or Create
     *
     * @param $id
     * @param array $fields
     * @return mixed|Model
     * @throws Exception
     */
    public function findOrCreate($id, $fields = [])
    {
        if($item = $this->findById($id)) {
            return $item;
        };

        return (new static)->create($fields);
    }

    /**
     * Find Or New
     *
     * @param $id
     * @return mixed|Model
     * @throws Exception
     */
    public function findOrNew($id)
    {
        if($item = $this->findById($id)) {
            return $item;
        };

        return new static;
    }

    /**
     * Where
     *
     * @param string $column
     * @param string $arg1
     * @param null|string $arg2
     * @param string $condition
     *
     * @return Model
     * @throws Exception
     */
    public function findFirstWhereOrNew($column, $arg1, $arg2 = null, $condition = 'AND')
    {
        if($item = $this->where($column, $arg1, $arg2, $condition)->first()) {
            return $item;
        };

        return new static;
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
     * @throws Exception
     */
    public function findFirstWhereOrDie($column, $arg1, $arg2 = null, $condition = 'AND') {
        $results = $this->query->findFirstWhereOrDie( $column, $arg1, $arg2, $condition);
        return $this->fetchResult( $results );
    }

    /**
     * Fetch Result
     *
     * @param $result
     *
     * @return mixed
     * @throws Exception
     */
    protected function fetchResult( $result )
    {
        // Return Null
        if( ! $result ) {
            return null;
        }

        // Cast Results
        if( $result instanceof Results ) {
            if( $result->class == null ) {
               $result->class = static::class;
            }
            $result->castResults();
        } else {
            $result = $this->castProperties( (array) $result );
        }

        // Eager Loader
        if($this->with) {
            list($name, $with) = array_pad(explode('.', $this->with, 2), 2, null);
            $loader = new EagerLoader();
            $relation = $this->{$name}();
            $result = $loader->load([
                'name' => $name,
                'relation' => $relation,
            ], $result, $with);
        }

        return $result;
    }

    /**
     * Delete
     *
     * @param array|ArrayObject $ids
     *
     * @return array|false|int|null|object
     * @throws Exception
     */
    public function delete( $ids = [] ) {
        return $this->query->delete($ids);
    }

    /**
     * Count results
     *
     * @return array|bool|false|int|null|object
     * @throws Exception
     */
    public function count()
    {
        return $this->query->count();
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
        if( ! is_array($args) ) {
            $args = func_get_args();
        }

        $this->query->select($args);

        return $this;
    }

    /**
     * Reselect
     *
     * @param $args
     * @return $this
     */
    public function reselect($args)
    {
        if( ! is_array($args) ) {
            $args = func_get_args();
        }

        $this->query->reselect($args);

        return $this;
    }

    /**
     * Get base field value
     *
     * Some fields need to be saved as serialized arrays. Getting
     * the field by the base value is used by Fields to populate
     * their values.
     *
     * This method must be implemented to return the base value
     * of a field if it is saved as a bracket group.
     *
     * @param $field_name
     *
     * @return null
     * @throws Exception
     */
    public function getBaseFieldValue($field_name)
    {
        $data = (array) $this->properties;

        if( $this->getID() && empty( $data[$field_name] ) ) {
            $data = (array) $this->query->findById($this->getID())->get();
        }

        return $this->getValueOrNull( $data[$field_name] ?? null );
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
     * Get the ID from properties
     *
     * @return mixed
     */
    public function getID()
    {
        return !empty($this->properties[$this->idColumn]) ? $this->properties[$this->idColumn] : null;
    }

    /**
     * Get ID Column
     *
     * @return string
     */
    public function getIdColumn()
    {
        return $this->idColumn;
    }

    /**
     * Save changes directly
     *
     * @param array|Fields $fields
     *
     * @return mixed
     * @throws Exception
     */
    public function save( $fields = [] ) {
        if( isset( $this->properties[$this->idColumn] ) && $this->findById($this->properties[$this->idColumn]) ) {
            return $this->update($fields);
        }
        return $this->create($fields);
    }

    /**
     * Cast Properties
     *
     * @param $properties
     *
     * @return $this
     */
    public function castProperties( $properties )
    {
        // Create Unaltered copy
        $this->propertiesUnaltered = $this->propertiesUnaltered ?: (array) $properties;

        // Cast properties
        $cast = [];
        $this->properties = (array) $properties;
        foreach ($this->properties as $name => $property ) {
            $cast[$name] = $this->getCast($name);
        }
        $this->properties = apply_filters( 'tr_model_cast_fields', $cast, $this );

        return $this;
    }

    /**
     * Get Cast
     *
     * Get the cast value of a property
     *
     * @param $property
     *
     * @return mixed
     */
    public function getCast( $property )
    {
        $value = !empty($this->properties[$property]) ? $this->properties[$property] : null;

        if ( ! empty( $this->cast[$property] ) ) {
            $handle = $this->cast[$property];

            // Integer
            if ( $handle == 'int' || $handle == 'integer' ) {

                $value = (int) $value;
            }

            // Priority Array
            if ( $handle == 'array' ) {
                if ( is_string($value) && tr_is_json($value)  ) {
                    $value = json_decode( $value, true );
                } elseif ( is_string($value) && is_serialized( $value ) ) {
                    $value = unserialize( $value );
                }
            }

            // Priority Object
            if ( $handle == 'object' ) {
                if ( is_string($value) && tr_is_json($value)  ) {
                    $value = json_decode( $value );
                } elseif ( is_string($value) && is_serialized( $value ) ) {
                    $value = unserialize( $value );
                }
            }

            // Callback
            if ( is_callable($handle) ) {
                $value = call_user_func($this->cast[$property], $value );
            }
        }

        return $this->properties[$property] = $value;
    }

    /**
     * Has One
     *
     * @param string $modelClass
     * @param null|string $id_foreign
     *
     * @return mixed|null
     */
    public function hasOne($modelClass, $id_foreign = null)
    {
        $id = $this->getID();

        if( ! $id_foreign && $this->resource ) {
            $id_foreign = $this->resource . '_id';
        }

        /** @var Model $relationship */
        $relationship = new $modelClass;
        $relationship->setRelatedModel( $this );
        $relationship->relatedBy = [
            'type' => 'hasOne',
            'query' => [
                'caller' => $this,
                'class' => $modelClass,
                'id_foreign' => $id_foreign
            ]
        ];

        return $relationship->findAll()->where( $id_foreign, $id)->take(1);
    }

    /**
     * Belongs To
     *
     * @param string $modelClass
     * @param null|string $id_local
     *
     * @return $this|null
     */
    public function belongsTo($modelClass, $id_local = null)
    {
        /** @var Model $relationship */
        $relationship = new $modelClass;
        $relationship->setRelatedModel( $this );
        $relationship->relatedBy = [
            'type' => 'belongsTo',
            'query' => [
                'caller' => $this,
                'class' => $modelClass,
                'local_id' => $id_local
            ]
        ];

        if( ! $id_local && $relationship->resource ) {
            $id_local = $relationship->resource . '_id';
        }

        $id = $this->getProperty( $id_local );
        return $relationship->where( $relationship->getIdColumn(), $id)->take(1);
    }

    /**
     * Has Many
     *
     * @param string $modelClass
     * @param null|string $id_foreign
     *
     * @return null|Model
     */
    public function hasMany($modelClass, $id_foreign = null)
    {
        $id = $this->getID();

        /** @var Model $relationship */
        $relationship = new $modelClass;
        $relationship->setRelatedModel( $this );
        $relationship->relatedBy = [
            'type' => 'hasMany',
            'query' => [
                'caller' => $this,
                'class' => $modelClass,
                'id_foreign' => $id_foreign
            ]
        ];

        if( ! $id_foreign && $this->resource ) {
            $id_foreign = $this->resource . '_id';
        }

        return $relationship->findAll()->where( $id_foreign, $id );
    }

    /**
     * Belongs To Many
     *
     * This is for Many to Many relationships.
     *
     * @param $modelClass
     * @param string $junction_table
     * @param null|string $id_column
     * @param null|string $id_foreign
     *
     * @return null|Model
     */
    public function belongsToMany( $modelClass, $junction_table, $id_column = null, $id_foreign = null )
    {
        $id = $this->getID();

        // Column ID
        if( ! $id_column && $this->resource ) {
            $id_column =  $this->resource . '_id';
        }

        /** @var Model $relationship */
        $relationship = new $modelClass;

        // Foreign ID
        if( ! $id_foreign && $relationship->resource ) {
            $id_foreign =  $relationship->resource . '_id';
        }
        $rel_table = $relationship->getTable();

        // Set Junction: `attach` and `detach` will use inverse columns
        $relationship->setJunction( [
            'table' => $junction_table,
            'columns' => [$id_foreign, $id_column],
            'id_foreign' => $id
        ] );

        // Join
        $join_table = $junction_table;
        $rel_join = $rel_table.'.'.$relationship->getIdColumn();
        $foreign_join = $join_table.'.'.$id_foreign;
        $where_column = $join_table.'.'.$id_column;
        $relationship->getQuery()->distinct()->join($join_table, $foreign_join, $rel_join);

        $relationship->setRelatedModel( $this );
        $relationship->relatedBy = [
            'type' => 'belongsToMany',
            'query' => [
                'caller' => $this,
                'class' => $modelClass,
                'junction_table' => $junction_table,
                'id_column' => $id_column,
                'id_foreign' => $id_foreign,
                'where_column' => $where_column,
            ]
        ];

        return  $relationship->reselect($rel_table.'.*')
                             ->where($where_column, $id)
                             ->findAll();
    }

    /**
     * Attach to Junction Table
     *
     * @param array $args
     *
     * @return array $query
     * @throws Exception
     */
    public function attach( array $args )
    {
        $rows = [];
        $query = new Query();
        $junction = $this->getJunction();
        $id_foreign = $junction['id_foreign'];

        foreach ( $args as $id ) {
            $rows[] = [ $id, $id_foreign ];
        }

        $result = $query->table( $junction['table'] )->create($junction['columns'], $rows);

        return [$result, $query];
    }

    /**
     * Detach from Junction Table
     *
     * @param array $args
     *
     * @return array
     * @throws Exception
     */
    public function detach( array $args = [] )
    {
        $query = new Query();
        $junction = $this->getJunction();
        $id_foreign = $junction['id_foreign'];

        list( $local_column, $foreign_column ) = $junction['columns'];

        $query->where($foreign_column, '=', $id_foreign);

        if( !empty($args) ) {
            $query->where($local_column, 'IN', $args);
        }

        $result = $query->table( $junction['table'] )->delete();

        return [$result, $query];
    }

    /**
     * Sync Junction Table
     *
     * First detach and then attach new records.
     *
     * @param array $args
     *
     * @return array $results
     * @throws Exception
     */
    public function sync( array $args = [] )
    {
        $results = [];
        $results['detach'] = $this->detach();
        $results['attach'] = $this->attach( $args );

        return $results;
    }

    /**
     * Get Table
     *
     * @return null
     */
    public function getTable()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        return  $this->table ? $this->table : $wpdb->prefix . $this->resource;
    }

    /**
     * Get Query
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get Related Model
     *
     * @return null|Model
     */
    public function getRelatedModel()
    {
        return $this->currentRelationshipModel;
    }

    /**
     * Set Related Model
     *
     * @param Model $model
     *
     * @return Model $this
     */
    public function setRelatedModel( Model $model )
    {
        $this->currentRelationshipModel = clone $model;

        return $this;
    }

    /**
     * Get Relationship
     *
     * @return array|null
     */
    public function getRelatedBy()
    {
        return $this->relatedBy;
    }

    /**
     * Get Junction
     *
     * @return null|string
     */
    public function getJunction()
    {
        return $this->junction;
    }

    /**
     * Set Junction
     *
     * @param array $junction
     *
     * @return $this
     */
    public function setJunction( array $junction )
    {
        $this->junction = $junction;

        return $this;
    }

    /**
     * Get Last SQL Query
     *
     * @return null|string
     * @throws Exception
     */
    public function getSuspectSQL()
    {
        if( ! $this->query->lastCompiledSQL ) {
            $this->query->compileFullQuery();
        }

        return $this->query->lastCompiledSQL;
    }

    /**
     * Get the Query result
     *
     * @param $results
     *
     * @return Model|Results|null|object
     *
     * @throws Exception
     */
    protected function getQueryResult( $results ) {
        return $this->fetchResult( $results );
    }

    /**
     * Get attribute as property
     *
     * @param $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getProperty($key);
    }

    /**
     * Property Exists
     *
     * @param $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getProperty($key));
    }

    /**
     * Unset Property
     *
     * @param $key
     */
    public function __unset($key)
    {
        if(isset($this->properties[$key])) {
            unset($this->properties[$key]);
        }
    }

    /**
     * Set attribute as property
     *
     * @param $key
     * @param null $value
     */
    public function __set($key, $value = null)
    {
        $this->setProperty($key, $value);
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * If the attribute has a get mutator, we will call that then return what
     * it returns as the value, which is useful for transforming values on
     * retrieval from the model to a form that is more useful for usage.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getPropertyValue($key)
    {
      $value = $this->getPropertyFromArray($key);

      return $value;
    }

    /**
     * Get a relationship.
     *
     * If the "attribute" exists as a method on the model, we will just assume
     * it is a relationship and will load and return results from the query
     * and hydrate the relationship's value on the "relationships" array.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        if(array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }

        return null;
    }

    /**
     * Get an attribute from the $this->properties array.
     *
     * If the attribute has a get mutator, we will call that then return what
     * it returns as the value, which is useful for transforming values on
     * retrieval from the model to a form that is more useful for usage.
     *
     * @param string $key
     * @return mixed
     */
    protected function getPropertyFromArray($key)
    {
        $value = null;

        if (array_key_exists($key, $this->properties)) {
            $value = $this->properties[$key];
        }

        if ($this->hasGetMutator($key)) {
            return $this->mutateProperty($key, $value);
        }

        return $value;
    }

    /**
     * Get a relationship value from a method.
     *
     * @param  string  $method
     * @return mixed
     *
     * @throws LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
      return $this->$method() ? $this->$method()->get() : null;
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
      return method_exists($this, 'get'.Str::camelize($key).'Property');
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateProperty($key, $value)
    {
      return $this->{'get'.Str::camelize($key).'Property'}($value);
    }

    /**
     * Get Form Fields
     *
     * @return array
     */
    public function getFormFields()
    {
        $this->castProperties($this->properties);

        return $this->properties;
    }

    /**
     * Eager Load With
     *
     * @param $name
     * @return $this
     */
    public function with($name)
    {
        $this->with = $name;

        return $this;
    }

    /**
     * Set Relationship
     *
     * @param $name
     * @param $value
     */
    public function setRelationship($name, $value)
    {
        $this->relationships[$name] = $value;
    }

    /**
     * Get Relationship
     *
     * @param string $name
     * @return mixed|null
     */
    public function getRelationship($name) {

        $names = explode('.', $name);
        $rel = $this;
        foreach ($names as $name) {
            $rel = $rel->relationships[$name];
        }
        return $rel;
    }

}
