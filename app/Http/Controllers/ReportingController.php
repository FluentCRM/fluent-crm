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
        $emails = CampaignEmail::orderBy('id', 'DESC')
            ->with('subscriber', 'campaign')
            ->paginate();

        return [
            'emails' => $emails
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
