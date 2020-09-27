<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Subscriber;

class ContactActivityLogger
{
    public function register()
    {
        // Login Tracker
        add_action('wp_login', array($this, 'trackLogin'), 10, 2);

        // Global Tracker
        add_action('fluentform_track_contact_activity_by_user', array($this, 'trackActivityByUser'));
        add_action('fluentform_track_activity_by_subscriber', array($this, 'trackActivityBySubscriber'));
    }

    public function trackLogin($username, $user)
    {
        $this->trackActivityByUser($user);
    }

    public function trackActivityByUser($user)
    {
        if (is_numeric($user)) {
            $user = get_user_by('ID', $user);
        }
        if (!$user || empty($user->user_email)) {
            return;
        }

        $subscriber = Subscriber::where('email', $user->user_email)->first();

        if (!$subscriber) {
            return;
        }

        $subscriber->last_activity = current_time('mysql');
        if (!$subscriber->ip && fluentCrmWillTrackIp()) {
            $ip = FluentCrm('request')->getIp();
            if($ip != '127.0.0.1') {
                $subscriber->ip = $ip;
            }
        }
        return $subscriber->save();
    }


    public function trackActivityBySubscriber($subscriber)
    {
        if (is_numeric($subscriber)) {
            $subscriber = Subscriber::where('id', $subscriber)->first();
        }
        if (!$subscriber) {
            return;
        }

        $subscriber->last_activity = current_time('mysql');
        if (!$subscriber->ip && fluentCrmWillTrackIp()) {
            $ip = FluentCrm('request')->getIp();
            if($ip != '127.0.0.1') {
                $subscriber->ip = $ip;
            }
        }
        return $subscriber->save();
    }

}
