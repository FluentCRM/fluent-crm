<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Hooks\Handlers\ActivationHandler;
use FluentCrm\App\Models\ActivityLog;
use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\SystemLog;
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
        $settings = (array)$request->get('settings', []);

        $existingSettings = get_option('fluentcrm-global-settings');

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
            'fluentcrm-global-settings', $existingSettings
        );

        return $this->sendSuccess([
            'message' => __('Settings Updated', 'fluent-crm')
        ]);
    }

    public function getDoubleOptinSettings(Request $request)
    {
        //check if list id comes
        $listId = $request->get('list_id', null);
        $doubleOptinSettings = null;
        //if list id sent then it is double optin setup of a list
        if ($listId) {
            $meta = fluentcrm_get_list_meta($listId, 'double_optin_settings');
            $doubleOptinSettings = $meta ? $meta->value : null;
        }

        //if no double optin setup of list found or this is global
        if (!$doubleOptinSettings) {
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
        }

        $data = [
            'settings' => $doubleOptinSettings
        ];

        if ($listId) {
            $globalDoubleOptin = fluentcrm_get_list_meta($listId, 'global_double_optin');
            $data['global_double_optin'] = $globalDoubleOptin ? $globalDoubleOptin->value : 'yes';
        }

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
        $listId = $request->get('list_id');
        $globalDoubleOptin = sanitize_text_field($request->get('global_double_optin'));

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

        if ($listId) {
            fluentcrm_update_list_meta($listId, 'double_optin_settings', $settings);
            if ($globalDoubleOptin) {
                fluentcrm_update_list_meta($listId, 'global_double_optin', $globalDoubleOptin);
            }
        } else {
            fluentcrm_update_option('double_optin_settings', $settings);
        }

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
            'postalserver' => [
                'label'       => __('Postal Server', 'fluent-crm'),
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/postalserver/handle/' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handling-with-postal-server/',
                'input_title' => __('Postal Server Bounce Handler Webhook URL', 'fluent-crm'),
                'input_info'  => __('Please paste this URL into your Postal Server\'s Webhook settings to enable Bounce Handling with FluentCRM. Please select only MessageBounced & MessageDeliveryFailed event', 'fluent-crm')
            ],
            'smtp2go' => [
                'label' => 'SMTP2Go',
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/smtp2go/handle/' . $securityCode),
                'doc_url' => 'https://fluentcrm.com/docs/bounce-handling-with-smtp2go/',
                'input_title' => 'SMTP2Go Bounce Handler Webhook URL',
                'input_info' => 'Please paste this URL into your SMTP2Go\'s Webhook settings to enable Bounce Handling with FluentCRM'
            ],
            'brevo' => [
                'label'       => __('Brevo (ex Sendinblue)', 'fluent-crm'),
                'webhook_url' => get_rest_url(null, 'fluent-crm/v2/public/bounce_handler/brevo/handle/' . $securityCode),
                'doc_url'     => 'https://fluentcrm.com/docs/bounce-handling-with-brevo/',
                'input_title' => __('Brevo Bounce Handler Webhook URL', 'fluent-crm'),
                'input_info'  => __('Please paste this URL into your Brevo\'s Webhook settings to enable Bounce Handling with FluentCRM', 'fluent-crm')
            ],
        ];

        $data = [
            /**
             * Determine FluentCRM Bounce Handler settings.
             *
             * This filter allows modification of the bounce handler settings.
             *
             * @since 2.5.95
             *
             * @param array  $bounceSettings The current bounce settings.
             * @param string $securityCode   The security code for the bounce handler.
             */
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
        $events = [];

        $nextRun = Helper::getNextMinuteTaskTimeStamp();

        $events[] = (object)array(
            'hook'       => 'fluentcrm_scheduled_every_minute_tasks',
            'is_overdue' => (time() - $nextRun) > 30,
            'human_name' => __('Scheduled Email Sending Tasks', 'fluent-crm'),
            'next_run'   => human_time_diff($nextRun, time()),
            'interval'   => 60
        );

        $nextFiverMinutesRun = wp_next_scheduled('fluentcrm_scheduled_five_minute_tasks');
        $events[] = (object)array(
            'hook'       => 'fluentcrm_scheduled_hourly_tasks',
            'is_overdue' => ($nextFiverMinutesRun - time()) < -60,
            'human_name' => __('Scheduled Email Processing', 'fluent-crm'),
            'next_run'   => human_time_diff($nextFiverMinutesRun, time()),
            'interval'   => 300
        );

        $nextHourlyRun = wp_next_scheduled('fluentcrm_scheduled_hourly_tasks');
        $events[] = (object)array(
            'hook'       => 'fluentcrm_scheduled_hourly_tasks',
            'is_overdue' => ($nextHourlyRun - time()) < -120,
            'human_name' => __('Scheduled Automation Tasks', 'fluent-crm'),
            'next_run'   => human_time_diff($nextHourlyRun, time()),
            'interval'   => 3600
        );


        return [
            'cron_events' => $events,
            'server'      => [
                'memory_limit'       => intval(fluentCrmGetMemoryLimit() / 1048576) . 'MB',
                'usage_percent'      => fluentCrmGetMemoryUsagePercentage(),
                'max_execution_time' => fluentCrmMaxRunTime() . ' seconds',
                'has_server_cron'    => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON
            ]
        ];
    }

    public function runCron(Request $request)
    {
        $hookName = $request->get('hook');
        $hookNames = [
            'fluentcrm_scheduled_every_minute_tasks' => __('Scheduled Email Sending', 'fluent-crm'),
            'fluentcrm_scheduled_hourly_tasks'       => __('Scheduled Automation Tasks', 'fluent-crm'),
            'fluentcrm_scheduled_five_minute_tasks'  => __('Scheduled Email Processing', 'fluent-crm')
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

        $refDate = gmdate('Y-m-d 00:00:01', time() - $daysBefore * 86400);

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
                'title' => __('Email Opens', 'fluent-crm'),
                'count' => CampaignUrlMetric::where('type', 'open')
                    ->where('created_at', '<', $refDate)
                    ->count()
            ];
        }

        if (in_array('system_logs', $selectedLogs)) {
            $dataCounters[] = [
                'title' => __('System Logs', 'fluent-crm'),
                'count' => SystemLog::where('created_at', '<', $refDate)
                    ->count()
            ];
        }

        if (in_array('activity_logs', $selectedLogs)) {
            $dataCounters[] = [
                'title' => __('Activity Logs', 'fluent-crm'),
                'count' => ActivityLog::where('created_at', '<', $refDate)
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

        $perChunk = 10000; // Deleting 10,000 per chunk
        $hasMore = false;

        $refDate = date('Y-m-d 00:00:01', time() - $daysBefore * 86400);
        if (in_array('emails', $selectedLogs)) {

            $campaignIds = CampaignEmail::where('created_at', '<', $refDate)
                ->where('status', 'sent')
                ->groupBy('campaign_id')
                ->pluck('campaign_id');

            foreach ($campaignIds->toArray() as $campaignId) {
                fluentcrm_update_campaign_meta($campaignId, '_data_trunked', 'yes');
            }

            CampaignEmail::where('created_at', '<', $refDate)
                ->where('status', 'sent')
                ->limit($perChunk)
                ->delete();

            $hasMore = CampaignEmail::where('created_at', '<', $refDate)
                ->where('status', 'sent')
                ->exists();
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
                ->limit($perChunk)
                ->delete();

            if (!$hasMore) {
                $hasMore = CampaignUrlMetric::whereIn('type', $urlMetricsTypes)
                    ->where('created_at', '<', $refDate)
                    ->exists();
            }

        }

        if (in_array('system_logs', $selectedLogs)) {
            SystemLog::where('created_at', '<', $refDate)
                ->limit($perChunk)
                ->delete();

            if (!$hasMore) {
                $hasMore = SystemLog::where('created_at', '<', $refDate)
                    ->exists();
            }

        }

        if (in_array('activity_logs', $selectedLogs)) {
            ActivityLog::where('created_at', '<', $refDate)
                ->limit($perChunk)
                ->delete();

            if (!$hasMore) {
                $hasMore = ActivityLog::where('created_at', '<', $refDate)
                    ->exists();
            }

        }

        return [
            'message'  => sprintf(__('Logs older than %d days have been deleted successfully', 'fluent-crm'), $daysBefore),
            'has_more' => $hasMore
        ];
    }

    public function deleteRestKey(Request $request)
    {
        $data = $request->all();
        $this->validate($data, [
            'user_id' => 'required',
            'uuid'    => 'required'
        ]);

        $data['user_id'] = intval($data['user_id']);
        $data['uuid'] = sanitize_text_field($data['uuid']);

        if (!get_user_meta($data['user_id'], '_fcrm_has_role', true)) {
            return $this->sendError([
                'message' => __('Sorry, the provided user does not have FluentCRM access', 'fluent-crm')
            ]);
        }

        if (!current_user_can('manage_options')) {
            return $this->sendError([
                'message' => __('Sorry, You do not have permission to delete REST API', 'fluent-crm')
            ]);
        }

        $deleted = \WP_Application_Passwords::delete_application_password($data['user_id'], $data['uuid']);

        if ($deleted) {
            $applicationUsers = fluentcrm_get_option('_rest_api_users', []);

            foreach ($applicationUsers[$data['user_id']] as $index => $uuid) {
                if ($uuid == $data['uuid']) {
                    array_splice($applicationUsers[$data['user_id']], $index, 1);
                }
            }

            fluentcrm_update_option('_rest_api_users', $applicationUsers);
        }

        if (!$deleted) {
            return $this->sendError([
                'message' => __('Something is wrong', 'fluent-crm')
            ]);
        }

        return [
            'message' => __('API Key has been successfully deleted', 'fluent-crm')
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
                            'uuid'    => $password['uuid'],
                            'name'    => $password['name'],
                            'created' => gmdate('Y-m-d H:i:s', $password['created'])
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

        /**
         * Determine the deep integration providers for FluentCRM.
         *
         * This filter allows modification of the deep integration providers used in FluentCRM such as Woocommerce, Easy Digital Downloads, etc.
         * 
         * @since 2.5.1
         * 
         * @param array An array of deep integration providers.
         * @param bool  $withFields Whether to include fields in the integration providers.
         */
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
            /**
             * Determine whether to allow deep integration sync for a specific provider.
             *
             * This filter allows you to modify the result of the deep integration sync for a given provider.
             *
             * @since 2.5.1
             *
             * @param mixed  The result of the integration sync. Default false. Expected to be a boolean.
             * @param array  $data     The data to be synced.
             */
            $result = apply_filters('fluentcrm_deep_integration_sync_' . $provider, false, $data);
        } else {
            /**
             * Determine the result of saving deep integration settings for a specific provider.
             *
             * The dynamic portion of the hook name, `$provider`, refers to the specific integration provider.
             *
             * @since 2.5.1
             *
             * @param mixed  The result of the save operation. Default false. Expected to be a boolean.
             * @param array  $data    The data being saved.
             */
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

        $result = update_option('_fluentcrm_compliance_settings', $data, 'no');

        if ($result) {
            do_action('fluent_crm/sync_subscriber_delete_setting', 'compliance_settings', $data['delete_contact_on_user']);
        }

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
            if ($key === 'campaign_ids' && is_array($datum)) {
                $data[$key] = array_map('intval', $datum);
            } else {
                $data[$key] = sanitize_text_field($datum);
            }
        }

        if (Arr::get($data, 'company_module') == 'yes') {
            require_once(FLUENTCRM_PLUGIN_PATH . 'database/migrations/CompaniesMigrator.php');
            \FluentCrmMigrations\CompaniesMigrator::migrate();
        }

        if (Arr::get($data, 'event_tracking') == 'yes') {
            require_once(FLUENTCRM_PLUGIN_PATH . 'database/migrations/SubscriberEventTracking.php');
            \FluentCrmMigrations\SubscriberEventTracking::migrate();
        }

        if (Arr::get($data, 'activity_log') == 'yes') {
            require_once(FLUENTCRM_PLUGIN_PATH . 'database/migrations/ActivityLogsMigrator.php');
            \FluentCrmMigrations\ActivityLogsMigrator::migrate();
        }

        update_option('_fluentcrm_experimental_settings', $data, 'yes');

        return [
            'message' => __('Settings has been updated', 'fluent-crm')
        ];
    }

    public function getCampaigns(Request $request)
    {
        $campaigns = Campaign::orderBy('id', 'DESC')->get();

        return [
            'campaigns' => $campaigns
        ];
    }

}
