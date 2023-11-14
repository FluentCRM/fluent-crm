<?php

namespace FluentCrm\App\Hooks\Handlers;

/**
 * ActivationHandler Class
 *
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */

class ActivationHandler
{
    public function handle($network_wide = false)
    {
        // Run DB Migrations
        require_once(FLUENTCRM_PLUGIN_PATH . 'database/FluentCRMDBMigrator.php');

        // Task scheduler for sending emails
        $this->registerWpCron();

        // Default global settings/options
        $this->addDefaultGlobalSettings();
    }

    public function registerWpCron()
    {
        add_filter('cron_schedules', function ($schedules) {

            $schedules['fluentcrm_every_minute'] = array(
                'interval' => 60,
                'display'  => esc_html__('Every Minute (FluentCRM)', 'fluentform'),
            );

            $schedules['fluentcrm_scheduled_five_minute_tasks'] = array(
                'interval' => 300,
                'display'  => esc_html__('Every 5 Minutes (FluentCRM)', 'fluentform'),
            );

            return $schedules;
        }, 10, 1);

        $hookName = 'fluentcrm_scheduled_minute_tasks';
        if (!wp_next_scheduled($hookName)) {
            wp_schedule_event(time(), 'fluentcrm_every_minute', $hookName);
        }

        $hookName = 'fluentcrm_scheduled_five_minute_tasks';
        if (!wp_next_scheduled($hookName)) {
            wp_schedule_event(time(), 'fluentcrm_scheduled_five_minute_tasks', $hookName);
        }


        $dailyHook = 'fluentcrm_scheduled_hourly_tasks';
        if (!wp_next_scheduled($dailyHook)) {
            wp_schedule_event(time(), 'hourly', $dailyHook);
        }

        $weeklyHook = 'fluentcrm_scheduled_weekly_tasks';
        if (!wp_next_scheduled($weeklyHook)) {
            wp_schedule_event(time(), 'weekly', $weeklyHook);
        }

    }

    public function addDefaultGlobalSettings()
    {
        $key = 'fluentcrm-global-settings';

        $defaults = [
            'campaign' => [
                'from' => [
                    'name' => '',
                    'email' => ''
                ]
            ],
            'email' => [
                'emails_per_second' => 4
            ]
        ];

        $settings = get_option($key) ?: [];

        update_option($key, array_merge($defaults, $settings));
    }
}
