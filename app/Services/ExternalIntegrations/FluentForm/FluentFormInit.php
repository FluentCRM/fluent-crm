<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentForm;

use FluentCrm\Framework\Support\Arr;
use FluentForm\App\Helpers\Helper;
use FluentFormPro\Payments\PaymentHelper;

class FluentFormInit
{
    public function init()
    {
        if (defined('FLUENTFORM_FRAMEWORK_UPGRADE')) {
            new \FluentCrm\App\Services\ExternalIntegrations\FluentForm\Bootstrap();
        }

        add_filter('fluentform/submissions_widgets', array($this, 'pushContactWidget'), 10, 3);
        if (defined('FLUENTFORMPRO')) {
            add_filter('fluent_crm/subscriber_info_widgets', array($this, 'pushSubscriberInfoWidget'), 10, 2);
        }
    }

    public function pushContactWidget($widgets, $resources, $submission)
    {
        $userId = $submission->user_id;

        if (!$userId) {
            $userInputs = json_decode($submission->response, true);

            if (!$userInputs) {
                return $widgets;
            }

            $maybeEmail = Arr::get($userInputs, 'email');

            if (!$maybeEmail) {
                $emailField = Helper::getFormMeta($submission->form_id, '_primary_email_field');
                if (!$emailField) {
                    return $widgets;
                }
                $maybeEmail = Arr::get($userInputs, $emailField);
            }
        } else {
            $maybeEmail = $userId;
        }

        if (!$maybeEmail) {
            return $widgets;
        }

        $profileHtml = fluentcrm_get_crm_profile_html($maybeEmail, true);
        if (!$profileHtml) {
            return $widgets;
        }

        $widgets['fluent_crm'] = [
            'title'   => __('FluentCRM Profile', 'fluent-crm'),
            'content' => $profileHtml
        ];
        return $widgets;
    }

    public function pushSubscriberInfoWidget($widgets, $subscriber)
    {
        if(!$subscriber->email) {
            return $widgets;
        }

        $subscriptions = fluentCrmDb()->table('fluentform_subscriptions')
            ->join('fluentform_transactions', 'fluentform_subscriptions.submission_id', '=', 'fluentform_transactions.submission_id')
            ->where('fluentform_transactions.payer_email', $subscriber->email)
            ->select('fluentform_subscriptions.*', 'fluentform_transactions.currency')
            ->orderBy('fluentform_subscriptions.created_at', 'desc')
            ->get();

        if (empty($subscriptions)) {
            return $widgets;
        }

        $html = '<ul class="fc_full_listed fc_memberpress_subscription_lists">';
        foreach ($subscriptions as $subscription) {
            $html .= $this->generateSubscriptionHtml($subscription);
        }

        $html .= '</ul>';

        $widgets[] = [
            'title'   => __('FluentForm Subscriptions', 'fluentcampaign-pro'),
            'content' => $html
        ];

        return $widgets;
    }
    private function generateSubscriptionHtml($subscription)
    {
        $subscription->formatted_recurring_amount = PaymentHelper::formatMoney($subscription->recurring_amount, $subscription->currency);

        if ($subscription->status == 'active') {
            if ($subscription->bill_times) {
                $billingText = sprintf(esc_html__('Will be cancelled after %d payments. (%d/%d)', 'fluent-crm'), $subscription->bill_times, $subscription->bill_count, $subscription->bill_times);
            } else {
                $billingText = __('Will be billed until cancelled', 'fluent-crm');
            }
        }

        $formatted_date = date_i18n(get_option('date_format'), strtotime($subscription->created_at));
        $permalink = esc_url(admin_url('admin.php?page=fluent_forms&form_id='.$subscription->form_id.'&route=entries#/entries/'.$subscription->submission_id.'?sort_by=DESC&current_page=1&pos=0&type='));

        $html = '<li>';
        $html .= '<span class="fc_mepr_subscription_header">';
        $html .= '<span class="fc_mepr_subscription_status ' . esc_attr($subscription->status) . '">' . esc_html($subscription->status) . '</span>';
        $html .= '<span class="fc_mepr_subscription_price">' . $subscription->formatted_recurring_amount . '<small>/'.$subscription->billing_interval.'</small></span>';
        $html .= '</span>';
        $html .= '<a href="' . $permalink . '" target="_blank" class="fc_mepr_subscription_title">';
        $html .= '<b>' . esc_html($subscription->item_name) . '<span class="fc_dash_extrernal dashicons dashicons-external"></span></b>';
        $html .= '</a>';
        $html .= '<span class="fc_date">' . __('Start Date: ', 'fluentcampaign-pro') . $formatted_date . '</span>';
        if ($subscription->status == 'active') {
            $html .= sprintf('<span class="fc_date period_date">%s%s</span>', __('Expiry Date: ', 'fluentcampaign-pro'), $billingText);
        } else {
            $html .= sprintf('<span class="fc_date period_date">%s%s</span>', __('Cancelled Date: ', 'fluentcampaign-pro'), date_i18n(get_option('date_format'), strtotime($subscription->updated_at)));
        }
        $html .= '</li>';
        return $html;
    }
    
}
