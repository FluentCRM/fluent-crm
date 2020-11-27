<?php

namespace WPManageNinja\WPOrm;

use ArrayAccess;
use JsonSerializable;
use JsonEncodingException;
use WPManageNinja\WPOrm\Jsonable;
use WPManageNinja\WPOrm\Arrayable;
use WPManageNinja\WPOrm\Relation\Pivot;
use WPManageNinja\WPOrm\Relation\HasOne;
use WPManageNinja\WPOrm\Relation\HasMany;
use WPManageNinja\WPOrm\Relation\BelongsTo;
use WPManageNinja\WPOrm\Relation\BelongsToMany;

abstract class Model implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected static $tablePrefix = '';

    protected static $globalScopes = [];

    protected static $booted = [];

    protected static $events = [];

    protected $table = null;

    protected $primaryKey = 'id';

    protected $isWpTable = false;

    protected $timestamps = true;

    public $exists = false;

    public $wasRecentlyCreated = false;

    protected $attributes = [];

    protected $original = [];

    protected $relations = [];

    protected $fillable = ['*'];

    protected $guarded = [];

    protected $query = null;

    protected $builder = null;

    public function __construct($attributes = [])
    {
        $this->bootModel();

        $this->syncOriginal();

        $this->fill($attributes);
    }

    public function setTable($table)
    {
        $this->table = $table;
    }

    public function bootModel()
    {
        $calledClass = static::getCalledClassName();

        if (!isset(static::$booted[$calledClass])) {
            static::$booted[$calledClass] = true;
            static::boot();
        }
    }

    public static function getCalledClassName()
    {
        return str_replace('\\', '_', get_called_class());
    }

    public static function boot()
    {
        // Note: Don't remove this method
    }

    public static function registerModelEvent($event, $callback)
    {
        $calledClass = static::getCalledClassName();
        static::$events["model.{$calledClass}.{$event}"] = $callback;
    }

    public static function retrieved($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function saving($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function saved($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function creating($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function created($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function updating($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function updated($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function deleting($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public static function deleted($callback)
    {
        static::registerModelEvent(__FUNCTION__, $callback);
    }

    public function fireModelEvent($event)
    {
        $calledClass = static::getCalledClassName();

        $eventName = "model.{$calledClass}.{$event}";

        if (isset(static::$events[$eventName])) {
            return call_user_func(static::$events[$eventName], $this);
        }
    }

    public static function addGlobalScope($scope, $callback)
    {
        static::$globalScopes[static::getCalledClassName()][$scope] = $callback;
    }

    public function getGlobalScopes()
    {
        $calledClass = static::getCalledClassName();

        if (array_key_exists($calledClass, static::$globalScopes)) {
            return static::$globalScopes[$calledClass];
        }

        return [];
    }

    public function applyGlobalScopes($query = null)
    {
        $query = $query ?: $this->query;

        foreach ($this->getGlobalScopes() as $scope) {
            $scope($query);
        }

        return $query;
    }

    public function removeGlobalScopes()
    {
        $calledClass = static::getCalledClassName();

        static::$globalScopes[$calledClass] = [];

        return $this->query;
    }

    public function removeGlobalScope($scope)
    {
        $calledClass = static::getCalledClassName();

        unset(static::$globalScopes[$calledClass][$scope]);

        return $this->query;
    }

    public function syncOriginal()
    {
        $this->original = $this->attributes;

        return $this;
    }

    public function fill($attributes)
    {
        return $this->setAttributes($attributes);
    }

    public function setAttributes($attributes, $isGuarded = true)
    {
        foreach ($attributes as $key => $value) {
            if (!$isGuarded) {
                $this->setAttribute($key, $value);
            } else {
                if ($this->isFillable($key)) {
                    $this->setAttribute($key, $value);
                }
            }
        }

        return $this;
    }

    public function isFillable($key)
    {
        $fillable = $this->getFillable();

        if (in_array($key, $fillable)) {
            return true;
        }

        if ($this->isGuarded($key)) {
            return false;
        }

        return !empty($fillable) && $fillable[0] == '*';
    }

    public function isGuarded($key)
    {
        $guarded = $this->getGuarded();

        return in_array($key, $guarded) || $guarded == ['*'];
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    public function getGuarded()
    {
        return $this->guarded;
    }

    public function setAttribute($key, $value)
    {
        $setter = $this->getMutator($key, 'set');

        if ($setter) {
            return $this->{$setter}($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    public function getMutator($key, $type)
    {
        $key = ucwords(str_replace(['-', '_'], ' ', $key));

        $method = $type.str_replace(' ', '', $key).'Attribute';

        if (method_exists($this, $method)) {
            return $method;
        }
    }

    public function setRawAttributes($attributes, $sync = false)
    {
        $this->attributes = (array) $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($key)
    {
        $attribute = null;

        if ($this->hasAttribute($key)) {
            $attribute = $this->attributes[$key];
        }

        $getter = $this->getMutator($key, 'get');

        if ($getter) {
            return $this->{$getter}($attribute);
        } elseif (!is_null($attribute)) {
            return $attribute;
        }

        if ($this->relationLoaded($key)) {
            return $this->getRelation($key);
        }

        if ($this->relationshipExists($key)) {
            return $this->getRelationFromMethod($key);
        }
    }

    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    public function getRelation($key)
    {
        return $this->relations[$key];
    }

    public function setRelation($relationName, $relation)
    {
        $this->relations[$relationName] = $relation;
    }

    public function relationshipExists($key)
    {
        if (method_exists(get_class(), $key)) {
            return;
        }

        return method_exists($this, $key);
    }

    public function getRelationFromMethod($key)
    {
        $this->load($key);

        return $this->getRelation($key);
    }

    public function hasAttribute($key)
    {
        return array_key_exists($key, $this->attributes);
    }

    public function hasTimestamps()
    {
        return $this->timestamps;
    }

    public function hasLocalScope($method)
    {
        $scopeMethod = 'scope' . ucfirst($method);

        return method_exists($this, $scopeMethod);
    }

    public function callLocalScope($method, $builder, $params)
    {
        $scopeMethod = 'scope' . ucfirst($method);

        call_user_func_array(
            [$this, $scopeMethod],
            array_merge([$this->query], $params)
        );

        return $builder;
    }

    public function load($relations)
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        $query = $this->newQuery()->with($relations);

        $query->eagerLoadRelations([$this]);

        return $this;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        $attributes = $this->getAttributes();

        // Check if any accessor is available and call it
        foreach (get_class_methods($this) as $method) {
            if (method_exists(get_class(), $method)) {
                continue;
            }

            if (substr($method, 0, 3) == 'get' && substr($method, -9) == 'Attribute') {
                $key = str_replace(['get', 'Attribute'], '', $method);
                if ($key) {
                    $pieces = preg_split('/(?=[A-Z])/', $key);
                    $pieces = array_map('strtolower', array_filter($pieces));
                    $key = implode('_', $pieces);
                    $value = array_key_exists($key, $this->attributes) ? $this->attributes[$key] : null;
                    $attributes[$key] = $this->{$method}($value);
                }
            }
        }

        return array_merge($attributes, $this->relations);
    }

    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }

        return $json;
    }

    public function getTable($tableName = null)
    {
        return $this->table;
    }

    public function getKeyName()
    {
        return $this->primaryKey;
    }

    public function getKey()
    {
        $key = $this->getKeyName();

        if ($this->hasAttribute($key)) {
            return $this->attributes[$key];
        }
    }

    public function getQualifiedKeyName()
    {
        return $this->getTable().'.'.$this->getKeyName();
    }

    public function newBaseQuery()
    {
        return new QueryBuilder;
    }

    public function newQuery()
    {
        $baseQuery = $this->newBaseQuery()->table($this->getTable());

        $this->query =  new ModelQueryBuilder($baseQuery, $this);

        $this->registerEventForGlobalScopes($baseQuery);

        return $this->query;
    }

    protected function registerEventForGlobalScopes($baseQuery)
    {
        $baseQuery->registerEvent('before-select', ':any', function ($query) {
            $this->applyGlobalScopes();
        });
    }

    public function newInstance($attributes = [], $exists = false)
    {
        $model = (new static)->setAttributes($attributes);

        $model->exists = $exists;

        return $model;
    }

    public function newInstanceFromDB($attributes = [], $exists = false)
    {
        $model = (new static)->setRawAttributes($attributes, true);

        $model->exists = $exists;

        $model->fireModelEvent('retrieved', $model);

        return $model;
    }

    public function newCollection($models = [])
    {
        return new ModelCollection($models);
    }

    public function newPivot($parent, array $attributes, $table, $exists)
    {
        return new Pivot($parent, $attributes, $table, $exists);
    }

    public function push()
    {
        if (!$this->save()) return false;

        foreach ($this->relations as $models) {
            $relations = ModelCollection::make($models);
            foreach ($relations as $relation) {
                if (!$relation->push()) return false;
            }
        }

        return true;
    }

    public function save()
    {
        $query = $this->newQuery();

        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        if ($this->exists) {
            $saved = $this->isDirty() ? $this->performUpdate($query) : true;
        } else {
            $saved = $this->performInsert($query);
        }

        $this->fireModelEvent('saved');

        $this->syncOriginal();

        return $saved;
    }

    protected function performInsert($query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        $this->updateTimestamps();

        $this->setAttribute($this->getKeyName(), $query->insert($this->attributes));

        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created');

        return true;
    }

    protected function performUpdate($query)
    {
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            if ($this->timestamps) {
                $this->updateTimestamps();
            }

            $dirty = $this->getDirty();

            if (count($dirty) > 0) {
                $this->setKeysForSaveQuery($query)->update($dirty);
            }
        }

        $this->fireModelEvent('updated');

        return true;
    }

    protected function setKeysForSaveQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    protected function getKeyForSaveQuery()
    {
        if (isset($this->original[$this->getKeyName()])) {
            return $this->original[$this->getKeyName()];
        }

        return $this->getAttribute($this->getKeyName());
    }

    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();

        if (!$this->isDirty(static::UPDATED_AT)) {
            $this->{static::UPDATED_AT} = $time;
        }

        if (!$this->exists && !$this->isDirty(static::CREATED_AT)) {
            $this->{static::CREATED_AT} = $time;
        }
    }

    public function delete()
    {
        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        if (!$this->exists) {
            return;
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        $this->performDelete();

        $this->fireModelEvent('deleted');

        return true;
    }

    protected function performDelete()
    {
        $this->setKeysForSaveQuery($this->newQuery())->delete();

        $this->exists = false;
    }

    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }

    public function freshTimestamp($timestamp = null)
    {
        return fluentCrmTimestamp($timestamp);
    }

    public function isDirty($attributes = null)
    {
        $dirty = $this->getDirty();

        if (is_null($attributes)) {
            return count($dirty) > 0;
        }

        if (!is_array($attributes)) {
            $attributes = func_get_args();
        }

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }

        return false;
    }

    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] &&
                ! $this->originalIsNumericallyEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }


    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     *
     * @param  string  $key
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];

        $original = $this->original[$key];

        return is_numeric($current) && is_numeric($original) && strcmp((string) $current, (string) $original) === 0;
    }

    public function getOriginal($key = null)
    {
        if (!is_null($key)) {
            return isset($this->original[$key]) ? $this->original[$key] : null;
        }

        return $this->original;
    }

    public function hasOne($related, $foreignKey, $localKey)
    {
        $instance = new $related;

        return new HasOne(
            $instance->newQuery(),
            $this,
            $instance->getTable().'.'.$foreignKey,
            $localKey
        );
    }

    public function belongsTo($related, $foreignKey = null, $otherKey = null)
    {
        list(, $caller) = debug_backtrace(false, 2);

        $relation = $caller['function'];

        $instance = new $related;

        return new BelongsTo($instance->newQuery(), $this, $foreignKey, $otherKey, $relation);
    }

    public function hasMany($related, $foreignKey, $localKey)
    {
        $instance = new $related;

        return new HasMany(
            $instance->newQuery(),
            $this,
            $instance->getTable().'.'.$foreignKey,
            $localKey
        );
    }

    public function belongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey)
    {
        $instance = new $related;

        $parent = $this->getModel();

        return new BelongsToMany(
            $instance->newQuery(),
            $parent,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $this->guessBelongsToManyRelationName()
        );
    }

    protected function guessBelongsToManyRelationName()
    {
        $caller = null;

        $self = __FUNCTION__;

        $methods = ['belongsToMany'];

        foreach (debug_backtrace(false) as $key => $trace) {
            if(!in_array($trace['function'], $methods) && $trace['function'] != $self) {
                $caller = $trace['function'];
                break;
            }
        }

        return !is_null($caller) ? $caller : null;
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function __Set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return ! is_null($this->getAttribute($offset));
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    public function forwardCallToModelQueryBuilder($method, $params)
    {
        return call_user_func_array([$this->newQuery(), $method], $params);
    }

    public function __call($method, $params)
    {
        return $this->forwardCallToModelQueryBuilder($method, $params);
    }

    public static function __callStatic($method, $params)
    {
        return call_user_func_array([new static, $method], $params);
    }
}
