<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Models\SubscriberMeta;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Models\SubscriberPivot;

/**
 *  Cleanup Class
 *
 * Used to handle cleanup related assets for subscribers, campaigns and automations.
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class Cleanup
{
    /**
     * Cleanup related data of a subscriber.
     *
     * @param array $subscriberIds
     */
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
            \FluentCampaign\App\Models\SequenceTracker::whereIn('subscriber_id', $subscriberIds)->delete();
        }
    }

    /**
     * Cleanup related data of a campaign.
     *
     * @param int $campaignId
     */
    public function deleteCampaignAssets($campaignId)
    {
        CampaignEmail::where('id', $campaignId)->delete();
        CampaignUrlMetric::where('campaign_id', $campaignId)->delete();
    }

    /**
     * Cleanup related data of a list.
     *
     * @param int $listId
     */
    public function deleteListAssets($listId)
    {
        SubscriberPivot::where('object_type', 'FluentCrm\App\Models\Lists')->where('object_id', $listId)->delete();
    }

    /**
     * Cleanup related data of a tag.
     *
     * @param int $listId
     */
    public function deleteTagAssets($listId)
    {
        SubscriberPivot::where('object_type', 'FluentCrm\App\Models\Tag')->where('object_id', $listId)->delete();
    }

    /**
     * Cancel Future Emails.
     *
     * @param \FluentCrm\App\Models\Subscriber $subscriber
     */
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

        if (defined('FLUENTCAMPAIGN')) {
            \FluentCampaign\App\Models\SequenceTracker::where('subscriber_id', $subscriber->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled'
                ]);
        }
    }

    /**
     * Change the future emails email_address of a provided contact.
     *
     * @param \FluentCrm\App\Models\Subscriber $subscriber
     */
    public function handleContactEmailChanged($subscriber)
    {
        CampaignEmail::where('subscriber_id', $subscriber->id)
            ->whereIn('status', ['draft', 'scheduled'])
            ->update([
                'email_address' => $subscriber->email
            ]);
    }
}
