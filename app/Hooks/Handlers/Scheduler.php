<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentForm\App\Services\Emogrifier\Emogrifier;
use FluentForm\View;

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
        (new \FluentCrm\Includes\Mailer\Handler)->processSubscriberEmail($subscriber->id);
    }
}
