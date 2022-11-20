<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\AutoSubscribe;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

/**
 *  AutoSubscribeHandler Class
 *
 * Used to handle the auto-subscribe functionality for different WordPress Events.
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class AutoSubscribeHandler
{

    public function userRegistrationHandler($userId)
    {
        if (is_multisite()) {
            if (is_network_admin()) {
                return false;
            }
            if (function_exists('WP_Ultimo')) {
                return false;
            }
        }

        $settings = (new AutoSubscribe())->getRegistrationSettings();

        if (Arr::get($settings, 'status') != 'yes') {

            $user = get_user_by('ID', $userId);
            $contact = Subscriber::where('email', $user->user_email)->first();
            if ($contact) {
                $contact->user_id = $user->ID;
                $contact->save();
            }

            return false;
        }

        $subscriberData = FunnelHelper::prepareUserData($userId);
        if ($listId = Arr::get($settings, 'target_list')) {
            $subscriberData['lists'] = [$listId];
        }

        if ($tags = Arr::get($settings, 'target_tags')) {
            $subscriberData['tags'] = $tags;
        }

        $isDoubleOptin = Arr::get($settings, 'double_optin') == 'yes';

        if ($isDoubleOptin) {
            $subscriberData['status'] = 'pending';
        } else {
            $subscriberData['status'] = 'subscribed';
        }

        $contact = FunnelHelper::createOrUpdateContact($subscriberData);

        if (!$contact) {
            return false;
        }

        if ($contact->status == 'pending' && $subscriberData['status'] == 'pending') {
            $contact->sendDoubleOptinEmail();
        }

        add_action("updated_user_meta", function ($meta_id, $userId, $meta_key, $_meta_value) use ($contact) {
            if ($userId == $contact->user_id && ($meta_key == 'first_name' || $meta_key == 'last_name') && $_meta_value) {
                $contact->{$meta_key} = $_meta_value;
                $contact->save();
            }
        }, 10, 4);

    }

    public function addSubscribeCheckbox($buttonHtml)
    {

        $settings = (new AutoSubscribe())->getCommentSettings();

        $settings = apply_filters('fluentcrm_comment_form_subscribe_settings', $settings);

        if (Arr::get($settings, 'status') != 'yes') {
            return $buttonHtml;
        }

        if (Arr::get($settings, 'show_only_new') == 'yes') {
            if ($userId = get_current_user_id()) {
                $user = get_user_by('ID', $userId);
                $contact = Subscriber::where('user_id', $userId)->orWhere('email', $user->user_email)->first();
                if ($contact && $contact->status == 'subscribed') {
                    return $buttonHtml;
                }
            }
        }

        $label = Arr::get($settings, 'checkbox_label');
        if (!$label) {
            $label = __('Subscribe to newsletter', 'fluent-crm');
        }

        $checkedTag = '';

        if (Arr::get($settings, 'auto_checked') == 'yes') {
            $checkedTag = 'checked="true"';
        }

        $html = '<p class="comment-form-fc-consent comment-form-cookies-consent"><input ' . $checkedTag . ' id="wp-comment-fc-consent" name="wp-comment-fc-consent" type="checkbox" value="yes"><label for="wp-comment-fc-consent">' . $label . '</label></p>';

        return $html . $buttonHtml;
    }

    public function handleCommentPost($commentId, $isApproved, $commentData)
    {
        // is this a spam comment?
        if ($isApproved === 'spam') {
            return false;
        }

        if (defined('WC_PLUGIN_FILE') && Arr::get($commentData, 'comment_type') == 'review') {
            do_action('fluentcrm_woo_review_comment_post', $commentId, $isApproved, $commentData);
        }

        $isChecked = Arr::get($_REQUEST, 'wp-comment-fc-consent') == 'yes';
        if (!$isChecked) {
            return false;
        }

        $subscriberData = [
            'full_name'  => Arr::get($commentData, 'comment_author'),
            'email'      => Arr::get($commentData, 'comment_author_email'),
            'ip_address' => Arr::get($commentData, 'comment_author_IP')
        ];

        if ($userId = Arr::get($commentData, 'user_id')) {
            $subscriberData['user_id'] = $userId;
        }

        $subscriberData = array_filter($subscriberData);

        $settings = (new AutoSubscribe())->getCommentSettings();

        if ($listId = Arr::get($settings, 'target_list')) {
            $subscriberData['lists'] = [$listId];
        }

        if ($tags = Arr::get($settings, 'target_tags')) {
            $subscriberData['tags'] = $tags;
        }

        $isDoubleOptin = Arr::get($settings, 'double_optin') == 'yes';

        if ($isDoubleOptin) {
            $subscriberData['status'] = 'pending';
        }

        $contact = FunnelHelper::createOrUpdateContact($subscriberData);

        if (!$contact) {
            return false;
        }

        if ($contact->status == 'pending') {
            $contact->sendDoubleOptinEmail();
        }

        return true;
    }

    public function syncUserUpdate($userId, $oldData)
    {

        if (is_multisite() && is_network_admin()) {
            return false;
        }

        if (!Helper::isUserSyncEnabled()) {
            return false;
        }

        // check if user email has been changed
        $user = get_user_by('ID', $userId);

        if ($user->user_email != $oldData->user_email) {
            // email has been changed
            $oldSubscriber = Subscriber::where('email', $oldData->user_email)->first();

            // check if a contact is exist with the new email id
            $newSubscriber = Subscriber::where('email', $user->user_email)->first();

            if ($newSubscriber) {
                fluentCrmDb()->table('fc_subscribers')
                    ->where('id', $oldSubscriber->id)
                    ->update([
                        'user_id' => ''
                    ]);
                $oldSubscriber = false;
            }

            if ($oldSubscriber) {
                $updateData = [
                    'email'      => $user->user_email,
                    'hash'       => md5($user->user_email),
                    'updated_at' => current_time('mysql'),
                    'user_id'    => $user->ID
                ];

                if ($user->first_name) {
                    $updateData['first_name'] = $user->first_name;
                }

                if ($user->last_name) {
                    $updateData['last_name'] = $user->last_name;
                }

                return fluentCrmDb()->table('fc_subscribers')
                    ->where('id', $oldSubscriber->id)
                    ->update($updateData);
            }
        }

        // we just have to change the first name and lastname
        $updateData = Helper::getWPMapUserInfo($user);

        unset($updateData['email']);

        if (!$updateData) {
            return false;
        }

        $updateData['updated_at'] = current_time('mysql');

        return fluentCrmDb()->table('fc_subscribers')
            ->where('email', $user->user_email)
            ->update($updateData);
    }

    public function maybeDeleteContact($userId, $reassignId, $user)
    {

        if (is_multisite() && is_network_admin()) {
            return false;
        }

        if (!Helper::isContactDeleteOnUserDeleteEnabled()) {
            return false;
        }

        $subscriber = Subscriber::where('user_id', $userId)->first();
        if (!$subscriber) {
            $subscriber = Subscriber::where('email', $user->user_email)->first();
        }

        if (!$subscriber) {
            return false;
        }

        return Helper::deleteContacts([$subscriber->id]);
    }
}
