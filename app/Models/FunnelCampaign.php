<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;

class FunnelCampaign extends Campaign
{
    protected static $type = 'funnel_email_campaign';

    public static function getMock()
    {
        $defaultTemplate = Helper::getDefaultEmailTemplate();
        return [
            'id'               => '',
            'parent_id'        => '',
            'title'            => 'Funnel Campaign Holder',
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
                'template_config' => Helper::getTemplateConfig($defaultTemplate)
            ]
        ];
    }

    public function sendToCustomAddresses($addresses = [], $args = [])
    {
        if (!$addresses) {
            return;
        }
        $time = current_time('mysql');
        foreach ($addresses as $address) {
            if (!is_email($address)) {
                return;
            }

            // check if the email has any subscriber
            $subscriber = Subscriber::where('email', $address)->first();
            if ($subscriber && $subscriber->status == 'subscribed') {
                $this->subscribe([$subscriber->id], $args);
            } else {
                // We have to handle manually
                $emailBody = (new BlockParser())->parse($this->email_body);
                $email = [
                    'campaign_id'   => $this->id,
                    'email_address' => $address,
                    'email_subject' => $this->email_subject,
                    'email_body'    => $emailBody,
                    'created_at'    => $time,
                    'updated_at'    => $time,
                    'is_parsed'     => 1,
                    'note'          => 'Email Sent From Funnel'
                ];

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
    }
}
