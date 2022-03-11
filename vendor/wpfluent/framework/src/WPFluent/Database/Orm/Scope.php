<?php

namespace FluentCrm\Framework\Database\Orm;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \FluentCrm\Framework\Database\Orm\Builder  $builder
     * @param  \FluentCrm\Framework\Database\Orm\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
