<?php

namespace FluentCrm\App\Services\Funnel\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Includes\Helpers\Arr;

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
            'category'    => 'WordPress Triggers',
            'label'       => 'New User Sign Up',
            'description' => 'This Funnel will be initiated when a new user will be registered in your site'
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
            'title'     => 'New User Sign Up Funnel',
            'sub_title' => 'This Funnel will be initiated when a new user will be registered in your site',
            'fields'    => [
                'subscription_status' => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => 'Subscription Status',
                    'placeholder' => 'Select Status'
                ],
                'subscription_status_info' => [
                    'type' => 'html',
                    'info' => '<b>An Automated double-optin email will be sent for new subscribers</b>',
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
            'run_only_one' => 'no',
            'user_roles'   => []
        ];
    }

    public function getConditionFields($funnel)
    {
        return [
            'update_type'  => [
                'type'    => 'radio',
                'label'   => 'If Exist?',
                'help'    => 'Please specify what will happen if the subscriber already exist in the database',
                'options' => FunnelHelper::getUpdateOptions()
            ],
            'run_only_one' => [
                'type'        => 'yes_no_check',
                'check_label' => 'Run this Funnel only once for each subscriber'
            ],
            'user_roles'   => [
                'type'        => 'multi-select',
                'is_multiple' => true,
                'label'       => 'User Roles',
                'help'        => 'Select which roles registration will run this automation Funnel',
                'placeholder' => 'Select Roles',
                'options'     => FunnelHelper::getUserRoles(),
                'inline_help' => 'Leave blank to run for all user roles'
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
        if ($updateType == 'skip_all_if_exist') {
            return false;
        }

        // check run_only_one
        if ($subscriber && FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id)) {
            return false;
        }

        // check user roles
        if ($roles = Arr::get($conditions, 'user_roles', [])) {
            return !!array_intersect($user->roles, $roles);
        }

        return true;
    }
}
