<?php
namespace TypeRocket\Models\Traits;

use TypeRocket\Models\Model;

trait MetaData
{

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
     * Where Meta
     *
     * @param string|array $key
     * @param string $operator
     * @param string|int|null|bool $value
     * @param string $condition
     *
     * @return Model
     */
    public function whereMeta($key, $operator = '!=', $value = null, $condition = 'AND')
    {
        $table = $this->getTable();
        $idColumns = $this->getMetaIdColumns();
        $modelMetaClass = $this->getMetaModelClass();
        $meta_table = (new $modelMetaClass)->getTable();

        if(is_array($key)) {
            $operator = strtoupper($operator);
            $condition = in_array($operator, ['AND', 'OR', '||', '&&']) ? $operator : 'AND';
            $where = array_map(function($value) use ($meta_table) {

                if(is_string($value)) {
                    return strtoupper($value);
                }

                $key = $value['column'];
                $operator = $value['operator'];
                $value = $value['value'];

                return [
                    [
                        'column' => "`{$meta_table}`.`meta_key`",
                        'operator' => '=',
                        'value' => $key,
                    ],
                    'AND',
                    [
                        'column' => "`{$meta_table}`.`meta_value`",
                        'operator' => $operator,
                        'value' => $value,
                    ]
                ];
            }, $key);
        } else {
            $where = [
                [
                    'column' => "`{$meta_table}`.`meta_key`",
                    'operator' => '=',
                    'value' => $key,
                ],
                'AND',
                [
                    'column' => "`{$meta_table}`.`meta_value`",
                    'operator' => $operator,
                    'value' => $value,
                ]
            ];
        }

        $this->join(
            $meta_table,
            "`{$table}`.`{$idColumns['local']}`",
            "`{$meta_table}`.`{$idColumns['foreign']}`"
        );

        return $this->where($where, $condition);
    }
}