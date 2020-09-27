<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Services\Reporting;
use FluentCrm\Includes\Request\Request;
use FluentCrm\App\Models\CampaignUrlMetric;

class ReportingController extends Controller
{
    public function getContactGrowth(Request $request, Reporting $reporting)
    {
        list($from, $to) = $request->get('date_range');
        return $this->sendSuccess([
            'stats' => $reporting->getSubscribersGrowth($from, $to)
        ]);
    }

    public function getEmailSentStats(Request $request, Reporting $reporting)
    {
        list($from, $to) = $request->get('date_range');
        return $this->sendSuccess([
            'stats' => $reporting->getEmailStats($from, $to)
        ]);
    }

    public function getEmailOpenStats(Request $request, Reporting $reporting)
    {
        list($from, $to) = $request->get('date_range');
        return $this->sendSuccess([
            'stats' => $reporting->getEmailOpenStats($from, $to)
        ]);
    }

    public function getEmailClickStats(Request $request, Reporting $reporting)
    {
        list($from, $to) = $request->get('date_range');
        return $this->sendSuccess([
            'stats' => $reporting->getEmailClickStats($from, $to)
        ]);
    }
}
