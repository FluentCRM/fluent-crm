<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\AutoSubscribe;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Includes\Helpers\Arr;

class AutoSubscribeHandler
{
    public function userRegistrationHandler($userId)
    {
        $settings = (new AutoSubscribe())->getRegistrationSettings();
        if (Arr::get($settings, 'status') != 'yes') {
            return;
        }

        $subscriberData = FunnelHelper::prepareUserData($userId);
        if($listId = Arr::get($settings, 'target_list')) {
            $subscriberData['lists'] = [$listId];
        }

        if($tags = Arr::get($settings, 'target_tags')) {
            $subscriberData['tags'] = $tags;
        }

        $isDoubleOptin = Arr::get($settings, 'double_optin') == 'yes';

        if($isDoubleOptin) {
            $subscriberData['status'] = 'pending';
        }

        $contact = FunnelHelper::createOrUpdateContact($subscriberData);

        if($contact->status = 'pending') {
            $contact->sendDoubleOptinEmail();
        }
    }

    public function addSubscribeCheckbox($buttonHtml)
    {

        $settings = (new AutoSubscribe())->getCommentSettings();

        $settings = apply_filters('fluentcrm_comment_form_subscribe_settings', $settings);

        if(Arr::get($settings, 'status') != 'yes') {
            return $buttonHtml;
        }

        if(Arr::get($settings, 'show_only_new') == 'yes') {
            if($userId = get_current_user_id()) {
                $user = get_user_by('ID', $userId);
                $contact = Subscriber::where('user_id', $userId)->orWhere('email', $user->user_email)->first();
                if($contact && $contact->status == 'subscribed') {
                    return  $buttonHtml;
                }
            }
        }

        $label = Arr::get($settings, 'checkbox_label');
        if(!$label) {
            $label = __('Subscribe to newsletter', 'fluentcrm');
        }

        $checkedTag = '';

        if(Arr::get($settings, 'auto_checked') == 'yes') {
            $checkedTag = 'checked="true"';
        }

        $html = '<p class="comment-form-fc-consent comment-form-cookies-consent"><input '.$checkedTag.' id="wp-comment-fc-consent" name="wp-comment-fc-consent" type="checkbox" value="yes"><label for="wp-comment-fc-consent">'.$label.'</label></p>';

        return $html.$buttonHtml;
    }

    public function handleCommentPost($commentId, $isApproved, $commentData)
    {
        $isChecked = Arr::get($_REQUEST, 'wp-comment-fc-consent') == 'yes';
        if(!$isChecked) {
            return false;
        }
        // is this a spam comment?
        if ( $isApproved === 'spam' ) {
            return false;
        }

        $subscriberData  = [
            'full_name' => Arr::get($commentData, 'comment_author'),
            'email' => Arr::get($commentData, 'comment_author_email'),
            'ip_address' => Arr::get($commentData, 'comment_author_IP')
        ];

        if($userId = Arr::get($commentData, 'user_id')) {
            $subscriberData['user_id'] = $userId;
        }

        $subscriberData = array_filter($subscriberData);

        $settings = (new AutoSubscribe())->getCommentSettings();

        if($listId = Arr::get($settings, 'target_list')) {
            $subscriberData['lists'] = [$listId];
        }

        if($tags = Arr::get($settings, 'target_tags')) {
            $subscriberData['tags'] = $tags;
        }

        $isDoubleOptin = Arr::get($settings, 'double_optin') == 'yes';

        if($isDoubleOptin) {
            $subscriberData['status'] = 'pending';
        }

        $contact = FunnelHelper::createOrUpdateContact($subscriberData);

        if($contact->status = 'pending') {
            $contact->sendDoubleOptinEmail();
        }

        return true;
    }
}
