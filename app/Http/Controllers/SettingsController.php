<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Services\AutoSubscribe;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Request\Request;

class SettingsController extends Controller
{
    public function get(Request $request)
    {
        $existingSettings = get_option(FLUENTCRM . '-global-settings');

        $keys = $request->get('settings_keys', []);

        $returnSettings = [];
        foreach ($keys as $key) {
            if ($key == 'email_settings') {
                $returnSettings[$key] = Helper::getGlobalEmailSettings();
            } else if (isset($existingSettings[$key])) {
                $returnSettings[$key] = $existingSettings[$key];
            }
        }

        return $this->sendSuccess(
            $returnSettings
        );
    }

    public function save(Request $request)
    {
        $settings = $request->get('settings');

        $existingSettings = get_option(FLUENTCRM . '-global-settings');

        if (!$existingSettings) {
            $existingSettings = [];
        }

        foreach ($settings as $settingsKey => $setting) {
            $existingSettings[$settingsKey] = $setting;
        }

        update_option(
            FLUENTCRM . '-global-settings', $existingSettings
        );

        return $this->sendSuccess([
            'message' => __('Settings Updated', 'fluent-crm')
        ]);
    }

    public function getDoubleOptinSettings(Request $request)
    {
        $data = [
            'settings' => Helper::getDoubleOptinSettings()
        ];

        if (in_array('settings_fields', $request->get('with', []))) {
            $data['settings_fields'] = [
                'email_subject'         => [
                    'type'        => 'input-text-popper',
                    'placeholder' => 'Optin Email Subject',
                    'label'       => 'Email Subject',
                    'help'        => 'Your double-optin email subject'
                ],
                'email_body'            => [
                    'type'        => 'wp-editor',
                    'placeholder' => 'Double-Optin Email Body',
                    'label'       => 'Email Body',
                    'help'        => 'Provide Email Body for the double-optin',
                    'inline_help' => 'Use #activate_link# for plain url or {{crm.activate_button|Confirm Subscription}} for default button'
                ],
                'design_template'       => [
                    'type'    => 'image-radio',
                    'label'   => 'Design Template',
                    'help'    => 'Email Design Template for this double-optin email',
                    'options' => Helper::getEmailDesignTemplates()
                ],
                'after_confirm_message' => [
                    'type'        => 'wp-editor',
                    'placeholder' => 'After Confirmation Message',
                    'label'       => 'After Confirmation Message',
                    'help'        => 'This message will be shown after a subscriber confirm subscription'
                ]
            ];
        }

        return $this->sendSuccess($data);
    }

    public function saveDoubleOptinSettings(Request $request)
    {
        $settings = wp_unslash($request->get('settings'));

        $this->validate($settings, [
            'email_subject'         => 'required',
            'email_body'            => 'required',
            'after_confirm_message' => 'required'
        ], [
            'email_subject.required'         => 'Email Subject is required',
            'email_body.required'            => 'Email Body is required',
            'after_confirm_message.required' => 'After Confirmation Message is required'
        ]);

        // let's check if message contains #activate_link# or {{crm.activate_button
        $emailBody = $settings['email_body'];

        if (
            strpos($emailBody, '#activate_link#') === false &&
            strpos($emailBody, '{{crm.activate_button') === false
        ) {
            return $this->sendError([
                'message' => 'Email Body need to contains activation link'
            ]);
        }

        fluentcrm_update_option('double_optin_settings', $settings);

        return $this->sendSuccess([
                'message' => __('Double Opt-in settings has been updated')
            ]
        );
    }

    public function TestRequestResolver(Request $request)
    {
        return [
            'message' => 'Valid',
            'params'  => $request->all()
        ];
    }

    public function resetDB(Request $request)
    {
        if (!current_user_can('manage_options')) {
            return $this->sendError([
                'message' => 'Sorry, You do not have admin permission to reset database'
            ]);
        }

        if (!defined('FLUENTCRM_IS_DEV_FEATURES') || !FLUENTCRM_IS_DEV_FEATURES) {
            return $this->sendError([
                'message' => 'Development mode is not activated. So you can not use this feature. You can define "FLUENTCRM_IS_DEV_FEATURES" in your wp-config to enable this feature'
            ]);
        }

        $tables = [
            'fc_campaign_emails',
            'fc_campaigns',
            'fc_campaign_url_metrics',
            'fc_funnel_metrics',
            'fc_funnels',
            'fc_funnel_sequences',
            'fc_funnel_subscribers',
            'fc_lists',
            'fc_meta',
            'fc_subscriber_meta',
            'fc_subscriber_notes',
            'fc_subscriber_pivot',
            'fc_subscribers',
            'fc_tags',
            'fc_url_stores'
        ];

        if (defined('FLUENTCAMPAIGN_PLUGIN_URL')) {
            $databases[] = 'fc_sequence_tracker';
        }

        global $wpdb;
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . $table);
        }
        // All tables are delete now let's run the migration
        \FluentCrm\Includes\Activator::handle(false);

        if (defined('FLUENTCAMPAIGN_PLUGIN_URL')) {
            \FluentCampaign\App\Migration\Migrate::run(false);
        }

        return [
            'message' => 'All FluentCRM Database Tables have been resetted',
            'tables'  => $tables
        ];
    }


    public function getBounceConfigs()
    {
        $sesBounceKey = fluentcrm_get_option('_fc_bounce_key');
        if (!$sesBounceKey) {
            $sesBounceKey = substr(md5(wp_generate_uuid4()), 0, 10); // first 8 digit
            fluentcrm_update_option('_fc_bounce_key', $sesBounceKey);
        }
        return [
            'bounce_settings' => [
                'ses' => site_url('?fluentcrm=1&route=bounce_handler&provider=ses&verify_key=' . $sesBounceKey)
            ]
        ];
    }

    public function getAutoSubscribeSettings(Request $request)
    {
        $autoSubscribeService = new AutoSubscribe();

        $data = [
            'registration_setting' => $autoSubscribeService->getRegistrationSettings(),
            'comment_settings'     => $autoSubscribeService->getCommentSettings()
        ];

        $with = $request->get('with', []);
        if (in_array('fields', $with)) {
            $data['registration_fields'] = $autoSubscribeService->getRegistrationFields();
            $data['comment_fields'] = $autoSubscribeService->getCommentFields();
        }

        return $data;
    }

    public function saveAutoSubscribeSettings(Request $request)
    {
        $registrationSettings = $request->get('registration_setting', []);
        $commentSettings = $request->get('comment_settings', []);

        fluentcrm_update_option('user_registration_subscribe_settings', $registrationSettings);
        fluentcrm_update_option('comment_form_subscribe_settings', $commentSettings);

        return [
            'message' => __('Settings has been updated', 'fluent-crm')
        ];
    }


    public function getCronStatus()
    {
        $hookNames = [
            'fluentcrm_scheduled_minute_tasks' => __('Scheduled Email Sending'),
            'fluentcrm_scheduled_hourly_tasks' => __('Scheduled Automation Tasks')
        ];

        $crons = _get_cron_array();
        $events = array();

        foreach ($crons as $time => $hooks) {
            foreach ($hooks as $hook => $hook_events) {
                if (!isset($hookNames[$hook])) {
                    continue;
                }
                foreach ($hook_events as $sig => $data) {
                    $events[] = (object)array(
                        'hook'       => $hook,
                        'human_name' => $hookNames[$hook],
                        'next_run'   => human_time_diff($time, time()),
                        'interval'   => $data['interval']
                    );
                }
            }
        }

        return [
            'cron_events' => $events
        ];
    }

    public function runCron(Request $request)
    {
        $hookName = $request->get('hook');
        $hookNames = [
            'fluentcrm_scheduled_minute_tasks' => __('Scheduled Email Sending'),
            'fluentcrm_scheduled_hourly_tasks' => __('Scheduled Automation Tasks')
        ];

        if(!isset($hookNames[$hookName])) {
            return $this->sendError([
                'message' => 'The provided hook name is not valid'
            ]);
        }

        do_action($hookName);

        return [
            'message' => __('Selected CRON Event successfully ran', 'fluent-crm')
        ];
    }

}
