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
        } else if ($event == 'unsubscribed') {
            $newStatus = 'unsubscribed';
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
                } else if ($eventName == 'spamreport') {
                    $newStatus = 'spammed';
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
                if ($eventName == 'unsubscribed') {
                    $newStatus = 'complained';
                } else if ($eventName == 'spam') {
                    $newStatus = 'spammed';
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
                if ($eventName == 'link_unsubscribe') {
                    $newStatus = 'complained';
                } else if ($eventName == 'spam_complaint') {
                    $newStatus = 'spammed';
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
                $newStatus = 'spammed';
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

        if ($status == 'error' && in_array($category, $bounceCategories)) {
            $status = 'bounced';
        }

        if (!in_array($status, $processStatuses)) {
            return [
                'message' => 'unknown_status'
            ];
        }

        $email = $request->get('to');

        if (!is_email($email)) {
            return [
                'message' => 'invalid_email'
            ];
        }

        if ($status == 'unsubscribed') {

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

    private function handlePostalserver($request)
    {
        $event = strtolower($request->get('event'));

        $processStatuses = [
            'messagebounced',
            'messagedeliveryfailed'
        ];

        if (!in_array($event, $processStatuses)) {
            return false;
        }

        $payload = $request->get('payload');


        if (!$payload || !is_array($payload)) {
            return false;
        }

        $reason = Arr::get($payload, 'details', 'Unknown Reason');

        if ($event == 'messagedeliveryfailed') {
            $payloadStatus = Arr::get($payload, 'status');
            if ($payloadStatus != 'HardFail') {
                return false;
            }
            $toEmail = Arr::get($payload, 'message.to');
        } else {
            $toEmail = Arr::get($payload, 'bounce.to');

            if (!$toEmail) {
                $toEmail = Arr::get($payload, 'message.to');
            }
        }

        if (!$toEmail || !is_email($toEmail)) {
            return false;
        }

        $unsubscribeData = [
            'email'  => $toEmail,
            'reason' => $reason,
            'status' => 'bounced'
        ];

        return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
    }

    private function handleSmtp2go($request)
    {
        $event = strtolower($request->get('event'));

        $processStatuses = [
            'bounce',
            'spam',
            'unsubscribe'
        ];
        
        if (!in_array($event, $processStatuses)) {
            return false;
        }

        $reason = sanitize_textarea_field($request->get('message', 'Unknown Reason'));

        if ($event == 'bounce') {
            $bounceType = $request->get('bounce');
            if ($bounceType == 'soft') {
                return false;
            }
        }

        $toEmail = $request->get('rcpt');
        if (!$toEmail || !is_email($toEmail)) {
            return false;
        }

        $newStatus = 'bounced';
        if ($event == 'unsubscribe') {
            $newStatus = 'unsubscribed';
        } else if ($event == 'spam') {
            $newStatus = 'spammed';
        }

        $unsubscribeData = [
            'email'  => $toEmail,
            'reason' => $reason,
            'status' => $newStatus,
            'unsubscribe_reason' => $reason
        ];

        return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
    }

    /**
     * @param $request \FluentCrm\Framework\Request\Request
     * @return boolean
     */
    private function handleBrevo($request)
    {
        $event = $request->getJson();

        if (!$event || !count($event)) {
            return false;
        }

        $unsubscribeData = [];
        
        $eventName = Arr::get($event, 'event');
        if (in_array($eventName, ['soft_bounce', 'hard_bounce', 'spam', 'error', 'blocked', 'unsubscribe'])) {
            $newStatus = 'complained';
            if (in_array($eventName, ['soft_bounce', 'hard_bounce'])) {
                $newStatus = 'bounced';
            } else if ($eventName == 'unsubscribe') {
                $newStatus = 'unsubscribed';
            } else if ($eventName == 'spam') {
                $newStatus = 'spammed';
            }
            $unsubscribeData = [
                'email'  => Arr::get($event, 'email'),
                'reason' => $newStatus . __(' status was set from Brevo Webhook API. Reason: ', 'fluent-crm') . Arr::get($event, 'reason', 'unknown') . __('. Recorded at: ', 'fluent-crm') . current_time('mysql'),
                'status' => $newStatus
            ];
        }

        if ($unsubscribeData) {
            return (new ExternalPages())->recordUnsubscribe($unsubscribeData);
        }

        return false;
    }
}
