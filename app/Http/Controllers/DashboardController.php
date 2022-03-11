<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Services\Stats;

/**
 *  DashboardController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class DashboardController extends Controller
{
    public function getStats(Stats $stats)
    {
        $overallStats = $stats->getCounts();

        return [
            'stats' => $overallStats,
            'sales' => apply_filters('fluentcrm_sales_stats', []),
            'dashboard_notices' => apply_filters('fluentcrm_dashboard_notices', []),
            'onboarding' => $stats->getOnboardingStat(),
            'quick_links' => $stats->getQuickLinks(),
            'ff_config' => [
                'is_installed' => defined('FLUENTFORM'),
                'create_form_link' => admin_url('admin.php?page=fluent_forms#add=1')
            ]
        ];
    }
}
