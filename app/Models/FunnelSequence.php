<?php

namespace FluentCrm\App\Models;

class FunnelSequence extends Model
{
    protected $table = 'fc_funnel_sequences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'funnel_id',
        'action_name',
        'parent_id',
        'condition_type',
        'title',
        'description',
        'status',
        'conditions',
        'settings',
        'delay',
        'c_delay',
        'sequence',
        'created_by',
        'type',
        'note',
    ];

    public static function boot()
    {
        static::updating(function ($model) {
            if(isset($model->settings)) {
                $model->settings = \maybe_serialize($model->settings);
            }
            if(isset($model->conditions)) {
                $model->conditions = \maybe_serialize($model->conditions);
            }
        });
    }

    public function funnel()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Funnel', 'funnel_id', 'id'
        );
    }

    public function setSettingsAttribute($settings)
    {
        $this->attributes['settings'] = \maybe_serialize($settings);
    }

    public function getSettingsAttribute($settings)
    {
        return \maybe_unserialize($settings);
    }

    public function setConditionsAttribute($conditions)
    {
        $this->attributes['conditions'] = \maybe_serialize($conditions);
    }

    public function getConditionsAttribute($conditions)
    {
        return \maybe_unserialize($conditions);
    }
}
