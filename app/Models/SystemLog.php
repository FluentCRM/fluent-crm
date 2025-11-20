<?php

namespace FluentCrm\App\Models;

/**
 *  System Log Model - DB Model for System Logs & Activities
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class SystemLog extends Model
{
    protected $table = 'fc_subscriber_notes';

    protected $guarded = ['id'];

    protected $fillable = [
        'subscriber_id',
        'parent_id',
        'created_by',
        'type',
        'title',
        'description',
        'created_at'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = fluentCrmTimestamp();
            }

            if (empty($model->subscriber_id)) {
                $model->subscriber_id = 0;
            }

            $model->status = '_system_log_';

            $model->updated_at = fluentCrmTimestamp();
        });

        static::updated(function ($model) {
            $model->updated_at = fluentCrmTimestamp();
        });

        static::addGlobalScope('status', function ($builder) {
            $builder->where('status', '_system_log_');
        });
    }
}
