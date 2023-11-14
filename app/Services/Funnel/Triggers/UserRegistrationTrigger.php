<?php

namespace FluentCrm\App\Services\Funnel\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;

class UserRegistrationTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'user_register';
        $this->priority = 10;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category'    => __('WordPress Triggers', 'fluent-crm'),
            'label'       => __('New User Sign Up', 'fluent-crm'),
            'description' => __('This Funnel will be initiated when a new user has been registered in your site', 'fluent-crm'),
            'icon'        => 'fc-icon-wp_new_user_signup',
        ];
    }

    public function getFunnelSettingsDefaults()
    {
        return [
            'subscription_status' => 'subscribed'
        ];
    }

    public function getSettingsFields($funnel)
    {
        return [
            'title'     => __('New User Sign Up Funnel', 'fluent-crm'),
            'sub_title' => __('This Funnel will be initiated when a new user has been registered in your site', 'fluent-crm'),
            'fields'    => [
                'subscription_status' => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => __('Subscription Status', 'fluent-crm'),
                    'placeholder' => __('Select Status', 'fluent-crm')
                ],
                'subscription_status_info' => [
                    'type' => 'html',
                    'info' => '<b>'.__('An Automated double-optin email will be sent for new subscribers', 'fluent-crm').'</b>',
                    'dependency'  => [
                        'depends_on'    => 'subscription_status',
                        'operator' => '=',
                        'value'    => 'pending'
                    ]
                ]
            ]
        ];
    }

    public function getFunnelConditionDefaults($funnel)
    {
        return [
            'update_type'  => 'update', // skip_all_actions, skip_update_if_exist
            'user_roles'   => []
        ];
    }

    public function getConditionFields($funnel)
    {
        return [
            'update_type'  => [
                'type'    => 'radio',
                'label'   => __('If Contact Already Exist?', 'fluent-crm'),
                'help'    => __('Please specify what will happen if the subscriber already exist in the database', 'fluent-crm'),
                'options' => FunnelHelper::getUpdateOptions()
            ],
            'user_roles'   => [
                'type'        => 'multi-select',
                'is_multiple' => true,
                'label'       => __('Targeted User Roles', 'fluent-crm'),
                'help'        => __('Select which roles registration will run this automation Funnel', 'fluent-crm'),
                'placeholder' => __('Select Roles', 'fluent-crm'),
                'options'     => FunnelHelper::getUserRoles(),
                'inline_help' => __('Leave blank to run for all user roles', 'fluent-crm')
            ]
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        $userId = $originalArgs[0];
        $subscriberData = FunnelHelper::prepareUserData($userId);
        if (empty($subscriberData['email'])) {
            return;
        }


        $willProcess = $this->isProcessable($funnel, $subscriberData);
        $willProcess = apply_filters('fluentcrm_funnel_will_process_' . $this->triggerName, $willProcess, $funnel, $subscriberData, $originalArgs);
        if (!$willProcess) {
            return;
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);
        $subscriberData['status'] = $subscriberData['subscription_status'];
        unset($subscriberData['subscription_status']);

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id' => $userId
        ]);

    }

    private function isProcessable($funnel, $subscriberData)
    {
        $user = get_user_by('ID', $subscriberData['user_id']);

        $conditions = $funnel->conditions;
        // check update_type
        $updateType = Arr::get($conditions, 'update_type');

        $subscriber = FunnelHelper::getSubscriber($subscriberData['email']);
        if ($updateType == 'skip_all_if_exist' && $subscriber) {
            return false;
        }

        // check run_only_one
        if ($subscriber && FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id)) {
            return false;
        }

        // check user roles
        if ($roles = Arr::get($conditions, 'user_roles', [])) {
            $userRoles = array_values($user->roles);
            return !!array_intersect($userRoles, $roles);
        }

        return true;
    }
}
