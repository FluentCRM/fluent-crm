<?php

namespace FluentCrm\App\Models;

class CampaignUrlMetric extends Model
{
    protected $table = 'fc_campaign_url_metrics';

    public function campaign()
    {
        return $this->belongsTo(__NAMESPACE__.'\Campaign', 'campaign_id', 'id');
    }

    public static function maybeInsert($data)
    {
        $query = static::where([
            'campaign_id' => $data['campaign_id'],
            'subscriber_id' => $data['subscriber_id'],
            'type' => $data['type']
        ])->when(!empty($data['url_id']), function ($query) use ($data) {
            $query->where('url_id', $data['url_id']);
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
                wpFluent()->raw('count(*) as total'),
                'fc_url_stores.url',
                'fc_url_stores.id'
            )
            ->where('fc_campaign_url_metrics.campaign_id', $campaignId)
            ->where('fc_campaign_url_metrics.type', 'click')
            ->groupBy('fc_campaign_url_metrics.url_id')
            ->join('fc_url_stores', 'fc_url_stores.id', '=', 'fc_campaign_url_metrics.url_id')
            ->orderBy('total', 'DESC')
            ->get();

        return $stats;
    }

    public function getCampaignAnalytics($campaignId)
    {
        $stats = static::select('type', wpFluent()->raw('count(*) as total'))
            ->where('campaign_id', $campaignId)
            ->groupBy('type')
            ->get();

        foreach ($stats as $stat) {
            $stat->is_percent = true;
            $stat->label = ucfirst($stat->type) . ' Rate' . ' ('.$stat->total.')';
        }

        $revenue = fluentcrm_get_campaign_meta($campaignId, '_campaign_revenue');

        if($revenue && $revenue->value) {
            $data = (array) $revenue->value;
            foreach ($data as $currency => $cents) {
                if($cents) {
                    $stats[] = [
                        'label' => 'Revenue ('.$currency.')',
                        'total' => number_format($cents / 100, 2)
                    ];
                }
            }
        }

        return $stats;
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
            'subjects' => $subjectCounts,
            'total_clicks' => $totalClicks,
            'total_opens' => $totalOpens
        ];
    }

    private function getSubjectMetric($subjectId, $campaignId)
    {
        $clickMetrics = $this->getClickMetrics($campaignId, $subjectId);

        $openCount = (new CampaignEmail)->getOpenCount($subjectId);

        $clickTotal = array_sum($clickMetrics->pluck('total'));

        return [
            'clicks'       => $clickMetrics,
            'total_clicks' => $clickTotal,
            'total_opens'  => $openCount
        ];
    }

    public function getClickMetrics($campaignId, $subjectId)
    {
        return static::select(
                wpFluent()->raw('count(*) as total'),
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
