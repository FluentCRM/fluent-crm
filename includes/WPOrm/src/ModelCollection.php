<?php

namespace WPManageNinja\WPOrm;

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

class ModelCollection implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable
{
    protected $models = [];

    public function __construct($models = [])
    {
        $this->models = $models;
    }

    public static function make($items)
    {
        if (is_null($items)) return new static;

        if ($items instanceof self) return $items;

        return new static(is_array($items) ? $items : [$items]);
    }

    public function push($item)
    {
        $this->models[] = $item;
    }

    public function count()
    {
        return count($this->models);
    }

    public function toArray()
    {
        return array_map(function ($model) {
            return method_exists($model, 'toArray') ? $model->toArray() : $model;
        }, $this->models);
    }

    public function all()
    {
        return array_values($this->models);
    }

    public function find($id, $key = 'id')
    {
        foreach ($this->models as $model) {
            if ($model->{$key} == $id) {
                return $model;
            }
        }
    }

    public function map($callback)
    {
        $result = [];
        foreach ($this->models as $key => &$value) {
            $result[] = $callback($value, $key);
        }
        return new static($result);
    }

    public function filter($callback)
    {
        $result = [];
        foreach ($this->models as $key => &$value) {
            if ((bool) $callback($value, $key)) {
                $result[] = $value;
            }
        }
        
        return new static($result);
    }

    public function each($callback)
    {
        foreach ($this->models as $key => &$value) {
            $result = $callback($value, $key);
            if ($result === false) {
                break;
            }
        }

        return $this;
    }

    public function pluck($field, $keyBy = null)
    {
        $result = [];
        foreach ($this->models as $value) {
            $item = $value->toArray();
            if (isset($item[$field])) {
                if ($keyBy) {
                    if (isset($item[$keyBy])) {
                        $result[$item[$keyBy]] = $item[$field];
                    } else {
                        $result[] = $item[$field];
                    }
                } else {
                    $result[] = $item[$field];
                }
            }
        }

        return $result;
    }

    public function where($key, $operator, $value = null)
    {
        $result = [];

        if (is_null($value)) {
            $value = $operator;
            $operator = '==';
        }

        foreach ($this->models as $model) {
            $val = false;
            if (array_key_exists($key, $model->getAttributes())) {
                if ($operator == '==') {
                    $val = $model->{$key} == $value;
                }
                if ($operator == '===') {
                    $val = $model->{$key} === $value;
                }
                if ($operator == '!=') {
                    $val = $model->{$key} != $value;
                }
                if ($operator == '!==') {
                    $val = $model->{$key} !== $value;
                }
                if ($operator == '>') {
                    $val = $model->{$key} > $value;
                }
                if ($operator == '>=') {
                    $val = $model->{$key} >= $value;
                }
                if ($operator == '<') {
                    $val = $model->{$key} < $value;
                }
                if ($operator == '<=') {
                    $val = $model->{$key} <= $value;
                }
                if ($operator == 'in') {
                    $val = in_array($model->{$key}, $value);
                }
                if ($operator == 'notin') {
                    $val = !in_array($model->{$key}, $value);
                }
                if ($operator == 'between') {
                    $val = ($model->{$key} >= $value[0] && $model->{$key} <= $value[1]);
                }
                if ($operator == 'notbetween') {
                    $val = ($model->{$key} < $value[0] || $model->{$key} > $value[1]);
                }
                if ($val) {
                    $result[] = $model;
                }
            }
        }

        return new static($result);
    }

    public function whereIn($key, $value)
    {
        return $this->where($key, 'in', (array) $value);
    }

    public function whereNotIn($key, $value)
    {
        return $this->where($key, 'notin', (array) $value);
    }

    public function whereBetween($key, $value)
    {
        $value = array_values((array) $value);
        if (!$value) {
            $value = [null, null];
        }
        if (count($value) == 1) {
            $value[] = $value[0];
        }

        return $this->where($key, 'between', $value);
    }

    public function whereNotBetween($key, $value)
    {
        $value = array_values((array) $value);
        if (!$value) {
            $value = [null, null];
        }
        if (count($value) == 1) {
            $value[] = $value[0];
        }

        return $this->where($key, 'notbetween', $value);
    }

    public function keyBy($key)
    {
        $result = [];
        foreach ($this->models as $item) {
            if (isset($item[$key])) {
                if (!is_null($item[$key])) {
                    $result[$item[$key]] = $item;
                }
            }
        }

        return new static($result);
    }

    public function getDictionary($models = null)
    {
        $dictionary = [];

        $models = $models ?: $this->models;

        foreach ($models as $value) {
            $dictionary[$value->getKey()] = $value;
        }

        return $dictionary;
    }

    public function unique($key = null)
    {
        $key = is_null($key) ? 'id' : $key;

        return new static(
            array_values(
                $this->keyBy($key)->toArray()
            )
        );
    }

    public function first()
    {
        $first = reset($this->models);
        
        return $first ?: null;
    }

    public function last()
    {
        return end($this->models);
    }

    public function isEmpty()
    {
        return !count($this->models);
    }

    public function merge($models)
    {
        $dictionary = $this->getDictionary();

        foreach ($models as $model) {
            $dictionary[$model->getKey()] = $model;
        }

        return new static(array_values($dictionary));
    }

    public function load($relations)
    {
        $model = $this->first();

        if ($model) {
            $model->newQuery()->with($relations)->eagerLoadRelations($this->all());
        }
        
        return $this->models;
    }

    public function getModelKeys()
    {
        return array_map(function($m) {
            return $m->getKey(); 
        }, $this->models);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function getIterator()
    {
        return new ArrayIterator($this->models);
    }

    public function offsetExists($offset)
    {
        return isset($this->models[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->models[$offset];
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->models[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->models[$offset]);
    }
}
