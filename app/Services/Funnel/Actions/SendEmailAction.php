<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\FunnelCampaign;
use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\Includes\Helpers\Arr;

class SendEmailAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'send_custom_email';
        $this->priority = 10;
        parent::__construct();
        add_filter('fluentcrm_funnel_sequence_saving_' . $this->actionName, array($this, 'savingAction'), 10, 2);
        add_filter('fluentcrm_funnel_sequence_deleting_' . $this->actionName, array($this, 'deleteAction'), 10, 2);
        add_filter('fluentcrm_funnel_sequence_filtered_' . $this->actionName, array($this, 'gettingAction'), 10, 2);
    }

    public function getBlock()
    {
        return [
            'title'       => 'Send Custom Email',
            'description' => 'Send a custom Email to your subscriber or custom email address',
            'icon' => fluentCrmMix('images/funnel_icons/custom_email.svg'),
            'settings'    => [
                'reference_campaign' => '',
                'send_email_to_type' => 'contact',
                'send_email_custom'  => '',
                'campaign'           => FunnelCampaign::getMock()
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => 'Send Custom Email',
            'sub_title' => 'Please provide email details that you want to send',
            'fields'    => [
                'send_email_to_type' => [
                    'type'    => 'radio',
                    'label'   => 'Send Email to',
                    'options' => [
                        [
                            'id'    => 'contact',
                            'title' => 'Send To the contact'
                        ],
                        [
                            'id'    => 'custom',
                            'title' => 'Send to Custom Email Address'
                        ]
                    ]
                ],
                'send_email_custom'  => [
                    'type'        => 'input-text',
                    'label'       => 'Send To Email Addresses (If Custom)',
                    'placeholder' => 'Custom Email Addresses',
                    'inline_help' => 'Use comma separated values for multiple',
                    'dependency'  => [
                        'depends_on'    => 'send_email_to_type',
                        'operator' => '=',
                        'value'    => 'custom'
                    ]
                ],
                'campaign'           => [
                    'label' => '',
                    'type'  => 'email_campaign_composer'
                ]
            ]
        ];
    }


    public function savingAction($sequence, $funnel)
    {
        $funnelCampaign = Arr::get($sequence, 'settings.campaign', []);
        $funnelCampaignId = Arr::get($funnelCampaign, 'id');

        $data = Arr::only($funnelCampaign, array_keys(FunnelCampaign::getMock()));

        $sequenceId = Arr::get($sequence,'id');

        if ($funnelCampaignId) {
            // We have this campaign
            $data['settings'] = \maybe_serialize($data['settings']);
            $data['type'] = 'funnel_email_campaign';
            $data['title'] = $funnel->title . ' ('. $funnel->id . ' - '. $sequenceId . ')';
            FunnelCampaign::where('id', $funnelCampaignId)->update($data);
        } else {
            $data['parent_id'] = $funnel->id;
            $data['type'] = 'funnel_email_campaign';
            $data['title'] = $funnel->title . ' ('. $funnel->id . ' - '. $sequenceId . ')';
            $campaign = FunnelCampaign::create($data);
            $funnelCampaignId = $campaign->id;
        }

        $sequence['settings']['reference_campaign'] = $funnelCampaignId;
        $sequence['settings']['campaign'] = [];
        return $sequence;
    }

    public function gettingAction($sequence, $funnel)
    {
        $refCampaignData = FunnelCampaign::getMock();
        if ($refCampaignId = Arr::get($sequence, 'settings.reference_campaign')) {
            $refCampaign = FunnelCampaign::find($refCampaignId);
            if ($refCampaign) {
                $refCampaignData = Arr::only($refCampaign->toArray(), array_keys(FunnelCampaign::getMock()));
            }
        }
        $sequence['settings']['campaign'] = $refCampaignData;
        return $sequence;
    }

    public function deleteAction($sequence, $funnel)
    {
        if($refCampaign = Arr::get($sequence->settings, 'reference_campaign')) {
            FunnelCampaign::where('id', $refCampaign)->delete();
            CampaignEmail::where('campaign_id', $refCampaign)->delete();
            CampaignUrlMetric::where('campaign_id', $refCampaign)->delete();
        }
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        $settings = $sequence->settings;
        $refCampaign = Arr::get($settings, 'reference_campaign');
        if(!$refCampaign) {
            return;
        }

        $campaign = FunnelCampaign::find($refCampaign);
        if(!$campaign) {
            return;
        }

        $args = [
            'status' => 'scheduled',
            'scheduled_at' => current_time('mysql'),
            'email_type' => 'funnel_email_campaign',
            'note' => 'Email Sent From Funnel '.$sequence->funnel_id
        ];

        if(Arr::get($settings, 'send_email_to_type') == 'contact') {
            $campaign->subscribe([$subscriber->id], $args);
        } else if($customAddresses = Arr::get($settings, 'send_email_custom')) {
            $customAddresses = array_map('trim', explode(',', $customAddresses));
            $campaign->sendToCustomAddresses($customAddresses, $args);
        }
    }
}
