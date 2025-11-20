<?php

namespace FluentCrm\App\Models;

use FluentCrm\Framework\Database\Orm\Builder;

class Label extends Model
{
    protected $table = 'fc_terms';

    protected $guarded = ['id'];

    /*
     * taxonomy_name: global_label
     * taxonomy_name global_label is the default taxonomy name/type for the Label
     * so it is not required to pass the taxonomy_name while creating a label
     */
    protected $fillable = ['parent_id', 'slug', 'title', 'description', 'position', 'settings', 'created_at', 'updated_at'];

    protected $hidden = ['taxonomy_name'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->taxonomy_name = $model->taxonomy_name ?: 'global_label'; // default type is label
        });

        static::addGlobalScope('taxonomy_name', function (Builder $builder) {
            $builder->where('taxonomy_name', '=', 'global_label');
        });
    }

    public function getSettingsAttribute($value)
    {
        return \maybe_unserialize($value);
    }

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = maybe_serialize($value);
    }



}