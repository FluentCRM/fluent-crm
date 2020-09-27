<?php

namespace FluentCrm\App\Models;

class Funnel extends Model
{
    private static $type = 'funnels';

    protected $table = 'fc_funnels';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'title',
        'trigger_name',
        'status',
        'conditions',
        'settings',
        'created_by',
        'updated_at'
    ];

    public static function boot()
    {
        static::creating(function ($model) {
            $model->type = self::$type;
        });

        static::addGlobalScope('type', function ($builder) {
            $builder->where('type', '=', self::$type);
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function actions()
    {
        return $this->hasMany(
            __NAMESPACE__ . '\FunnelSequence', 'funnel_id', 'id'
        );
    }

    public function subscribers()
    {
        return $this->hasMany(
            __NAMESPACE__ . '\FunnelSubscriber', 'funnel_id', 'id'
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
