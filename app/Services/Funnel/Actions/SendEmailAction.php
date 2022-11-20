<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\FunnelCampaign;
use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Framework\Support\Arr;

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
            'category'    => __('Email', 'fluent-crm'),
            'title'       => __('Send Custom Email', 'fluent-crm'),
            'description' => __('Send a custom Email to your subscriber or custom email address', 'fluent-crm'),
            'icon'        => 'fc-icon-send_campaign',//fluentCrmMix('images/funnel_icons/custom_email.svg'),
            'settings'    => [
                'reference_campaign' => '',
                'send_email_to_type' => 'contact',
                'send_email_custom'  => '',
                'campaign'           => FunnelCampaign::getMock(),
                'is_scheduled'       => 'no',
                'scheduled_at'       => '',
                'skip_if_overdue'    => 'no',
                'mailer_settings'    => [
                    'from_name'      => '',
                    'from_email'     => '',
                    'reply_to_name'  => '',
                    'reply_to_email' => '',
                    'is_custom'      => 'no'
                ]
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => __('Send Custom Email', 'fluent-crm'),
            'sub_title' => __('Please provide email details that you want to send', 'fluent-crm'),
            'fields'    => [
                'send_email_to_type' => [
                    'type'          => 'radio',
                    'wrapper_class' => 'fc_half_field',
                    'label'         => __('Send Email to', 'fluent-crm'),
                    'options'       => [
                        [
                            'id'    => 'contact',
                            'title' => __('Send To the contact', 'fluent-crm')
                        ],
                        [
                            'id'    => 'custom',
                            'title' => __('Send to Custom Email Address', 'fluent-crm')
                        ]
                    ]
                ],
                'send_email_custom'  => [
                    'wrapper_class' => 'fc_half_field',
                    'type'          => 'input-text',
                    'label'         => __('Send To Email Addresses (If Custom)', 'fluent-crm'),
                    'placeholder'   => __('Custom Email Addresses', 'fluent-crm'),
                    'inline_help'   => __('Use comma separated values for multiple', 'fluent-crm'),
                    'dependency'    => [
                        'depends_on' => 'send_email_to_type',
                        'operator'   => '=',
                        'value'      => 'custom'
                    ]
                ],
                'campaign'           => [
                    'label' => '',
                    'wrapper_class' => 'fc_email_writer',
                    'type'  => 'email_campaign_composer'
                ],
                'is_scheduled'       => [
                    'type'          => 'yes_no_check',
                    'check_label'   => __('Schedule this email to a specific date', 'fluent-crm'),
                    'wrapper_class' => 'fc_half_field fc_no_pad_l',
                ],
                'skip_if_overdue'    => [
                    'type'          => 'yes_no_check',
                    'wrapper_class' => 'fc_half_field',
                    'check_label'   => __('Skip sending email if date is overdued', 'fluent-crm'),
                    'dependency'    => [
                        'depends_on' => 'is_scheduled',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'scheduled_at'       => [
                    'label'         => __('Schedule Date and Time', 'fluent-crm'),
                    'type'          => 'date_time',
                    'wrapper_class' => 'fc_no_marg_b',
                    'placeholder'   => __('Select Date and Time', 'fluent-crm'),
                    'inline_help'   => __('If schedule date is past in the runtime then email will be sent immediately', 'fluent-crm'),
                    'dependency'    => [
                        'depends_on' => 'is_scheduled',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'mailer_settings'    => [
                    'type'        => 'custom_sender_config',
                    'check_label' => __('Set Custom From Name and Email', 'fluent-crm')
                ]
            ]
        ];
    }

    public function savingAction($sequence, $funnel)
    {
        $funnelCampaign = Arr::get($sequence, 'settings.campaign', []);
        $funnelCampaignId = Arr::get($funnelCampaign, 'id');

        $isStripped = Arr::get($sequence, 'is_stripped');
        if ($isStripped && $funnelCampaignId) {
            $refCampaign = FunnelCampaign::find($funnelCampaignId);
            $data = Arr::only($refCampaign->toArray(), array_keys(FunnelCampaign::getMock()));
        } else {
            $data = Arr::only($funnelCampaign, array_keys(FunnelCampaign::getMock()));
        }

        $sequenceId = Arr::get($sequence, 'id');

        $data['settings']['mailer_settings'] = Arr::get($sequence, 'settings.mailer_settings', []);

        if ($funnelCampaignId && $funnel->id == Arr::get($data, 'parent_id')) {
            // We have this campaign
            $data['settings'] = \maybe_serialize($data['settings']);
            $data['type'] = 'funnel_email_campaign';
            $data['title'] = $funnel->title . ' (' . $funnel->id . ' - ' . $sequenceId . ')';
            FunnelCampaign::where('id', $funnelCampaignId)->update($data);
        } else {
            $data['parent_id'] = $funnel->id;
            $data['type'] = 'funnel_email_campaign';
            $data['title'] = $funnel->title . ' (' . $funnel->id . ' - ' . $sequenceId . ')';
            $campaign = FunnelCampaign::create($data);
            $funnelCampaignId = $campaign->id;

            if ($data['design_template'] == 'visual_builder' && !empty($funnelCampaign['_visual_builder_design'])) {
                fluentcrm_update_campaign_meta($funnelCampaignId, '_visual_builder_design', $funnelCampaign['_visual_builder_design']);
            }
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

                if ($refCampaignData['design_template'] == 'visual_builder') {
                    $refCampaignData['_visual_builder_design'] = fluentcrm_get_campaign_meta($refCampaignData['id'], '_visual_builder_design', true);
                }
            }
        }

        $sequence['settings']['campaign'] = $refCampaignData;

        if (empty($sequence['settings']['mailer_settings'])) {
            $sequence['settings']['mailer_settings'] = [
                'from_name'      => '',
                'from_email'     => '',
                'reply_to_name'  => '',
                'reply_to_email' => '',
                'is_custom'      => 'no'
            ];
        }

        return $sequence;
    }

    public function deleteAction($sequence, $funnel)
    {
        $refCampaign = Arr::get($sequence->settings, 'reference_campaign');
        if ($refCampaign) {
            FunnelCampaign::where('id', $refCampaign)->delete();
            CampaignEmail::where('campaign_id', $refCampaign)->delete();
            CampaignUrlMetric::where('campaign_id', $refCampaign)->delete();
            fluentcrm_delete_campaign_meta($refCampaign, '');
        }
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        $settings = $sequence->settings;
        $refCampaign = Arr::get($settings, 'reference_campaign');
        if (!$refCampaign) {
            FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
            return;
        }

        $campaign = FunnelCampaign::find($refCampaign);

        if (!$campaign) {
            return;
        }

        $scheduledAt = current_time('mysql');
        if (Arr::get($settings, 'is_scheduled') == 'yes') {
            $providedDate = Arr::get($settings, 'scheduled_at');

            if ($providedDate && Arr::get($settings, 'skip_if_overdue') == 'yes') {
                if (strtotime($scheduledAt) > strtotime($providedDate)) {
                    FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
                    return;
                }
            }

            if ($providedDate && strtotime($providedDate) > strtotime($scheduledAt)) {
                $scheduledAt = $providedDate;
            }
        }

        $args = [
            'status'       => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'email_type'   => 'funnel_email_campaign',
            'note'         => __('Email Sent From Funnel: ', 'fluent-crm') . $campaign->title
        ];

        if (Arr::get($settings, 'send_email_to_type') == 'contact') {

            $campaign->processAndSubscribe($subscriber, [
                'funnel_subscriber_id' => $funnelSubscriberId
            ], $args);

            do_action('fluentcrm_process_contact_jobs', $subscriber);
        } else if ($customAddresses = Arr::get($settings, 'send_email_custom')) {
            $customAddresses = array_map('trim', explode(',', $customAddresses));
            $campaign->sendToCustomAddresses($customAddresses, $args, $subscriber);
        }
    }
}
