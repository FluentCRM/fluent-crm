<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\Includes\Request\Request;

class CampaignAnalyticsController extends Controller
{
    public function getLinksReport(CampaignUrlMetric $campaignUrlMetric, $campaignId)
    {
        return $this->sendSuccess([
            'links' => $campaignUrlMetric->getLinksReport($campaignId)
        ]);
    }
}
