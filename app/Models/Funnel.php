<?php

namespace FluentCrm\App\Models;

/**
 *  Funnel Model - DB Model for Automation Funnels
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class Funnel extends Model
{
    private static $type = 'funnels';

    protected $table = 'fc_funnels';

    protected $guarded = ['id'];

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
            $builder->where('fc_funnels.type', '=', self::$type);
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

    public function getSubscribersCount()
    {
        return $this->subscribers()->count();
    }
}
