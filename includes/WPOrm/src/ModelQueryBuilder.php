<?php

namespace WPManageNinja\WPOrm;

use WPManageNinja\WPOrm\Relation\Relation;
use WpFluent\QueryBuilder\QueryBuilderHandler;
use WPManageNinja\WPOrm\ModelNotFoundException;

class ModelQueryBuilder
{
    protected $query = null;

    protected $model = true;

    protected $eagerLoads = [];

    public function __construct($query, $model)
    {
        $this->query = $query;

        $this->model = $model;
    }

    public function create(array $attributes = [])
    {
        $instance = $this->model->newInstance($attributes);

        $instance->save();

        return $instance;
    }

    public function firstOrCreate(array $attributes, array $values = [], $persist = true)
    {
        $instance = $this->where($attributes)->first();

        if (!$instance) {
            $instance = $this->model->newInstance($attributes + $values);

            $persist && $instance->save();
        }

        return $instance;
    }

    public function updateOrCreate(array $findBy, array $attributes)
    {
        $instance = $this->firstOrNew($findBy);

        $instance->fill($attributes)->save();

        return $instance;
    }

    public function update(array $attributes = [])
    {
        if ($this->model->hasTimestamps()) {
            $attributes = $attributes + [
                $this->model->getUpdatedAtColumn() => $this->model->freshTimestamp()
            ];
        }

        return $this->query->update($attributes);
    }

    public function delete()
    {
        return $this->query->delete();
    }

    public function destroy($ids = [])
    {
        $count = 0;

        $ids = is_array($ids) ? $ids : func_get_args();

        $class = get_class($this->model);

        $instance = new $class;

        if ($ids) {
            $models = $instance->whereIn($instance->getKeyName(), $ids)->get();
        } else {
            $models = $instance->get();
        }

        foreach ($models as $model) {
            if ($model->delete()) $count++;
        }

        return $count;
    }

    public function all()
    {
        return $this->get();
    }

    public function get()
    {
        $models = $this->getModels();

        if ($models && $this->hasEagerLoads()) {
            $this->eagerLoadRelations($models);
        }

        return $this->model->newCollection($models);
    }

    public function getModels()
    {
        return $this->hydrate($this->query->get());
    }

    public function hydrate($items)
    {
        $models = [];

        if ($items) {
            foreach ($items as $item) {
                $models[] = $this->model->newInstanceFromDB($item, true);
            }
        }

        return $models;
    }

    public function find($id)
    {
        return $this->where($this->model->getKeyName(), $id)->first();
    }

    public function findOrFail($id)
    {
        $instance = $this->find($id);

        if ($instance) {
            return $instance;
        }

        $class = get_class($this->model);

        throw new ModelNotFoundException("$class($id) not found.", 404);
    }

    public function fresh()
    {
        $this->model->fill(
            $this->model->findOrFail($this->model->getKey())->getAttributes()
        );
    }

    public function first()
    {
        return $this->limit(1)->get()->first();
    }

    public function firstOrFail()
    {
        $instance = $this->first();

        if ($instance) {
            return $instance;
        }

        $class = get_class($this->model);

        throw new ModelNotFoundException("There is no $class available.", 404);
    }

    public function firstOrNew(array $attributes, array $values = [])
    {
        return $this->firstOrCreate($attributes, $values, false);
    }

    public function paginate($perPage = null)
    {
        $result = $this->getQuery()->paginate(
            $perPage ?: (isset($_REQUEST['per_page']) ? intval($_REQUEST['per_page']) : 10)
        );

        $result['data'] = $this->hydrate($result['data']);

        if ($this->hasEagerLoads()) {
            $this->eagerLoadRelations($result['data']);
        }

        $result['data'] = $this->model->newCollection($result['data']);

        return new \WPManageNinja\WPOrm\Paginator($result);
    }

    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
    {
        if (strpos($relation, '.') !== false) {
            return $this->hasNested($relation, $operator, $count, $callback);
        }

        $relation = $this->getHasRelationQuery($relation);

        $relationCountSubQuery = $relation->getRelationCountQuery(
            $relation->getRelated()->newQuery(), $this
        );

        if ($callback) {
            call_user_func($callback, $relationCountSubQuery);
        }

        $rawSubQuery = (string) $this->subQuery($relationCountSubQuery->getQuery());

        if ($boolean == 'and') {
            $this->where($this->raw($rawSubQuery . ' ' . $operator . ' ' . $count));
        } else {
            $this->orWhere($this->raw($rawSubQuery . ' ' . $operator . ' ' . $count));
        }

        return $this;
    }

    public function whereHas($relation, $callback, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'and', $callback);
    }

    public function orHas($relation, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or');
    }

    public function orWhereHas($relation, $callback, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or', $callback);
    }

    public function doesNotHave($relation, $boolean = 'and', $callback = null)
    {
        return $this->has($relation, '<', 1, $boolean, $callback);
    }

    public function orDoesNotHave($relation)
    {
        return $this->doesNotHave($relation, 'or');
    }

    public function whereDoesNotHave($relation, $callback)
    {
        return $this->doesNotHave($relation, 'and', $callback);
    }

    public function orWhereDoesNotHave($relation, $callback = null)
    {
        return $this->doesNotHave($relation, 'or', $callback);
    }

    public function hasNested($relations, $operator = '>=', $count = 1, $callback = null)
    {
        $relations = explode('.', $relations);

        $closure = function ($query) use (&$closure, &$relations, $operator, $count, $callback) {
            if (count($relations) > 1) {
                $query->whereHas(array_shift($relations), $closure);
            } else {
                $query->has(array_shift($relations), $operator, $count, $callback);
            }
        };

        return $this->whereHas(array_shift($relations), $closure);
    }

    protected function getHasRelationQuery($relation)
    {
        return Relation::noConstraints(function() use ($relation)
        {
            return $this->getModel()->$relation();
        });
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getSql()
    {
        return $this->query->getQuery()->getSql();
    }

    public function getRawSql()
    {
        return $this->query->getQuery()->getRawSql();
    }

    public function getBindings()
    {
        return $this->query->getQuery()->getBindings();
    }

    public function toSql()
    {
        return [
            'query' => $this->getSql(),
            'bindings' => $this->getBindings()
        ];
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getColumns()
    {
        $wpdb = $this->query->db();

        $tableName = $this->model->getTable();

        return $wpdb->get_col("DESC " . $wpdb->prefix.$tableName, 0);
    }

    public function when($value, $callback)
    {
        if ((bool) $value) {
            $callback($this);
        }

        return $this;
    }

    public function with($relations)
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        $eagers = $this->parseRelations($relations);

        $this->eagerLoads = array_merge($this->eagerLoads, $eagers);

        return $this;
    }

    protected function parseRelations(array $relations)
    {
        $results = [];

        foreach ($relations as $name => $constraints) {
            if (is_numeric($name)) {
                list($name, $constraints) = [$constraints, function () {
                }];
            }

            $results = $this->parseNested($name, $results);

            $results[$name] = $constraints;
        }

        return $results;
    }

    protected function parseNested($name, $results)
    {
        $progress = [];

        foreach (explode('.', $name) as $segment) {
            $progress[] = $segment;

            if (! isset($results[$last = implode('.', $progress)])) {
                $results[$last] = function () {
                };
            }
        }

        return $results;
    }

    public function without($relations)
    {
        $this->eagerLoads = array_diff_key($this->eagerLoads, array_flip(
            is_array($relations) ? $relations : func_get_args()
        ));

        return $this;
    }

    public function hasEagerLoads()
    {
        return count($this->eagerLoads);
    }

    public function getEagerLoads()
    {
        return $this->eagerLoads;
    }

    public function eagerLoadRelations(array $models)
    {
        foreach ($this->eagerLoads as $name => $constraints) {
            if (strpos($name, '.') === false) {
                $models = $this->loadRelation($models, $name, $constraints);
            }
        }

        return $models;
    }

    protected function loadRelation(array $models, $name, $constraints)
    {
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($models);

        call_user_func($constraints, $relation);

        $models = $relation->initRelation($models, $name);

        $results = $relation->getEager();

        return $relation->match($models, $results, $name);
    }

    public function getRelation($relation)
    {
        $query = Relation::noConstraints(function () use ($relation) {
            return $this->getModel()->$relation();
        });

        $nested = $this->nestedRelations($relation);

        if ($nested) {
            $query->getQuery()->with($nested);
        }

        return $query;
    }

    protected function nestedRelations($relation)
    {
        $nested = array();

        foreach ($this->eagerLoads as $name => $constraints) {
            if ($this->isNested($name, $relation)) {
                $nested[substr($name, strlen($relation.'.'))] = $constraints;
            }
        }

        return $nested;
    }

    protected function isNested($name, $relation)
    {
        return strpos($name, '.') !== false && strpos($name, $relation.'.') === 0;
    }

    public function withoutGlobalScopes()
    {
        return $this->model->removeGlobalScopes();
    }

    public function withoutGlobalScope($identifier)
    {
        $scopes = $this->model->getGlobalScopes();

        if (array_key_exists($identifier, $scopes)) {
            $this->model->removeGlobalScope($identifier);
        }

        return $this;
    }

    public function forwardCallToQueryBuilderHandler($method, $params)
    {
        $result = call_user_func_array([$this->query, $method], $params);

        return $result instanceof QueryBuilderHandler ? $this : $result;
    }

    public function __call($method, $params)
    {
        if ($this->model->hasLocalScope($method)) {
            return $this->model->callLocalScope($method, $this, $params);
        }

        return $this->forwardCallToQueryBuilderHandler($method, $params);
    }
}
