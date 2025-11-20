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
class SubscriberNote extends Model
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

            $model->updated_at = fluentCrmTimestamp();
            $model->created_by = $model->created_by ?: get_current_user_id();
        });

        static::updated(function ($model) {
            $model->updated_at = fluentCrmTimestamp();
        });

        static::addGlobalScope('status', function ($builder) {
            $builder->whereNotIn('status', ['_company_note_', '_system_log_']);
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

    public function markAs($status)
    {
        $this->status = $status;
        $this->save();
        return $this;
    }

    public function createdBy()
    {
        if (!$this->created_by) {
            return false;
        }

        $user = get_user_by('ID', $this->created_by);

        return [
            'ID'           => $user->ID,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'display_name' => $user->display_name
        ];
    }
}
