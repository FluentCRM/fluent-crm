<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

/**
 *  ContactActivityLogger Class
 *
 * Logs Contact's activity based on different WordPress Events.
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class ContactActivityLogger
{
    public function register()
    {
        // Login Tracker
        add_action('wp_login', array($this, 'trackLogin'), 10, 2);

        // Global Tracker
        add_action('fluent_crm/track_activity_by_subscriber', array($this, 'trackActivityBySubscriber'));

    }

    public function trackLogin($username, $user)
    {
        update_user_meta($user->ID, '_last_login', current_time('mysql'));
        $this->trackActivityByUser($user, 'login');
    }

    public function trackActivityByUser($user, $type = '')
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

        $this->trackActivityBySubscriber($subscriber);

        if ($type == 'login') {
            fluentcrm_update_subscriber_meta($subscriber->id, '_last_login', current_time('mysql'));
        }

        return true;
    }


    public function trackActivityBySubscriber($subscriber)
    {
        if (is_numeric($subscriber)) {
            $subscriber = Subscriber::where('id', $subscriber)->first();
        }

        if (!$subscriber) {
            return;
        }

        if ($subscriber->last_activity && strtotime($subscriber->last_activity) > (current_time('timestamp') - 3600)) {
            return;
        }

        $data = [
            'last_activity' => current_time('mysql')
        ];

        if (!$subscriber->ip && fluentCrmWillTrackIp()) {
            $ip = FluentCrm('request')->getIp(fluentCrmWillAnonymizeIp());
            if ($ip != '127.0.0.1') {
                $data['ip'] = $ip;
            }
        }

        return fluentCrmDb()->table('fc_subscribers')
            ->where('id', $subscriber->id)
            ->update($data);

    }
}
