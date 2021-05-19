<?php
namespace TypeRocket\Elements\Traits;

trait Conditional
{
    public $conditions = [];
    protected $conditionMode = 'display';

    /**
     * Set Condition
     *
     * @param string $column
     * @param string|null $arg1
     * @param null|string $arg2
     * @param string $condition
     * @return $this
     */
    public function when($column, $arg1 = null, $arg2 = null, $condition = 'and')
    {
        $whereQuery = [];

        if( !empty($this->conditions) ) {
            $whereQuery['condition'] = strtolower($condition);
        } else {
            $whereQuery['condition'] = null;
        }

        $num = func_num_args();
        $whereQuery['field'] = $column;

        if( $num == 1 ) {
            $whereQuery['operator'] = '=';
            $whereQuery['value'] = true;
        }
        elseif( $num < 3 ) {
            $whereQuery['operator'] = '=';
            $whereQuery['value'] = $arg1;
        }
        else {
            $whereQuery['operator'] = strtolower($arg1);
            $whereQuery['value'] = $arg2;
        }

        $this->conditions[] = $whereQuery;

        return $this;
    }

    /**
     * Set Condition
     *
     * @param string $column
     * @param string|null $arg1
     * @param null|string $arg2
     * @return $this
     */
    public function orWhen($column, $arg1 = null, $arg2 = null)
    {
        $num = func_num_args();
        $field = $column;

        if( $num == 1 ) {
            $op = '=';
            $value = true;
        }
        elseif( $num < 3 ) {
            $op = '=';
            $value = $arg1;
        }
        else {
            $op = strtolower($arg1);
            $value = $arg2;
        }

        return $this->when($field, $op, $value, 'or');
    }

    /**
     * Condition mode
     *
     * @param string $mode options: display or include
     *
     * @return $this
     */
    public function conditionMode(string $mode)
    {
        $this->conditionMode = $mode;

        return $this;
    }

    /**
     * Set Conditional Attribute
     *
     * @param bool $array
     *
     * @return string|array
     */
    public function getConditionalAttribute($array = false)
    {
        if(!$this->conditions) {
            return !$array ? '' : [];
        }

        $conditions = esc_attr(json_encode($this->conditions));
        $mode = esc_attr($this->conditionMode);
        $conditionsName = 'data-tr-conditions';
        $modeName = 'data-tr-condition-mode';

        if($array) {
            return [$conditionsName => $conditions, $mode => $mode];
        }

        $attr = "{$conditionsName}=\"{$conditions}\"";

        if($this->conditionMode) {
            $attr .= " {$modeName}=\"{$mode}\"";
        }

        return $attr;
    }

}