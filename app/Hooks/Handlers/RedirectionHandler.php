<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\UrlStores;
use FluentCrm\Framework\Support\Arr;

/**
 *  RedirectionHandler Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class RedirectionHandler
{
    public function redirect($data)
    {
        nocache_headers();

        $mailId = false;
        $urlSlug = sanitize_text_field($data['ns_url']);

        if (isset($data['mid'])) {
            $mailId = intval($data['mid']);
        }

        $urlData = fluentCrmGetFromCache('url_' . $urlSlug, function () use ($urlSlug) {
            return UrlStores::getRowByShort($urlSlug);
        });

        if (!$urlData) {
            return;
        }

        if (isset($data['fch'])) {
            $urlData->url_token = $data['fch'];
        }

        $redirectUrl = trim($this->trackUrlClick($mailId, $urlData));
        $redirectUrl = htmlspecialchars_decode($redirectUrl);

        if ($redirectUrl) {
            // remove zero width space
            $redirectUrl = str_replace(["\xE2\x80\x8B", '%E2%80%8B'], '', $redirectUrl);
            do_action('fluentcrm_email_url_click', $redirectUrl, $mailId, $urlData);
            wp_redirect($redirectUrl, 307);
            exit;
        }
    }

    public function trackUrlClick($mailId, $urlData)
    {
        if (!$mailId) {
            return $urlData->url;
        }

        $campaignEmail = CampaignEmail::with(['subscriber'])->find($mailId);

        if (!$campaignEmail || !$campaignEmail->subscriber) {
            return $urlData->url;
        }

        $campaign = fluentCrmGetFromCache('campaign_' . $campaignEmail->campaign_id, function () use ($campaignEmail) {
            return Campaign::withoutGlobalScopes()->find($campaignEmail->campaign_id);
        });

        if (!$campaign) {
            return $urlData->url;
        }

        if (!$campaignEmail->is_open) {
            CampaignUrlMetric::maybeInsert([
                'type'          => 'open',
                'campaign_id'   => $campaignEmail->id,
                'subscriber_id' => $campaignEmail->subscriber_id,
                'ip_address'    => FluentCrm('request')->getIp(fluentCrmWillAnonymizeIp())
            ]);

            do_action('fluent_crm/email_opened', $campaignEmail);
        }

        $id = CampaignUrlMetric::maybeInsert([
            'url_id'        => $urlData->id,
            'campaign_id'   => $campaignEmail->campaign_id,
            'subscriber_id' => $campaignEmail->subscriber_id,
            'type'          => 'click',
            'ip_address'    => FluentCrm('request')->getIp(fluentCrmWillAnonymizeIp())
        ]);

        $tokenVerified = false;

        /**
         * Filter whether to use cookies for FluentCRM redirection.
         *
         * This filter allows you to control whether cookies should be used for tracking
         * FluentCRM redirection. By default, it is set to true.
         *
         * @since 2.8.44
         *
         * @param bool Whether to use cookies for redirection. Default true.
         */
        if (apply_filters('fluent_crm/will_use_cookie', true) && !empty($urlData->url_token)) {
            // validate the URL token here
            if (substr($campaignEmail->email_hash, 0, 8) == $urlData->url_token) {
                $tokenVerified = true;
                $secureHash = fluentCrmGetContactSecureHash($campaignEmail->subscriber_id);
                setcookie("fc_hash_secure", $secureHash, time() + 7776000, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 90 days */
                $_COOKIE['fc_hash_secure'] = $secureHash;
            }

            if ($campaignEmail->campaign_id) {
                setcookie("fc_cid", $campaignEmail->campaign_id, time() + 9676800, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 28 days */
            }
        }

        do_action('fluent_crm/email_url_clicked', $campaignEmail, $urlData);

        $args = $campaign->getUtmParams();

        $campaignEmail->click_counter += 1;
        $campaignEmail->is_open = 1;
        $campaignEmail->save();

        do_action('fluent_crm/track_activity_by_subscriber', $campaignEmail->subscriber_id);

        $url = $urlData->url;

        $url = str_replace('&amp;', '&', $url);
        $url = esc_url_raw($url);

        if (strpos($url, 'route=smart_url')) {
            // this is a smart URL
            $url_components = parse_url($url);
            parse_str($url_components['query'], $params);

            if (!empty($params['slug'])) {
                $subscriber = $campaignEmail->subscriber;

                $signedHash = Arr::get($_REQUEST, 'signed_hash');
                $isSecure = $tokenVerified && $signedHash && wp_check_password($campaignEmail->email_hash, $signedHash);

                if ($isSecure) {
                    do_action('fluent_crm/smart_link_verified', $subscriber);
                }

                do_action('fluentcrm_smartlink_clicked_direct', sanitize_text_field($params['slug']), $subscriber, $campaignEmail);
            }
        }

        if (strpos($urlData->url, 'route=bnu')) {
            $url_components = parse_url($url);
            parse_str($url_components['query'], $params);
            if (!empty($params['aid'])) {
                $benchmarkActionId = intval($params['aid']);
                do_action('fluencrm_benchmark_link_clicked', $benchmarkActionId, $campaignEmail->subscriber);
            }
            $args['bnu_timer_' . time()] = time();
        }

        if ($args) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }
}
