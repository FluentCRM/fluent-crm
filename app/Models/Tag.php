<?php

namespace FluentCrm\App\Models;

class Tag extends Model
{
    protected $table = 'fc_tags';

    /**
     * Get all of the subscribers that belongs to the tag.
     *
     * @return \WPManageNinja\WPOrm\Relation\BelongsToMany
     */
    public function subscribers()
    {
        return $this->belongsToMany(
            __NAMESPACE__.'\Subscriber', 'fc_subscriber_pivot', 'object_id', 'subscriber_id'
        )->where('object_type', __CLASS__);
    }
}
