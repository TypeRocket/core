<?php
namespace TypeRocket\Database;

use TypeRocket\Models\Model;

class EagerLoader
{

    protected $load = [];
    protected $with = null;

    /**
     * Eager Load
     *
     * @param array $load
     * @param array|Results $results
     * @param string|null $with
     * @return mixed
     */
    public function load($load, $results, $with)
    {
        $this->load = $load;
        $this->with = $with;

        return $this->withEager($results);
    }

    /**
     * With Eager
     *
     * @param array|Results $results
     * @return mixed
     */
    protected function withEager($results) {
        if(empty($this->load) || empty($results)) {
            return $results;
        }

        /** @var Model $relation */
        $relation = $this->load['relation'] ? clone $this->load['relation']: null;
        $name = $this->load['name'];

        if(is_null($relation)) {
            /** @var Model|Results $results */
            if($results instanceof Results) {
                foreach($results as $key => $value) {
                    /** @var Model $value */
                    $value->setRelationship($name, null);
                }
            } else {
                $results->setRelationship($name, null);
            }

            return $results;
        }

        $type = $relation->getRelatedBy()['type'];
        $results = $this->{$type}($results);

        return $results;
    }

    /**
     * Compile Items
     *
     * @param array|Results $items results from query
     * @param string $on the field to group items by
     * @param bool $array return array or object
     * @param string $resultClass
     * @param bool $unset unset the on property from result item
     *
     * @return array
     */
    protected function compileItems($items, $on = null, $array = false, $resultClass = '\TypeRocket\Database\Results', $unset = false) {
        $set = [];

        if(!empty($items)) {
            foreach ($items as $item) {
                $index = $item->{$on};

                if(!$array) {
                    $set[$index] = $item;
                } else {
                    if(empty($set[$index]) && $resultClass) {
                        $set[$index] = new $resultClass;
                    }

                    if($unset) { unset($item->{$on}); }
                    $set[$index]->append($item);
                }
            }
        }

        return $set;
    }

    /**
     * Belongs To
     *
     * @param array|Results $result
     * @return mixed
     * @throws \Exception
     */
    public function belongsTo($result)
    {
        $ids = [];
        /** @var Model $relation */
        $relation = clone $this->load['relation'];
        $name = $this->load['name'];
        $by = $relation->getRelatedBy();
        $query = $by['query'];

        if($result instanceof Results) {
            foreach($result as $model) {
                /** @var Model $model */
                $ids[] = $model->{$query['local_id']};
            }
        } elseif($result instanceof Model) {
            $ids[] = $result->{$query['local_id']};
        }

        $on = $relation->getIdColumn();
        $relation->where($on, 'IN', $ids)->with($this->with);

        if(is_callable($query['scope'])) {
            $query['scope']($relation);
        }

        $items = $relation->get();

        $set = $this->compileItems($items, $on, false, $relation->getResultsClass());

        if($result instanceof Results) {
            foreach($result as $key => $value) {
                /** @var Model $value */
                $local_id = $value->{$query['local_id']};
                $value->setRelationship($name, $set[$local_id] ?? null);
            }
        } else {
            $lookup = $result->{$query['local_id']};
            $result->setRelationship($name, $set[$lookup] ?? null);
        }

        return $result;
    }

    /**
     * Has One
     *
     * @param array|Results $result
     * @return mixed
     * @throws \Exception
     */
    public function hasOne($result)
    {
        $ids = [];
        /** @var Model $relation */
        $relation = clone $this->load['relation'];
        $name = $this->load['name'];
        $query = $relation->getRelatedBy()['query'];

        if($result instanceof Results) {
            foreach($result as $model) {
                /** @var Model $model */
                $ids[] = $model->getId();
            }
        } elseif($result instanceof Model) {
            $ids[] = $result->getId();
        }

        $relation->where($query['id_foreign'], 'IN', $ids)->with($this->with);

        if(is_callable($query['scope'])) {
            $query['scope']($relation);
        }

        $items = $relation->get();

        $set = $this->compileItems($items, $query['id_foreign'], false, $relation->getResultsClass());

        if($result instanceof Results) {
            foreach($result as $key => $value) {
                /** @var Model $value */
                $local_id = $value->getId();
                $value->setRelationship($name, $set[$local_id] ?? null);
            }
        } else {
            $result->setRelationship($name, $set[$result->getId()] ?? null);
        }

        return $result;
    }

    /**
     * Has Many
     *
     * @param array|Results $result
     * @return mixed
     * @throws \Exception
     */
    public function hasMany($result)
    {
        $ids = [];
        /** @var Model $relation */
        $relation = clone $this->load['relation'];
        $name = $this->load['name'];
        $query = $relation->getRelatedBy()['query'];

        if($result instanceof Results) {
            foreach($result as $model) {
                /** @var Model $model */
                $ids[] = $model->getId();
            }
        } elseif($result instanceof Model) {
            $ids[] = $result->getId();
        }

        $relation->where($query['id_foreign'], 'IN', $ids)->with($this->with);

        if(is_callable($query['scope'])) {
            $query['scope']($relation);
        }

        $items = $relation->get();

        $set = $this->compileItems($items, $query['id_foreign'], true, $relation->getResultsClass() );

        if($result instanceof Results) {
            foreach($result as $key => $value) {
                /** @var Model $value */
                $local_id = $value->getId();
                $value->setRelationship($name, $set[$local_id] ?? null);
            }
        } else {
            $result->setRelationship($name, $set[$result->getId()] ?? null);
        }

        return $result;
    }


    /**
     * Belong To Many
     *
     * @param array|Results $result
     * @return mixed
     * @throws \Exception
     */
    public function belongsToMany($result)
    {
        $ids = [];
        /** @var Model $relation */
        $relation = clone $this->load['relation'];
        $name = $this->load['name'];
        $query = $relation->getRelatedBy()['query'];
        $set = [];

        $relationId = $query['where_column'];
        if (($pos = strpos($relationId, ".")) !== false) { 
            $relationId = substr($relationId, $pos + 1); 
        }

        if($result instanceof Results) {
            foreach($result as $model) {
                /** @var Model $model */
                $ids[] = $model->$relationId ?? $model->getId();
            }
        } elseif($result instanceof Model) {
            $ids[] = $result->$relationId ?? $result->getId();
        }

        $relation
            ->select($query['where_column'] . ' as the_relationship_id')
            ->where($query['where_column'], 'IN', $ids)
            ->with($this->with);

        if(is_callable($query['scope'])) {
            $query['scope']($relation);
        }

        $items = $relation->get();

        $set = $this->compileItems($items, 'the_relationship_id', true, $relation->getResultsClass());

        if($result instanceof Results) {
            foreach($result as $key => $value) {
                /** @var Model $value */
                $local_id = $value->getId();
                $value->setRelationship($name, $set[$local_id] ?? null);
            }
        } else {
            $result->setRelationship($name, $set[$result->getId()] ?? null);
        }

        return $result;
    }
}