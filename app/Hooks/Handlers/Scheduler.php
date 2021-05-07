<?php

namespace FluentCrm\App\Hooks\Handlers;

class Scheduler
{
    public static function process()
    {
        do_action(FLUENTCRM . '_process_scheduled_tasks_init');

        // Send Pending Emails
        (new \FluentCrm\Includes\Mailer\Handler)->handle();
    }

    public static function processForSubscriber($subscriber)
    {
        if(!defined('FLUENTCRM_DOING_BULK_IMPORT')) {
            (new \FluentCrm\Includes\Mailer\Handler)->processSubscriberEmail($subscriber->id);
        }
    }

    public static function processHourly()
    {
        // cleanup campaigns
        (new \FluentCrm\Includes\Mailer\Handler)->finishProcessing();
    }
}
