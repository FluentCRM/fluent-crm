<?php

namespace FluentCrm\App\Hooks\Handlers;

/**
 *  DeactivationHandler Class
 *
 * FluentCRM Deactivation Handler Class.
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class DeactivationHandler
{
    public function handle()
    {
        wp_clear_scheduled_hook('fluentcrm_scheduled_minute_tasks');
        wp_clear_scheduled_hook('fluentcrm_scheduled_hourly_tasks');
        wp_clear_scheduled_hook('fluentcrm_scheduled_weekly_tasks');
        wp_clear_scheduled_hook('fluentcrm_scheduled_five_minute_tasks');
    }
}
