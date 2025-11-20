<?php

namespace FluentCrm\App\Models;

/**
 *  Activity Log Model - DB Model for Activity Logs
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class ActivityLog extends Model
{
    protected $table = 'fc_activity_logs';

    protected $guarded = ['id'];

    protected $fillable = [
        'object_type',
        'object_id',
        'action',
        'source',
        'description',
        'activity_by',
        'created_at',
        'updated_at'
    ];

    // Ensure the key is added to every serialized row
    protected $appends = ['activity_by_email'];

    // Cast the description column to an array (or object)
    protected $casts = [
        'description' => 'array', // Automatically decodes JSON to array
        // Use 'object' instead of 'array' if you prefer stdClass objects
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = fluentCrmTimestamp();
            }

            if (empty($model->activity_by)) {
                $model->activity_by = 0;
            }

            $model->updated_at = fluentCrmTimestamp();
        });

        static::updated(function ($model) {
            $model->updated_at = fluentCrmTimestamp();
        });
    }

    public function getActivityByEmailAttribute()
    {
        $user = User::where('ID', $this->activity_by)->first();
        return $user->display_name . ' (' . $user->user_email . ')';
    }

}
