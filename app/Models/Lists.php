<?php

namespace FluentCrm\App\Models;

class Lists extends Model
{
    protected $table = 'fc_lists';

    /**
     * Many2Many: List belongs to many Subscriber
     *
     * @return \FluentCrm\App\Models\Base\Collection
     */
    public function subscribers()
    {
        return $this->belongsToMany(
            __NAMESPACE__.'\Subscriber', 'fc_subscriber_pivot', 'object_id', 'subscriber_id'
        )->where('object_type', __CLASS__);
    }
}
