<?php

namespace FluentCrm\App\Models;

class SubscriberNote extends Model
{
    protected $table = 'fc_subscriber_notes';

    protected $fillable = [
        'subscriber_id',
        'parent_id',
        'created_by',
        'status',
        'type',
        'title',
        'description'
    ];

    public static function boot()
    {
        static::creating(function ($model) {
            $model->created_at = fluentCrmTimestamp();
            $model->updated_at = fluentCrmTimestamp();
            $model->created_by = $model->created_by ?: get_current_user_id();
        });

        static::updated(function ($model) {
            $model->updated_at = fluentCrmTimestamp();
        });
    }
    /**
     * One2One: SubscriberNote belongs to one Subscriber
     * @return Model
     */
    public function subscriber()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Subscriber', 'subscriber_id', 'id'
        );
    }

    /**
     * One2One: SubscriberNote belongs to one User
     * @return Model
     */
    public function added_by()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\User', 'created_by', 'ID'
        );
    }

    public function markAs($status)
    {
        $this->status = $status;
        $this->save();
        return $this;
    }
}
