<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Models\SubscriberMeta;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Models\SubscriberPivot;

class Cleanup
{
    public function deleteSubscribersAssets($subscriberIds)
    {
        CampaignEmail::whereIn('subscriber_id', $subscriberIds)->delete();
        CampaignUrlMetric::whereIn('subscriber_id', $subscriberIds)->delete();
        SubscriberMeta::whereIn('subscriber_id', $subscriberIds)->delete();
        SubscriberNote::whereIn('subscriber_id', $subscriberIds)->delete();
        SubscriberPivot::whereIn('subscriber_id', $subscriberIds)->delete();
        FunnelMetric::whereIn('subscriber_id', $subscriberIds)->delete();
        FunnelSubscriber::whereIn('subscriber_id', $subscriberIds)->delete();

        if (defined('FLUENTCAMPAIGN_DIR_FILE')) {
            \FluentCampaign\App\Models\SequenceTracker::whereIn('subscriber_id' , $subscriberIds)->delete();
        }
    }

    public function deleteCampaignAssets($campaignId)
    {
        CampaignEmail::where('id', $campaignId)->delete();
        CampaignUrlMetric::where('campaign_id', $campaignId)->delete();
    }

    public function deleteListAssets($listId)
    {
        SubscriberPivot::where('object_type', 'FluentCrm\App\Models\Lists')->where('object_id', $listId)->delete();
    }

    public function deleteTagAssets($listId)
    {
        SubscriberPivot::where('object_type', 'FluentCrm\App\Models\Tag')->where('object_id', $listId)->delete();
    }

    public function handleUnsubscribe($subscriber)
    {
        CampaignEmail::where('subscriber_id', $subscriber->id)
            ->whereIn('status', ['pending', 'scheduled', 'draft'])
            ->update([
                'status' => 'cancelled'
            ]);

        FunnelSubscriber::where('subscriber_id', $subscriber->id)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled'
            ]);

        if(defined('FLUENTCAMPAIGN')) {
            \FluentCampaign\App\Models\SequenceTracker::where('subscriber_id', $subscriber->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled'
                ]);
        }
    }
}
