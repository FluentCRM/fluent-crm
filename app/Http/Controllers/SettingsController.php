<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
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
                    'placeholder' => __('Optin Email Subject', 'fluent-crm'),
                    'label'       => __('Email Subject', 'fluent-crm'),
                    'help'        => __('Your double-optin email subject', 'fluent-crm')
                ],
                'email_body'            => [
                    'type'        => 'wp-editor',
                    'placeholder' => __('Double-Optin Email Body', 'fluent-crm'),
                    'label'       => __('Email Body', 'fluent-crm'),
                    'help'        => __('Provide Email Body for the double-optin', 'fluent-crm'),
                    'inline_help' => 'Use #activate_link# for plain url or {{crm.activate_button|Confirm Subscription}} for default button'
                ],
                'design_template'       => [
                    'type'    => 'image-radio',
                    'label'   => __('Design Template', 'fluent-crm'),
                    'help'    => __('Email Design Template for this double-optin email', 'fluent-crm'),
                    'options' => Helper::getEmailDesignTemplates()
                ],
                'after_confirm_message' => [
                    'type'        => 'wp-editor',
                    'placeholder' => __('After Confirmation Message', 'fluent-crm'),
                    'label'       => __('After Confirmation Message', 'fluent-crm'),
                    'help'        => __('This message will be shown after a subscriber confirm subscription', 'fluent-crm')
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
            'email_subject.required'         => __('Email Subject is required', 'fluent-crm'),
            'email_body.required'            => __('Email Body is required', 'fluent-crm'),
            'after_confirm_message.required' => __('After Confirmation Message is required', 'fluent-crm')
        ]);

        // let's check if message contains #activate_link# or {{crm.activate_button
        $emailBody = $settings['email_body'];

        if (
            strpos($emailBody, '#activate_link#') === false &&
            strpos($emailBody, '{{crm.activate_button') === false
        ) {
            return $this->sendError([
                'message' => __('Email Body need to contains activation link', 'fluent-crm')
            ]);
        }

        fluentcrm_update_option('double_optin_settings', $settings);

        return $this->sendSuccess([
                'message' => __('Double Opt-in settings has been updated', 'fluent-crm')
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
                'message' => __('Sorry, You do not have admin permission to reset database', 'fluent-crm')
            ]);
        }

        if (!defined('FLUENTCRM_IS_DEV_FEATURES') || !FLUENTCRM_IS_DEV_FEATURES) {
            return $this->sendError([
                'message' => __('Development mode is not activated. So you can not use this feature. You can define "FLUENTCRM_IS_DEV_FEATURES" in your wp-config to enable this feature', 'fluent-crm')
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
            'message' => __('All FluentCRM Database Tables have been resetted', 'fluent-crm'),
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

        $data = [
            'bounce_settings' => [
                'ses' => site_url('?fluentcrm=1&route=bounce_handler&provider=ses&verify_key=' . $sesBounceKey)
            ]
        ];

        if(defined('FLUENTMAIL')) {
            $smtpSettings = get_option('fluentmail-settings', []);
            if(!$smtpSettings || !count($smtpSettings['connections'])) {
                $data['fluentsmtp_info'] = [
                    'configured' => false
                ];
            } else {
                $data['fluentsmtp_info'] = [
                    'configured' => true,
                    'verified_senders' => array_keys($smtpSettings['mappings'])
                ];
            }
            $data['fluentsmtp_info']['config_url'] = admin_url('options-general.php?page=fluent-mail#/connections');
        } else {
            $data['fluentsmtp_info'] = false;
        }

        return $data;
    }

    public function getAutoSubscribeSettings(Request $request)
    {
        $autoSubscribeService = new AutoSubscribe();

        $data = [
            'registration_setting' => $autoSubscribeService->getRegistrationSettings(),
            'comment_settings'     => $autoSubscribeService->getCommentSettings(),
            'user_syncing_settings' => $autoSubscribeService->getUserSyncSettings()
        ];

        $with = $request->get('with', []);
        if (in_array('fields', $with)) {
            $data['registration_fields'] = $autoSubscribeService->getRegistrationFields();
            $data['comment_fields'] = $autoSubscribeService->getCommentFields();
            $data['user_syncing_fields'] = $autoSubscribeService->getUserSyncFields();
        }

        if(defined('WC_PLUGIN_FILE') && defined('FLUENTCAMPAIGN_DIR_FILE')) {
            $data['woo_checkout_fields'] = $autoSubscribeService->getWooCheckoutFields();
            $data['woo_checkout_settings'] = $autoSubscribeService->getWooCheckoutSettings();
        }

        return $data;
    }

    public function saveAutoSubscribeSettings(Request $request)
    {
        $registrationSettings = $request->get('registration_setting', []);
        $commentSettings = $request->get('comment_settings', []);
        $userSyncSettings = $request->get('user_syncing_settings', []);


        fluentcrm_update_option('user_registration_subscribe_settings', $registrationSettings);
        fluentcrm_update_option('comment_form_subscribe_settings', $commentSettings);
        fluentcrm_update_option('user_syncing_settings', $userSyncSettings);

        if(defined('WC_PLUGIN_FILE') && defined('FLUENTCAMPAIGN_DIR_FILE')) {
            $wooCheckoutSettings = $request->get('woo_checkout_settings');
            fluentcrm_update_option('woo_checkout_form_subscribe_settings', $wooCheckoutSettings);
        }

        return [
            'message' => __('Settings has been updated', 'fluent-crm')
        ];
    }


    public function getCronStatus()
    {

        $hookNames = [
            'fluentcrm_scheduled_minute_tasks' => __('Scheduled Email Sending', 'fluent-crm'),
            'fluentcrm_scheduled_hourly_tasks' => __('Scheduled Automation Tasks', 'fluent-crm')
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
            'fluentcrm_scheduled_minute_tasks' => __('Scheduled Email Sending', 'fluent-crm'),
            'fluentcrm_scheduled_hourly_tasks' => __('Scheduled Automation Tasks', 'fluent-crm')
        ];

        if(!isset($hookNames[$hookName])) {
            return $this->sendError([
                'message' => __('The provided hook name is not valid', 'fluent-crm')
            ]);
        }

        do_action($hookName);

        return [
            'message' => __('Selected CRON Event successfully ran', 'fluent-crm')
        ];
    }

    public function getOldLogDetails(Request $request)
    {
        $data =  $request->all();
        $this->validate($data, [
            'days_before' => 'required|numeric|min:7',
            'selected_logs' => 'array|required'
        ]);

        $selectedLogs = $data['selected_logs'];
        $daysBefore = $data['days_before'];

        $refDate = date('Y-m-d 00:00:01', time() - $daysBefore * 86400);

        $dataCounters = [];
        if(in_array('emails', $selectedLogs)) {
            $dataCounters[] = [
                'title' => __('Email History Logs', 'fluent-crm'),
                'count' => CampaignEmail::where('created_at', '<', $refDate)
                    ->where('status', 'sent')
                    ->count()
            ];
        }

        if(in_array('email_clicks', $selectedLogs)) {
            $dataCounters[] = [
                'title' => __('Email clicks', 'fluent-crm'),
                'count' => CampaignUrlMetric::where('type', 'click')
                    ->where('created_at', '<',  $refDate)
                    ->count()
            ];
        }

        if(in_array('email_open', $selectedLogs)) {
            $dataCounters[] = [
                'title' => __('Email clicks', 'fluent-crm'),
                'count' => CampaignUrlMetric::where('type', 'open')
                    ->where('created_at', '<',  $refDate)
                    ->count()
            ];
        }

        return [
            'log_counts' => $dataCounters
        ];
    }

    public function removeOldLogs(Request $request)
    {
        $data =  $request->all();
        $this->validate($data, [
            'days_before' => 'required|numeric|min:7',
            'selected_logs' => 'array|required'
        ]);

        $selectedLogs = $data['selected_logs'];
        $daysBefore = $data['days_before'];

        $refDate = date('Y-m-d 00:00:01', time() - $daysBefore * 86400);
        if(in_array('emails', $selectedLogs)) {
            CampaignEmail::where('created_at', '<', $refDate)
                ->where('status', 'sent')
                ->delete();
        }
        $urlMetricsTypes = [];
        if(in_array('email_clicks', $selectedLogs)) {
            $urlMetricsTypes[] = 'click';
        }
        if(in_array('email_open', $selectedLogs)) {
            $urlMetricsTypes[] = 'open';
        }

        if($urlMetricsTypes) {
            CampaignUrlMetric::whereIn('type', $urlMetricsTypes)
                ->where('created_at', '<',  $refDate)
                ->delete();
        }

        return [
            'message' => sprintf(__('Logs older than %d days have been deleted successfully', 'fluent-crm'), $daysBefore)
        ];
    }

}
