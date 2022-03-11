<?php

namespace FluentCrm\App\Hooks\Handlers;

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
        $mailId = false;
        $urlSlug = sanitize_text_field($data['ns_url']);

        if (isset($data['mid'])) {
            $mailId = intval($data['mid']);
        }

        $urlData = UrlStores::getRowByShort($urlSlug);

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

        $campaignEmail = CampaignEmail::with(['campaign', 'subscriber'])->find($mailId);

        if (!$campaignEmail) {
            return $urlData->url;
        }

        $id = CampaignUrlMetric::maybeInsert([
            'url_id'        => $urlData->id,
            'campaign_id'   => $campaignEmail->campaign_id,
            'subscriber_id' => $campaignEmail->subscriber_id,
            'type'          => 'click',
            'ip_address'    => FluentCrm('request')->getIp()
        ]);

        if (apply_filters('fluentcrm_will_use_cookie', true)) {
            setcookie("fc_s_hash", $campaignEmail->subscriber->hash, time() + 9676800);  /* expire in 28 days */
            $_COOKIE['fc_s_hash'] = $campaignEmail->subscriber->hash;
            if ($campaignEmail->campaign_id) {
                setcookie("fc_cid", $campaignEmail->campaign_id, time() + 9676800);  /* expire in 28 days */
            }
        }

        do_action(FLUENTCRM . '_email_url_clicked', $campaignEmail, $urlData);

        $args = $campaignEmail->campaign->getUtmParams();

        $campaignEmail->click_counter += 1;
        $campaignEmail->is_open = 1;
        $campaignEmail->save();

        do_action('fluentform_track_activity_by_subscriber', $campaignEmail->subscriber_id);

        $url = str_replace('&amp;', '&', $urlData->url);;

        if(strpos($url, 'route=smart_url')) {
            // this is a smart URL
            $url_components = parse_url($url);

            parse_str($url_components['query'], $params);

            if(!empty($params['slug'])) {
                do_action('fluentcrm_smartlink_clicked_direct', sanitize_text_field($params['slug']), $campaignEmail->subscriber);
            }
        }

        if(strpos($urlData->url, 'route=bnu')) {
            $url_components = parse_url($url);
            parse_str($url_components['query'], $params);
            if(!empty($params['aid'])) {
                $benchmarkActionId = intval($params['aid']);
                do_action('fluencrm_benchmark_link_clicked', $benchmarkActionId, $campaignEmail->subscriber);
            }
            $args['bnu_timer_'.time()] = time();
        }

        if ($args) {
            $url = add_query_arg($args, $url);
        }

        return esc_url_raw($url);
    }
}
