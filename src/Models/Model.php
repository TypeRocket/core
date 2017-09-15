<?php
namespace TypeRocket\Models;

use TypeRocket\Database\Query;
use TypeRocket\Database\Results;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Http\Cookie;
use TypeRocket\Http\Fields;
use TypeRocket\Utility\Inflect;

class Model
{
    protected $fillable = [];
    protected $guard = ['id'];
    protected $format = [];
    protected $cast = [];
    protected $static = [];
    protected $builtin = [];
    protected $resource = null;
    protected $table = null;
    protected $errors = null;
    protected $query;
    protected $old = null;
    protected $onlyOld = false;
    protected $dataOverride = null;
    protected $properties = [];
    protected $propertiesUnaltered = [];
    protected $explicitProperties = [];
    protected $idColumn = 'id';
    protected $resultsClass = Results::class;
    protected $currentRelationshipModel = null;
    private $junction = null;

    /**
     * Construct Model based on resource
     */
    public function __construct()
    {
        $this->init();
        /** @var \wpdb $wpdb */
        global $wpdb;

        $this->table = $this->initTable( $wpdb );
        $type    = (new \ReflectionClass( $this ))->getShortName();

        if( ! $this->resource ) {
            $this->resource = strtolower( Inflect::pluralize($type) );
        }

        $this->query = $this->initQuery( new Query() );
        $this->query->resultsClass = $this->resultsClass;
        $table = $this->getTable();
        $this->query->table($table);

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
     * @param \wpdb $wpdb
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
     * @param array|\ArrayObject $static
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
     * @param array|\ArrayObject $fillable
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
     * @param array|\ArrayObject $format
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
     * @param array|\ArrayObject $guard
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
     * @return array|mixed|void
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
     * @return array|mixed|void
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
     * @return array|mixed|void
     */
    public function getFormatFields()
    {
        return $this->format;
    }

    /**
     * Get Property
     *
     * By key
     *
     * @param $key
     *
     * @return null
     */
    public function getProperty( $key )
    {
        $data = null;

        if (array_key_exists( $key, $this->properties )) {
            $data = $this->properties[$key];
        }

        return $data;
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
     * @param array|\ArrayObject $fields
     *
     * @return array
     */
    public function getFilteredMetaFields( $fields )
    {
        $builtin = array_flip( $this->builtin );

        return array_diff_key( $fields, $builtin );
    }

    /**
     * Get only the fields that are considered to
     * be builtin fields.
     *
     * @param array|\ArrayObject $fields
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
     * @param array|\ArrayObject $fields
     *
     * @return mixed|void
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

            if (  is_string($data) && tr_is_json($data)  ) {
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
     * @param array|\ArrayObject $fields
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
     * @param array|\ArrayObject $arr
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
     * @param array|\ArrayObject $ids
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
     * Find the first record and set properties
     *
     * @return array|bool|false|int|null|object
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
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return mixed
     */
    public function create( $fields = [] )
    {
        $fields = $this->provisionFields( $fields );

        return $this->query->create($fields);
    }

    /**
     * Update resource by TypeRocket fields
     *
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return mixed
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
     */
    public function findOrDie($id) {
        $results = $this->query->findOrDie($id);
        return $this->getQueryResult($results);
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
        $results = $this->query->findFirstWhereOrDie( $column, $arg1, $arg2, $condition);
        return $this->fetchResult( $results );
    }

    /**
     * Fetch Result
     *
     * @param $result
     *
     * @return mixed
     */
    protected function fetchResult( $result )
    {
        // Return Null
        if( ! $result ) {
            return null;
        }

        // Return Results
        if( $result instanceof Results ) {
            if( $result->class == null ) {
               $result->class = static::class;
            }
            $result->castResults();

            return $result;
        }

        // Cast Properties
        $this->castProperties( (array) $result );

        return $this;
    }

    /**
     * Delete
     *
     * @param array|\ArrayObject $ids
     *
     * @return array|false|int|null|object
     */
    public function delete( $ids = [] ) {
        return $this->query->delete($ids);
    }

    /**
     * Count results
     *
     * @return array|bool|false|int|null|object
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
     */
    public function getBaseFieldValue($field_name)
    {
        $data = (array) $this->properties;

        if( $this->getID() && empty( $data[$field_name] ) ) {
            $data = (array) $this->query->findById($this->getID())->get();
        }

        return $this->getValueOrNull( $data[$field_name] );
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
     * @param array|\TypeRocket\Http\Fields $fields
     *
     * @return mixed
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
        $this->propertiesUnaltered = (array) $properties;

        // Cast properties
        $cast = [];
        $this->properties = (array) $properties;
        foreach ($this->properties as $name => $property ) {
            $cast[$name] = $this->getCast($name);
        }
        $this->properties = $cast;

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

            if ( $handle == 'int' || $handle == 'integer' ) {
                // Integer
                $value = (int) $value;
            } if ( $handle == 'array' ) {
                // Priority Array
                if ( is_string($value) && tr_is_json($value)  ) {
                    $value = json_decode( $value, true );
                } elseif ( is_string($value) && is_serialized( $value ) ) {
                    $value = unserialize( $value );
                }
            } if ( $handle == 'object' ) {
                // Priority Object
                if ( is_string($value) && tr_is_json($value)  ) {
                    $value = json_decode( $value );
                } elseif ( is_string($value) && is_serialized( $value ) ) {
                    $value = unserialize( $value );
                }
            } elseif ( is_callable($handle) ) {
                // Callback
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
        if( ! $id ) {
           return null;
        }

        if( ! $id_foreign && $this->resource ) {
            $id_foreign = $this->resource . '_id';
        }

        /** @var Model $relationship */
        $relationship = new $modelClass;
        $relationship->setRelatedModel( $this );

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
        if( ! $this->getID() ) {
            return null;
        }

        /** @var Model $relationship */
        $relationship = new $modelClass;
        $relationship->setRelatedModel( $this );

        if( ! $id_local && $relationship->resource ) {
            $id_local = $relationship->resource . '_id';
        }

        $id = $this->getProperty( $id_local );
        return $relationship->where( $this->getIdColumn(), $id)->take(1);
    }

    /**
     * Has Many
     *
     * @param string $modelClass
     * @param null|string $id_foreign
     *
     * @return null|\TypeRocket\Models\Model
     */
    public function hasMany($modelClass, $id_foreign = null)
    {
        $id = $this->getID();
        if( ! $id ) {
            return null;
        }

        /** @var Model $relationship */
        $relationship = new $modelClass;
        $relationship->setRelatedModel( $this );

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
     * @return null|\TypeRocket\Models\Model
     */
    public function belongsToMany( $modelClass, $junction_table, $id_column = null, $id_foreign = null )
    {
        $id = $this->getID();
        if( ! $id ) {
            return null;
        }

        // Column ID
        if( ! $id_column && $this->resource ) {
            $id_column =  $this->resource . '_id';
        }

        /** @var Model $relationship */
        $relationship = new $modelClass;
        $relationship->setRelatedModel( $this );

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
        return  $relationship->select($rel_table.'.*')
                             ->where($where_column, $id)
                             ->findAll();
    }

    /**
     * Attach to Junction Table
     *
     * @param array $args
     *
     * @return array $query
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
        /** @var \wpdb $wpdb */
        global $wpdb;

        return  $this->table ? $this->table : $wpdb->prefix . $this->resource;
    }

    /**
     * Get Query
     *
     * @return \TypeRocket\Database\Query
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
        return isset($this->properties[$key]);
    }

    /**
     * Unset Property
     *
     * @param $key
     *
     * @return bool
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

}