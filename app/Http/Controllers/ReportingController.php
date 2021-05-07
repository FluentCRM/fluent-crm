<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Reporting;
use FluentCrm\Includes\Request\Request;
use FluentCrm\App\Models\CampaignUrlMetric;

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
}
