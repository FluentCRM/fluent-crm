<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\Helper;

/**
 *  CampaignUrlMetric Model - DB Model for Email URL Metrics
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class CampaignUrlMetric extends Model
{
    protected $table = 'fc_campaign_url_metrics';

    protected $guarded = ['id'];

    public function campaign()
    {
        return $this->belongsTo(__NAMESPACE__ . '\Campaign', 'campaign_id', 'id');
    }

    public function subscriber()
    {
        return $this->belongsTo(__NAMESPACE__ . '\Subscriber', 'subscriber_id', 'id');
    }

    public function url_stores()
    {
        return $this->belongsTo(__NAMESPACE__ . '\UrlStores', 'url_id', 'id');
    }

    public static function maybeInsert($data)
    {
        $query = static::where([
            'campaign_id'   => $data['campaign_id'],
            'subscriber_id' => $data['subscriber_id'],
            'type'          => $data['type']
        ])->when(!empty($data['url_id']), function ($query) use ($data) {
            return $query->where('url_id', $data['url_id']);
        });

        if ($instance = $query->first()) {
            $instance->counter += 1;
            $instance->save();
            return $instance;
        }

        return static::create($data);
    }

    public function getLinksReport($campaignId)
    {
        $stats = static::select(
            fluentCrmDb()->raw('count(*) as total'),
            'fc_url_stores.url',
            'fc_url_stores.id'
        )
            ->where('fc_campaign_url_metrics.campaign_id', $campaignId)
            ->where('fc_campaign_url_metrics.type', 'click')
            ->groupBy('fc_campaign_url_metrics.url_id')
            ->join('fc_url_stores', 'fc_url_stores.id', '=', 'fc_campaign_url_metrics.url_id')
            ->orderBy('total', 'DESC')
            ->get()->toArray();

        /**
         * Check the validity of a single click in FluentCRM Email Campaign.
         *
         * This filter allows you to modify the validity check for a single click.
         *
         * @since 2.8.43
         * 
         * @param bool $validity Default validity status. Default is true.
         */
        if ($stats && apply_filters('fluent_crm/check_single_click_validity', true)) {
            $campaign = fluentCrmGetFromCache('campaign_' . $campaignId, function () use ($campaignId) {
                return Campaign::withoutGlobalScopes()->find($campaignId);
            });

            $campaignLinks = [];
            if ($campaign && $campaign->email_body) {
                $campaignLinks = Helper::getLinksFromString($campaign->email_body);
            }

            if ($campaignLinks) {
                foreach ($stats as $statIndex => $stat) {
                    if ($stat['total'] < 1 && !in_array($stat['url'], $campaignLinks)) {
                        unset($stats[$statIndex]);
                        continue;
                    }
                    $stats[$statIndex]['url'] = str_replace(['&amp;'], ['&'], $stat['url']);
                    $stats[$statIndex]['url'] = esc_url_raw($stats[$statIndex]['url']);
                }
            }
        }

        return $stats;
    }

    public function getCampaignAnalytics($campaignId)
    {
        $unsubscribeCount = CampaignUrlMetric::where('campaign_id', $campaignId)
            ->where('type', 'unsubscribe')
            ->distinct()
            ->count('subscriber_id');

        $formattedStatus = [];

        $openCount = fluentCrmDb()->table('fc_campaign_emails')
            ->where('campaign_id', $campaignId)
            ->where(function ($q) {
                $q->where('is_open', 1)
                    ->orWhereNotNull('click_counter');
            })
            ->count();

        $clickCount = fluentCrmDb()->table('fc_campaign_emails')
            ->where('campaign_id', $campaignId)
            ->whereNotNull('click_counter')
            ->count();

        if ($openCount) {
            $formattedStatus['open'] = [
                'total'      => $openCount,
                'label'      => sprintf(__('Open Rate (%d)', 'fluent-crm'), $openCount),
                'type'       => 'open',
                'is_percent' => true,
                'icon_class' => 'dashicons dashicons-buddicons-pm'
            ];
        }

        if ($clickCount) {
            $formattedStatus['click'] = [
                'total'      => $clickCount,
                'label'      => sprintf(__('Click Rate (%d)', 'fluent-crm'), $clickCount),
                'type'       => 'click',
                'is_percent' => true,
                'icon_class' => 'el-icon el-icon-position'
            ];
        }


        if ($openCount && $clickCount) {
            $formattedStatus['ctor'] = [
                'total'      => number_format(($clickCount / $openCount) * 100, 2) . '%',
                'label'      => __('Click To Open Rate', 'fluent-crm'),
                'type'       => 'ctor',
                'icon_class' => 'el-icon el-icon-chat-dot-square'
            ];
        }

        if ($unsubscribeCount) {
            $formattedStatus['unsubscribe'] = [
                'total'      => $unsubscribeCount,
                'label'      => sprintf(__('Unsubscribe (%d)', 'fluent-crm'), $unsubscribeCount),
                'type'       => 'unsubscribe',
                'is_percent' => true,
                'icon_class' => 'el-icon el-icon-warning-outline'
            ];
        }

        $revenue = fluentcrm_get_campaign_meta($campaignId, '_campaign_revenue');

        if ($revenue && $revenue->value) {
            $data = (array)$revenue->value;
            foreach ($data as $currency => $cents) {
                if ($cents && $currency !== 'orderIds') {
                    $formattedStatus['revenue'] = [
                        'label'      => __('Revenue', 'fluent-crm') . ' (' . $currency . ')',
                        'type'       => 'revenue',
                        'total'      => number_format($cents / 100, 2),
                        'icon_class' => 'el-icon el-icon-money'
                    ];
                }
            }
        }

        return $formattedStatus;
    }

    public function getSubjectStats($campaign)
    {
        $subjects = $campaign->subjects()->get();

        if ($subjects->isEmpty()) {
            return [];
        }

        $subjectCounts = (new CampaignEmail)->getSubjectCount($campaign->id);

        $totalClicks = 0;
        $totalOpens = 0;

        foreach ($subjectCounts as $subjectCount) {
            $metric = $this->getSubjectMetric(
                $subjectCount->email_subject_id, $campaign->id
            );
            $totalClicks += $metric['total_clicks'];
            $totalOpens += $metric['total_opens'];
            $subjectCount->metric = $metric;
        }

        return [
            'subjects'     => $subjectCounts,
            'total_clicks' => $totalClicks,
            'total_opens'  => $totalOpens
        ];
    }

    private function getSubjectMetric($subjectId, $campaignId)
    {
        $clickMetrics = $this->getClickMetrics($campaignId, $subjectId);

        $openCount = (new CampaignEmail)->getOpenCount($subjectId);

        $clickTotal = array_sum($clickMetrics->pluck('total')->toArray());

        return [
            'clicks'       => $clickMetrics,
            'total_clicks' => $clickTotal,
            'total_opens'  => $openCount
        ];
    }

    public function getClickMetrics($campaignId, $subjectId)
    {
        return static::select(
            fluentCrmDb()->raw('count(*) as total'),
            'fc_url_stores.url'
        )
            ->where('fc_campaign_url_metrics.campaign_id', $campaignId)
            ->where('fc_campaign_url_metrics.type', 'click')
            ->where('fc_campaign_emails.email_subject_id', $subjectId)
            ->groupBy('fc_campaign_url_metrics.url_id')
            ->join('fc_url_stores', 'fc_url_stores.id', '=', 'fc_campaign_url_metrics.url_id')
            ->join('fc_campaign_emails', 'fc_campaign_emails.subscriber_id', '=', 'fc_campaign_url_metrics.subscriber_id')
            ->orderBy('total', 'DESC')
            ->get();
    }
}
