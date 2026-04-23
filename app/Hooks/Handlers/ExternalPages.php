<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\Meta;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Models\SubscriberPivot;
use FluentCrm\App\Models\Webhook;
use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Libs\Mailer\Mailer;
use FluentCrm\App\Services\Libs\Parser\Parser;
use FluentCrm\App\Services\Sanitize;
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
        'email_preview'       => 'handlePreviewEmail',
        'general'             => 'handleGeneralRequest'
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
            do_action('litespeed_control_set_nocache', 'nocache due to fluentcrm dynamic data');
            $this->{$route}();
        }
    }

    public function bounceHandler()
    {
        $provider = sanitize_text_field($this->request->get('provider'));

        if ($provider == 'ses') {
            $this->bounceHandlerSES();
        }
    }

    public function bounceHandlerSES()
    {
        // check bounce key
        $sesBounceKey = fluentcrm_get_option('_fc_bounce_key');
        $verifyKey = Arr::get($_REQUEST, 'verify_key');

        if ($verifyKey != $sesBounceKey) {
            wp_send_json([
                'status'  => 422,
                'message' => __('verify_key verification failed', 'fluent-crm')
            ], 422);
        }

        $postdata = \file_get_contents('php://input');

        if (is_wp_error($postdata)) {
            \error_log('SNS ERROR: ' . $postdata->get_error_message());
            wp_send_json([
                'status'  => 423,
                'message' => __('failed', 'fluent-crm')
            ], 422);
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


        if (empty($postdata['notificationType']) && !empty($postdata['Message'])) {
            $postdata = json_decode($postdata['Message'], true);
            $notificationType = Arr::get($postdata, 'notificationType', $notificationType);
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
                $reason = Arr::get($complainedRecipient, 'diagnosticCode');
                if (!$reason) {
                    $reason = 'SES complained received as: ' . Arr::get($complaint, 'complaintFeedbackType');
                }
                $data = [
                    'email'  => $this->extractEmail(Arr::get($complainedRecipient, 'emailAddress')),
                    'reason' => $reason,
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
                if ($oldStatus == $data['status']) {
                    return false;
                }

                $subscriber = $subscriber->updateStatus($data['status']);
                $key = 'reason';

                if ($data['status'] == 'unsubscribed') {
                    $key = 'unsubscribe_reason';
                }

                fluentcrm_update_subscriber_meta($subscriber->id, $key, $data['reason']);
            } else {
                $willStore = apply_filters('fluent_crm/bounced_email_store', true);
                if ($willStore) {
                    $contactData = Arr::only($data, ['email', 'status']);
                    if (!isset($contactData['created_at'])) {
                        $contactData['created_at'] = current_time('mysql');
                    }

                    $key = 'reason';

                    if ($data['status'] == 'unsubscribed') {
                        $key = 'unsubscribe_reason';
                    }

                    $contact = Subscriber::store($contactData);
                    fluentcrm_update_subscriber_meta($contact->id, $key, $data['reason']);
                }
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
                $contactData = Arr::only($data, ['email']);
                if (!isset($contactData['created_at'])) {
                    $contactData['created_at'] = current_time('mysql');
                }
                $contactData['status'] = 'bounced';
                $contact = Subscriber::store($contactData);
                fluentcrm_update_subscriber_meta($contact->id, 'reason', $data['reason']);
                return true;
            }

            $existingCount = fluentcrm_get_subscriber_meta($subscriber->id, '_soft_bounce_count', 0);
            if (!$existingCount) {
                $existingCount = 0;
            }

            /**
             * Modify the soft bounce limit.
             *
             * This filter allows you to change the default soft bounce limit.
             *
             * @param int The default soft bounce limit. Default is 1.
             * @since 2.7.0
             *
             */
            $softCountLimit = apply_filters('fluent_crm/soft_bounce_limit', 1);
            if ($existingCount <= $softCountLimit) {
                fluentcrm_update_subscriber_meta($subscriber->id, '_soft_bounce_count', ($existingCount + 1));
            } else {
                $oldStatus = $subscriber->status;
                if ($oldStatus != 'bounced') {
                    $subscriber = $subscriber->updateStatus('bounced');
                    fluentcrm_update_subscriber_meta($subscriber->id, 'reason', $data['reason']);
                }
            }
        }
    }

    public function unsubscribePage()
    {

        nocache_headers();

        $campaignEmailId = $this->request->get('ce_id');
        $managedSecureHash = $this->request->get('secure_hash');
        $subscriber = null;

        if ($managedSecureHash) {
            $subscriber = fluentCrmApi('contacts')->getContactByManagedSecureHash($managedSecureHash);
        }

        // check if this is a POST request
        if ($this->request->method() == 'POST') {
            // This is List-Unsubscribe request
            if ($subscriber && $subscriber->status != 'unsubscribed') {
                if ($campaignEmailId) {
                    $campaignEmail = CampaignEmail::find($campaignEmailId);
                } else {
                    $campaignEmail = null;
                }

                do_action('fluent_crm/before_contact_unsubscribe_from_email', $subscriber, $campaignEmail, 'from_header');

                $subscriber = $subscriber->updateStatus('unsubscribed');

                fluentcrm_update_subscriber_meta($subscriber->id, 'unsubscribe_reason', 'Unsubscribe From List Header');
                if ($campaignEmail) {
                    CampaignUrlMetric::maybeInsert([
                        'campaign_id'   => $campaignEmail->campaign_id,
                        'subscriber_id' => $campaignEmail->subscriber_id,
                        'type'          => 'unsubscribe'
                    ]);
                }
            }

            wp_send_json_success([
                'message' => __("You've successfully unsubscribed from our email list.", 'fluent-crm')
            ], 200);
            return;
        }

        if (!$subscriber) {
            $this->unsubscribeRequestForm();
            return;
        }

        setcookie("fc_hash_secure", $subscriber->getSecureHash(), time() + 7776000, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 90 days */

        if ($campaignEmailId) {
            $campaignEmailId = (int)$campaignEmailId;
            $campaignEmail = CampaignEmail::where('id', $campaignEmailId)->first();
            if (!$campaignEmail || !$subscriber || $campaignEmail->subscriber_id != $subscriber->id) {
                $this->unsubscribeRequestForm();
                return;
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

        /**
         * Define the unsubscribe texts displayed on the unsubscribe page in FluentCRM.
         *
         * @param array {
         *     An array of texts to be displayed on the unsubscribe page.
         *
         * @type string $heading The heading text.
         * @type string $heading_description The description text under the heading.
         * @type string $email_label The label for the email address input field.
         * @type string $reason_label The label for the reason input field.
         * @type string $button_text The text for the unsubscribe button.
         * }
         * @param object $subscriber The subscriber object.
         * @since 2.7.0
         *
         */
        $texts = apply_filters('fluent_crm/unsubscribe_texts', [
            'heading'             => __('Unsubscribe', 'fluent-crm'),
            'heading_description' => __('We\'re sorry to see you go!', 'fluent-crm'),
            'email_label'         => __('Your Email Address', 'fluent-crm'),
            'reason_label'        => __('Please let us know a reason', 'fluent-crm'),
            'button_text'         => __('Unsubscribe', 'fluent-crm')
        ], $subscriber);

        $complianceSettings = Helper::getComplianceSettings();
        $data = [
            'business'              => $businessSettings,
            'campaign_email'        => $campaignEmail,
            'subscriber'            => $subscriber,
            'mask_email'            => $absEmail,
            'abs_hash'              => $absEmailHash,
            'combined_hash'         => md5($subscriber->email . $absEmail),
            'reasons'               => $this->unsubscribeReasons(),
            'secure_hash'           => fluentCrmGetContactManagedHash($subscriber->id),
            'texts'                 => $texts,
            'one_click_unsubscribe' => Arr::get($complianceSettings, 'one_click_unsubscribe')
        ];

        add_action('wp_loaded', function () use ($data) {
            fluentCrm('view')->render('external.unsubscribe', $data);
            exit();
        }, 1);
    }

    public function unsubscribeRequestForm()
    {
        do_action('fluent_crm/doing_unsubscribe_request_form');

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);
        $this->loadAssets();
        $data = [
            'business' => $businessSettings,
        ];

        add_action('wp_loaded', function () use ($data) {
            fluentCrm('view')->render('external.unsubscribe_request_form', $data);
            exit();
        }, 1);
    }

    public function manageSubscriptionRequestForm()
    {
        do_action('fluent_crm/doing_unsubscribe_request_form');

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);
        $this->loadAssets();
        $data = [
            'business' => $businessSettings,
        ];

        add_action('wp_loaded', function () use ($data) {
            fluentCrm('view')->render('external.manage_subscription_request_form', $data);
            exit();
        }, 1);
    }

    public function handleUnsubscribeRequestAjax()
    {
        $email = Arr::get($_REQUEST, 'email');

        if (!$email || !is_email($email)) {
            wp_send_json_error([
                'message' => __('Please provide a valid email address', 'fluent-crm')
            ], 422);
        }

        $subscriber = Subscriber::where('email', $email)->first();

        if (!$subscriber) {
            wp_send_json_error([
                'message' => __('Sorry! We could not verify your email address', 'fluent-crm')
            ], 422);
        }

        if ($subscriber->status != 'subscribed') {
            wp_send_json_error([
                'message' => __('Looks like you are already unsubscribed', 'fluent-crm')
            ], 422);
        }

        // Let's send unsubscribe email with link
        $data = [
            'business'        => fluentcrmGetGlobalSettings('business_settings', []),
            'unsubscribe_url' => add_query_arg(array_filter([
                'fluentcrm'   => 1,
                'route'       => 'unsubscribe',
                'secure_hash' => fluentCrmGetContactManagedHash($subscriber->id)
            ]), site_url('/')),
            'subscriber'      => $subscriber
        ];

        $emailBody = (string)fluentCrm('view')->make('external.unsubscribe_request_email', $data);
        $emailSubject = __('Confirm your unsubscribe Request', 'fluent-crm');

        do_action('fluent_crm/before_unsubscribe_request_email', $subscriber, $data);

        Mailer::send([
            'to'      => [
                'email' => $subscriber->email,
                'name'  => $subscriber->full_name
            ],
            'subject' => $emailSubject,
            'body'    => $emailBody
        ]);

        wp_send_json_success([
            'message' => __("We've sent an email to your inbox that contains a link to unsubscribe from our mailing list. Please check your email address and unsubscribe.", 'fluent-crm')
        ]);
    }

    public function handleManageSubRequestAjax()
    {

        $email = Arr::get($_REQUEST, 'email');

        if (!$email || !is_email($email)) {
            wp_send_json_error([
                'message' => __('Please provide a valid email address', 'fluent-crm')
            ], 422);
        }

        $subscriber = Subscriber::where('email', $email)->first();

        if (!$subscriber) {
            wp_send_json_error([
                'message' => __('Sorry! We could not verify your email address', 'fluent-crm')
            ], 422);
        }

        // Let's send unsubscribe email with link
        $data = [
            'business'                => fluentcrmGetGlobalSettings('business_settings', []),
            'manage_subscription_url' => add_query_arg(array_filter([
                'fluentcrm'   => 1,
                'route'       => 'manage_subscription',
                'ce_id'       => $subscriber->id,
                'secure_hash' => fluentCrmGetContactManagedHash($subscriber->id)
            ]), site_url('/')),
            'subscriber'              => $subscriber
        ];

        $emailBody = (string)fluentCrm('view')->make('external.manage_sub_request_email', $data);
        $emailSubject = __('Your Email preferences URL', 'fluent-crm');

        do_action('fluent_crm/before_manage_sub_request_email', $subscriber, $data);

        Mailer::send([
            'to'      => [
                'email' => $subscriber->email,
                'name'  => $subscriber->full_name
            ],
            'subject' => $emailSubject,
            'body'    => $emailBody
        ]);

        wp_send_json_success([
            'message' => __("We've sent an email to your inbox that contains a link to email management from. Please check your email address to get the link.", 'fluent-crm')
        ]);
    }

    public function unsubscribeReasons()
    {
        /**
         * Define the list of unsubscribe reasons in FluentCRM.
         *
         * This filter allows modification of the reasons provided to users when they choose to unsubscribe from emails.
         *
         * @param array {
         *     An associative array of unsubscribe reasons.
         *
         * @type string $no_longer Reason for no longer wanting to receive emails.
         * @type string $never_signed_up Reason for never signing up for the email list.
         * @type string $emails_inappropriate Reason for finding the emails inappropriate.
         * @type string $emails_spam Reason for considering the emails as spam.
         * @type string $other Reason for other, with a prompt to fill in the reason.
         * }
         * @since 2.5.1
         *
         */
        return apply_filters('fluent_crm/unsubscribe_reasons', [
            'no_longer'            => __('I no longer want to receive these emails', 'fluent-crm'),
            'never_signed_up'      => __('I never signed up for this email list', 'fluent-crm'),
            'emails_inappropriate' => __('The emails are inappropriate', 'fluent-crm'),
            'emails_spam'          => __('The emails are spam', 'fluent-crm'),
            'other'                => __('Other (fill in reason below)', 'fluent-crm')
        ]);
    }

    public function handleUnsubscribe()
    {
        $request = FluentCrm('request');
        $data = $request->all();

        $subscriber = null;

        if ($secureHash = $request->get('secure_hash')) {
            $subscriber = fluentCrmApi('contacts')->getContactByManagedSecureHash($secureHash);
        }

        if (!$subscriber) {
            wp_send_json_error([
                'message' => __('Sorry, No email found based on your data', 'fluent-crm')
            ], 422);
        }

        $oldStatus = $subscriber->status;


        $emailId = intval($request->get('_e_id'));
        if ($emailId) {
            $campaignEmail = CampaignEmail::find($emailId);
        } else {
            $campaignEmail = null;
        }

        do_action('fluent_crm/before_contact_unsubscribe_from_email', $subscriber, $campaignEmail, 'web_ui');


        if ($oldStatus != 'unsubscribed') {
            $subscriber = $subscriber->updateStatus('unsubscribed');

            /**
             * Fires when a subscriber is unsubscribed from Web UI
             * @param Subscriber $subscriber
             * @param array $data Unsubscribe data from Web UI Form
             */
            do_action('fluent_crm/subscriber_unsubscribed_from_web_ui', $subscriber, $data);
        }


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

        fluentcrm_update_subscriber_meta($subscriber->id, 'unsubscribe_reason', $reason);

        if ($campaignEmail) {
            CampaignUrlMetric::maybeInsert([
                'campaign_id'   => $campaignEmail->campaign_id,
                'subscriber_id' => $campaignEmail->subscriber_id,
                'type'          => 'unsubscribe',
                'ip_address'    => FluentCrm()->request->getIp(fluentCrmWillAnonymizeIp())
            ]);
        }

        $redirect = Arr::get(Helper::getGlobalEmailSettings(), 'unsubscribe_redirect', '');
        if (!$redirect) {
            $redirect = false;
        }

        if ($redirect) {
            /**
             * Determine the redirect URL for a campaign email in FluentCRM.
             *
             * This filter allows you to modify the redirect URL for a campaign email.
             *
             * @param string $redirect The redirect URL.
             * @param object $subscriber The subscriber object.
             * @since 2.7.40
             *
             */
            $redirect = apply_filters('fluent_crm/parse_campaign_email_text', $redirect, $subscriber);
            $redirect = str_replace(['&amp;', '+'], ['&', '%2B'], $redirect);
        }

        if (!$reason) {
            $reason = 'n/a';
        }
        SubscriberNote::create([
            'subscriber_id' => $subscriber->id,
            'type'          => 'system_log',
            'title'         => __('Unsubscribed', 'fluent-crm'),
            'description'   => sprintf(__('Subscriber unsubscribed from IP Address: %1s <br />Reason: %2s', 'fluent-crm'), FluentCrm()->request->getIp(fluentCrmWillAnonymizeIp()), $reason)
        ]);

        $message = __("You've successfully unsubscribed from our email list.", 'fluent-crm');
        wp_send_json_success([
            /**
             * Determine the unsubscribe response message in FluentCRM.
             *
             * This filter allows modification of the message displayed to the user
             * when they unsubscribe from a mailing list.
             *
             * @param string $message The default unsubscribe response message.
             * @param object $subscriber The subscriber object containing subscriber or contact details.
             * @since 2.7.0
             *
             */
            'message'      => apply_filters('fluent_crm/unsub_response_message', $message, $subscriber),
            /**
             * Determine the URL to which the user is redirected after unsubscribing in FluentCRM.
             *
             * @param string $redirect The default redirect URL.
             * @param object $subscriber The subscriber or contact object.
             * @since 2.7.0
             *
             */
            'redirect_url' => apply_filters('fluent_crm/unsub_redirect_url', $redirect, $subscriber)
        ], 200);
    }

    private function trackEmailOpen()
    {
        $mailHash = sanitize_text_field($this->request->get('_e_hash'));

        $emailId = (int)$this->request->get('_e_id');

        if ($emailId) {
            $email = CampaignEmail::where('id', $emailId)->first();
        } else {
            $email = CampaignEmail::where('email_hash', $mailHash)->first();
        }

        if ($email && $email->email_hash != $mailHash) {
            $email = null;
        }

        if ($email) {
            $updated = fluentCrmDb()->table('fc_campaign_emails')
                ->where('id', $email->id)
                ->where('is_open', 0)
                ->update([
                    'is_open' => 1
                ]); // returns affected rows

            if ($updated) {
                CampaignUrlMetric::maybeInsert([
                    'type'          => 'open',
                    'campaign_id'   => $email->campaign_id,
                    'subscriber_id' => $email->subscriber_id,
                    'ip_address'    => FluentCrm()->request->getIp(fluentCrmWillAnonymizeIp())
                ]);

                do_action('fluent_crm/email_opened', $email);
            }
        }

        if (ini_get('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        //turn off gzip compression
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }

        @ini_set('zlib.output_compression', 'Off');

        // we are sending 1x1 pixel transparent gif image
        header('Content-Encoding: none');
        header('Content-Type: image/gif');
        header('Content-Length: 43');
        header('Cache-Control: private, no-cache, no-cache=Set-Cookie, proxy-revalidate');
        header('Expires: Wed, 11 Jan 2000 12:59:00 GMT');
        header('Last-Modified: Wed, 11 Jan 2006 12:59:00 GMT');
        header('Pragma: no-cache');
        // Transparent 1x1 GIF as hex format
        $image = base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');

        die($image);
    }

    public function confirmationPage()
    {

        $hash = sanitize_text_field($this->request->get('hash'));

        if (!$hash) {
            return;
        }

        nocache_headers();

        $secureHash = sanitize_text_field($this->request->get('secure_hash'));
        $subscriber = false;

        if ($secureHash) {
            $subscriber = fluentCrmApi('contacts')->getContactBySecureHash($secureHash);
        }

        if (!$subscriber) {
            $body = __('Sorry! Your confirmation url is not valid', 'fluent-crm');
        } else {
            if (!is_user_logged_in()) {
                $secureHash = fluentCrmGetContactSecureHash($subscriber->id);
                setcookie("fc_hash_secure", $secureHash, time() + 7776000, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 90 days */
            }

            do_action('fluent_crm/track_activity_by_subscriber', $subscriber);

            if ($subscriber->status != 'subscribed') {

                $subscriber = $subscriber->updateStatus('subscribed');

                do_action('fluentcrm_process_contact_jobs', $subscriber);

                /**
                 * Fires when a contact is subscribed after double opt-in confirmation
                 * @param Subscriber $subscriber
                 */
                do_action('fluent_crm/subscriber_confirmed_via_double_optin', $subscriber);

                SubscriberNote::create([
                    'subscriber_id' => $subscriber->id,
                    'type'          => 'system_log',
                    'title'         => __('Subscriber double opt-in confirmed', 'fluent-crm'),
                    'description'   => __('Subscriber confirmed double opt-in from IP Address:', 'fluent-crm') . ' ' . $this->request->getIp()
                ]);

                /**
                 * Determine whether to use cookies for FluentCRM.
                 *
                 * This filter allows you to control whether cookies should be used for FluentCRM. It is used in conjunction with the `setcookie` function when a user is not logged in.
                 *
                 * @param bool Whether to use cookies. Default true.
                 * @since 2.7.0
                 *
                 */
                if (!is_user_logged_in() && apply_filters('fluent_crm/will_use_cookie', true)) {
                    setcookie("fc_hash_secure", fluentCrmGetContactSecureHash($subscriber->id), time() + 7776000, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 90 days */
                }
            }

            $listIdOfSubscriber = Helper::latestListIdOfSubscriber($subscriber->id);

            $config = null;
            if ($listIdOfSubscriber) {
                $globalDoubleOptin = fluentcrm_get_list_meta($listIdOfSubscriber, 'global_double_optin');
                if ($globalDoubleOptin && $globalDoubleOptin->value == 'no') {
                    $meta = fluentcrm_get_meta($listIdOfSubscriber, 'FluentCrm\App\Models\Lists', 'double_optin_settings', []);
                    $config = $meta ? $meta->value : null;
                }
            }

            if (!$config) {
                $config = Helper::getDoubleOptinSettings();
            }

            /**
             * Determine the double opt-in options configuration in FluentCRM.
             *
             * This filter allows modification of the double opt-in options configuration.
             *
             * @param array $config The double opt-in options configuration array.
             * @param object $subscriber The subscriber object.
             * @return array The modified double opt-in options configuration array.
             * @since 2.6.0
             *
             */
            $config = apply_filters('fluent_crm/double_optin_options', $config, $subscriber);

            if (Arr::get($config, 'after_confirmation_type') == 'redirect' && $url = Arr::get($config, 'after_conf_redirect_url')) {
                /**
                 * Determine the campaign email text URL in FluentCRM.
                 *
                 * This filter allows you to modify the URL used in the campaign email text.
                 *
                 * @param string $url The original URL.
                 * @param object $subscriber The subscriber object.
                 * @return string The filtered URL.
                 * @since 2.7.0
                 *
                 */
                $url = apply_filters('fluent_crm/parse_campaign_email_text', $url, $subscriber);
                if ($url) {
                    $url = trim($url);
                    $url = str_replace(['&amp;', '+'], ['&', '%2B'], $url);
                    wp_redirect($url, 307);
                    exit();
                }
            }

            /**
             * Determine the campaign email text after confirmation message in FluentCRM.
             *
             * This filter allows modification of the campaign email text after the confirmation message.
             *
             * @param string $after_confirm_message The message displayed after confirmation.
             * @param object $subscriber The subscriber object.
             * @since 2.7.0
             *
             */
            $body = apply_filters('fluent_crm/parse_campaign_email_text', $config['after_confirm_message'], $subscriber);

            /**
             * Filter the confirmation text before it is parsed in FluentCRM.
             *
             * This filter allows you to modify the CRM text before it is parsed and processed.
             *
             * @param string $body The text to be parsed.
             * @param object $subscriber The subscriber object containing subscriber data.
             * @since 2.7.0
             *
             */
            $body = apply_filters('fluent_crm/parse_extended_crm_text', $body, $subscriber);

        }

        wp_enqueue_style(
            'fluentcrm_unsubscribe',
            FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css',
            [],
            FLUENTCRM_PLUGIN_VERSION
        );

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);

        $data = [
            'body'       => $body,
            'subscriber' => $subscriber,
            'business'   => $businessSettings
        ];
        add_action('wp_loaded', function () use ($data) {
            fluentCrm('view')->render('external.confirmation', $data);
            exit();
        }, 1);
    }

    public function handleContactWebhook()
    {
        if ($this->request->method() != 'POST') {
            wp_send_json_error([
                'message' => __('Webhook must need to be as POST Method', 'fluent-crm'),
                'type'    => 'invalid_request_method'
            ], 200);
        }

        $postData = $this->request->get();

        if (empty($postData['email'])) {
            $postData = (array)$this->request->getJson();
        }

        if (empty($hash = $this->request->get('hash'))) {
            wp_send_json_error([
                'message' => __('Invalid Webhook URL', 'fluent-crm'),
                'type'    => 'invalid_webhook_url'
            ], 200);
        }

        $webhook = Webhook::where('key', $hash)->first();

        if (!$webhook) {
            wp_send_json_error([
                'message' => __('Invalid Webhook Hash', 'fluent-crm'),
                'type'    => 'invalid_webhook_hash'
            ], 200);
        }

        /**
         * Manage the incoming webhook data in FluentCRM.
         *
         * This filter allows modification of the incoming webhook data before it is processed.
         *
         * @param array $postData The incoming webhook data.
         * @param string $webhook The webhook identifier.
         * @param object $request The request object containing the webhook data.
         *
         * @return array The filtered webhook data.
         * @since 2.5.95
         *
         */
        $postData = apply_filters('fluent_crm/incoming_webhook_data', $postData, $webhook, $this->request);

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

        if (isset($postData['names'])) {
            $postData['first_name'] = Arr::get($postData['names'], 'first_name', '');
            $postData['last_name'] = Arr::get($postData['names'], 'last_name', '');
        }

        if (isset($postData['full_name'])) {
            $postData = Subscriber::explodeFullName($postData);
        }

        $subscriberModel = new Subscriber;

        $mainFields = Arr::only($postData, $subscriberModel->getFillable());

        foreach ($mainFields as $fieldKey => $value) {
            if (is_array($value)) {
                $mainFields[$fieldKey] = map_deep($value, 'sanitize_text_field');
            } else {
                $mainFields[$fieldKey] = wp_unslash(sanitize_text_field($value));
            }
        }


        $customValues = [];
        $customColumns = array_map(function ($field) {
            return $field['slug'];
        }, fluentcrm_get_option('contact_custom_fields', []));

        if ($customColumns) {
            $customValues = [];
            foreach (Arr::only($postData, $customColumns) as $itemKey => $value) {
                if (is_string($value)) {
                    $customValues[$itemKey] = sanitize_textarea_field($value);
                } else {
                    $customValues[$itemKey] = map_deep($value, 'sanitize_textarea_field');
                }
            }
        }

        $tags = array_filter((array)Arr::get($postData, 'tags', []));
        $lists = array_filter((array)Arr::get($postData, 'lists', []));

        if (!$tags) {
            $tags = Arr::get($webhook->value, 'tags', []);
        }

        if (!$lists) {
            $lists = Arr::get($webhook->value, 'lists', []);
        }


        $companies = Helper::maybeParseAndFilterWebhookData($webhook, $postData, 'companies');
        $defaultStatus = Arr::get($webhook->value, 'status', '');

        $extraData = [
            'detach_tags'  => Arr::get($postData, 'detach_tags', []),
            'detach_lists' => Arr::get($postData, 'detach_lists', []),
            'tags'         => $tags,
            'lists'        => $lists,
            'companies'    => $companies,
            'status'       => $defaultStatus
        ];

        $data = array_merge(
            $mainFields,
            $customValues,
            $extraData
        );

        $data = array_filter($data);

        /**
         * Manage the contact data for the webhook.
         *
         * This filter allows modification of the contact data before it is processed by the webhook.
         *
         * @param array $data The contact data to be filtered.
         * @param array $postData The original post data received from the webhook.
         * @param string $webhook The identifier for the webhook.
         * @since 2.5.1
         *
         */
        $data = apply_filters('fluent_crm/webhook_contact_data', $data, $postData, $webhook);

        $forceUpdate = (!empty($data['status']) && $data['status'] != Arr::get($webhook->value, 'status', '')) || $data['status'] == 'subscribed';

        $user = get_user_by('email', $data['email']);

        if ($user) {
            $data['user_id'] = $user->ID;
        }

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
        $contactId = (int)$_GET['ce_id'];
        $subscriber = false;

        $managedSecureHash = sanitize_text_field(Arr::get($_REQUEST, 'secure_hash'));

        if ($managedSecureHash) {
            $subscriber = fluentCrmApi('contacts')->getContactByManagedSecureHash($managedSecureHash);

            /*
             * remove this at march 2024
             */
            if (!$subscriber) {
                $subscriber = fluentCrmApi('contacts')->getContactBySecureHash($managedSecureHash);
            }

            if ($subscriber && $subscriber->id != $contactId) {
                return;
            }
        }

        if (!$subscriber) {
            $this->manageSubscriptionRequestForm();
            return;
        }

        $this->loadAssets();

        $managedSecureHash = fluentCrmGetContactManagedHash($subscriber->id);
        add_action('wp_loaded', function () use ($subscriber, $managedSecureHash) {
            echo $this->getManageSubscriptionHtml($subscriber, $managedSecureHash); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit();
        }, 1);
    }

    public function getManageSubscriptionHtml($subscriber, $secureHash = '')
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
            'business'         => $businessSettings,
            'secure_hash'      => $secureHash
        ]);
    }

    public function handleManageSubPref()
    {
        $secureHash = Arr::get($_REQUEST, '_secure_hash');

        $absHash = Arr::get($_REQUEST, '_abs_hash');
        $email = Arr::get($_REQUEST, 'email');
        $originalHash = Arr::get($_REQUEST, '_original_hash');

        $subscriber = null;
        if ($secureHash) {
            $subscriber = fluentCrmApi('contacts')->getContactByManagedSecureHash($secureHash);
        }

        if (!$subscriber) {
            wp_send_json_error([
                'message' => __('Sorry! No subscriber found in the database', 'fluent-crm')
            ], 422);
            return;
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
                ], 422);
            }

            // Check if unique
            $exist = Subscriber::where('email', $email)->where('id', '!=', $subscriber->id)->first();
            if ($exist) {
                wp_send_json_error([
                    'message' => __('The new email has been used to another account. Please use a new email address', 'fluent-crm')
                ], 422);
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
                'message' => sprintf(__('A confirmation email has been sent to %s. Please confirm your email address to resubscribe with changed email address', 'fluent-crm'), $email)
            ], 200);
            return;
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
                'message' => sprintf(__('A confirmation email has been sent to %s. Please confirm your email address to resubscribe', 'fluent-crm'), $email)
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
        if (strlen($first) > 2) {
            $first = str_replace(substr($first, 2), str_repeat('*', strlen($first) - 2), $first);
        }
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

        $complianceSettings = Helper::getComplianceSettings();

        wp_localize_script('fluentcrm_public_pref', 'fluentcrm_public_pref', [
            'ajaxurl'          => admin_url('admin-ajax.php'),
            /**
             * Determine if auto unsubscribe should be enabled in FluentCRM.
             *
             * This filter allows customization of the auto unsubscribe behavior.
             *
             * @param bool|string Default value is 'no'. Can be overridden to 'yes' to enable auto unsubscribe.
             * @return bool|string Filtered value to determine if auto unsubscribe should be enabled.
             * @since 2.8.34
             *
             */
            'auto_unsubscribe' => apply_filters('fluent_crm/will_auto_unsubscribe', Arr::get($complianceSettings, 'one_click_unsubscribe', 'no'))
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

        $handler = sanitize_text_field(Arr::get($_REQUEST, 'handler'));

        do_action('fluentcrm_webhook_to_' . $handler, $data);

        wp_send_json([
            'message' => __('No Action found', 'fluent-crm'),
            'action'  => 'fluentcrm_webhook_to_' . $handler
        ]);
    }

    public function handlePreviewEmail()
    {

        nocache_headers();

        $emailHash = sanitize_text_field(Arr::get($_REQUEST, '_e_hash'));

        if (!$emailHash) {
            // Maybe it's the Campaign Share Email
            $newsLetterShareId = sanitize_text_field(Arr::get($_REQUEST, 'fc_newsletter'));

            if ($newsLetterShareId) {
                $this->showNewesLetterView($newsLetterShareId);
                return;
            }
        }

        $email = CampaignEmail::where('email_hash', $emailHash)->with(['campaign', 'subscriber'])->first();

        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);

        if (!$email || !$email->campaign || !$email->subscriber) {
            fluentCrm('view')->render('external.view_on_browser', [
                'business'      => $businessSettings,
                'email_heading' => '',
                'email'         => null,
                'email_body'    => '<h2 style=\'text-align: center; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif\'>Sorry, web preview could not be loaded</h2>',
                'cssAssets'     => [
                    FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css?version=' . FLUENTCRM_PLUGIN_VERSION
                ],
                'footer_text'   => ''
            ]);
            exit();
        }

        if ($email->campaign && Arr::get($email->campaign->settings, 'template_config')) {
            $templateConfig = wp_parse_args($email->campaign->settings['template_config'], Helper::getTemplateConfig($email->campaign->design_template, false));
        } else {
            $templateConfig = Helper::getTemplateConfig('', false);
        }

        $rawTemplates = [
            'raw_html',
            'visual_builder'
        ];

        if (in_array($email->campaign->design_template, $rawTemplates)) {
            $emailBody = $email->campaign->email_body;
        } else {
            $emailBody = (new BlockParser($email->subscriber))->parse($email->campaign->email_body);
        }

        /**
         * Email Footer Text For WebUI
         * @param string $footerText
         * @param CampaignEmail $email
         */
        $footerText = Helper::getEmailFooterContent($email->campaign);

        /**
         * Determine the footer text of the web email in FluentCRM.
         *
         * This filter allows you to modify the footer text of the web email.
         *
         * @param string $footerText The current footer text.
         * @param array $email The email data array.
         *
         * @return string The modified footer text.
         * @since 2.8.40
         *
         */
        $footerText = apply_filters('fluent_crm/web_email_footer_text', $footerText, $email);

        $emailSubject = $email->email_subject;
        $preHeader = ($email->campaign) ? $email->campaign->email_pre_header : '';

        $subscriber = $email->subscriber;
        if ($subscriber) {
            /**
             * Determine the campaign email body content text in FluentCRM.
             *
             * This filter allows modification of the campaign email text before it is sent to the subscriber.
             *
             * @param string $emailBody The email body content.
             * @param object $subscriber The subscriber object containing subscriber details.
             *
             * @return string The filtered email body content.
             * @since 2.8.02
             *
             */
            $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $subscriber);
            /**
             * Determine the footer text of a campaign email in FluentCRM.
             *
             * This filter allows you to modify the footer text of a campaign email before it is sent to the subscriber.
             *
             * @param string $footerText The footer text of the campaign email.
             * @param object $subscriber The subscriber object containing subscriber details.
             *
             * @return string The modified footer text.
             * @since 2.8.02
             *
             */
            $footerText = apply_filters('fluent_crm/parse_campaign_email_text', $footerText, $subscriber);
            /**
             * Determine the campaign email subject text in FluentCRM.
             *
             * This filter allows you to modify the email subject text for a campaign.
             *
             * @param string $emailSubject The original email subject text.
             * @param object $subscriber The subscriber object.
             *
             * @return string The filtered email subject text.
             * @since 2.8.02
             *
             */
            $emailSubject = apply_filters('fluent_crm/parse_campaign_email_text', $emailSubject, $subscriber);
            /**
             * Determine the Email pre-header text of a campaign email in FluentCRM.
             *
             * This filter allows you to modify the pre-header text of a campaign email before it is sent to the subscriber.
             *
             * @param string $preHeader The pre-header text of the campaign email.
             * @param object $subscriber The subscriber object containing subscriber details.
             *
             * @return string The filtered pre-header text.
             * @since 2.8.02
             *
             */
            $preHeader = apply_filters('fluent_crm/parse_campaign_email_text', $preHeader, $subscriber);
        }

        $templateData = [
            'preHeader'   => $preHeader,
            'email_body'  => $emailBody,
            'footer_text' => $footerText,
            'config'      => $templateConfig
        ];

        if ($email->campaign->design_template == 'visual_builder' || $email->campaign->design_template == 'raw_html') {
            $content = $emailBody;
            if ($email->campaign->design_template == 'visual_builder') {
                /**
                 * Determine the email design template content in the visual builder in FluentCRM.
                 *
                 * @param string $content The email content.
                 * @param array $templateData The template data.
                 * @param object $email ->campaign The email campaign object.
                 * @param object $email ->subscriber The email subscriber object.
                 * @since 2.7.40
                 *
                 */
                $content = apply_filters('fluent_crm/email-design-template-visual_builder',
                    $content,
                    $templateData,
                    $email->campaign,
                    $email->subscriber
                );
            }

            $footerText = '';
        } else {
            /**
             * Determine the email design template for web preview in FluentCRM.
             *
             * @param string $emailBody The email body content.
             * @param array $templateData The template data.
             * @param object $email ->campaign The email campaign object.
             * @param object $email ->subscriber The email subscriber object.
             *
             * @return string The filtered email design template for web preview.
             * @since 2.7.0
             *
             */
            $content = apply_filters('fluent_crm/email-design-template-web_preview',
                $emailBody,
                $templateData,
                $email->campaign,
                $email->subscriber
            );
        }

        if (strpos($content, '{{crm') || strpos($content, '##crm')) {
            $content = str_replace(['{{crm_global_email_footer}}', '{{crm_preheader_text}}'], ['', $preHeader], $content);
            if (strpos($content, '##crm.') || strpos($content, '{{crm.')) {
                /**
                 * Determine the Smartcode text content before it is parsed in FluentCRM.
                 *
                 * This filter allows you to modify the Smartcode text content before it is parsed.
                 *
                 * @param string $content The Smartcode text content to be parsed.
                 * @param object $email ->subscriber The subscriber object associated with the email.
                 * @since 2.7.0
                 *
                 */
                $content = apply_filters('fluent_crm/parse_extended_crm_text', $content, $email->subscriber);
            }
        }


        /**
         * Determine the email design template content for various types in FluentCRM.
         *
         * This filter allows customization of the email design template content.
         *
         * @param string $content The email content.
         * @param array $templateData The data used in the email template.
         * @param object $campaign The campaign object.
         * @param object $subscriber The subscriber object.
         * @since 2.8.40
         *
         */
        $content = apply_filters(
            'fluent_crm/email-design-template-' . $email->campaign->design_template,
            $content,
            $templateData,
            $email->campaign,
            $subscriber
        );

        $preViewUrl = site_url('?fluentcrm=1&route=email_preview&_e_hash=' . $email->email_hash);
        $content = str_replace(['##web_preview_url##', '{{crm_global_email_footer}}', '{{crm_preheader_text}}'], [$preViewUrl, $footerText, $preHeader], $content);

        if (strpos($content, '##crm.') || strpos($content, '{{crm.')) {
            /**
             * Determine the Smartcode text content before it is parsed in FluentCRM.
             *
             * This filter allows you to modify the Smartcode text content before it is parsed.
             *
             * @param string $content The Smartcode text content to be parsed.
             * @param object $subscriber The subscriber object associated with the email.
             * @since 2.8.40
             *
             */
            $content = apply_filters('fluent_crm/parse_extended_crm_text', $content, $subscriber);
        }

        $content = str_replace(['https://fonts.googleapis.com/css2', 'https://fonts.googleapis.com/css'], 'https://fonts.bunny.net/css', $content);

        $data = [
            'business'      => $businessSettings,
            'email_heading' => $emailSubject,
            'email'         => $email,
            'email_body'    => [
                'rendered' => $content
            ],
            'cssAssets'     => [
                FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css?version=' . FLUENTCRM_PLUGIN_VERSION
            ]
        ];

        /**
         * Determine the data used to view an email in the browser in FluentCRM.
         *
         * This filter allows modification of the data that is used when viewing an email in the browser.
         *
         * @param array $data The data to be used for viewing the email in the browser.
         * @param object $email The email object containing the email details.
         * @since 2.7.40
         *
         */
        $data = apply_filters('fluent_crm/email_view_on_browser_data', $data, $email);

        fluentCrm('view')->render('external.view_on_browser', $data);
        exit();
    }

    public function showNewesLetterView($nsHash)
    {
        nocache_headers();
        $meta = Meta::where('object_type', 'FluentCrm\App\Models\Campaign')
            ->where('key', '_campaign_share_id')
            ->where('value', $nsHash)
            ->first();


        $businessSettings = fluentcrmGetGlobalSettings('business_settings', []);

        $campaign = null;

        if ($meta) {
            $campaign = Campaign::withoutGlobalScope('type')->find($meta->object_id);
        }

        if (!$campaign) {
            fluentCrm('view')->render('external.view_on_browser', [
                'business'      => $businessSettings,
                'email_heading' => '',
                'email'         => null,
                'email_body'    => '<h2 style=\'text-align: center; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif\'>Sorry, web preview could not be loaded</h2>',
                'cssAssets'     => [
                    FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css?version=' . FLUENTCRM_PLUGIN_VERSION
                ],
                'footer_text'   => ''
            ]);
            exit();
        }

        $emailBody = fluentcrm_get_campaign_meta($campaign->id, '_cached_email_body', true);

        if (!$emailBody) {
            // Let's generate the email body
            $rawTemplates = [
                'raw_html',
                'visual_builder'
            ];

            if (in_array($campaign->design_template, $rawTemplates)) {
                $emailBody = $campaign->email_body;
            } else {
                $emailBody = (new BlockParser(fluentcrm_get_current_contact()))->parse($campaign->email_body);
            }
        }

        $emailSubject = $campaign->subject;


        $subscriber = fluentcrm_get_current_contact();

        /**
         * Determine the campaign email subject text in FluentCRM.
         *
         * This filter allows you to modify the email subject text for a campaign.
         *
         * @param string $emailSubject The original email subject text.
         * @param object $subscriber The subscriber object.
         *
         * @return string The filtered email subject text.
         * @since 2.8.44
         *
         */
        $emailSubject = apply_filters('fluent_crm/parse_campaign_email_text', $emailSubject, $subscriber);
        /**
         * Determine the campaign email text in FluentCRM.
         *
         * This filter allows modification of the campaign email text before it is sent to the subscriber.
         *
         * @param string $emailBody The email body content.
         * @param object $subscriber The subscriber object.
         * @since 2.8.44
         *
         */
        $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $subscriber);

        $templateConfig = wp_parse_args($campaign->settings['template_config'], Helper::getTemplateConfig($campaign->design_template, false));

        $templateData = [
            'preHeader'   => '',
            'email_body'  => $emailBody,
            'footer_text' => '',
            'config'      => $templateConfig
        ];


        if ($campaign->design_template == 'visual_builder' || $campaign->design_template == 'raw_html') {
            $content = $emailBody;
            if ($campaign->design_template == 'visual_builder') {
                /**
                 * Determine the email design template in the visual builder in FluentCRM.
                 *
                 * This filter allows customization of the email design template in the visual builder.
                 *
                 * @param string $content The current content of the email design template.
                 * @param array $templateData Data related to the email template.
                 * @param object $campaign The campaign object.
                 * @param object $subscriber The subscriber object.
                 *
                 * @return string The filtered email design template content.
                 * @since 2.8.44
                 *
                 */
                $content = apply_filters('fluent_crm/email-design-template-visual_builder',
                    $content,
                    $templateData,
                    $campaign,
                    $subscriber
                );
            }
        } else {
            /**
             * Determine the email design template content for various template types in FluentCRM.
             *
             * This filter allows modification of the email design template content before it is sent.
             *
             * @param string $content The email content after applying the design template.
             * @param string $emailBody The original email body content.
             * @param array $templateData An array of data used in the email template.
             * @param object $campaign The campaign object containing campaign details.
             * @param object $subscriber The subscriber object containing subscriber details.
             * @since 2.8.44
             *
             */
            $content = apply_filters('fluent_crm/email-design-template-' . $campaign->design_template,
                $emailBody,
                $templateData,
                $campaign,
                $subscriber
            );
        }

        $content = str_replace(['https://fonts.googleapis.com/css2', 'https://fonts.googleapis.com/css'], 'https://fonts.bunny.net/css', $content);

        $data = [
            'business'      => $businessSettings,
            'email_heading' => $emailSubject,
            'email'         => null,
            'email_body'    => [
                'rendered' => $content
            ],
            'cssAssets'     => [
                FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css?version=' . FLUENTCRM_PLUGIN_VERSION
            ]
        ];

        /**
         * Determine the full email newsletter data in FluentCRM.
         *
         * This filter allows modification of the email newsletter data before it is processed.
         *
         * @param array $data The email newsletter data.
         * @since 2.8.44
         *
         */
        $data = apply_filters('fluent_crm/email_newsletter_data', $data);

        fluentCrm('view')->render('external.view_on_browser', $data);
        exit();
    }

    public function handleGeneralRequest()
    {
        $data = $_REQUEST;
        $handler = sanitize_text_field(Arr::get($_REQUEST, 'handler'));

        if ($handler) {
            do_action('fluent_crm/handle_frontend_for_' . $handler, $data);
        }

    }
}
