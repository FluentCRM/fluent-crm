<?php

namespace FluentCrm\App\Models;

/**
 *  FunnelSequence Model - DB Model for Automation Sequences
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class FunnelSequence extends Model
{
    protected $table = 'fc_funnel_sequences';

    protected $guarded = ['id'];

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
        parent::boot();

        static::updating(function ($model) {
            if (isset($model->settings) && is_array($model->settings)) {
                $model->settings = \maybe_serialize($model->settings);
            }
            if (isset($model->conditions) && is_array($model->conditions)) {
                $model->conditions = \maybe_serialize($model->conditions);
            }
        });
    }

    public function funnel()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Funnel', 'funnel_id', 'id'
        );
    }

    public function setSettingsAttribute($settings)
    {
        if (is_array($settings)) {
            $this->attributes['settings'] = \maybe_serialize($settings);
        } else {
            $this->attributes['settings'] = $settings;
        }
    }

    public function getSettingsAttribute($settings)
    {
        return \maybe_unserialize($settings);
    }

    public function setConditionsAttribute($conditions)
    {
        if (is_array($conditions)) {
            $this->attributes['conditions'] = \maybe_serialize($conditions);
        } else {
            $this->attributes['conditions'] = $conditions;
        }
    }

    public function getConditionsAttribute($conditions)
    {
        return \maybe_unserialize($conditions);
    }
}
