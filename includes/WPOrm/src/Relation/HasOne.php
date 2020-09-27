<?php

namespace WPManageNinja\WPOrm\Relation;

use WPManageNinja\WPOrm\ModelCollection;

class HasOne extends HasOneOrMany
{
    public function getResults()
    {
        return $this->query->first();
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
        return $this->matchOne($models, $results, $relation);
    }
}
