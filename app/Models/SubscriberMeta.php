<?php

namespace FluentCrm\App\Models;

/**
 *  SubscriberMeta Model - DB Model for Contact meta data
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */

class SubscriberMeta extends Model
{
    protected $table = 'fc_subscriber_meta';

    protected $guarded = ['id'];

    /**
     * One2One: SubscriberNote belongs to one Subscriber
     * @return \FluentCrm\Framework\Database\Orm\Relations\BelongsTo
     */
    public function subscriber()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Subscriber', 'subscriber_id', 'id'
        );
    }

    public function scopeFilterByKey($query, $key)
    {
        if ($key) {
            $query->where('key', $key);
        }

        return $query;
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = maybe_serialize($value);
    }

    public function getValueAttribute($value)
    {
        return maybe_unserialize($value);
    }
}
