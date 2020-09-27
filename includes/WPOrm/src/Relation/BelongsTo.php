<?php

namespace WPManageNinja\WPOrm\Relation;

use WPManageNinja\WPOrm\Model;
use WPManageNinja\WPOrm\ModelCollection;
use WPManageNinja\WPOrm\ModelQueryBuilder;
use WPManageNinja\WPOrm\Relation\Relation;

class BelongsTo extends Relation
{
    protected $foreignKey;

    protected $otherKey;

    protected $relation;

    public function __construct(ModelQueryBuilder $query, Model $parent, $foreignKey, $otherKey, $relation)
    {
        $this->otherKey = $otherKey;
        $this->relation = $relation;
        $this->foreignKey = $foreignKey;

        parent::__construct($query, $parent);
    }

    public function getResults()
    {
        return $this->query->first();
    }

    public function getRelationCountQuery(ModelQueryBuilder $query, ModelQueryBuilder $parent)
    {
        $wpdbPrefix = $this->getWPDBPrefix();

        $foreignKey = explode('.', $this->getQualifiedForeignKey());

        $foreignKey[0] = $wpdbPrefix . $foreignKey[0];

        $foreignKey = '`' . implode('`.`', $foreignKey) . '`';

        $otherKey = explode('.', $this->getQualifiedOtherKeyName());

        $otherKey[0] = $wpdbPrefix . $otherKey[0];

        $otherKey = '`' . implode('`.`', $otherKey) . '`';

        $query->select($query->raw('count(*)'));

        return $query->where($query->raw($foreignKey . ' = ' . $otherKey));
    }

    public function addConstraints()
    {
        if (static::$constraints) {
            $table = $this->related->getTable();

            $this->query->where($table.'.'.$this->otherKey, '=', $this->parent->{$this->foreignKey});
        }
    }

    public function addEagerConstraints(array $models)
    {
        $key = $this->related->getTable().'.'.$this->otherKey;

        $this->query->whereIn($key, $this->getEagerModelKeys($models));
    }

    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        foreach ($models as $model) {
            if (!is_null($value = $model->{$this->foreignKey})) {
                $keys[] = $value;
            }
        }

        if (!$keys) {
            return [];
        }

        return array_values(array_unique($keys));
    }

    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }

        return $models;
    }

    public function match(array $models, ModelCollection $results, $relation)
    {
        $foreign = $this->foreignKey;

        $other = $this->otherKey;

        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($other)] = $result;
        }

        foreach ($models as $model) {
            if (isset($dictionary[$model->$foreign])) {
                $model->setRelation($relation, $dictionary[$model->$foreign]);
            }
        }

        return $models;
    }

    public function associate(Model $model)
    {
        $this->parent->setAttribute(
            $this->foreignKey, $model->getAttribute($this->otherKey)
        );

        return $this->parent->setRelation($this->relation, $model);
    }

    public function dissociate()
    {
        $this->parent->setAttribute($this->foreignKey, null);

        return $this->parent->setRelation($this->relation, null);
    }

    public function update(array $attributes)
    {
        $instance = $this->getResults();

        return $instance->fill($attributes)->save();
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function getQualifiedForeignKey()
    {
        return $this->parent->getTable().'.'.$this->foreignKey;
    }

    public function getOtherKey()
    {
        return $this->otherKey;
    }

    public function getQualifiedOtherKeyName()
    {
        return $this->related->getTable().'.'.$this->otherKey;
    }
}
