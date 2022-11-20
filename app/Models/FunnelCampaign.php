<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;

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
                'mailer_settings' =>  [
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
                $emailBody = apply_filters('fluentcrm_parse_campaign_email_text', $emailBody, $refSubscriber);
                $emailSubject = apply_filters('fluentcrm_parse_campaign_email_text', $emailSubject, $refSubscriber);
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

    public function processAndSubscribe($subscriber, $refData = [], $args = [])
    {
        foreach ($refData as $refKey => $data) {
            $subscriber->{$refKey} = $data;
        }

        // We have to handle manually
     //   $emailBody = (new BlockParser($this->subscriber))->parse($this->email_body);
       // $args['email_body'] = apply_filters('fluentcrm_parse_campaign_email_text', $emailBody, $subscriber);
      //  $args['email_subject'] = apply_filters('fluentcrm_parse_campaign_email_text', $this->email_subject, $subscriber);;
       // $args['is_parsed'] = 1;

        return $this->subscribe([$subscriber], $args, true);
    }
}
