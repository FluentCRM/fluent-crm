<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\CampaignUrlMetric;

/**
 *  UrlMetrics Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class UrlMetrics
{
    protected $urlMappers = [];

    public function fluentcrmAddUrlTracking($content, $data, $shorting = true)
    {
        $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
        preg_match_all("/$regexp/siU", $content, $matches);
        $urls = [];
        if (isset($matches[2]) && count($matches[2])) {
            $urls = array_unique($matches[2]);
        }
        if (!$urls) {
            return $content;
        }
        $replaces = [];
        foreach ($urls as $url) {
            $finalUrl = add_query_arg($data, $url);
            $replaces['href="' . $url . '"'] = 'href="' . $finalUrl . '"';
        }
        return str_replace(array_keys($replaces), array_values($replaces), $content);
    }

    public function getShortUrlId($url)
    {
        if(isset($this->urlMappers[$url])) {
            return $this->urlMappers[$url];
        }
    }

    public function trackUrl()
    {
        if (!isset($_GET['cm_id']) || !isset($_GET['su_id']) || wp_doing_ajax()) {
            return;
        }
        $campaignId = intval($_GET['cm_id']);
        $subscriberId = intval($_GET['su_id']);
        $currentUrl = $this->getCurrentUrl();
        $actualUrl = explode('?', $currentUrl)[0];

        $urlParams = $_GET;
        unset($urlParams['cm_id']);
        unset($urlParams['su_id']);

        $actualUrl = add_query_arg($urlParams, $actualUrl);

        $metric = CampaignUrlMetric::where('subscriber_id', $subscriberId)->where('url', $actualUrl)->first();

        if($metric) {
            CampaignUrlMetric::where('id', $metric->id)->update([
                'counter' => $metric->counter + 1,
                'updated_at' => fluentCrmTimestamp()
            ]);
        } else {
            $data = [
                'url'           => $actualUrl,
                'campaign_id'   => $campaignId,
                'subscriber_id' => $subscriberId,
                'counter'       => 1,
                'ip_address'    => $this->getClientIP(),
                'created_at' => fluentCrmTimestamp(),
                'updated_at' => fluentCrmTimestamp()
            ];
            $metric = CampaignUrlMetric::insert($data);
            do_action('fluentcrm_email_link_click_inserted', $metric);
        }
    }

    public function getClickCount($campaignId)
    {
        return CampaignUrlMetric::where('campaign_id', $campaignId)
            ->where('counter', '>', 0)
            ->count();
    }

    protected function getCurrentUrl()
    {
        return "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    protected function getClientIP()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}
