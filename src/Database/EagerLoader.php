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
     * @param $results
     * @param string|null $with
     * @return mixed
     * @throws \Exception
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
     * @param $result
     * @return mixed
     * @throws \Exception
     */
    protected function withEager($result) {
        if(empty($this->load) || empty($result)) {
            return $result;
        }

        /** @var Model $relation */
        $relation = $this->load['relation'] ? clone $this->load['relation']: null;
        $name = $this->load['name'];


        if(is_null($relation)) {
            if($result instanceof Results) {
                foreach($result as $key => $value) {
                    /** @var Model $value */
                    $value->setRelationship($name, null);
                }
            } else {
                $result->setRelationship($name, null);
            }

            return $result;
        }

        $type = $relation->getRelatedBy()['type'];

        if(method_exists($this, $type)) {
            $result = $this->{$type}($result);
        } else {
            throw new \Exception("Eager loading not supported for $type. No load $name.");
        }

        return $result;
    }

    /**
     * Belongs To
     *
     * @param $result
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
        $set = [];

        if($result instanceof Results) {
            foreach($result as $model) {
                /** @var Model $model */
                $ids[] = $model->{$query['local_id']};
            }
        } elseif($result instanceof Model) {
            $ids[] = $result->{$query['local_id']};
        }

        $on = $relation->getIdColumn();
        $items = $relation->removeTake()->removeWhere()->where($on, 'IN', $ids)->with($this->with)->get();

        foreach($items as $item) { $set[$item->{$on}] = $item; }

        if($result instanceof Results) {
            foreach($result as $key => $value) {
                /** @var Model $value */
                $local_id = $value->{$query['local_id']};
                $value->setRelationship($name, $set[$local_id]);
            }
        } else {
            $result->setRelationship($name, $set[$result->{$query['local_id']}]);
        }

        return $result;
    }

    /**
     * Has One
     *
     * @param $result
     * @return mixed
     * @throws \Exception
     */
    public function hadOne($result)
    {
        $ids = [];
        /** @var Model $relation */
        $relation = clone $this->load['relation'];
        $name = $this->load['name'];
        $query = $relation->getRelatedBy()['query'];
        $set = [];

        if($result instanceof Results) {
            foreach($result as $model) {
                /** @var Model $model */
                $ids[] = $model->getId();
            }
        } elseif($result instanceof Model) {
            $ids[] = $result->getId();
        }

        $items = $relation->removeTake()->removeWhere()->where($query['id_foreign'], 'IN', $ids)->with($this->with)->get();

        foreach($items as $item) {
            $set[$item->{$query['id_foreign']}] = $item;
        }

        if($result instanceof Results) {
            foreach($result as $key => $value) {
                /** @var Model $value */
                $local_id = $value->getId();
                $value->setRelationship($name, $set[$local_id]);
            }
        } else {
            $result->setRelationship($name, $set[$result->getId()]);
        }

        return $result;
    }

    /**
     * Has Many
     *
     * @param $result
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
        $set = [];

        if($result instanceof Results) {
            foreach($result as $model) {
                /** @var Model $model */
                $ids[] = $model->getId();
            }
        } elseif($result instanceof Model) {
            $ids[] = $result->getId();
        }

        $items = $relation->removeTake()->removeWhere()->where($query['id_foreign'], 'IN', $ids)->with($this->with)->get();

        foreach($items as $item) {
            $set[$item->{$query['id_foreign']}][] = $item;
        }

        if($result instanceof Results) {
            foreach($result as $key => $value) {
                /** @var Model $value */
                $local_id = $value->getId();
                $value->setRelationship($name, $set[$local_id]);
            }
        } else {
            $result->setRelationship($name, $set[$result->getId()]);
        }

        return $result;
    }


    /**
     * Belong To Many
     *
     * @param $result
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

        if($result instanceof Results) {
            foreach($result as $model) {
                /** @var Model $model */
                $ids[] = $model->getId();
            }
        } elseif($result instanceof Model) {
            $ids[] = $result->getId();
        }

        $items = $relation
            ->select($query['where_column'] . ' as the_relationship_id')
            ->removeTake()
            ->removeWhere()
            ->where($query['where_column'], 'IN', $ids)
            ->with($this->with)
            ->get();

        foreach($items as $item) {
            $set[$item->the_relationship_id][] = $item;
            unset($item->the_relationship_id);
        }

        if($result instanceof Results) {
            foreach($result as $key => $value) {
                /** @var Model $value */
                $local_id = $value->getId();
                $value->setRelationship($name, $set[$local_id]);
            }
        } else {
            $result->setRelationship($name, $set[$result->getId()]);
        }

        return $result;
    }
}