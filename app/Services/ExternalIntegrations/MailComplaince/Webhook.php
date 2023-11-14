<?php

namespace FluentCrm\App\Services\ExternalIntegrations\MailComplaince;


use FluentCrm\App\Hooks\Handlers\ExternalPages;
use FluentCrm\Framework\Support\Arr;

class Webhook
{
    /**
     * @param $serviceName
     * @param $request \FluentCrm\Framework\Request\Request
     */
    public function handle($serviceName, $request)
    {
        $method = 'handle' . ucfirst(strtolower($serviceName));
        if (method_exists($this, $method)) {
            return $this->{$method}($request);
        }

        return null;
    }

    /**
     * @param $request \FluentCrm\Framework\Request\Request
     */
    private function handleMailgun($request)
    {
        $eventData = $request->get('event-data', []);

        if (!$eventData) {
            return false;
        }
        $event = Arr::get($eventData, 'event');

        $catchEvents = ['failed', 'unsubscribed', 'complained'];

        if (!in_array($event, $catchEvents)) {
            return false;
        }

        $recipientEmail = Arr::get($eventData, 'recipient');
        if (!$recipientEmail) {
            return false;
        }

        $newStatus = 'bounced';
        if ($event == 'complained') {
            $newStatus = 'complained';
        }

        $unsubscribeData = [
            'email'  => $recipientEmail,
            'reason' => $newStatus . __(' was set by mailgun webhook api with event name: ', 'fluent-crm') . $event . __(' at ', 'fluent-crm') . current_time('mysql'),
            'status' => $newStatus
        ];

        return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
    }

    /**
     * @param $request \FluentCrm\Framework\Request\Request
     * @return boolean
     */
    private function handleSendgrid($request)
    {
        $events = $request->getJson();

        if (!$events || !count($events)) {
            return false;
        }

        $unsubscribeData = [];
        foreach ($events as $event) {
            if ($unsubscribeData || !is_array($event)) {
                continue;
            }
            $eventName = Arr::get($event, 'event');
            if (in_array($eventName, ['dropped', 'bounce', 'spamreport', 'unsubscribe'])) {
                $newStatus = 'complained';
                if ($eventName == 'bounce') {
                    $newStatus = 'bounced';
                } else if ($eventName == 'unsubscribe') {
                    $newStatus = 'unsubscribed';
                }
                $unsubscribeData = [
                    'email'  => Arr::get($event, 'email'),
                    'reason' => $newStatus . __(' status was set from SendGrid Webhook API. Reason: ', 'fluent-crm') . Arr::get($event, 'reason') . __('. Recorded at: ', 'fluent-crm') . current_time('mysql'),
                    'status' => $newStatus
                ];
            }
        }

        if ($unsubscribeData) {
            return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
        }

        return false;
    }

    /**
     * @param $request \FluentCrm\Framework\Request\Request
     * @return boolean
     */
    private function handlePepipost($request)
    {
        $events = $request->getJson();

        if (!$events || !count($events)) {
            return false;
        }

        $unsubscribeData = [];
        foreach ($events as $event) {
            if ($unsubscribeData || !is_array($event)) {
                continue;
            }
            $eventName = Arr::get($event, 'EVENT');
            if (in_array($eventName, ['bounced', 'invalid', 'spam', 'unsubscribed'])) {
                $newStatus = 'bounced';
                if ($eventName == 'unsubscribed' || $eventName == 'spam') {
                    $newStatus = 'complained';
                }
                $reason = $newStatus . __(' status was set from SendGrid Webhook API. Reason: ', 'fluent-crm') . Arr::get($event, 'BOUNCE_TYPE') . __('. Recorded at: ', 'fluent-crm') . current_time('mysql');

                if ($sourceResponse = Arr::get($event, 'RESPONSE')) {
                    $reason = $sourceResponse;
                }

                $email = Arr::get($event, 'EMAIL');
                if ($email) {
                    $unsubscribeData = [
                        'email'  => $email,
                        'reason' => $reason,
                        'status' => $newStatus
                    ];
                }
            }
        }

        if ($unsubscribeData) {
            return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
        }

        return false;
    }

    /**
     * @param $request \FluentCrm\Framework\Request\Request
     * @return boolean
     */
    private function handleSparkpost($request)
    {
        $events = $request->getJson();

        if (!$events || !count($events)) {
            return false;
        }

        $unsubscribeData = [];
        foreach ($events as $event) {
            if ($unsubscribeData || !is_array($event)) {
                continue;
            }

            $event = Arr::get($event, 'msys.message_event');
            if (!$event || !is_array($event)) {
                continue;
            }

            $eventName = Arr::get($event, 'type');
            if (in_array($eventName, ['bounce', 'spam_complaint', 'link_unsubscribe'])) {
                $newStatus = 'bounced';
                if ($eventName == 'spam_complaint' || $eventName == 'link_unsubscribe') {
                    $newStatus = 'complained';
                }
                $reason = $newStatus . __(' status was set from Sparkpost Webhook API. Reason: ', 'fluent-crm') . $eventName . __('. Recorded at: ', 'fluent-crm') . current_time('mysql');

                if ($sourceResponse = Arr::get($event, 'raw_reason')) {
                    $reason = $sourceResponse;
                }

                $email = Arr::get($event, 'rcpt_to');
                if ($email) {
                    $unsubscribeData = [
                        'email'  => $email,
                        'reason' => $reason,
                        'status' => $newStatus
                    ];
                }
            }
        }

        if ($unsubscribeData) {
            return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
        }

        return false;
    }

    /**
     * @param $request \FluentCrm\Framework\Request\Request
     * @return boolean
     */
    private function handlePostmark($request)
    {
        $event = $request->getJson();


        if (!$event || !is_array($event)) {
            return false;
        }

        $unsubscribeData = [];

        $eventName = Arr::get($event, 'RecordType');
        if (in_array($eventName, ['Bounce', 'SpamComplaint'])) {
            $newStatus = 'bounced';
            if ($eventName == 'SpamComplaint') {
                $newStatus = 'complained';
            }

            $reason = $newStatus . __(' status was set from PostMark Webhook API. Reason: ', 'fluent-crm') . $eventName . __('. Recorded at: ', 'fluent-crm') . current_time('mysql');

            if ($sourceResponse = Arr::get($event, 'Description')) {
                $reason = $sourceResponse;
            }

            $email = Arr::get($event, 'Email');
            if ($email) {
                $unsubscribeData = [
                    'email'  => $email,
                    'reason' => $reason,
                    'status' => $newStatus
                ];
            }
        }

        if ($unsubscribeData) {
            return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
        }

        return false;
    }

    private function handleElasticemail($request)
    {
        $status = strtolower($request->get('status'));

        $processStatuses = [
            'bounced',
            'abusereport',
            'unsubscribed'
        ];

        $bounceCategories = [
            'NoMailbox',
            'BlackListed',
            'ManualCancel'
        ];

        $category = $request->get('category', 'unknown');

        if($status == 'error' && in_array($category, $bounceCategories)) {
            $status = 'bounced';
        }

        if(!in_array($status, $processStatuses)) {
            return false;
        }

        $email = $request->get('to');

        if(!is_email($email)) {
            return false;
        }

        if($status == 'unsubscribed') {

            $unsubscribeData = [
                'email'  => $email,
                'reason' => 'Unsubscribed from ElasticEmail Webhook API',
                'status' => 'unsubscribed'
            ];

            return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
        }


        $unsubscribeData = [
            'email'  => $email,
            'reason' => sprintf('Unsubscribed from ElasticEmail Webhook with Category %s', $category),
            'status' => 'bounced'
        ];

        return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
    }
}
