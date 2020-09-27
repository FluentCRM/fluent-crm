<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
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
}
