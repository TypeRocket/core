<?php
namespace TypeRocket\Models\Traits;

use TypeRocket\Models\Model;

trait MetaData
{
    protected static $metaQueries = 0;

    /**
     * Get Meta Model Class
     *
     * @return string
     */
    protected function getMetaModelClass()
    {
        return '';
    }

    /**
     * Get ID Columns
     *
     * @return array
     */
    protected function getMetaIdColumns()
    {
        return [
            'local' => null,
            'foreign' => null,
        ];
    }

    /**
     * Posts Meta Fields
     *
     * @param bool $withoutPrivate
     *
     * @return null|\TypeRocket\Models\Model
     */
    public function meta( $withoutPrivate = false )
    {
        $meta = $this->hasMany( $this->getMetaModelClass(), $this->getMetaIdColumns()['foreign'], function($rel) use ($withoutPrivate) {
            if( $withoutPrivate ) {
                $rel->notPrivate();
            }
        } );

        return $meta;
    }

    /**
     * Posts Meta Fields Without Private
     *
     * @return null|\TypeRocket\Models\Model
     */
    public function metaWithoutPrivate()
    {
        return $this->meta( true );
    }

    /**
     * Get Number of Meta Queries
     *
     * @return int
     */
    public function resetNumMetaQueries()
    {
        return static::$metaQueries = 0;
    }

    /**
     * Where Meta
     *
     * @param string|array $key
     * @param string $operator
     * @param string|int|null|bool $value
     * @param string $condition
     *
     * @return Model|MetaData
     */
    public function whereMeta($key, $operator = '=', $value = null, $condition = 'AND')
    {
        $counter = &static::$metaQueries;

        $num = func_num_args();

        if($num == 2) {
            $value = $operator;
            $operator = '=';
        }

        $table = $this->getTable();
        $idColumns = $this->getMetaIdColumns();
        $modelMetaClass = $this->getMetaModelClass();
        $meta_table = (new $modelMetaClass)->getTable();

        if(is_array($key)) {
            $operator = strtoupper($operator);
            $condition = in_array($operator, ['AND', 'OR', '||', '&&']) ? $operator : 'AND';
            $where = array_map(function($value) use ($meta_table, &$counter, $table, $idColumns) {

                if(is_string($value)) {
                    return strtoupper($value);
                }

                $table_alias = 'tr_mt' . $counter++;

                $this->join(
                    $meta_table . ' AS ' . $table_alias,
                    "`{$table}`.`{$idColumns['local']}`",
                    "`{$table_alias}`.`{$idColumns['foreign']}`"
                );

                $key = $value['column'];
                $operator = $value['operator'];
                $value = $value['value'];

                return [
                    [
                        'column' => "`{$table_alias}`.`meta_key`",
                        'operator' => '=',
                        'value' => $key,
                    ],
                    'AND',
                    [
                        'column' => "`{$table_alias}`.`meta_value`",
                        'operator' => $operator,
                        'value' => $value,
                    ]
                ];
            }, $key);
        } else {
            $table_alias = 'tr_mt' . $counter++;

            $this->join(
                $meta_table . ' AS ' . $table_alias,
                "`{$table}`.`{$idColumns['local']}`",
                "`{$table_alias}`.`{$idColumns['foreign']}`"
            );

            $where = [
                [
                    'column' => "`{$table_alias}`.`meta_key`",
                    'operator' => '=',
                    'value' => $key,
                ],
                'AND',
                [
                    'column' => "`{$table_alias}`.`meta_value`",
                    'operator' => $operator,
                    'value' => $value,
                ]
            ];
        }

        return $this->where($where, $condition);
    }
}