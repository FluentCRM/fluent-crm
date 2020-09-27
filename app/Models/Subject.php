<?php

namespace FluentCrm\App\Models;

class Subject extends Model
{
    protected $table = 'fc_meta';

    protected $guarded = ['id'];

    public static function boot()
    {
        static::creating(function ($model) {
            $model->object_type = __class__;
        });
        static::saving(function ($model) {
            $model->object_type = __class__;
        });

        static::addGlobalScope('object_type', function ($builder) {
            $builder->where('object_type', '=', __class__);
        });
    }

    public function campaign()
    {
        return $this->belongsTo(__NAMESPACE__.'\Campaign', 'object_id', 'id');
    }

    public function emails()
    {
        return $this->hasMany(__NAMESPACE__.'\CampaignEmail', 'email_subject_id', 'id');
    }
}