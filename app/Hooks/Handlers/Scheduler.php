<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Services\ExternalIntegrations\Maintenance;
use FluentCrm\App\Services\Libs\Mailer\Handler;

/**
 *  Scheduler Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class Scheduler
{
    public static function process()
    {
        do_action('fluentcrm_process_scheduled_tasks_init');

        // Send Pending Emails
        (new Handler)->handle();
    }

    public static function processForSubscriber($subscriber)
    {
        if (!defined('FLUENTCRM_DOING_BULK_IMPORT')) {
            (new Handler)->processSubscriberEmail($subscriber->id);
        }
    }

    public static function processHourly()
    {
        // cleanup campaigns
        (new Handler)->finishProcessing();
    }

    /**
     * @return void
     */
    public static function processWeekly()
    {
        (new Maintenance())->maybeProcessData();
    }
}
