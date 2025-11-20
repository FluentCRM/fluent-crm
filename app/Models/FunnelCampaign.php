<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

/**
 *  FunnelCampaign Model - DB Model for Automation Campaigns
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class FunnelCampaign extends Campaign
{
    protected static $type = 'funnel_email_campaign';

    protected $guarded = ['id'];

    public static function getMock()
    {
        $defaultTemplate = Helper::getDefaultEmailTemplate();
        return [
            'id'               => '',
            'parent_id'        => '',
            'title'            => __('Funnel Campaign Holder', 'fluent-crm'),
            'status'           => 'published',
            'template_id'      => '',
            'email_subject'    => '',
            'email_pre_header' => '',
            'email_body'       => '',
            'utm_status'       => 0,
            'utm_source'       => '',
            'utm_medium'       => '',
            'utm_campaign'     => '',
            'utm_term'         => '',
            'utm_content'      => '',
            'design_template'  => $defaultTemplate,
            'settings'         => (object)[
                'template_config' => Helper::getTemplateConfig($defaultTemplate),
                'mailer_settings' => [
                    'from_name'      => '',
                    'from_email'     => '',
                    'reply_to_name'  => '',
                    'reply_to_email' => '',
                    'is_custom'      => 'no'
                ]
            ]
        ];
    }

    public function sendToCustomAddresses($addresses = [], $args = [], $refSubscriber = false)
    {
        if (!$addresses) {
            return;
        }
        $time = current_time('mysql');
        foreach ($addresses as $address) {
            if (!is_email($address)) {
                continue;
            }

            // check if the email has any subscriber
            $subscriber = Subscriber::where('email', $address)->first();
            if ($subscriber && $subscriber->status != 'subscribed') {
                continue;
            }

            // We have to handle manually
            $emailBody = (new BlockParser($refSubscriber))->parse($this->email_body);

            $emailSubject = $this->email_subject;

            if ($refSubscriber) {
                /**
                 * Filter the campaign email body content.
                 *
                 * This filter allows you to modify the email body content before it is sent to the subscriber.
                 *
                 * @since 2.7.0
                 *
                 * @param string $emailBody The email body content.
                 * @param object $refSubscriber The subscriber object reference.
                 */
                $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $refSubscriber);
                /**
                 * Filter the email subject text for a campaign.
                 *
                 * This filter allows you to modify the email subject text before it is sent to the subscriber.
                 *
                 * @since 2.7.0
                 * 
                 * @param string $emailSubject The original email subject text.
                 * @param object $refSubscriber The subscriber object reference.
                 *
                 * @return string The filtered email subject text.
                 */
                $emailSubject = apply_filters('fluent_crm/parse_campaign_email_text', $emailSubject, $refSubscriber);
            }

            $email = [
                'campaign_id'   => $this->id,
                'email_address' => $address,
                'email_subject' => $emailSubject,
                'email_body'    => $emailBody,
                'created_at'    => $time,
                'updated_at'    => $time,
                'is_parsed'     => 1,
                'note'          => __('Email Sent From Funnel', 'fluent-crm')
            ];

            if ($subscriber) {
                $email['subscriber_id'] = $subscriber->id;
            }

            if ($args) {
                $email = wp_parse_args($email, $args);
            }

            $insertId = CampaignEmail::insert($email);
            $emailHash = Helper::generateEmailHash($insertId);

            CampaignEmail::where('id', $insertId)
                ->update([
                    'email_hash' => $emailHash
                ]);
        }
    }


    /**
     * Add one or more subscribers to the campaign
     * @param array $subscriberIds
     * @param array $emailArgs extra campaign_email args
     * @param bool $isModel if the $subscriberIds is collection or not
     * @return array
     */
    public function subscribe($subscriberIds, $emailArgs = [], $isModel = false)
    {
        $updateIds = [];

        $mailHeaders = Helper::getMailHeadersFromSettings(Arr::get($this->settings, 'mailer_settings', []));

        if ($isModel) {
            $subscribers = $subscriberIds;
        } else {
            $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();
        }

        $sendableStatuses = fluentCrmEmailSendableStatuses();

        foreach ($subscribers as $subscriber) {
            if (!in_array($subscriber->status, $sendableStatuses, true)) {
                continue; // We don't want to send emails to non-subscribed members
            }

            $time = fluentCrmTimestamp();
            $email = [
                'campaign_id'   => $this->id,
                'status'        => $this->status,
                'subscriber_id' => $subscriber->id,
                'email_address' => $subscriber->email,
                'email_headers' => $mailHeaders,
                'created_at'    => $time,
                'updated_at'    => $time,
                'email_body'    => '',
            ];
            $subjectItem = $this->guessEmailSubject();
            $emailSubject = $this->email_subject;

            // Let's create the email body here
            $rawTemplates = [
                'raw_html',
                'visual_builder'
            ];
            $emailBody = $this->email_body;
            if (!in_array($this->design_template, $rawTemplates)) {
                $emailBody = (new BlockParser($subscriber))->parse($emailBody);
            }
            $emailBody = str_replace(['https://fonts.googleapis.com/css2', 'https://fonts.googleapis.com/css'], 'https://fonts.bunny.net/css', $emailBody);
            /**
             * Filter the email body content for a campaign.
             *
             * This filter allows you to modify the email body content before it is sent to the subscriber.
             *
             * @since 2.8.44
             * 
             * @param string $emailBody The original email body content.
             * @param object $subscriber The subscriber object containing subscriber details.
             *
             * @return string The filtered email body content.
             */
            $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $subscriber);
            // email body creation done


            if ($subjectItem && !empty($subjectItem->value)) {
                $emailSubject = $subjectItem->value;
                $email['email_subject_id'] = $subjectItem->id;
            }

            /**
             * Filter the campaign email subject text.
             *
             * This filter allows you to modify the email subject text for a campaign.
             *
             * @since 2.8.40
             * 
             * @param string $emailSubject The original email subject text.
             * @param object $subscriber The subscriber object.
             *
             * @return string The filtered email subject text.
             */
            $email['email_subject'] = apply_filters('fluent_crm/parse_campaign_email_text', $emailSubject, $subscriber);

            if ($emailArgs) {
                $email = wp_parse_args($emailArgs, $email);
            }

            $inserted = CampaignEmail::create($email);

            $subscriber->campaign_id = $this->id;
            $subscriber->email_id = $inserted->id;

            $emailHash = Helper::generateEmailHash($inserted->id);

            /**
             * Filter the email body content of a campaign email.
             *
             * This filter allows you to modify the email body content before it is sent to the subscriber.
             *
             * @since 2.8.44
             * 
             * @param string $emailBody The email body content.
             * @param object $subscriber The subscriber object containing subscriber details.
             *
             * @return string The filtered email body content.
             */
            $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $subscriber);
            $urls = $this->getShortUrls($emailBody);

            if ($urls) {
                $emailBody = Helper::attachUrls($emailBody, $urls, $inserted->id, $emailHash);
            }

            CampaignEmail::where('id', $inserted->id)
                ->update([
                    'email_hash' => $emailHash,
                    'email_body' => $emailBody,
                    'is_parsed'  => 1
                ]);

            $updateIds[] = $inserted->id;
        }

        $emailCount = $this->getEmailCount();
        if ($emailCount != $this->recipients_count) {
            $this->recipients_count = $emailCount;
            $this->save();
        }

        return $updateIds;
    }

    public function processAndSubscribe($subscriber, $refData = [], $args = [])
    {
        foreach ($refData as $refKey => $data) {
            $subscriber->{$refKey} = $data;
        }

        /*
         * Note: We are not using the parse_campaign_email_text filter here
         * Have a plan to remove this below commented code
         */
        // We have to handle manually
        //  $emailBody = (new BlockParser($this->subscriber))->parse($this->email_body);
        // $args['email_body'] = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $subscriber);
        //  $args['email_subject'] = apply_filters('fluent_crm/parse_campaign_email_text', $this->email_subject, $subscriber);
        // $args['is_parsed'] = 1;

        return $this->subscribe([$subscriber], $args, true);
    }


    public function getShortUrls($emailBody)
    {
        if (!fluentcrmTrackClicking()) {
            return [];
        }

        return Helper::urlReplaces($emailBody);
    }
}
