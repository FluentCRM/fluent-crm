<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\UrlStores;

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

        $redirectUrl = $this->trackUrlClick($mailId, $urlData);

        $redirectUrl = htmlspecialchars_decode($redirectUrl);

        if ($redirectUrl) {
            do_action('fluentcrm_email_url_click', $redirectUrl, $mailId, $urlData);
            nocache_headers();
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

        if(!$campaign) {
            error_log('Campaign could not be found');
            return $urlData->url;
        }

        $id = CampaignUrlMetric::maybeInsert([
            'url_id'        => $urlData->id,
            'campaign_id'   => $campaignEmail->campaign_id,
            'subscriber_id' => $campaignEmail->subscriber_id,
            'type'          => 'click',
            'ip_address'    => FluentCrm('request')->getIp(fluentCrmWillAnonymizeIp())
        ]);

        if (apply_filters('fluentcrm_will_use_cookie', true)) {
            $secureHash = fluentCrmGetContactSecureHash($campaignEmail->subscriber_id);
            setcookie("fc_s_hash", $campaignEmail->subscriber->hash, time() + 7776000, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 90 days */
            setcookie("fc_hash_secure", $secureHash, time() + 7776000, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 90 days */

            $_COOKIE['fc_s_hash'] = $campaignEmail->subscriber->hash;
            $_COOKIE['fc_hash_secure'] = $secureHash;

            if ($campaignEmail->campaign_id) {
                setcookie("fc_cid", $campaignEmail->campaign_id, time() + 9676800, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 28 days */
            }
        }

        do_action(FLUENTCRM . '_email_url_clicked', $campaignEmail, $urlData);

        $args = $campaign->getUtmParams();

        $campaignEmail->click_counter += 1;
        $campaignEmail->is_open = 1;
        $campaignEmail->save();

        do_action('fluentform_track_activity_by_subscriber', $campaignEmail->subscriber_id);

        $url = str_replace('&amp;', '&', $urlData->url);;

        if (strpos($url, 'route=smart_url')) {
            // this is a smart URL
            $url_components = parse_url($url);

            parse_str($url_components['query'], $params);

            if (!empty($params['slug'])) {
                do_action('fluentcrm_smartlink_clicked_direct', sanitize_text_field($params['slug']), $campaignEmail->subscriber);
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

        return esc_url_raw($url);
    }
}
