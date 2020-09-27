<?php

namespace FluentCrm\Includes;

class Activator
{
    public static function handle($network_wide = false)
    {
        // Run DB Migrations
        require_once(FLUENTCRM_PLUGIN_PATH . 'database/DBMigrator.php');

        // Task scheduler for sending emails
        static::registerWpCron();

        // Default global settings/options
        static::addDefaultGlobalSettings();
    }

    public static function registerWpCron()
    {
        add_filter('cron_schedules', function ($schedules) {
            $schedules['fluentcrm_every_minute'] = array(
                'interval' => 60,
                'display'  => esc_html__('Every Minute (FluentCRM)', 'fluentform'),
            );
            return $schedules;
        }, 10, 1);

        $hookName = 'fluentcrm_scheduled_minute_tasks';
        if (!wp_next_scheduled($hookName)) {
            wp_schedule_event(time(), 'fluentcrm_every_minute', $hookName);
        }

        $dailyHook = 'fluentcrm_scheduled_hourly_tasks';
        if (!wp_next_scheduled($dailyHook)) {
            wp_schedule_event(time(), 'hourly', $dailyHook);
        }

    }

    public static function addDefaultGlobalSettings()
    {
        $key = FLUENTCRM.'-global-settings';

        $defaults = [
            'campaign' => [
                'from' => [
                    'name' => '',
                    'email' => ''
                ]
            ],
            'email' => [
                'emails_per_second' => 0
            ]
        ];

        $settings = get_option($key) ?: [];

        update_option($key, array_merge($defaults, $settings));
    }
}
