<?php

namespace FluentCrm\App\Models;

/**
 *  SubscriberNote Model - DB Model for Contact's notes
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class EventTracker extends Model
{
    protected $table = 'fc_event_tracking';

    protected $guarded = ['id'];

    protected $fillable = [
        'subscriber_id',
        'counter',
        'created_by',
        'provider',
        'event_key',
        'title',
        'value'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = $model->created_by ?: get_current_user_id();
        });

    }

    /**
     * One2One: SubscriberNote belongs to one Subscriber
     * @return \FluentCrm\Framework\Database\Orm\Relations\BelongsTo
     */
    public function subscriber()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Subscriber', 'subscriber_id', 'id'
        );
    }
}
