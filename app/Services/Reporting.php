<?php

namespace FluentCrm\App\Services;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;

class Reporting
{
    use ReportingHelperTrait;

    public function getSubscribersGrowth($from = false, $to = false)
    {
        $period = $this->makeDatePeriod(
            $from = $this->makeFromDate($from),
            $to = $this->makeToDate($to),
            $frequency = $this->getFrequency($from, $to)
        );

        list($groupBy, $orderBy) = $this->getGroupAndOrder($frequency);

        $items = wpFluent()->table('fc_subscribers')
            ->select($this->prepareSelect($frequency))
            ->whereBetween('created_at', $from->format('Y-m-d'), $to->format('Y-m-d'))
            ->groupBy($groupBy)
            ->orderBy($orderBy, 'ASC')
            ->where('status', 'subscribed')
            ->get();

        return $this->getResult($period, $items);
    }

    public function getEmailOpenStats($from = false, $to = false)
    {
        $period = $this->makeDatePeriod(
            $from = $this->makeFromDate($from),
            $to = $this->makeToDate($to),
            $frequency = $this->getFrequency($from, $to)
        );

        list($groupBy, $orderBy) = $this->getGroupAndOrder($frequency);

        $items = wpFluent()->table('fc_campaign_url_metrics')
            ->select($this->prepareSelect($frequency))
            ->whereBetween('created_at', $from->format('Y-m-d'), $to->format('Y-m-d'))
            ->groupBy($groupBy)
            ->where('type', 'open')
            ->orderBy($orderBy, 'ASC')
            ->get();

        return $this->getResult($period, $items);
    }

    public function getEmailClickStats($from = false, $to = false)
    {
        $period = $this->makeDatePeriod(
            $from = $this->makeFromDate($from),
            $to = $this->makeToDate($to),
            $frequency = $this->getFrequency($from, $to)
        );

        list($groupBy, $orderBy) = $this->getGroupAndOrder($frequency);

        $items = wpFluent()->table('fc_campaign_url_metrics')
            ->select($this->prepareSelect($frequency))
            ->whereBetween('created_at', $from->format('Y-m-d'), $to->format('Y-m-d'))
            ->groupBy($groupBy)
            ->where('type', 'click')
            ->orderBy($orderBy, 'ASC')
            ->get();

        return $this->getResult($period, $items);
    }

    public function getEmailStats($from = false, $to = false, $status = 'sent')
    {
        $period = $this->makeDatePeriod(
            $from = $this->makeFromDate($from),
            $to = $this->makeToDate($to),
            $frequency = $this->getFrequency($from, $to)
        );

        list($groupBy, $orderBy) = $this->getGroupAndOrder($frequency);

        $items = wpFluent()->table('fc_campaign_emails')
            ->select($this->prepareSelect($frequency, 'scheduled_at'))
            ->whereBetween('scheduled_at', $from->format('Y-m-d'), $to->format('Y-m-d'))
            ->groupBy($groupBy)
            ->orderBy($orderBy, 'ASC')
            ->where('status', $status)
            ->get();

        return $this->getResult($period, $items);
    }

    public function funnelStat($funnelId, $sequences = [], $from = false, $to = false)
    {
        if (!$sequences) {
            $sequences = FunnelSequence::where('funnel_id', $funnelId)
                ->orderBy('sequence', 'ASC')
                ->get();
        }

        if (!$sequences) {
            return [];
        }

        $sequenceIds = $sequences->pluck('id');

        $totalSubscriberCount = FunnelSubscriber::where('funnel_id', $funnelId)
            ->groupBy('subscriber_id')
            ->count();

        $items = FunnelMetric::select([
            'sequence_id',
            'benchmark_currency',
            wpFluent()->raw('COUNT(sequence_id) AS count'),
        ])
            ->groupBy('sequence_id')
            ->whereIn('sequence_id', $sequenceIds)
            ->get()->keyBy('sequence_id');

        $totalRevenue = FunnelMetric::select([
            wpFluent()->raw('SUM(benchmark_value) AS benchmark_total'),
        ])->whereIn('sequence_id', $sequenceIds)->first();

        if ($totalRevenue && $totalRevenue->benchmark_total) {
            $totalRevenue = $totalRevenue->benchmark_total / 100;
        } else {
            $totalRevenue = 0;
        }

        $formattedReports = [
            [
                'label' => 'Entrance',
                'count' => $totalSubscriberCount,
                'sequence_id' => 0,
                'type' => 'root',
                'percent' => 100,
                'previous_step_count' => $totalSubscriberCount,
                'drop_count' => 0,
                'drop_percent' => 0
            ]
        ];

        $currency = 'USD';
        $prevCount = $totalSubscriberCount;
        foreach ($sequences as $sequence ) {
            if (empty($items[$sequence->id])) {
                continue;
            }

            $count = ($items[$sequence->id]->count) ? $items[$sequence->id]->count : 0;
            $dropCount = $prevCount - $count;
            $formattedReports[] = [
                'label' => $sequence->title,
                'count' => intval($count),
                'sequence_id' => $sequence->id,
                'type' => $sequence->type,
                'percent' => ceil(($count / $totalSubscriberCount) * 100),
                'previous_step_count' => $prevCount,
                'drop_count' => $dropCount,
                'drop_percent' => ($dropCount && $count) ? floor((1 - ($count / $prevCount)) * 100) : 0
            ];

            if ($items[$sequence->id]->benchmark_currency) {
                $currency = $items[$sequence->id]->benchmark_currency;
            }

            $prevCount = $count;
        }

        return [
            'metrics' => $formattedReports,
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 2, '.', ' '),
            'revenue_currency' => $currency
        ];
    }
}
