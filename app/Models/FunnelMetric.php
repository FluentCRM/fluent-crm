<?php

namespace FluentCrm\App\Models;

class FunnelMetric extends Model
{
    protected $table = 'fc_funnel_metrics';

    public function scopeStatus($query, $status = 'completed')
    {
        return $query->where('status', $status);
    }

    public function funnel()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Funnel', 'funnel_id', 'id'
        );
    }

    public function sequence()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\FunnelSequence', 'sequence_id', 'id'
        );
    }

    public function subscriber()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Subscriber', 'subscriber_id', 'id'
        );
    }

}
