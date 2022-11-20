<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberMeta;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Models\SubscriberPivot;
use FluentCrm\App\Services\Helper;

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
            ->whereIn('status', ['pending', 'scheduled', 'draft', 'processing', 'scheduling'])
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


    /**
     * @param $userId int
     * @param $resign int|null
     * @param $deletedUser \WP_User
     * @return bool
     */
    public function handleUserDelete($userId, $resign, $deletedUser)
    {
        $settings = Helper::getComplianceSettings();
        if ($settings['delete_contact_on_user'] !== 'yes') {
            return false;
        }

        $subscriber = Subscriber::where('user_id', $userId)->first();

        if(!$subscriber && $deletedUser) {
            $subscriber = Subscriber::where('email', $deletedUser->user_email)->first();
        }

        if (!$subscriber) {
            return false;
        }

        // delete the subscriber now;
        Helper::deleteContacts([$subscriber->id]);

        return true;
    }

    public function attachCrmExporter($exporters)
    {
        $settings = Helper::getComplianceSettings();
        if ($settings['personal_data_export'] !== 'yes') {
            return $exporters;
        }

        $exporters['fluent-crm'] = [
            'exporter_friendly_name' => __('FluentCRM Data', 'fluent-crm'),
            'callback'               => [$this, 'exportPersonalDataWP'],
        ];

        return $exporters;

    }

    public function exportPersonalDataWP($user_email, $page = 1)
    {
        $subscriber = Subscriber::where('email', $user_email)->first();

        if (!$subscriber) {
            return [
                'data' => [],
                'done' => true
            ];
        }

        $customerFields = $subscriber->custom_fields();
        $mainFields = $subscriber->toArray();

        $data = [
            'group_id'    => 'fluent-crm-contact',
            'group_label' => __('Fluent CRM Data', 'fluent-crm'),
            'item_id'     => 'crm-contact',
            'data'        => []
        ];

        foreach ($mainFields as $fieldKey => $fieldValue) {
            if($fieldValue) {
                $data['data'][] = [
                    'name'  => $fieldKey,
                    'value' => $fieldValue
                ];
            }
        }

        foreach ($customerFields as $fieldKey => $customerField) {
            $data['data'][] = [
                'name'  => $fieldKey,
                'value' => $customerField
            ];
        }

        return [
            'data' => [$data],
            'done' => true,
        ];

    }
}
