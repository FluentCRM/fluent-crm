<?php

namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

defined('ABSPATH') || exit;


/**
 * Extend API Wrapper for FluentCRM FluentCrmApi('tracker')
 *
 * Contacts API Wrapper Class that can be used as <code>FluentCrmApi('tracker')</code> to get the class instance
 *
 * @package FluentCrm\App\Api\Classes
 * @namespace FluentCrm\App\Api\Classes
 *
 * @version 2.8.4
 */
final class Tracker
{
    /*
     * Create event for a subscriber
     * Example Data:
     *   [
     *      'subscriber_id' => 1, // optional
     *      'email' => '', // optional
     *      'user_id' => 1, // optional
     *      'provider'        => 'woocommerce|custom|or_anything', // optional
     *      'event_key'        => 'checkout',
     *      'title'       => 'Purchase Done',
     *      'value' => 'STRING|Number'
     *  ];
     * @param array $data | \WP_Error
     *
     * @return \WP_Error|\FluentCrm\App\Models\EventTracker
     */
    public function track($data, $repeatable = true)
    {
        if (!Helper::isExperimentalEnabled('event_tracking')) {
            return new \WP_Error('not_enabled', 'Event Tracker is not enabled');
        }

        // find the subscriber
        $subscriber = $this->getSubscriber($data);

        if (is_wp_error($subscriber)) {
            return $subscriber;
        }

        // validate the data
        if (empty($data['event_key']) || empty($data['title'])) {
            return new \WP_Error('invalid_data', 'Invalid data provided. key and event are required');
        }

        // take only first 200 characters
        $data['event_key'] = substr($data['event_key'], 0, 192);
        $data['title'] = substr($data['title'], 0, 192);

        $eventData = [
            'provider'      => sanitize_text_field(Arr::get($data, 'provider', 'custom')),
            'subscriber_id' => $subscriber->id,
            'event_key'     => sanitize_text_field($data['event_key']),
            'title'         => sanitize_text_field($data['title']),
            'value'         => sanitize_textarea_field(Arr::get($data, 'value', '')),
            'counter'       => 1 // This is actually the count of the event
        ];

        if ($repeatable) {
            // check if exist
            $event = \FluentCrm\App\Models\EventTracker::where('subscriber_id', $subscriber->id)
                ->where('event_key', $eventData['event_key'])
                ->where('title', $eventData['title'])
                ->first();

            if ($event) {
                $event->value = $eventData['value'];
                $event->counter++;
                $event->save();
                do_action('fluent_crm/event_tracked', $event, $subscriber);
                return $event;
            }
        }

        $createdEvent = \FluentCrm\App\Models\EventTracker::create($eventData);
        do_action('fluent_crm/event_tracked', $createdEvent, $subscriber);
        return $createdEvent;
    }

    private function getSubscriber($data)
    {
        if (!empty($data['subscriber'])) {
            return $data['subscriber'];
        }

        // check for subscriber
        if (empty($data['subscriber_id']) && empty($data['email']) && empty($data['user_id'])) {
            $subscriber = fluentcrm_get_current_contact();
            if ($subscriber) {
                return $subscriber;
            }

            return new \WP_Error('subscriber_not_found', 'Current Subscriber could not be found');
        }

        $subscriber = null;

        if (!empty($data['subscriber_id'])) {
            $subscriber = Subscriber::where('id', $data['subscriber_id'])->first();
        } else if (!empty($data['email'])) {
            $subscriber = Subscriber::where('email', $data['email'])->first();
        } else if (!empty($data['user_id'])) {
            $user = get_user_by('ID', $data['user_id']);
            if ($user) {
                $subscriber = Subscriber::where('email', $user->user_email)->first();
            }
        }

        if (!$subscriber) {
            return new \WP_Error('subscriber_not_found', 'Subscriber not found');
        }

        return $subscriber;
    }
}

