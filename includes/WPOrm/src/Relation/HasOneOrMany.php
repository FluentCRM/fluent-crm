<?php

namespace WPManageNinja\WPOrm\Relation;

use WPManageNinja\WPOrm\Model;
use WPManageNinja\WPOrm\ModelCollection;
use WPManageNinja\WPOrm\ModelQueryBuilder;
use WPManageNinja\WPOrm\Relation\Relation;

abstract class HasOneOrMany extends Relation
{
    protected $foreignKey;

    protected $localKey;

    public function __construct(ModelQueryBuilder $query, Model $parent, $foreignKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;

        parent::__construct($query, $parent);
    }

    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());
        }
    }

    public function addEagerConstraints(array $models)
    {
        $this->query->whereIn($this->foreignKey, $this->getKeys($models, $this->localKey));
    }

    public function matchOne(array $models, ModelCollection $results, $relation)
    {
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }

    public function matchMany(array $models, ModelCollection $results, $relation)
    {
        return $this->matchOneOrMany($models, $results, $relation, 'many');
    }

    protected function matchOneOrMany(array $models, ModelCollection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);

            if (isset($dictionary[$key])) {
                $value = $this->getRelationValue($dictionary, $key, $type);

                $model->setRelation($relation, $value);
            }
        }

        return $models;
    }

    protected function getRelationValue(array $dictionary, $key, $type)
    {
        $value = $dictionary[$key];

        return $type == 'one' ? reset($value) : $this->related->newCollection($value);
    }

    protected function buildDictionary(ModelCollection $results)
    {
        $dictionary = array();

        $foreign = $this->getPlainForeignKey();

        foreach ($results as $result) {
            $dictionary[$result->{$foreign}][] = $result;
        }

        return $dictionary;
    }

    public function save(Model $model)
    {
        $model->setAttribute(
            $this->getPlainForeignKey(), $this->getParentKey()
        );

        return $model->save() ? $model : false;
    }

    public function saveMany(array $models)
    {
        array_walk($models, [$this, 'save']);

        return $models;
    }

    public function create(array $attributes)
    {
        $instance = $this->related->newInstance($attributes);

        $instance->setAttribute(
            $this->getPlainForeignKey(), $this->getParentKey()
        );

        $instance->save();

        return $instance;
    }

    public function createMany(array $records)
    {
        $instances = [];

        foreach ($records as $record) {
            $instances[] = $this->create($record);
        }

        return $instances;
    }

    public function update(array $attributes)
    {
        if ($this->related->hasTimestamps()) {
            $attributes[$this->relatedUpdatedAt()] = $this->related->freshTimestamp();
        }

        return $this->query->update($attributes);
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function getPlainForeignKey()
    {
        $segments = explode('.', $this->getForeignKey());

        return $segments[count($segments) - 1];
    }

    public function getParentKey()
    {
        return $this->parent->getAttribute($this->localKey);
    }
}
