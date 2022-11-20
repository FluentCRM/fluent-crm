<?php

namespace FluentCrm\App\Services;

trait ReportingHelperTrait
{
    protected static $daily = 'P1D';
    protected static $weekly = 'P1W';
    protected static $monthly = 'P1M';

    protected function makeFromDate($from)
    {
        $from = $from ?: '-30 days';

        return new \DateTime($from);
    }

    protected function makeToDate($to)
    {
        $to = $to ?: '+1 days';

        return new \DateTime($to);
    }

    protected function makeDatePeriod($from, $to, $interval = null)
    {
        $interval = $interval ?: static::$daily;

        return new \DatePeriod($from, new \DateInterval($interval), $to);
    }

    protected function getFrequency($from, $to)
    {
        $numDays = $to->diff($from)->format("%a");

        if ($numDays > 62 && $numDays <= 92) {
            return static::$weekly;
        } else if ($numDays > 92) {
            return static::$monthly;
        }

        return static::$daily;
    }

    protected function prepareSelect($frequency, $dateField = 'created_at')
    {
        $select = [
            fluentCrmDb()->raw('COUNT(id) AS count'),
            fluentCrmDb()->raw('DATE(' . $dateField . ') AS date')
        ];

        if ($frequency == static::$weekly) {
            $select[] = fluentCrmDb()->raw('WEEK(created_at) week');
        } else if ($frequency == static::$monthly) {
            $select[] = fluentCrmDb()->raw('MONTH(created_at) month');
        }

        return $select;
    }

    protected function getGroupAndOrder($frequency)
    {
        $orderBy = $groupBy = 'date';

        if ($frequency == static::$weekly) {
            $orderBy = $groupBy = 'week';
        } else if ($frequency == static::$monthly) {
            $orderBy = $groupBy = 'month';
        }

        return [$groupBy, $orderBy];
    }

    protected function getDateRangeArray($period)
    {
        $range = [];

        $formatter = 'basicFormatter';

        if ($this->isMonthly($period)) {
            $formatter = 'monYearFormatter';
        }

        foreach ($period as $date) {
            $date = $this->{$formatter}($date);
            $range[$date] = 0;
        }

        return $range;
    }

    protected function getResult($period, $items)
    {
        $range = $this->getDateRangeArray($period);

        $formatter = 'basicFormatter';

        if ($this->isMonthly($period)) {
            $formatter = 'monYearFormatter';
        }

        foreach ($items as $item) {
            $date = $this->{$formatter}($item->date);
            $range[$date] = (int)$item->count;
        }

        return $range;
    }

    protected function isMonthly($period)
    {
        return !!$period->getDateInterval()->m;
    }

    protected function basicFormatter($date)
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format('Y-m-d');
    }

    protected function monYearFormatter($date)
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format('M Y');
    }
}
