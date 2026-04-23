<?php

use FluentCrm\Framework\Foundation\Application;
use FluentCrm\App\Hooks\Handlers\ActivationHandler;
use FluentCrm\App\Hooks\Handlers\DeactivationHandler;

return function ($file) {

    require_once FLUENTCRM_PLUGIN_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

    register_activation_hook($file, function ($network_wide) use ($file) {
        (new ActivationHandler)->handle($network_wide);
    });

    add_action('wp_insert_site', function ($new_site) use ($file) {
        if (is_plugin_active_for_network('fluent-crm/fluent-crm.php')) {
            switch_to_blog($new_site->blog_id);
            (new ActivationHandler)->handle(false);
            restore_current_blog();
        }
    });

    register_deactivation_hook($file, function ($network_wide) {
        (new DeactivationHandler)->handle();
    });

    add_action('plugins_loaded', function () use ($file) {
        $app = new Application($file);
        require_once FLUENTCRM_PLUGIN_PATH . 'app/Functions/helpers.php';

        if (defined('FLUENTCAMPAIGN')) {
            add_filter('fluent_crm/dashboard_notices', function ($notices) {
                if (version_compare(FLUENTCRM_MIN_PRO_VERSION, FLUENTCAMPAIGN_PLUGIN_VERSION, '>')) {
                    $updateUrl = admin_url('plugins.php?s=fluentcampaign-pro&plugin_status=all&fluentcrm_pro_check_update=' . time());
                    $notices[] = '<div style="padding: 15px 10px;" class="updated"><b>Heads UP: </b> FluentCRM Pro needs to be updated to the latest version. <a href="' . esc_url($updateUrl) . '">Click here to update</a></div>';
                }
                return $notices;
            });
        }

        do_action('fluentcrm_loaded', $app);
        do_action('fluentcrm_addons_loaded', $app);

        add_action('init', function () use ($app) {
            do_action('fluent_crm/after_init', $app);
        }, 1000);

    });

    add_filter('cron_schedules', function ($schedules) {
        if (!is_array($schedules)) {
            $schedules = [];
        }

        if (!isset($schedules['fluentcrm_every_minute'])) {
            $schedules['fluentcrm_every_minute'] = array(
                'interval' => 60,
                'display'  => esc_html__('Every Minute (FluentCRM)', 'fluentform'),
            );
        }

        if (!isset($schedules['fluentcrm_scheduled_five_minute_tasks'])) {
            $schedules['fluentcrm_scheduled_five_minute_tasks'] = array(
                'interval' => 300,
                'display'  => esc_html__('Every 5 Minutes (FluentCRM)', 'fluentform'),
            );
        }

        return $schedules;
    }, 11);

    add_action('fluentcrm_loading_app', function () {

        if (!as_next_scheduled_action('fluentcrm_scheduled_every_minute_tasks')) {
            as_schedule_recurring_action(time(), 60, 'fluentcrm_scheduled_every_minute_tasks', [], 'fluent-crm');
        }

        $hookName = 'fluentcrm_scheduled_minute_tasks';
        if (!wp_next_scheduled($hookName)) {
            wp_schedule_event(time(), 'fluentcrm_every_minute', $hookName);
        }

        $hourlyHook = 'fluentcrm_scheduled_hourly_tasks';
        if (!wp_next_scheduled($hourlyHook)) {
            wp_schedule_event(time() + 100, 'hourly', $hourlyHook);
        }

        $hookName = 'fluentcrm_scheduled_five_minute_tasks';
        if (!wp_next_scheduled($hookName)) {
            wp_schedule_event(time() + 5, 'fluentcrm_every_minute', $hookName);
        }

        $weeklyHook = 'fluentcrm_scheduled_weekly_tasks';
        if (!wp_next_scheduled($weeklyHook)) {
            wp_schedule_event(time() + 1000, 'weekly', $weeklyHook);
        }

        $dailyHook = 'fluentcrm_scheduled_daily_tasks';
        if (!wp_next_scheduled($dailyHook)) {
            wp_schedule_event(time() + 500, 'daily', $dailyHook);
        }

        /*
         * The below schedule is powered by Action Scheduler by WooCommerce
         * It will run every day.
         */
        if (false === as_next_scheduled_action( 'fluent_crm_ascheduler_runs_daily' ) ) {
            as_schedule_recurring_action( strtotime('midnight today'), DAY_IN_SECONDS, 'fluent_crm_ascheduler_runs_daily', [], 'fluent-crm' );
        }
        /*
         *
         * @todo: Handle Duplicate Schedules and we can remove this code at the end of October 2024
         */
        $crons = _get_cron_array();
        $cronMaps = [
            'fluentcrm_scheduled_minute_tasks'      => 'fluentcrm_every_minute',
            'fluentcrm_scheduled_hourly_tasks'      => 'hourly',
            'fluentcrm_scheduled_five_minute_tasks' => 'fluentcrm_every_minute',
            'fluentcrm_scheduled_weekly_tasks'      => 'weekly',
            'fluentcrm_scheduled_daily_tasks'       => 'daily'
        ];

        $occurrences = [];
        $multiples = [];

        foreach ($crons as $time => $hooks) {
            foreach ($hooks as $hook => $hook_events) {
                if (!isset($cronMaps[$hook])) {
                    continue;
                }

                if (isset($occurrences[$hook])) {
                    $multiples[$hook] = isset($multiples[$hook]) ? $multiples[$hook] + 1 : 1;
                    continue;
                }

                $occurrences[$hook] = 1;
            }
        }

        if ($multiples) {
            foreach ($multiples as $scheduleKey => $multiple) {
                wp_clear_scheduled_hook($scheduleKey);
                $mapName = $cronMaps[$scheduleKey];
                wp_schedule_event(time() + 100, $mapName, $scheduleKey);
            }
        }

        // <--- Done Handling Duplicate Schedules

    }, 10);
};
