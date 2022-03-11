<?php

namespace FluentCrm\App\Models;

/**
 *  FunnelSubscriber Model - DB Model for Automation Subscribers
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */

class FunnelSubscriber extends Model
{
    protected $table = 'fc_funnel_subscribers';

    protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function funnel()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Funnel', 'funnel_id', 'id'
        );
    }

    public function next_sequence_item()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\FunnelSequence', 'next_sequence_id', 'id'
        );
    }

    public function last_sequence()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\FunnelSequence', 'last_sequence_id', 'id'
        );
    }

    public function metrics()
    {
        return $this->hasMany(
            __NAMESPACE__ . '\FunnelMetric', 'subscriber_id', 'subscriber_id'
        );
    }

    public function subscriber()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Subscriber', 'subscriber_id', 'id'
        );
    }

}
