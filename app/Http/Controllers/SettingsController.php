<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Hooks\Handlers\ActivationHandler;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Services\AutoSubscribe;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\RoleBasedTagging;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;

/**
 *  SettingsController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
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

                if ($key == 'business_settings') {
                    $existingSettings[$key] = fluentcrmGetGlobalSettings('business_settings');
                }

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

            if ($settingsKey == 'email_settings') {
                $emailFooter = Arr::get($setting, 'email_footer');
                if (!Helper::hasComplianceText($emailFooter)) {
                    return $this->sendError([
                        'message' => __('##crm.manage_subscription_url## or ##crm.unsubscribe_url## string is required for compliance. Please include unsubscription or manage subscription link', 'fluent-crm')
                    ]);
                }
            }
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
        $doubleOptinSettings = Helper::getDoubleOptinSettings();
        if (empty($doubleOptinSettings['tag_based_redirect'])) {
            $doubleOptinSettings['tag_based_redirect'] = 'no';
            $doubleOptinSettings['tag_redirects'] = [
                [
                    'field_key'   => [],
                    'field_value' => ''
                ]
            ];
        }
        $data = [
            'settings' => $doubleOptinSettings
        ];

        if (in_array('settings_fields', $request->get('with', []))) {

            $designTemplates = Helper::getEmailDesignTemplates();

            $designTemplates = Arr::only($designTemplates, ['simple', 'plain', 'classic', 'raw_classic']);

            $data['settings_fields'] = [
                'email_subject'            => [
                    'type'        => 'input-text-popper',
                    'placeholder' => __('Optin Email Subject', 'fluent-crm'),
                    'label'       => __('Email Subject', 'fluent-crm'),
                    'help'        => __('Your double-optin email subject', 'fluent-crm')
                ],
                'email_pre_header'         => [
                    'type'        => 'input-text-popper',
                    'placeholder' => __('Optin Email Pre Header', 'fluent-crm'),
                    'label'       => __('Email Pre Header', 'fluent-crm'),
                    'help'        => __('Your double-optin email pre header', 'fluent-crm')
                ],
                'email_body'               => [
                    'type'        => 'wp-editor',
                    'placeholder' => __('Double-Optin Email Body', 'fluent-crm'),
                    'label'       => __('Email Body', 'fluent-crm'),
                    'help'        => __('Provide Email Body for the double-optin', 'fluent-crm'),
                    'inline_help' => 'Use #activate_link# for plain url or {{crm.activate_button|Confirm Subscription}} for default button'
                ],
                'design_template'          => [
                    'type'    => 'image-radio',
                    'label'   => __('Design Template', 'fluent-crm'),
                    'help'    => __('Email Design Template for this double-optin email', 'fluent-crm'),
                    'options' => $designTemplates
                ],
                'confirmation_html_viewer' => [
                    'type'    => 'html-viewer',
                    'heading' => __('After Confirmation Actions', 'fluent-crm'),
                    'info'    => __('Please provide details after a contact confirm double option from email', 'fluent-crm') . '<hr />'
                ],
                'after_confirmation_type'  => [
                    'type'    => 'input-radio',
                    'label'   => __('After Confirmation Type', 'fluent-crm'),
                    'help'    => __('Please select what will happen once a contact confirm double-optin ', 'fluent-crm'),
                    'options' => [
                        [
                            'id'    => 'message',
                            'label' => __('Show Message', 'fluent-crm')
                        ],
                        [
                            'id'    => 'redirect',
                            'label' => __('Redirect to an URL', 'fluent-crm')
                        ]
                    ]
                ],
                'after_confirm_message'    => [
                    'type'        => 'wp-editor',
                    'placeholder' => __('After Confirmation Message', 'fluent-crm'),
                    'label'       => __('After Confirmation Message', 'fluent-crm'),
                    'help'        => __('This message will be shown after a subscriber confirm subscription', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'after_confirmation_type',
                        'operator'   => '=',
                        'value'      => 'message'
                    ]
                ],
                'after_conf_redirect_url'  => [
                    'type'        => 'input-text-popper',
                    'placeholder' => __('Redirect URL', 'fluent-crm'),
                    'label'       => __('Redirect URL', 'fluent-crm'),
                    'help'        => __('Please provide redirect URL after confirmation', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'after_confirmation_type',
                        'operator'   => '=',
                        'value'      => 'redirect'
                    ]
                ],
                'tag_based_redirect'       => [
                    'type'           => 'inline-checkbox',
                    'checkbox_label' => (defined('FLUENTCAMPAIGN')) ? __('Enable Tag based double optin redirect', 'fluent-crm') : 'Enable Tag based double optin redirect (Require FluentCRM Pro)',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'disabled'       => !defined('FLUENTCAMPAIGN')
                ],
                'tag_redirects'            => [
                    'label'                 => 'Configure your redirect URLs based on tags (Will be redirect to provided URL if any selected tag matched to the contact)',
                    'type'                  => 'form-many-drop-down-mapper',
                    'local_label'           => 'Targeted Tags',
                    'local_placeholder'     => 'Select Tags',
                    'remote_label'          => 'Redirect URL (After Double Optin Confirmation)',
                    'field_option_selector' => [
                        'option_key'  => 'tags',
                        'is_multiple' => true
                    ],
                    'remote_field_type'     => 'input-text-popper',
                    'remote_field'          => [
                        'placeholder' => 'Redirect URL'
                    ],
                    'help'                  => 'User will be redirect to the URL which match based on the tags at first match',
                    'dependency'            => [
                        'depends_on' => 'tag_based_redirect',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ],
                    'manage_serial'         => true
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
            'message' => __('Valid', 'fluent-crm'),
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
            $tables[] = 'fc_sequence_tracker';
        }

        global $wpdb;
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . $table);
        }
        // All tables are delete now let's run the migration
        (new ActivationHandler)->handle(false);

        if (defined('FLUENTCAMPAIGN_PLUGIN_URL')) {
            \FluentCampaign\App\Migration\Migrate::run(false);
        }

        $options = [
            '_fluentcrm_commerce_modules'
        ];

        foreach ($options as $option) {
            delete_option($option);
        }

        return [
            'message' => __('All FluentCRM Database Tables have been resetted', 'fluent-crm'),
            'tables'  => $tables
        ];
    }

    public function getBounceConfigs()
    {
        $securityCode = fluentcrm_get_option('_fc_bounce_key');
        if (!$securityCode) {
            $securityCode = 'fcrm_' . substr(md5(wp_generate_uuid4()), 0, 14); // first 14 digit
            fluentcrm_update_option('_fc_bounce_key', $securityCode);
        }

        $bounceSettings = [
            'ses'          => [
                'label'       => __('Amazon SES', 'fluent-crm'),
                'webhook_url' => site_url('index.php?fluentcrm=1&route=bounce_handler&provider=ses&verify_key=' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handler-with-amazon-ses/',
                'input_title' => __('Amazon SES Bounce Handler URL', 'fluent-crm'),
                'input_info'  => __('Please use this bounce handler url in your Amazon SES + SNS settings', 'fluent-crm')
            ],
            'mailgun'      => [
                'label'       => __('Mailgun', 'fluent-crm'),
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/mailgun/handle/' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handling-with-mailgun/',
                'input_title' => __('Mailgun Bounce Handler Webhook URL', 'fluent-crm'),
                'input_info'  => __('Please paste this URL into your Mailgun\'s Webhook settings to enable Bounce Handling with FluentCRM', 'fluent-crm')
            ],
            'pepipost'     => [
                'label'       => __('PepiPost', 'fluent-crm'),
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/pepipost/handle/' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handling-with-pepipost/',
                'input_title' => __('PepiPost Bounce Handler Webhook URL', 'fluent-crm'),
                'input_info'  => __('Please paste this URL into your PepiPost\'s Webhook settings to enable Bounce Handling with FluentCRM', 'fluent-crm')
            ],
            'postmark'     => [
                'label'       => __('PostMark', 'fluent-crm'),
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/postmark/handle/' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handling-with-postmark/',
                'input_title' => __('PostMark Bounce Handler Webhook URL', 'fluent-crm'),
                'input_info'  => __('Please paste this URL into your PostMark\'s Webhook settings to enable Bounce Handling with FluentCRM', 'fluent-crm')
            ],
            'sendgrid'     => [
                'label'       => __('SendGrid', 'fluent-crm'),
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/sendgrid/handle/' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handling-with-sendgrid/',
                'input_title' => __('SendGrid Bounce Handler Webhook URL', 'fluent-crm'),
                'input_info'  => __('Please paste this URL into your SendGrid\'s Webhook settings to enable Bounce Handling with FluentCRM', 'fluent-crm')
            ],
            'sparkpost'    => [
                'label'       => __('SparkPost', 'fluent-crm'),
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/sparkpost/handle/' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handling-with-sparkpost/',
                'input_title' => __('SparkPost Bounce Handler Webhook URL', 'fluent-crm'),
                'input_info'  => __('Please paste this URL into your SparkPost\'s Webhook settings to enable Bounce Handling with FluentCRM', 'fluent-crm')
            ],
            'elasticemail' => [
                'label'       => __('Elastic Email', 'fluent-crm'),
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/elasticemail/handle/' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handling-with-elastic-email/',
                'input_title' => __('Elastic Email Bounce Handler Webhook URL', 'fluent-crm'),
                'input_info'  => __('Please paste this URL into your Elastic Email\'s Webhook settings to enable Bounce Handling with FluentCRM', 'fluent-crm')
            ],
        ];

        $data = [
            'bounce_settings' => apply_filters('fluent_crm/bounce_handlers', $bounceSettings, $securityCode)
        ];

        if (defined('FLUENTMAIL')) {
            $smtpSettings = get_option('fluentmail-settings', []);
            if (!$smtpSettings || !count($smtpSettings['connections'])) {
                $data['fluentsmtp_info'] = [
                    'configured' => false
                ];
            } else {
                $data['fluentsmtp_info'] = [
                    'configured'       => true,
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

        $roleBasedTaggingClass = new RoleBasedTagging();

        $data = [
            'registration_setting'        => $autoSubscribeService->getRegistrationSettings(),
            'comment_settings'            => $autoSubscribeService->getCommentSettings(),
            'user_syncing_settings'       => $autoSubscribeService->getUserSyncSettings(),
            'role_based_tagging_settings' => $roleBasedTaggingClass->getSettings(true)
        ];

        $with = $request->get('with', []);
        if (in_array('fields', $with)) {
            $data['registration_fields'] = $autoSubscribeService->getRegistrationFields();
            $data['comment_fields'] = $autoSubscribeService->getCommentFields();
            $data['user_syncing_fields'] = $autoSubscribeService->getUserSyncFields();
            $data['role_based_tagging_settings_fields'] = $roleBasedTaggingClass->getFields();
        }

        if (defined('WC_PLUGIN_FILE')) {
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

        if (defined('FLUENTCAMPAIGN_PLUGIN_VERSION')) {
            $roleBasedSettings = $request->get('role_based_tagging_settings', []);
            fluentcrm_update_option('role_based_tagging_settings', $roleBasedSettings);
        }

        if (defined('WC_PLUGIN_FILE') && defined('FLUENTCAMPAIGN_DIR_FILE')) {
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
            'fluentcrm_scheduled_minute_tasks'      => __('Scheduled Email Sending', 'fluent-crm'),
            'fluentcrm_scheduled_hourly_tasks'      => __('Scheduled Automation Tasks', 'fluent-crm'),
            'fluentcrm_scheduled_five_minute_tasks' => __('Scheduled Email Processing', 'fluent-crm')
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
            'cron_events' => $events,
            'server'      => [
                'memory_limit'  => ini_get('memory_limit'),
                'usage_percent' => fluentCrmGetMemoryUsagePercentage()
            ]
        ];
    }

    public function runCron(Request $request)
    {
        $hookName = $request->get('hook');
        $hookNames = [
            'fluentcrm_scheduled_minute_tasks'      => __('Scheduled Email Sending', 'fluent-crm'),
            'fluentcrm_scheduled_hourly_tasks'      => __('Scheduled Automation Tasks', 'fluent-crm'),
            'fluentcrm_scheduled_five_minute_tasks' => __('Scheduled Email Processing', 'fluent-crm')
        ];

        if (!isset($hookNames[$hookName])) {
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
        $data = $request->all();
        $this->validate($data, [
            'days_before'   => 'required|numeric|min:7',
            'selected_logs' => 'array|required'
        ]);

        $selectedLogs = $data['selected_logs'];
        $daysBefore = $data['days_before'];

        $refDate = date('Y-m-d 00:00:01', time() - $daysBefore * 86400);

        $dataCounters = [];
        if (in_array('emails', $selectedLogs)) {
            $dataCounters[] = [
                'title' => __('Email History Logs', 'fluent-crm'),
                'count' => CampaignEmail::where('created_at', '<', $refDate)
                    ->where('status', 'sent')
                    ->count()
            ];
        }

        if (in_array('email_clicks', $selectedLogs)) {
            $dataCounters[] = [
                'title' => __('Email clicks', 'fluent-crm'),
                'count' => CampaignUrlMetric::where('type', 'click')
                    ->where('created_at', '<', $refDate)
                    ->count()
            ];
        }

        if (in_array('email_open', $selectedLogs)) {
            $dataCounters[] = [
                'title' => __('Email clicks', 'fluent-crm'),
                'count' => CampaignUrlMetric::where('type', 'open')
                    ->where('created_at', '<', $refDate)
                    ->count()
            ];
        }

        return [
            'log_counts' => $dataCounters
        ];
    }

    public function removeOldLogs(Request $request)
    {
        $data = $request->all();
        $this->validate($data, [
            'days_before'   => 'required|numeric|min:7',
            'selected_logs' => 'array|required'
        ]);

        $selectedLogs = $data['selected_logs'];
        $daysBefore = $data['days_before'];

        $refDate = date('Y-m-d 00:00:01', time() - $daysBefore * 86400);
        if (in_array('emails', $selectedLogs)) {
            CampaignEmail::where('created_at', '<', $refDate)
                ->where('status', 'sent')
                ->delete();
        }
        $urlMetricsTypes = [];
        if (in_array('email_clicks', $selectedLogs)) {
            $urlMetricsTypes[] = 'click';
        }
        if (in_array('email_open', $selectedLogs)) {
            $urlMetricsTypes[] = 'open';
        }

        if ($urlMetricsTypes) {
            CampaignUrlMetric::whereIn('type', $urlMetricsTypes)
                ->where('created_at', '<', $refDate)
                ->delete();
        }

        return [
            'message' => sprintf(__('Logs older than %d days have been deleted successfully', 'fluent-crm'), $daysBefore)
        ];
    }

    public function getRestKeys(Request $request)
    {
        $query = new \WP_User_Query(array(
            'meta_key'     => '_fcrm_has_role',
            'meta_value'   => 1,
            'meta_compare' => '=',
            'number'       => 200
        ));

        $managers = [];

        foreach ($query->get_results() as $user) {
            if (user_can($user, 'manage_options')) {
                continue;
            }

            $managers[] = [
                'id'        => $user->ID,
                'full_name' => $user->first_name . ' - ' . $user->last_name,
                'email'     => $user->user_email
            ];
        }

        $applicationUsers = fluentcrm_get_option('_rest_api_users', []);
        $restApps = [];

        if ($applicationUsers) {
            $userIds = array_keys($applicationUsers);
            $restUsers = get_users([
                'include' => $userIds,
                'number'  => 20
            ]);

            foreach ($restUsers as $restUser) {
                $applicationUUIDs = $applicationUsers[$restUser->ID];
                $passwords = get_user_meta($restUser->ID, '_application_passwords', true);

                $crmApps = [];

                foreach ($passwords as $password) {
                    if (in_array($password['uuid'], $applicationUUIDs)) {
                        $crmApps[] = [
                            'name'    => $password['name'],
                            'created' => date('Y-m-d H:i:s', $password['created'])
                        ];
                    }
                }

                if (!$crmApps) {
                    continue;
                }

                $restApps[] = [
                    'id'         => $restUser->ID,
                    'first_name' => $restUser->first_name,
                    'last_name'  => $restUser->last_name,
                    'email'      => $restUser->user_email,
                    'api_keys'   => $crmApps,
                    'manage_url' => admin_url('user-edit.php?user_id=' . $restUser->ID . '#application-passwords-section')
                ];
            }
        }


        return [
            'managers'  => $managers,
            'rest_keys' => $restApps
        ];
    }

    public function createRestKey(Request $request)
    {
        $data = $request->all();
        $this->validate($data, [
            'api_name'    => 'required',
            'api_user_id' => 'required'
        ]);

        $data['api_user_id'] = intval($data['api_user_id']);
        $data['api_name'] = sanitize_text_field($data['api_name']);

        // check if the provided user has FluentCRM Access
        if (!get_user_meta($data['api_user_id'], '_fcrm_has_role', true)) {
            return $this->sendError([
                'message' => __('Sorry, the provided user does not have FluentCRM access', 'fluent-crm')
            ]);
        }

        if (!current_user_can('manage_options')) {
            return $this->sendError([
                'message' => __('Sorry, You do not have permission to create REST API', 'fluent-crm')
            ]);
        }

        $user = get_user_by('ID', $data['api_user_id']);

        if (is_wp_error($user)) {
            return $this->sendError([
                'message' => __('Sorry, the provided user does not have FluentCRM access', 'fluent-crm')
            ]);
        }

        $prepared = (object)[
            'name' => $data['api_name']
        ];

        $created = \WP_Application_Passwords::create_new_application_password($user->ID, wp_slash((array)$prepared));

        if (is_wp_error($created)) {
            return $this->sendError([
                'message' => $created->get_error_message()
            ]);
        }


        $password = $created[0];
        $item = \WP_Application_Passwords::get_user_application_password($user->ID, $created[1]['uuid']);

        $item['info'] = [
            'api_password' => \WP_Application_Passwords::chunk_password($password),
            'api_username' => $user->user_login,
        ];

        $uuid = $item['uuid'];

        $applicationUsers = fluentcrm_get_option('_rest_api_users', []);

        if (!isset($applicationUsers[$user->ID])) {
            $applicationUsers[$user->ID] = [];
        }
        $applicationUsers[$user->ID][] = $uuid;

        fluentcrm_update_option('_rest_api_users', $applicationUsers);

        return [
            'item'    => $item,
            'message' => __('API Key has been successfully created', 'fluent-crm')
        ];
    }

    public function getIntegrations(Request $request)
    {
        $withFields = in_array('fields', $request->get('with', []));

        $deepIntegrationProviders = apply_filters('fluentcrm_deep_integration_providers', [], $withFields);

        return [
            'integrations' => $deepIntegrationProviders
        ];
    }

    public function saveIntegration(Request $request)
    {
        $provider = $request->get('provider');
        $action = $request->get('action');
        $data = $request->all();

        if ($action == 'sync') {
            $result = apply_filters('fluentcrm_deep_integration_sync_' . $provider, false, $data);
        } else {
            $result = apply_filters('fluentcrm_deep_integration_save_' . $provider, false, $data);
        }

        if ($result) {
            if (is_wp_error($result)) {
                return $this->sendError([
                    'message' => $result->get_error_message()
                ]);
            }
            return $result;
        }

        return $this->sendError([
            'message' => __('Sorry, the provided provider does not exist', 'fluent-crm')
        ]);
    }

    public function getComplianceSettings(Request $request)
    {
        return [
            'settings' => Helper::getComplianceSettings()
        ];
    }

    public function updateComplianceSettings(Request $request)
    {
        $data = Arr::only($request->all(), array_keys(Helper::getComplianceSettings()));

        foreach ($data as $key => $datum) {
            $data[$key] = sanitize_text_field($datum);
        }

        update_option('_fluentcrm_compliance_settings', $data, 'no');

        return [
            'message'  => __('Settings has been successfully updated'),
            'settings' => $data
        ];
    }

    public function getExperimentalSettings(Request $request)
    {
        return [
            'settings' => Helper::getExperimentalSettings()
        ];
    }

    public function updateExperimentalSettings(Request $request)
    {

        $data = Arr::only($request->all(), array_keys(Helper::getExperimentalSettings()));

        foreach ($data as $key => $datum) {
            $data[$key] = sanitize_text_field($datum);
        }

        if (Arr::get($data, 'company_module') == 'yes') {
            require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/CompaniesMigrator.php');
            \FluentCrmMigrations\CompaniesMigrator::migrate();
        }

        update_option('_fluentcrm_experimental_settings', $data, 'no');

        return [
            'message' => __('Settings has been updated', 'fluent-crm')
        ];
    }

}
