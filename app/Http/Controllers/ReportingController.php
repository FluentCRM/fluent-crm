<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Reporting;
use FluentCrm\Framework\Request\Request;
use FluentCrm\App\Models\CampaignUrlMetric;

/**
 *  ReportingController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class ReportingController extends Controller
{
    public function getContactGrowth(Request $request, Reporting $reporting)
    {
        list($from, $to) = $request->get('date_range') ?: ['', ''];
        return $this->sendSuccess([
            'stats' => $reporting->getSubscribersGrowth($from, $to)
        ]);
    }

    public function getEmailSentStats(Request $request, Reporting $reporting)
    {
        list($from, $to) = $request->get('date_range') ?: ['', ''];
        return $this->sendSuccess([
            'stats' => $reporting->getEmailStats($from, $to)
        ]);
    }

    public function getEmailOpenStats(Request $request, Reporting $reporting)
    {
        list($from, $to) = $request->get('date_range') ?: ['', ''];
        return $this->sendSuccess([
            'stats' => $reporting->getEmailOpenStats($from, $to)
        ]);
    }

    public function getEmailClickStats(Request $request, Reporting $reporting)
    {
        list($from, $to) = $request->get('date_range') ?: ['', ''];
        return $this->sendSuccess([
            'stats' => $reporting->getEmailClickStats($from, $to)
        ]);
    }

    public function getEmails(Request $request)
    {
        $status = $request->get('status', '');

        $emails = CampaignEmail::orderBy('scheduled_at', 'DESC')
            ->with('subscriber', 'campaign')
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->paginate();

        $statuses = null;

        if ($request->get('page') == 1) {
            $statuses = CampaignEmail::select('status')
                ->selectRaw('count(id) as total')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->map(function ($status) {
                    return $status->total;
                });
        }

        return [
            'emails' => $emails,
            'statuses' => $statuses
        ];
    }

    public function deleteEmails(Request $request)
    {
        $emailIds = $request->get('email_ids');
        CampaignEmail::whereIn('id', $emailIds)
            ->delete();

        return [
            'message' => __('Selected emails has been deleted', 'fluent-crm')
        ];
    }

    public function getAdvancedReportProviders()
    {
        return [
            /**
             * Determine the advanced report providers for FluentCRM.
             *
             * This filter allows you to modify the list of advanced report providers.
             *
             * @since 1.0.0
             *
             * @param array An array of advanced report providers.
             */
            'providers' => apply_filters('fluent_crm/advanced_report_providers', [])
        ];
    }

    public function ping()
    {
        return [
            'message' => 'pong'
        ];
    }
}
