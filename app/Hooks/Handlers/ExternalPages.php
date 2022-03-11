<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Models\Webhook;
use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;


/**
 *  ExternalPages Class
 *
 * For handling all publicly accessible pages for FluentCRM
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
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
        'smart_url'           => 'SmartUrlHandler',
        'webhook'             => 'handleGeneralWebhook', // ?fluentcrm=1&route=webhook&handler=handler_name
        'email_preview'       => 'handlePreviewEmail'
    ];

    protected function getRoute()
    {
        $this->request = FluentCrm('request');

        if ($this->request->has('fluentcrm')) {
            $route = $this->request->get('route');
            if ($route && isset($this->validRoutes[sanitize_text_field($route)])) {
                return $this->validRoutes[sanitize_text_field($route)];
            }
        }
    }

    public function route()
    {
        if (!isset($_GET['fluentcrm'])) {
            return false;
        }

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
                'message' => __('verify_key verification failed', 'fluent-crm')
            ], 423);
        }

        $postdata = \file_get_contents('php://input');

        if (is_wp_error($postdata)) {
            \error_log('SNS ERROR: ' . $postdata->get_error_message());
            wp_send_json([
                'status'  => 423,
                'message' => __('failed', 'fluent-crm')
            ], 423);
        }

        $postdata = \json_decode($postdata, true);

        $notificationType = Arr::get($postdata, 'notificationType');

        if (!$notificationType) {
            $notificationType = Arr::get($postdata, 'Type');
        }

        if ($notificationType == 'SubscriptionConfirmation') {
            \wp_remote_get($postdata['SubscribeURL']);
            wp_send_json([
                'status'  => 200,
                'message' => __('success', 'fluent-crm')
            ], 200);
        }

        if ($notificationType == 'Bounce') {
            $bounce = Arr::get($postdata, 'bounce', []);

            $bounceType = Arr::get($bounce, 'bounceType');
            if ($bounceType == 'Undetermined' || $bounceType == 'Permanent') {
                foreach ($bounce['bouncedRecipients'] as $bouncedRecipient) {
                    $data = [
                        'email'  => $this->extractEmail($bouncedRecipient['emailAddress']),
                        'reason' => Arr::get($bouncedRecipient, 'diagnosticCode'),
                        'status' => 'bounced'
                    ];
                    $this->recordUnsubscribe($data);
                }
            } else {
                foreach ($bounce['bouncedRecipients'] as $bouncedRecipient) {
                    $data = [
                        'email'  => $this->extractEmail($bouncedRecipient['emailAddress']),
                        'reason' => Arr::get($bouncedRecipient, 'diagnosticCode')
                    ];
                    $this->recordSoftBounce($data);
                }
            }
        } else if ($notificationType == 'Complaint') {
            $complaint = Arr::get($postdata, 'complaint', []);
            foreach ($complaint['complainedRecipients'] as $complainedRecipient) {
                $data = [
                    'email'  => $this->extractEmail(Arr::get($complainedRecipient, 'emailAddress')),
                    'reason' => Arr::get($complainedRecipient, 'diagnosticCode'),
                    'status' => 'complained'
                ];
                $this->recordUnsubscribe($data);
            }
        }

        wp_send_json([
            'status'  => 200,
            'message' => __('success', 'fluent-crm')
        ], 200);
    }

    public function recordUnsubscribe($data)
    {
        if (!empty($data['email']) && is_email($data['email'])) {
            $subscriber = Subscriber::where('email', $data['email'])->first();
            if ($subscriber) {
                $oldStatus = $subscriber->status;
                $subscriber->status = $data['status'];
                $subscriber->save();
                fluentcrm_update_subscriber_meta($subscriber->id, 'reason', $data['reason']);
                do_action('fluentcrm_subscriber_status_to_' . $data['status'], $subscriber, $oldStatus);
            } else {
                $contactData = Arr::only($data, ['email', 'status']);
                $contact = Subscriber::store($contactData);
                fluentcrm_update_subscriber_meta($contact->id, 'reason', $data['reason']);
            }
            return true;
        }

        return false;
    }

    private function recordSoftBounce($data)
    {
        if (!empty($data['email']) && is_email($data['email'])) {

            $email = sanitize_text_field($data['email']);
            $subscriber = Subscriber::where('email', $email)->first();
            if (!$subscriber) {
                return false;
            }
            $existingCount = fluentcrm_get_subscriber_meta($subscriber->id, '_soft_bounce_count', 0);
            if (!$existingCount) {
                $existingCount = 0;
            }

            $softCountLimit = apply_filters('fluentcrm_soft_bounce_limit', 1);
            if ($existingCount <= $softCountLimit) {
                fluentcrm_update_subscriber_meta($subscriber->id, '_soft_bounce_count', ($existingCount + 1));
            } else {
                $oldStatus = $subscriber->status;
                $subscriber->status = 'bounced';
                $subscriber->save();
                do_action('fluentcrm_subscriber_status_to_bounced', $subscriber, $oldStatus);
                fluentcrm_update_subscriber_meta($subscriber->id, 'reason', $data['reason']);
            }

        }
    }

    public function unsubscribePage()
    {
        nocache_headers();

        $campaignEmailId = $this->request->get('ce_id');
        $subscriberHash = $this->request->get('hash');

        $subscriber = Subscriber::where('hash', $subscriberHash)->first();

        if (!$subscriber) {
            echo __('Sorry! This is not a valid unsubscribe url. Please try again', 'fluent-crm');
            wp_die();
        }

        if ($campaignEmailId) {
            $campaignEmailId = intval($campaignEmailId);
            $campaignEmail = CampaignEmail::where('id', $campaignEmailId)->first();
            if (!$campaignEmail || !$subscriber || $campaignEmail->subscriber_id != $subscriber->id) {
                echo __('Sorry! This is not a valid unsubscribe url', 'fluent-crm');
                wp_die();
            }
        } else {
            $campaignEmail = (object)[
                'id' => 0
            ];
        }

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);

        $this->loadAssets();

        $absEmail = $this->hideEmail($subscriber->email);
        $absEmailHash = md5($absEmail);

        $texts = apply_filters('fluentcrm_unsubscribe_texts', [
            'heading'             => __('Unsubscribe', 'fluent-crm'),
            'heading_description' => __('We\'re sorry to see you go! Enter your email address to unsubscribe from this list.', 'fluent-crm'),
            'email_label'         => __('Your Email Address', 'fluent-crm'),
            'reason_label'        => __('Please let us know a reason', 'fluent-crm'),
            'button_text'         => __('Unsubscribe', 'fluent-crm')
        ], $subscriber);

        fluentCrm('view')->render('external.unsubscribe', [
            'business'       => $businessSettings,
            'campaign_email' => $campaignEmail,
            'subscriber'     => $subscriber,
            'mask_email'     => $absEmail,
            'abs_hash'       => $absEmailHash,
            'combined_hash'  => md5($subscriber->email . $absEmail),
            'reasons'        => $this->unsubscribeReasons(),
            'texts'          => $texts
        ]);

        exit();
    }

    public function unsubscribeReasons()
    {
        /**
         * Unsubscribe reasons
         * @param array Unsubscribe Reasons
         */
        $reasons = apply_filters('fluentcrm_unsubscribe_reason', [
            'no_loger'             => __('I no longer want to receive these emails', 'fluent-crm'),
            'never_signed_up'      => __('I never signed up for this email list', 'fluent-crm'),
            'emails_inappropriate' => __('The emails are inappropriate', 'fluent-crm'),
            'emails_spam'          => __('The emails are spam', 'fluent-crm')
        ]);

        $reasons['other'] = __('Other (fill in reason below)', 'fluent-crm');

        return $reasons;
    }

    public function handleUnsubscribe()
    {
        $request = FluentCrm('request');
        $data = $request->all();
        $subscriber = Subscriber::where('hash', $data['sub_hash'])->first();
        if (!$subscriber) {
            wp_send_json_error([
                'message' => __('Sorry, No email found based on your data', 'fluent-crm')
            ], 423);
        }

        $oldStatus = $subscriber->status;

        if ($oldStatus != 'unsubscribed') {
            $subscriber->status = 'unsubscribed';
            $subscriber->save();
            /**
             * Fires when a subscriber is unsubscribed
             * @param Subscriber $subscriber
             * @param string $oldStatus
             */
            do_action('fluentcrm_subscriber_status_to_unsubscribed', $subscriber, $oldStatus);
            /**
             * Fires when a subscriber is unsubscribed from Web UI
             * @param Subscriber $subscriber
             * @param array $data Unsubscribe data from Web UI Form
             */
            do_action('fluentcrm_subscriber_unsubscribed_from_web_ui', $subscriber, $data);
        }

        $emailId = intval($request->get('_e_id'));
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

        $campaignEmail = CampaignEmail::find($emailId);

        if ($reason && $campaignEmail) {
            fluentcrm_update_subscriber_meta(
                $subscriber->id, 'unsubscribe_reason', $reason
            );
        }

        if ($campaignEmail) {
            CampaignUrlMetric::maybeInsert([
                'campaign_id'   => $campaignEmail->campaign_id,
                'subscriber_id' => $campaignEmail->subscriber_id,
                'type'          => 'unsubscribe',
                'ip_address'    => FluentCrm()->request->getIp()
            ]);
        }

        $message = __('You are successfully unsubscribed from the email list', 'fluent-crm');
        wp_send_json_success([
            'message' => apply_filters('fluent_crm_unsub_response_message', $message, $subscriber)
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
        nocache_headers();
        header('Content-Type: image/gif');
        // Transparent 1x1 GIF as hex format
        die("\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x90\x00\x00\xff\x00\x00\x00\x00\x00\x21\xf9\x04\x05\x10\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x04\x01\x00\x3b");
    }

    public function confirmationPage()
    {
        if (!($subscriberId = $this->request->get('s_id'))) {
            return;
        }
        nocache_headers();
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

                /**
                 * Fires when a contact's status changed to subscribed
                 * @param Subscriber $subscriber
                 * @param string $oldStatus
                 */
                do_action('fluentcrm_subscriber_status_to_subscribed', $subscriber, $oldStatus);

                do_action('fluentcrm_process_contact_jobs', $subscriber);

                /**
                 * Fires when a contact is subscribed after double opt-in confirmation
                 * @param Subscriber $subscriber
                 */
                do_action('fluentcrm_subscribed_confirmed_via_double_optin', $subscriber);

                SubscriberNote::create([
                    'subscriber_id' => $subscriber->id,
                    'type'          => 'system_log',
                    'title'         => __('Subscriber double opt-in confirmed', 'fluent-crm'),
                    'description'   => __('Subscriber confirmed double opt-in from IP Address:', 'fluent-crm') . ' ' . $this->request->getIp()
                ]);
                if (!is_user_logged_in()) {
                    setcookie("fc_s_hash", $subscriber->hash, time() + 9676800);  /* expire in 28 days */
                }
            }

            $config = Helper::getDoubleOptinSettings();

            if (Arr::get($config, 'after_confirmation_type') == 'redirect' && $url = Arr::get($config, 'after_conf_redirect_url')) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $url = apply_filters('fluentcrm_parse_campaign_email_text', $url, $subscriber);
                    wp_redirect($url, 307);
                    exit();
                }
            }

            $body = apply_filters(
                'fluentcrm_parse_campaign_email_text',
                $config['after_confirm_message'],
                $subscriber
            );
        }

        wp_enqueue_style(
            'fluentcrm_unsubscribe',
            FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css',
            [],
            FLUENTCRM_PLUGIN_VERSION
        );

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);


        fluentCrm('view')->render('external.confirmation', [
            'body'       => $body,
            'subscriber' => $subscriber,
            'business'   => $businessSettings
        ]);

        exit();
    }

    public function handleContactWebhook()
    {
        if ($this->request->method() != 'POST') {
            return;
        }

        $postData = $this->request->get();

        if (empty($postData['email'])) {
            $postData = (array) $this->request->getJson();
        }

        if (empty($hash = $this->request->get('hash'))) {
            wp_send_json_error([
                'message' => __('Invalid Webhook URL', 'fluent-crm'),
                'type'    => 'invalid_webhook_url'
            ], 200);
        }

        if (!($webhook = Webhook::where('key', $hash)->first())) {
            wp_send_json_error([
                'message' => __('Invalid Webhook Hash', 'fluent-crm'),
                'type'    => 'invalid_webhook_hash'
            ], 200);
        }

        if ($keyBy = Arr::get($postData, '_key_by')) {
            if ($keyBy == 'hash' && $hash = Arr::get($postData, '_key_by_value')) {
                $exist = Subscriber::where('hash', $hash)->first();
                if ($exist && empty($postData['email'])) {
                    $postData['email'] = $exist->email;
                }
            }
        }

        $validator = FluentCrm('validator')->make($postData, [
            'email' => 'required|email'
        ])->validate();

        if ($validator->fails()) {
            wp_send_json_error([
                'message' => __('Validation failed.', 'fluent-crm'),
                'errors'  => $validator->errors(),
                'type'    => 'email_validation_failed'
            ], 200);
        }

        $subscriberModel = new Subscriber;

        $mainFields = Arr::only($postData, $subscriberModel->getFillable());
        foreach ($mainFields as $fieldKey => $value) {
            $mainFields[$fieldKey] = sanitize_text_field($value);
        }

        if (isset($postData['full_name'])) {
            $mainFields['full_name'] = sanitize_text_field($postData['full_name']);
        }

        $customValues = [];
        $customColumns = array_map(function ($field) {
            return $field['slug'];
        }, fluentcrm_get_option('contact_custom_fields', []));

        if ($customColumns) {
            $customValues = [];
            foreach (Arr::only($postData, $customColumns) as $itemKey => $value) {
                if (is_string($value)) {
                    $customValues[$itemKey] = sanitize_text_field($value);
                } else {
                    $customValues[$itemKey] = sanitize_text_field($value);
                }
            }
        }

        $tags = Arr::get($webhook->value, 'tags', []);
        if (!empty($postData['tags'])) {
            $postedTags = $postData['tags'];
            if (is_array($postedTags)) {
                $tags = $postedTags;
            }
        }

        $lists = Arr::get($webhook->value, 'lists', []);
        if (!empty($postData['lists'])) {
            $postedLists = $postData['lists'];
            if (is_array($postedLists)) {
                $lists = $postedLists;
            }
        }

        $defaultStatus = Arr::get($webhook->value, 'status', '');

        if ($postedStatus = Arr::get($postData, 'status')) {
            $defaultStatus = $postedStatus;
        }

        $extraData = [
            'tags'   => $tags,
            'lists'  => $lists,
            'status' => $defaultStatus
        ];

        $data = array_merge(
            $mainFields,
            $customValues,
            $extraData
        );

        $data = array_filter($data);

        /**
         * Webhook Contact Data
         *
         * @param array $data
         * @param array $postData
         * @param Webhook $webhook
         */
        $data = apply_filters('fluentcrm_webhook_contact_data', $data, $postData, $webhook);

        $forceUpdate = (!empty($data['status']) && $data['status'] != Arr::get($webhook->value, 'status', '')) || $data['status'] == 'subscribed';

        $subscriber = FluentCrmApi('contacts')->createOrUpdate($data, $forceUpdate);

        if ($subscriber->status == 'pending') {
            $subscriber->sendDoubleOptinEmail();
        }

        $message = $subscriber->wasRecentlyCreated ? 'created' : 'updated';

        wp_send_json_success([
            'message' => $message,
            'id'      => $subscriber->id,
            'type'    => 'success'
        ], 200);
    }

    public function handleBenchmarkUrl()
    {
        $benchmarkActionId = intval(Arr::get($_REQUEST, 'aid'));
        if ($benchmarkActionId) {
            /**
             * Fires when a benchmark linked is clicked
             * @param int $benchmarkActionId
             * @param Subscriber|false Current Contact Object or false if not available
             */
            do_action('fluencrm_benchmark_link_clicked', $benchmarkActionId, fluentcrm_get_current_contact());
        }
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

        echo $this->getManageSubscriptionHtml($subscriber);

        exit();

    }


    public function getManageSubscriptionHtml($subscriber)
    {
        $absEmail = $this->hideEmail($subscriber->email);

        $absEmailHash = md5($absEmail);

        $subscribedLists = $subscriber->lists->keyBy('id');

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);

        $lists = Helper::getPublicLists();

        $listOptions = [];
        foreach ($lists as $list) {
            $listOptions[] = [
                'id'       => strval($list->id),
                'label'    => $list->title,
                'selected' => isset($subscribedLists[$list->id])
            ];
        }

        return fluentCrm('view')->make('external.manage_subscription', [
            'subscriber'       => $subscriber,
            'abs_email'        => $absEmail,
            'abs_hash'         => $absEmailHash,
            'subscribed_lists' => $subscribedLists,
            'list_options'     => $listOptions,
            'business'         => $businessSettings
        ]);
    }

    public function handleManageSubPref()
    {
        $absHash = Arr::get($_REQUEST, '_abs_hash');
        $email = Arr::get($_REQUEST, 'email');
        $originalHash = Arr::get($_REQUEST, '_original_hash');
        $subscriber = Subscriber::with('lists')->where('hash', $originalHash)->first();

        if (!$subscriber) {
            wp_send_json_error([
                'message' => __('Sorry! No subscriber found in the database', 'fluent-crm')
            ], 423);
        }

        $addedLists = [];
        $detachedListIds = [];
        $publicLists = Helper::getPublicLists();
        if ($publicLists) {
            $lists = Arr::get($_REQUEST, 'lists', []);
            $alreadyListIds = array_keys($subscriber->lists->keyBy('id')->toArray());
            $publicListIds = array_values(array_keys($publicLists->keyBy('id')->toArray()));
            $addedListIds = array_values(array_intersect($lists, $publicListIds));
            if ($alreadyListIds) {
                $addedListIds = array_diff($addedListIds, $alreadyListIds);
            }
            $detachedListIds = array_values(array_diff($publicListIds, $lists));

            $addedLists = $addedListIds;
        }

        if ($absHash != md5($email)) {
            // Email has been changed
            if (!is_email($email)) {
                wp_send_json_error([
                    'message' => __('Email is not valid. Please provide a valid email', 'fluent-crm')
                ], 423);
            }

            // Check if unique
            $exist = Subscriber::where('email', $email)->where('id', '!=', $subscriber->id)->first();
            if ($exist) {
                wp_send_json_error([
                    'message' => __('The new email has been used to another account. Please use a new email address', 'fluent-crm')
                ], 423);
            }

            $subscriber->status = 'pending';
            $subscriber->email = $email;
            $subscriber->first_name = sanitize_text_field($_REQUEST['first_name']);
            $subscriber->last_name = sanitize_text_field($_REQUEST['last_name']);
            $subscriber->save();
            $subscriber->sendDoubleOptinEmail();

            if ($addedLists) {
                $subscriber->attachLists($addedLists);
            }
            if ($detachedListIds) {
                $subscriber->detachLists($detachedListIds);
            }

            wp_send_json_success([
                'message' => sprintf(__('A conformation email has been sent to %s. Please confirm your email address to resubscribe with changed email address', 'fluent-crm'), $email)
            ], 200);
        }

        // Just update the info
        $subscriber->first_name = sanitize_text_field($_REQUEST['first_name']);
        $subscriber->last_name = sanitize_text_field($_REQUEST['last_name']);
        $subscriber->save();

        if ($addedLists) {
            $subscriber->attachLists($addedLists);
        }

        if ($detachedListIds) {
            $subscriber->detachLists($detachedListIds);
        }

        if ($subscriber->status != 'subscribed') {
            $subscriber->sendDoubleOptinEmail();
            wp_send_json_success([
                'message' => sprintf(__('A conformation email has been sent to %s. Please confirm your email address to resubscribe', 'fluent-crm'), $email)
            ], 200);
        }

        wp_send_json_success([
            'message' => __('Your provided information has been successfully updated', 'fluent-crm')
        ], 200);
    }

    public function SmartUrlHandler()
    {
        if (isset($_REQUEST['slug'])) {
            do_action('fluentcrm_smartlink_clicked', sanitize_text_field($_REQUEST['slug']));
        }
    }

    private function hideEmail($email)
    {
        list($first, $last) = explode('@', $email);
        $first = str_replace(substr($first, 2), str_repeat('*', strlen($first) - 2), $first);
        $last = explode('.', $last);
        $last_domain = str_replace(substr($last['0'], '1'), str_repeat('*', strlen($last['0']) - 1), $last['0']);
        array_shift($last);
        return $first . '@' . $last_domain . '.' . implode('.', $last);
    }

    private function loadAssets()
    {
        if (defined('CT_VERSION')) {
            // oxygen page compatibility
            remove_action('wp_head', 'oxy_print_cached_css', 999999);
        }

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

    private function extractEmail($from_email)
    {
        $bracket_pos = strpos($from_email, '<');
        if (false !== $bracket_pos) {
            $from_email = substr($from_email, $bracket_pos + 1);
            $from_email = str_replace('>', '', $from_email);
            $from_email = trim($from_email);
        }

        if (is_email($from_email)) {
            return $from_email;
        }
        return false;
    }

    public function handleBackgroundProcessCallback()
    {
        $callbackName = sanitize_text_field($_REQUEST['callback_name']);
        if (!wp_verify_nonce($_REQUEST['nonce'], 'fluentcrm_callback_for_background')) {
            error_log($callbackName . ' Security Check Failed');
            die('Security Check Failed');
        }
        $data = $_REQUEST['payload'];
        do_action($callbackName, $data);
        echo 'success';
        die();
    }

    public function handleGeneralWebhook()
    {
        $data = $_REQUEST;

        $handler = Arr::get($_REQUEST, 'handler');

        do_action('fluentcrm_webhook_to_' . $handler, $data);

        wp_send_json([
            'message' => __('No Action found', 'fluent-crm'),
            'action'  => 'fluentcrm_webhook_to_' . $handler
        ]);
    }

    public function handlePreviewEmail()
    {
        $emailHash = sanitize_text_field(Arr::get($_REQUEST, '_e_hash'));

        $email = CampaignEmail::where('email_hash', $emailHash)->with(['campaign', 'subscriber'])->first();
        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);

        if (!$email || !$email->campaign || !$email->subscriber) {
            fluentCrm('view')->render('external.view_on_browser', [
                'business'      => $businessSettings,
                'email_heading' => '',
                'email_body'    => '<h2 style=\'text-align: center; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif\'>Sorry, web preview could not be loaded</h2>',
                'cssAssets'     => [
                    FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css?version=' . FLUENTCRM_PLUGIN_VERSION
                ],
                'footer_text'   => ''
            ]);
            exit();
        }

        if ($email->campaign && Arr::get($email->campaign->settings, 'template_config')) {
            $templateConfig = wp_parse_args($email->campaign->settings['template_config'], Helper::getTemplateConfig($email->campaign->design_template));
        } else {
            $templateConfig = Helper::getTemplateConfig();
        }

        $emailBody = (new BlockParser($email->subscriber))->parse($email->campaign->email_body);

        $footerText = Arr::get(Helper::getGlobalEmailSettings(), 'email_footer', '');
        $footerText = apply_filters('fluentcrm_parse_campaign_email_text', $footerText, $email->subscriber);

        /**
         * Email Footer Text For WebUI
         * @param string $footerText
         * @param CampaignEmail $email
         */
        $footerText = apply_filters('fluentcrm_web_email_footer_text', $footerText, $email);

        $templateData = [
            'preHeader'   => ($email->campaign) ? $email->campaign->email_pre_header : '',
            'email_body'  => $emailBody,
            'footer_text' => '',
            'config'      => $templateConfig
        ];

        $content = apply_filters('fluentcrm_email-design-template-classic',
            $emailBody,
            $templateData,
            $email->campaign,
            $email->subscriber
        );

        fluentCrm('view')->render('external.view_on_browser', [
            'business'      => $businessSettings,
            'email_heading' => $email->email_subject,
            'email_body'    => $content,
            'cssAssets'     => [
                FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css?version=' . FLUENTCRM_PLUGIN_VERSION
            ],
            'footer_text'   => $footerText
        ]);

        exit();
    }
}
