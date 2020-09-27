<?php

namespace FluentCrm\Includes;

class Deactivator
{
    public static function handle()
    {
        wp_clear_scheduled_hook( 'fluentcrm_scheduled_minute_tasks' );
        wp_clear_scheduled_hook( 'fluentcrm_scheduled_hourly_tasks' );
    }
}
