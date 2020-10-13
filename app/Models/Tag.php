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

    public function totalCount()
    {
        return wpFluent()->table('fc_subscriber_pivot')
            ->where('object_type', 'FluentCrm\App\Models\Tag')
            ->where('object_id', $this->id)
            ->count();
    }

    public function countByStatus($status = 'subscribed')
    {
        return wpFluent()->table('fc_subscriber_pivot')
            ->where('fc_subscriber_pivot.object_type', 'FluentCrm\App\Models\Tag')
            ->where('fc_subscriber_pivot.object_id', $this->id)
            ->join('fc_subscribers', 'fc_subscribers.id', '=', 'fc_subscriber_pivot.subscriber_id')
            ->where('fc_subscribers.status', $status)
            ->count();
    }
}
