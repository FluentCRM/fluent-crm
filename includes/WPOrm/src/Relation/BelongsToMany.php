<?php

namespace WPManageNinja\WPOrm\Relation;

use WPManageNinja\WPOrm\Model;
use WPManageNinja\WPOrm\ModelCollection;
use WPManageNinja\WPOrm\ModelQueryBuilder;
use WPManageNinja\WPOrm\Relation\Relation;

class BelongsToMany extends Relation
{
    protected $table;

    protected $foreignKey;

    protected $otherKey;

    protected $relationName;

    protected $pivotColumns = [];

    protected $pivotWheres = [];

    public function __construct($query, $parent, $table, $foreignKey, $otherKey, $relationName)
    {
        $this->table = $table;
        $this->otherKey = $otherKey;
        $this->foreignKey = $foreignKey;
        $this->relationName = $relationName;

        parent::__construct($query, $parent);
    }

    public function addConstraints()
    {
        $this->performJoin();

        if (static::$constraints) {
            $this->setWhere();
        }
    }

    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        $baseTable = $this->related->getTable();

        $key = $baseTable.'.'.$this->related->getKeyName();

        $query->join($this->table, $key, '=', $this->getOtherKey());

        return $this;
    }

    public function getRelationCountQuery(ModelQueryBuilder $query, ModelQueryBuilder $parent)
    {
        $parentStatements = $parent->getQuery()->getStatements();
        $queryStatements = $query->getQuery()->getStatements();

        if (reset($parentStatements['tables']) == reset($queryStatements['tables'])) {
            return $this->getRelationCountQueryForSelfJoin($query, $parent);
        }

        $this->performJoin($query);

        return parent::getRelationCountQuery($query, $parent);
    }

    public function getRelationCountQueryForSelfJoin(ModelQueryBuilder $query, ModelQueryBuilder $parent)
    {
        $wpdbPrefix = $this->getWPDBPrefix();

        $hash = $this->getRelationCountHash();

        $query->from(
            $query->raw(
                '`'.$wpdbPrefix.$this->table.'` as `'.$wpdbPrefix.$hash.'`'
            )
        );

        $key = explode('.', $this->getQualifiedParentKeyName());

        $key[0] = $wpdbPrefix . $key[0];

        $key = '`' . implode('`.`', $key) . '`';

        $query->select($query->raw('count(*)'));

        return $query->where(
            $query->raw('`'.$wpdbPrefix.$hash.'`.`'.$this->foreignKey . '` = ' . $key)
        );
    }

    public function getRelationCountHash()
    {
        return 'self_'.md5(microtime(true));
    }

    protected function setWhere()
    {
        $foreign = $this->getForeignKey();

        $this->query->where($foreign, '=', $this->parent->getKey());

        return $this;
    }

    public function addEagerConstraints(array $models)
    {
        $this->query->whereIn($this->getForeignKey(), $this->getKeys($models));
    }

    public function getForeignKey()
    {
        return $this->table.'.'.$this->foreignKey;
    }

    public function getOtherKey()
    {
        return $this->table.'.'.$this->otherKey;
    }

    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    public function withPivot($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $this->pivotColumns = array_merge($this->pivotColumns, $columns);

        return $this;
    }

    public function withTimestamps($createdAt = null, $updatedAt = null)
    {
        return $this->withPivot(
            $createdAt ?: $this->createdAt(),
            $updatedAt ?: $this->updatedAt()
        );
    }

    public function wherePivot($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = '=';
        }

        $this->pivotWheres[] = [$column, $operator, $value, $boolean];

        $table = $this->table;

        if ($boolean == 'and') {
            return $this->where($table.'.'.$column, $operator, $value);
        } else {
            return $this->orWhere($table.'.'.$column, $operator, $value);
        }
    }

    public function orWherePivot($column, $operator = null, $value = null)
    {
        return $this->wherePivot($column, $operator, $value, 'or');
    }

    public function first()
    {
        $results = $this->limit(1)->get();

        return count($results) > 0 ? $results->first() : null;
    }

    public function firstOrFail()
    {
        $model = $this->first();

        if (!is_null($model)) return $model;

        $class = get_class($model);

        wp_send_json([
            'success' => false,
            'error' => "The instance of {$class} model does not exist!"
        ], 404);
    }

    public function getResults()
    {
        return $this->get();
    }

    public function get()
    {
        $this->addSelect($this->query);

        $models = $this->query->getModels();

        $this->hydratePivotRelation($models);

        if ($models) {
            $models = $this->query->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }

    public function paginate($perPage = null)
    {
        $perPage = $perPage ?: (isset($_REQUEST['per_page']) ? intval($_REQUEST['per_page']) : 10);

        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

        $offest = ($page - 1) * $perPage;

        $total = $this->query->count();

        $this->addSelect($this->query);

        $result = $this->query->offset($offest)->limit($perPage)->get();

        $this->hydratePivotRelation($result->all());

        return new \WPManageNinja\WPOrm\Paginator([
            'total' => $total,
            'per_page' => (int) $perPage,
            'current_page' => (int) $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offest + 1,
            'to' => (count($result) < $perPage) ? $total : ($page * $perPage),
            'data' => $result
        ]);
    }

    protected function addSelect($query)
    {
        $prefix = $this->getWPDBPrefix();

        $query->select($query->raw($prefix.$this->related->getTable().'.*'));

        foreach ($this->getAliasedPivotColumns() as $rawColumn) {
            $query->select($query->raw($prefix.$rawColumn));
        }
    }

    protected function getAliasedPivotColumns()
    {
        $columns = [];

        $defaults = array($this->foreignKey, $this->otherKey);

        foreach (array_merge($defaults, $this->pivotColumns) as $column) {
            $columns[] = $this->table.'.'.$column.' as pivot_'.$column;
        }

        return array_unique($columns);
    }

    protected function hydratePivotRelation($models)
    {
        foreach ($models as $model) {
            $pivot = $this->newExistingPivot($this->cleanPivotAttributes($model));

            $model->setRelation('pivot', $pivot);
        }
    }

    public function newExistingPivot(array $attributes = [])
    {
        return $this->newPivot($attributes, true);
    }

    public function newPivot(array $attributes = [], $exists = false)
    {
        $pivot = $this->related->newPivot(
            $this->parent, $attributes, $this->table, $exists
        );

        return $pivot->setPivotKeys($this->foreignKey, $this->otherKey);
    }

    protected function cleanPivotAttributes($model)
    {
        $values = [];

        foreach ($model->getAttributes() as $key => $value) {
            if (strpos($key, 'pivot_') === 0) {
                $values[substr($key, 6)] = $value;

                unset($model->$key);
            }
        }

        return $values;
    }

    public function match(array $models, ModelCollection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $model->getKey();

            if (isset($dictionary[$key])) {
                $collection = $this->related->newCollection($dictionary[$key]);

                $model->setRelation($relation, $collection);
            }
        }

        return $models;
    }

    protected function buildDictionary($results)
    {
        $foreign = $this->foreignKey;

        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->pivot->$foreign][] = $result;
        }

        return $dictionary;
    }

    public function attach($id, array $attributes = [])
    {
        if ($id instanceof Model) $id = $id->getKey();

        $query = $this->newPivotStatement();

        return $query->insert($this->createAttachRecords((array) $id, $attributes));
    }

    public function newPivotStatement()
    {
        return $this->query->getQuery()->table($this->table);
    }

    protected function createAttachRecords($ids, array $attributes)
    {
        $records = [];

        $hasTimeStamp = array_intersect([
            $this->createdAt(), $this->updatedAt()
        ], $this->pivotColumns);

        foreach ($ids as $key => $value) {
            $records[] = $this->attacher($key, $value, $attributes, $hasTimeStamp);
        }

        return $records;
    }

    protected function attacher($key, $value, $attributes, $timed)
    {
        list($id, $extra) = $this->getAttachId($key, $value, $attributes);

        $record = $this->createAttachRecord($id, $timed);

        return array_merge($record, $extra);
    }

    protected function getAttachId($key, $value, array $attributes)
    {
        if (is_array($value)) {
            return array($key, array_merge($value, $attributes));
        }

        return array($value, $attributes);
    }

    protected function createAttachRecord($id, $timed)
    {
        $record[$this->foreignKey] = $this->parent->getKey();

        $record[$this->otherKey] = $id;

        if ($timed) {
            $record = $this->setTimestampsOnAttach($record);
        }

        return $record;
    }

    protected function setTimestampsOnAttach(array $record, $exists = false)
    {
        $fresh = $this->parent->freshTimestamp();

        if (!$exists && in_array($this->createdAt(), $this->pivotColumns)) {
            $record[$this->createdAt()] = $fresh;
        }

        if (in_array($this->updatedAt(), $this->pivotColumns)) {
            $record[$this->updatedAt()] = $fresh;
        }

        return $record;
    }

    public function detach($ids = [])
    {
        if ($ids instanceof Model) $ids = (array) $ids->getKey();

        $query = $this->newPivotQuery();

        $ids = (array) $ids;

        if (count($ids) > 0) {
            $query->whereIn($this->otherKey, (array) $ids);
        }

        $results = $query->delete();

        return $results;
    }

    protected function newPivotQuery()
    {
        $query = $this->newPivotStatement();

        foreach ($this->pivotWheres as $whereArgs) {
            call_user_func_array([$query, 'where'], $whereArgs);
        }

        return $query->where($this->foreignKey, $this->parent->getKey());
    }

    public function sync($ids, $detaching = true)
    {
        $changes = array(
            'attached' => array(), 'detached' => array(), 'updated' => array()
        );

        $ids = $ids instanceof ModelCollection ? $ids->getModelKeys() : $ids;

        $current = $this->getPivotOtherKeys();

        $records = $this->formatSyncList($ids);

        $detach = array_diff($current, array_keys($records));

        if ($detaching && count($detach) > 0) {
            $this->detach($detach);

            $changes['detached'] = (array) array_map(function($v) { return (int) $v; }, $detach);
        }

        $changes = array_merge(
            $changes, $this->attachNew($records, $current)
        );

        return $changes;
    }

    protected function getPivotOtherKeys()
    {
        $keys = array_map(function($item) {
            $array = (array) $item;
            $values = array_values($array);
            return reset($values);
        }, $this->newPivotQuery()->select($this->otherKey)->get());

        return array_unique($keys);
    }

    protected function formatSyncList(array $records)
    {
        $results = array();

        foreach ($records as $id => $attributes) {
            if (!is_array($attributes)) {
                list($id, $attributes) = array($attributes, []);
            }

            $results[$id] = $attributes;
        }

        return $results;
    }

    protected function attachNew(array $records, array $current)
    {
        $changes = array('attached' => [], 'updated' => []);

        foreach ($records as $id => $attributes) {

            if ( ! in_array($id, $current)) {
                $this->attach($id, $attributes);

                $changes['attached'][] = (int) $id;
            } elseif (count($attributes) > 0 && $this->updateExistingPivot($id, $attributes)) {
                $changes['updated'][] = (int) $id;
            }
        }

        return $changes;
    }

    public function updateExistingPivot($id, array $attributes)
    {
        if (in_array($this->updatedAt(), $this->pivotColumns)) {
            $attributes = $this->setTimestampsOnAttach($attributes, true);
        }

        $updated = $this->newPivotStatementForId($id)->update($attributes);

        return $updated;
    }

    public function newPivotStatementForId($id)
    {
        return $this->newPivotQuery()->where($this->otherKey, $id);
    }
}
