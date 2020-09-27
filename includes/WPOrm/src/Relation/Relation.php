<?php

namespace WPManageNinja\WPOrm\Relation;

use Closure;
use WPManageNinja\WPOrm\Model;
use WPManageNinja\WPOrm\ModelCollection;
use WPManageNinja\WPOrm\ModelQueryBuilder;

abstract class Relation
{
    protected $query;

    protected $parent;

    protected $related;

    protected static $constraints = true;

    public function __construct(ModelQueryBuilder $query, Model $parent)
    {
        $this->query = $query;
        $this->parent = $parent;
        $this->related = $query->getModel();

        $this->addConstraints();
    }

    abstract public function addConstraints();

    abstract public function addEagerConstraints(array $models);

    abstract public function initRelation(array $models, $relation);

    abstract public function match(array $models, ModelCollection $results, $relation);

    abstract public function getResults();

    public function getEager()
    {
        return $this->get();
    }

    public function getRelationCountQuery(ModelQueryBuilder $query, ModelQueryBuilder $parent)
    {
        $wpdbPrefix = $this->getWPDBPrefix();
        
        $foreignKey = explode('.', $this->getForeignKey());

        $foreignKey[0] = $wpdbPrefix . $foreignKey[0];

        $foreignKey = '`' . implode('`.`', $foreignKey) . '`';

        $otherKey = explode('.', $this->getQualifiedForeignKey());

        $otherKey[0] = $wpdbPrefix . $otherKey[0];

        $otherKey = '`' . implode('`.`', $otherKey) . '`';

        $query->select($query->raw('count(*)'));

        return $query->where($query->raw($foreignKey. ' = ' . $otherKey));
    }

    public static function noConstraints(Closure $callback)
    {
        static::$constraints = false;
        
        $results = call_user_func($callback);

        static::$constraints = true;

        return $results;
    }

    protected function getKeys(array $models, $key = null)
    {
        return array_unique(array_values(array_map(function ($value) use ($key) {
            return $key ? $value->getAttribute($key) : $value->getKey();
        }, $models)));
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getBaseQuery()
    {
        return $this->query->getQuery();
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getRelated()
    {
        return $this->related;
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function getQualifiedForeignKey()
    {
        return $this->parent->getTable().'.'.$this->parent->getKeyName();
    }

    public function getQualifiedParentKeyName()
    {
        return $this->parent->getQualifiedKeyName();
    }

    public function createdAt()
    {
        return $this->parent->getCreatedAtColumn();
    }

    public function updatedAt()
    {
        return $this->parent->getUpdatedAtColumn();
    }

    public function relatedUpdatedAt()
    {
        return $this->related->getUpdatedAtColumn();
    }

    public function getWPDBPrefix()
    {
        $adapter = $this->query->getQuery()->getConnection()->getAdapterConfig();

        return $adapter['prefix'];
    }

    public function __call($method, $parameters)
    {
        $result = call_user_func_array(array($this->query, $method), $parameters);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
