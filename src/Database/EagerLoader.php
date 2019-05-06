<?php


namespace TypeRocket\Database;


use TypeRocket\Models\Model;

class EagerLoader
{

    protected $load = [];


    /**
     * Eager Load
     *
     * @param array $load
     * @param $results
     * @return mixed
     * @throws \Exception
     */
    public function load($load, $results)
    {
        $this->load = $load;

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
        $relation = clone $this->load['relation'];
        $name = $this->load['name'];
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
        $items = $relation->removeTake()->removeWhere()->where($on, 'IN', $ids)->get();

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

        $items = $relation->removeTake()->removeWhere()->where($query['id_foreign'], 'IN', $ids)->get();

        foreach($items as $item) {
            $set[$item[$query['id_foreign']]] = $item;
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