<?php

namespace WPManageNinja\WPOrm\Relation;

use WPManageNinja\WPOrm\Model;
use WPManageNinja\WPOrm\ModelQueryBuilder;

class Pivot extends Model
{
    protected $parent;

    protected $foreignKey;

    protected $otherKey;

    protected $guarded = [];

    public function __construct(Model $parent, $attributes, $table, $exists = false)
    {
        parent::__construct();

        $this->setRawAttributes($attributes, true);

        $this->setTable($table);

        $this->parent = $parent;

        $this->exists = $exists;

        $this->timestamps = $this->hasTimestampAttributes();
    }

    protected function setKeysForSaveQuery($query)
    {
        $query->where($this->foreignKey, $this->getAttribute($this->foreignKey));

        return $query->where($this->otherKey, $this->getAttribute($this->otherKey));
    }

    public function delete()
    {
        return $this->getDeleteQuery()->delete();
    }

    protected function getDeleteQuery()
    {
        $foreign = $this->getAttribute($this->foreignKey);

        $query = $this->newQuery()->where($this->foreignKey, $foreign);

        return $query->where($this->otherKey, $this->getAttribute($this->otherKey));
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function getOtherKey()
    {
        return $this->otherKey;
    }

    public function setPivotKeys($foreignKey, $otherKey)
    {
        $this->foreignKey = $foreignKey;

        $this->otherKey = $otherKey;

        return $this;
    }

    public function hasTimestampAttributes()
    {
        return array_key_exists($this->getCreatedAtColumn(), $this->attributes);
    }

    public function getCreatedAtColumn()
    {
        return $this->parent->getCreatedAtColumn();
    }

    public function getUpdatedAtColumn()
    {
        return $this->parent->getUpdatedAtColumn();
    }
}
