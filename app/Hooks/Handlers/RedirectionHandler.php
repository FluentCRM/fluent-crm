<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\UrlStores;

class RedirectionHandler
{
    public function redirect($data)
    {
        $mailId = false;
        $urlSlug = sanitize_text_field($data['ns_url']);

        if (isset($data['mid'])) {
            $mailId = intval($data['mid']);
        }

        $urlData = UrlStores::where('short', $urlSlug)->first();

        if (!$urlData) {
            return;
        }

        $redirectUrl = $this->trackUrlClick($mailId, $urlData);

        if ($redirectUrl) {
            do_action('fluentcrm_email_url_click', $redirectUrl, $mailId, $urlData);
            wp_redirect(htmlspecialchars_decode($redirectUrl));
            exit;
        }
    }

    public function trackUrlClick($mailId, $urlData)
    {
        if (!$mailId) {
            return $urlData->url;
        }

        $campaignEmail = CampaignEmail::with('campaign')->find($mailId);

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

        setcookie("fc_sid", $campaignEmail->subscriber_id, time() + 2419200);  /* expire in 7 days */
        if ($campaignEmail->campaign_id) {
            setcookie("fc_cid", $campaignEmail->campaign_id, time() + 2419200);  /* expire in 7 days */
        }

        do_action(FLUENTCRM . '_email_url_clicked', $campaignEmail, $urlData);

        $args = $campaignEmail->campaign->getUtmParams();

        $campaignEmail->click_counter += 1;
        $campaignEmail->is_open = 1;
        $campaignEmail->save();

        do_action('fluentform_track_activity_by_subscriber', $campaignEmail->subscriber_id);

        if ($args) {
            return esc_url_raw(add_query_arg($args, $urlData->url));
        }

        return $urlData->url;
    }
}
