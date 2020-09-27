<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Webhook;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;

class ExternalPages
{
    protected $request = null;

    protected $validRoutes = [
        'unsubscribe'         => 'unsubscribePage',
        'manage_subscription' => 'manageSubscription',
        'confirmation'        => 'confirmationPage', // ?fluentcrm=1&route=confirmation&s_id={subscriber.id}&hash={subscriber.hash}
        'open'                => 'trackEmailOpen', // ?fluentcrm=1&route=open&_e_hash=kandkaskdja
        'bounce_handler'      => 'bounceHandler', // ?fluentcrm=1&route=bounce_handler&provider=ses&retry=1
        'contact'             => 'handleContactWebhook', // POST ?fluentcrm=1&route=contact&hash=khkhjkhjkhjkhkhkh
        'bnu'                 => 'handleBenchmarkUrl', // GET ?fluentcrm=1&route=bnu&aid=${sequence_id}
    ];

    protected function getRoute()
    {
        $this->request = FluentCrm('request');

        if ($this->request->has('fluentcrm')) {
            if ($route = $this->request->get('route')) {
                return $this->validRoutes[sanitize_text_field($route)];
            }
        }
    }

    public function route()
    {
        if ($route = $this->getRoute()) {
            $this->{$route}();
        }
    }

    public function bounceHandler()
    {
        $provider = sanitize_text_field($this->request->get('provider'));

        if ($provider == 'ses') {
            $this->bounceHandlerSES();;
        }
    }

    public function bounceHandlerSES()
    {
        // check bounce key
        $sesBounceKey = fluentcrm_get_option('_fc_bounce_key');
        $verifyKey = Arr::get($_REQUEST, 'verify_key');

        if ($verifyKey != $sesBounceKey) {
            wp_send_json([
                'status'  => 423,
                'message' => 'verify_key verification failed'
            ], 423);
        }

        $postdata = \file_get_contents('php://input');

        if (is_wp_error($postdata)) {
            \error_log('SNS ERROR: ' . $postdata->get_error_message());
            wp_send_json([
                'status'  => 423,
                'message' => 'failed'
            ], 423);
        }

        $postdata = \json_decode($postdata, true);

        $notificationType = Arr::get($postdata, 'Type');

        if ($notificationType == 'SubscriptionConfirmation') {
            \wp_remote_get($postdata['SubscribeURL']);
            wp_send_json([
                'status'  => 200,
                'message' => 'success'
            ], 200);
        }

        $message = \json_decode(Arr::get($postdata, 'Message'), true);
        $messageType = Arr::get($message, 'notificationType');
        if ($messageType == 'Bounce') {
            $bounce = $message['bounce'];
            foreach ($bounce['bouncedRecipients'] as $bouncedRecipient) {
                $data = [
                    'email'  => $bouncedRecipient['emailAddress'],
                    'reason' => Arr::get($bouncedRecipient, 'diagnosticCode'),
                    'status' => 'bounced'
                ];
                $this->recordUnsubscribe($data);
            }
        } else if ($messageType == 'complaint') {
            $complaint = $message['complaint'];
            foreach ($complaint['complainedRecipients'] as $complainedRecipient) {
                $data = [
                    'email'  => Arr::get($complainedRecipient, 'emailAddress'),
                    'reason' => Arr::get($complainedRecipient, 'diagnosticCode'),
                    'status' => 'complained'
                ];
                $this->recordUnsubscribe($data);
            }
        }

        wp_send_json([
            'status'  => 200,
            'message' => 'success'
        ], 200);
    }

    private function recordUnsubscribe($data)
    {
        if (!empty($data['email'])) {
            $subscriber = Subscriber::where('email', $data['email'])->first();
            if ($subscriber) {
                $subscriber->status = $data['status'];
                $subscriber->save();
                fluentcrm_update_subscriber_meta($subscriber->id, 'reason', $data['reason']);
            }
        }
    }

    public function unsubscribePage()
    {
        if (!($campaignEmailId = $this->request->get('ce_id'))) {
            return;
        }

        $campaignEmailId = intval($campaignEmailId);

        $campaignEmail = CampaignEmail::where('id', $campaignEmailId)->first();

        if (!$campaignEmail) {
            echo 'Sorry! This is not a valid unsubscribe url';
            wp_die();
        }

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);

        $this->loadAssets();

        fluentCrm('view')->render('external.unsubscribe', [
            'business'       => $businessSettings,
            'campaign_email' => $campaignEmail,
            'reasons'        => $this->unsubscribeReasons()
        ]);

        exit();
    }

    public function unsubscribeReasons()
    {
        $reasons = apply_filters('fluentcrm_unsubscribe_reason', [
            'no_loger'             => __('I no longer want to receive these emails', 'fluentcrm'),
            'never_signed_up'      => __('I never signed up for this email list', 'fluentcrm'),
            'emails_inappropriate' => __('The emails are inappropriate', 'fluentcrm'),
            'emails_spam'          => __('The emails are spam', 'fluentcrm')
        ]);

        $reasons['other'] = __('Other (fill in reason below)', 'fluentcrm');

        return $reasons;
    }

    public function handleUnsubscribe()
    {
        $request = FluentCrm('request');
        $emailId = intval($request->get('_e_id'));
        $emailAddress = sanitize_text_field($request->get('email_address'));
        $reason = sanitize_text_field($request->get('reason'));

        if ($reason == 'other') {
            if ($otherReason = $request->get('other_reason')) {
                $reason = sanitize_text_field($otherReason);
            }
        } else if ($reason) {
            $reasons = $this->unsubscribeReasons();
            if (isset($reasons[$reason])) {
                $reason = sanitize_text_field($reasons[$reason]);
            }
        }

        $campaignEmail = CampaignEmail::where('id', $emailId)->first();

        $subscriber = Subscriber::where('id', $campaignEmail->subscriber_id)->first();

        if (!$campaignEmail || $campaignEmail->email_address != $emailAddress || !$subscriber) {
            wp_send_json_error([
                'message' => __('Sorry, Email does not match with the database entry', 'fluentcrm')
            ], 423);
        }

        $oldStatus = $subscriber->status;

        if ($oldStatus != 'unsubscribed') {
            $subscriber->status = 'unsubscribed';
            $subscriber->save();
            do_action('fluentcrm_subscriber_status_to_unsubscribed', $subscriber, $oldStatus);
        }

        if ($reason) {
            fluentcrm_update_subscriber_meta(
                $campaignEmail->subscriber_id, 'unsubscribe_reason', $reason
            );
        }

        CampaignUrlMetric::maybeInsert([
            'campaign_id'   => $campaignEmail->campaign_id,
            'subscriber_id' => $campaignEmail->subscriber_id,
            'type'          => 'unsubscribe',
            'ip_address'    => FluentCrm()->request->getIp()
        ]);

        wp_send_json_success([
            'message' => __('You are successfully unsubscribed form the email list', 'fluentcrm')
        ], 200);
    }

    private function trackEmailOpen()
    {
        $mailHash = sanitize_text_field($this->request->get('_e_hash'));
        $email = CampaignEmail::where('email_hash', $mailHash)->first();

        if ($email) {
            CampaignUrlMetric::maybeInsert([
                'type'          => 'open',
                'campaign_id'   => $email->campaign_id,
                'subscriber_id' => $email->subscriber_id,
                'ip_address'    => FluentCrm()->request->getIp()
            ]);

            $email->is_open = 1;
            $email->save();
        }

        // we are sending 1x1 pixel transparent gif image
        header('Content-Type: image/gif');
        // Transparent 1x1 GIF as hex format
        die("\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x90\x00\x00\xff\x00\x00\x00\x00\x00\x21\xf9\x04\x05\x10\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x04\x01\x00\x3b");
    }

    public function confirmationPage()
    {
        if (!($subscriberId = $this->request->get('s_id'))) {
            return;
        }

        $subscriberId = intval($subscriberId);

        $hash = sanitize_text_field($this->request->get('hash'));

        $subscriber = Subscriber::find($subscriberId);

        if (!$subscriber || $hash != $subscriber->hash) {
            $body = 'Sorry! Your confirmation url is not valid';
        } else {
            if ($subscriber->status != 'subscribed') {
                $oldStatus = $subscriber->status;
                $subscriber->status = 'subscribed';
                $subscriber->save();
                do_action('fluentcrm_subscriber_status_to_subscribed', $subscriber, $oldStatus);
                do_action('fluentcrm_process_contact_jobs', $subscriber);
            }

            $config = Helper::getDoubleOptinSettings();

            $body = apply_filters(
                'fluentcrm-parse_campaign_email_text',
                $config['after_confirm_message'],
                $subscriber
            );
        }

        wp_enqueue_style(
            'fluentcrm_unsubscribe',
            FLUENTCRM_PLUGIN_URL . 'assets/public/unsubscribe.css',
            [],
            FLUENTCRM_PLUGIN_VERSION
        );

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);


        fluentCrm('view')->render('external.confirmation', [
            'body'       => $body,
            'subscriber' => $subscriber,
            'business'   => $businessSettings
        ]);

        wp_die();
    }

    public function handleContactWebhook()
    {
        if ($this->request->method() != 'POST') {
            return;
        }

        if (!($hash = $this->request->get('hash'))) {
            return;
        }

        if (!($webhook = Webhook::where('key', $hash)->first())) {
            return;
        }

        $validator = FluentCrm('validator')->make($this->request->get(), [
            'email' => 'required|email'
        ])->validate();

        if ($validator->fails()) {
            wp_send_json_error([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $this->request->merge([
            'tags'   => $webhook->value['tags'],
            'lists'  => $webhook->value['lists'],
            'status' => $webhook->value['status']
        ]);

        $subscriber = (new Subscriber)->updateOrCreate(
            $this->request->all(), false, true, true
        );

        $message = $subscriber->wasRecentlyCreated ? 'Created' : 'updated';

        wp_send_json_success([
            'message' => $message,
            'id'      => $subscriber->id
        ], 200);
    }

    public function handleBenchmarkUrl()
    {
        $benchmarkActionId = intval(Arr::get($_REQUEST, 'aid'));
        do_action('fluencrm_benchmark_link_clicked', $benchmarkActionId, fluentcrm_get_current_contact());
    }

    public function manageSubscription()
    {
        $contactId = intval($_GET['ce_id']);
        $hash = sanitize_text_field($_REQUEST['hash']);
        $subscriber = Subscriber::where('id', $contactId)
            ->where('hash', $hash)
            ->first();


        if (!$subscriber) {
            return;
        }

        $this->loadAssets();

        $absEmail = $this->hideEmail($subscriber->email);

        $absEmailHash = md5($absEmail);

        $subscribedLists = $subscriber->lists->keyBy('id');

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);

        $lists = $this->getPublicLists();

        $listOptions = [];
        foreach ($lists as $list) {
            $listOptions[] = [
                'id'       => strval($list->id),
                'label'    => $list->title,
                'selected' => isset($subscribedLists[$list->id])
            ];
        }

        fluentCrm('view')->render('external.manage_subscription', [
            'subscriber'       => $subscriber,
            'abs_email'        => $absEmail,
            'abs_hash'         => $absEmailHash,
            'subscribed_lists' => $subscribedLists,
            'list_options'     => $listOptions,
            'business'         => $businessSettings
        ]);

        exit();

    }

    public function handleManageSubPref()
    {
        $absHash = Arr::get($_REQUEST, '_abs_hash');
        $email = Arr::get($_REQUEST, 'email');
        $originalHash = Arr::get($_REQUEST, '_original_hash');
        $subscriber = Subscriber::with('lists')->where('hash', $originalHash)->first();

        if (!$subscriber) {
            wp_send_json_error([
                'message' => __('Sorry! No subscriber found in the database', 'fluentcrm')
            ], 423);
        }

        $addedLists = [];
        $detachedListIds = [];
        if ($lists = Arr::get($_REQUEST, 'lists')) {
            $publicLists = $this->getPublicLists();
            if ($publicLists) {
                $alreadyListIds = array_keys($subscriber->lists->keyBy('id')->toArray());
                $publicListIds = array_values(array_keys($publicLists->keyBy('id')->toArray()));
                $addedListIds = array_values(array_intersect($lists, $publicListIds));
                if ($alreadyListIds) {
                    $addedListIds = array_diff($addedListIds, $alreadyListIds);
                }
                $detachedListIds = array_values(array_diff($publicListIds, $lists));

                $addedLists = array_combine($addedListIds, array_fill(
                    0, count($addedListIds), ['object_type' => 'FluentCrm\App\Models\Lists']
                ));
            }
        }

        if ($absHash != md5($email)) {
            // Email has been changed
            if (!is_email($email)) {
                wp_send_json_error([
                    'message' => __('Email is not valid. Please provide a valid email', 'fluentcrm')
                ], 423);
            }

            // Check if unique
            $exist = Subscriber::where('email', $email)->where('id', '!=', $subscriber->id)->first();
            if ($exist) {
                wp_send_json_error([
                    'message' => __('The new email has been used to another account. Please use a new email address', 'fluentcrm')
                ], 423);
            }

            $subscriber->status = 'pending';
            $subscriber->email = $email;
            $subscriber->first_name = sanitize_text_field($_REQUEST['first_name']);
            $subscriber->last_name = sanitize_text_field($_REQUEST['last_name']);
            $subscriber->save();
            $subscriber->sendDoubleOptinEmail();

            if ($addedLists) {
                $subscriber->lists()->attach($addedLists);
            }
            if ($detachedListIds) {
                $subscriber->lists()->detach($detachedListIds);
            }

            wp_send_json_success([
                'message' => sprintf(__('A conformation email has been sent to %s. Please confirm your email address to resubscribe with changed email address', 'fluentcrm'), $email)
            ], 200);
        }

        // Just update the info
        $subscriber->first_name = sanitize_text_field($_REQUEST['first_name']);
        $subscriber->last_name = sanitize_text_field($_REQUEST['last_name']);
        $subscriber->save();

        if ($addedLists) {
            $subscriber->lists()->attach($addedLists);
        }

        if ($detachedListIds) {
            $subscriber->lists()->detach($detachedListIds);
        }

        if ($subscriber->status != 'subscribed') {
            $subscriber->sendDoubleOptinEmail();
            wp_send_json_success([
                'message' => sprintf(__('A conformation email has been sent to %s. Please confirm your email address to resubscribe', 'fluentcrm'), $email)
            ], 200);
        }

        wp_send_json_success([
            'message' => __('Your provided information has been successfully updated', 'fluentcrm')
        ], 200);
    }

    private function hideEmail($email)
    {
        list($first, $last) = explode('@', $email);
        $first = str_replace(substr($first, 2), str_repeat('*', strlen($first) - 2), $first);
        $last = explode('.', $last);
        $last_domain = str_replace(substr($last['0'], '1'), str_repeat('*', strlen($last['0']) - 1), $last['0']);
        return $first . '@' . $last_domain . '.' . $last['1'];
    }

    private function loadAssets()
    {
        wp_enqueue_style(
            'fluentcrm_public_pref',
            FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css',
            [],
            FLUENTCRM_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'fluentcrm_public_pref',
            FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.js',
            ['jquery'],
            FLUENTCRM_PLUGIN_VERSION
        );

        wp_localize_script('fluentcrm_public_pref', 'fluentcrm_public_pref', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }

    private function getPublicLists()
    {
        $emailSettings = Helper::getGlobalEmailSettings();
        $lists = [];
        $preListType = Arr::get($emailSettings, 'pref_list_type', 'none');
        if ($preListType == 'filtered_only') {
            $prefListItems = Arr::get($emailSettings, 'pref_list_items', []);
            if ($prefListItems) {
                $lists = Lists::whereIn('id', $prefListItems)->get();
                if ($lists->empty()) {
                    return [];
                }
            }
        } else if ($preListType == 'all') {
            $lists = Lists::get();
            if ($lists->empty()) {
                return [];
            }
        }
        return $lists;
    }
}
