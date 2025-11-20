<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Stats;
use FluentCrm\Framework\Support\Arr;

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

        $nextMinuteTask = Helper::getNextMinuteTaskTimeStamp();

        $notices = [];

        if ((time() - $nextMinuteTask) > 120) {
            $notices[] = '<div style="padding: 15px 10px;" class="error"><b>Attention: </b> Looks like the scheduled cron jobs are not running timely. Please consider setup server side cron. <a href="' . admin_url('admin.php?page=fluentcrm-admin#/settings/settings_tools') . '">Click here to check the status</a></div>';
        }

        $systemTips = '';
        $emailsCount = Arr::get($overallStats, 'email_sent.count', 0);
        if ($emailsCount > 400000) {
            $lastEmail = CampaignEmail::orderBy('id', 'ASC')->first();
            if ($lastEmail && strtotime($lastEmail->created_at) < strtotime('-120 days')) {
                $emailsCount = number_format($emailsCount, 0);
                $sysBody = '<div class="fc_system_tips">';
                $sysBody .= '<p>You have ' . $emailsCount . ' email history in the database. Consider clean up old email history to speed up your next email campaign.</p>';
                $sysBody .= '<a href="' . fluentcrm_menu_url_base('settings/settings_tools') . '" class="el-button el-button--small el-button--default">View Data Cleanup</a>';
                $sysBody .= '</div>';
                $systemTips = [
                    'title' => 'Database Cleanup Suggestion',
                    'body'  => $sysBody,
                ];
            }
        }

        /**
         * Define the FluentCRM dashboard notices.
         *
         * This filter allows modification of the notices displayed on the FluentCRM dashboard.
         *
         * @since 2.8.40
         *
         * @param array $notices An array of notices to be displayed on the dashboard.
         */
        $notices = apply_filters('fluent_crm/dashboard_notices', $notices);

        /**
         * Define the dashboard data for FluentCRM.
         *
         * @since 2.9.23
         * 
         * @param array {
         *     The dashboard data array.
         *
         *     @type array $stats             Overall statistics.
         *     @type array $sales             Sales statistics.
         *     @type array $dashboard_notices Notices to be displayed on the dashboard.
         *     @type array $onboarding        Onboarding statistics.
         *     @type array $quick_links       Quick links for the dashboard.
         *     @type array $ff_config         FluentForm configuration.
         *     @type array $recommendation    Recommendations for the user.
         *     @type array $system_tips       System tips for the user.
         * }
         */
        return apply_filters('fluent_crm/dashboard_data', [
            'stats'             => $overallStats,
            /**
             * Determine the FluentCRMsales statistics data.
             *
             * This filter allows modification of the sales statistics data before it is used.
             *
             * @since 2.7.0
             *
             * @param array An array of sales statistics data.
             */
            'sales'             => apply_filters('fluent_crm/sales_stats', []),
            'dashboard_notices' => $notices,
            'onboarding'        => $stats->getOnboardingStat(),
            'quick_links'       => $stats->getQuickLinks(),
            'ff_config'         => [
                'is_installed'     => defined('FLUENTFORM'),
                'create_form_link' => admin_url('admin.php?page=fluent_forms#add=1')
            ],
            'recommendation'    => $this->recommendation(),
            'system_tips'       => $systemTips
        ]);
    }

    private function recommendation()
    {
        if (defined('FLUENTCAMPAIGN')) {
            return false;
        }

        $recommendations = [];

        if (defined('WC_PLUGIN_FILE')) {
            $recommendations[] = [
                'provider'    => 'WooCommerce',
                'title'       => 'Do more with WooCommerce + FluentCRM',
                'description' => 'Integrate FluentCRM with WooCommerce and segment your customers by purchase behavior, send super targeted emails, onboarding emails, cross promotions and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'learn_more'  => 'https://fluentcrm.com/integrations/woocommerce-marketing-automation/',
                'base_title'  => 'Supercharge your WooCommerce store by upgrading FluentCRM Pro'
            ];
            $recommendations[] = [
                'provider'    => 'WooCommerce',
                'title'       => 'Do more with WooCommerce + FluentCRM',
                'description' => 'Integrate FluentCRM with WooCommerce and segment your customers by purchase behavior, send super targeted emails, onboarding emails, cross promotions and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'learn_more'  => 'https://fluentcrm.com/integrations/woocommerce-marketing-automation/',
                'base_title'  => 'Supercharge your WooCommerce store by upgrading FluentCRM Pro'
            ];
        }

        if (class_exists('\Easy_Digital_Downloads')) {
            $recommendations[] = [
                'provider'    => 'EDD',
                'title'       => 'Do more with EDD + FluentCRM',
                'description' => 'Integrate FluentCRM with Easy Digital Downloads and segment your customers by purchase behavior, send super targeted emails, onboarding emails, cross promotions and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'learn_more'  => 'https://fluentcrm.com/integrations/easy-digital-downloads-integration-fluentcrm/',
                'base_title'  => 'Supercharge your Digital Downloads store by upgrading FluentCRM Pro'
            ];
        }

        if (defined('LLMS_PLUGIN_FILE')) {
            $recommendations[] = [
                'provider'    => 'LifterLMS',
                'title'       => 'Do more with LifterLMS + FluentCRM',
                'description' => 'Integrate LifterLMS with FluentCRM and segment your students by courses, send super targeted emails, onboarding emails, cross promote more courses and many more.',
                'learn_more'  => 'https://fluentcrm.com/integrations/lifterlms/',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your LMS by upgrading FluentCRM Pro'
            ];
            $recommendations[] = [
                'provider'    => 'LifterLMS',
                'title'       => 'Do more with LifterLMS + FluentCRM',
                'description' => 'Integrate LifterLMS with FluentCRM and segment your students by courses, send super targeted emails, onboarding emails, cross promote more courses and many more.',
                'learn_more'  => 'https://fluentcrm.com/integrations/lifterlms/',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your LMS by upgrading FluentCRM Pro'
            ];
        } else if (defined('LEARNDASH_VERSION')) {
            $recommendations[] = [
                'provider'    => 'LearnDash',
                'title'       => 'Do more with LearnDash + FluentCRM',
                'description' => 'Integrate LearnDash with FluentCRM and segment your students by courses, send super targeted emails, onboarding emails, cross promote more courses and many more.',
                'learn_more'  => 'https://fluentcrm.com/integrations/learndash-integration-fluentcrm/',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your LMS by upgrading FluentCRM Pro'
            ];
            $recommendations[] = [
                'provider'    => 'LearnDash',
                'title'       => 'Do more with LearnDash + FluentCRM',
                'description' => 'Integrate LearnDash with FluentCRM and segment your students by courses, send super targeted emails, onboarding emails, cross promote more courses and many more.',
                'learn_more'  => 'https://fluentcrm.com/integrations/learndash-integration-fluentcrm/',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your LMS by upgrading FluentCRM Pro'
            ];
        } else if (defined('TUTOR_VERSION')) {
            $recommendations[] = [
                'provider'    => 'TutorLMS',
                'title'       => 'Do more with TutorLMS + FluentCRM',
                'description' => 'Integrate TutorLMS with FluentCRM and segment your students by courses, send super targeted emails, onboarding emails, cross promote more courses and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your LMS by upgrading FluentCRM Pro'
            ];
        } else if (defined('TUTOR_VERSION')) {
            $recommendations[] = [
                'provider'    => 'TutorLMS',
                'title'       => 'Do more with TutorLMS + FluentCRM',
                'description' => 'Integrate TutorLMS with FluentCRM and segment your students by courses, send super targeted emails, onboarding emails, cross promote more courses and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'learn_more'  => 'https://fluentcrm.com/docs/tutorlms-integration-with-fluentcrm/',
                'base_title'  => 'Supercharge your LMS by upgrading FluentCRM Pro'
            ];
        } else if (defined('LP_PLUGIN_FILE')) {
            $recommendations[] = [
                'provider'    => 'LearnPress',
                'title'       => 'Do more with LearnPress + FluentCRM',
                'description' => 'Integrate LearnPress with FluentCRM and segment your students by courses, send super targeted emails, onboarding emails, cross promote more courses and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'learn_more'  => 'https://fluentcrm.com/docs/learpress-integration-with-fluentcrm/',
                'base_title'  => 'Supercharge your LMS by upgrading FluentCRM Pro'
            ];
        }

        if (defined('PMPRO_VERSION')) {
            $recommendations[] = [
                'provider'    => 'PaidMembership Pro',
                'title'       => 'Do more with PaidMembership Pro + FluentCRM',
                'description' => 'Integrate PaidMembership Pro with FluentCRM and segment your members by membership levels, send super targeted emails, onboarding emails, cross promote more levels and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your Membership Site by upgrading FluentCRM Pro'
            ];
        } else if (defined('WLM3_PLUGIN_VERSION')) {
            $recommendations[] = [
                'provider'    => 'Wishlist Member',
                'title'       => 'Do more with Wishlist Member + FluentCRM',
                'description' => 'Integrate Wishlist Member with FluentCRM and segment your members by membership levels, send super targeted emails, onboarding emails, cross promote more levels and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your Membership Site by upgrading FluentCRM Pro'
            ];
        } else if (defined('MEPR_PLUGIN_NAME')) {
            $recommendations[] = [
                'provider'    => 'MemberPress',
                'title'       => 'Do more with MemberPress + FluentCRM',
                'description' => 'Integrate MemberPress with FluentCRM and segment your members by membership levels, send super targeted emails, onboarding emails, cross promote more levels and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your Membership Site by upgrading FluentCRM Pro'
            ];
        } else if (class_exists('\Restrict_Content_Pro')) {
            $recommendations[] = [
                'provider'    => 'Restrict Content Pro',
                'title'       => 'Do more with Restrict Content Pro + FluentCRM',
                'description' => 'Integrate Restrict Content Pro with FluentCRM and segment your members by membership levels, send super targeted emails, onboarding emails, cross promote more levels and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your Membership Site by upgrading FluentCRM Pro'
            ];
        }

        if (defined('BP_REQUIRED_PHP_VERSION') && function_exists('\buddypress')) {
            $title = defined('BP_PLATFORM_VERSION') ? 'BuddyBoss' : 'BuddyPress';
            $recommendations[] = [
                'provider'    => $title,
                'title'       => 'Do more with ' . $title . ' + FluentCRM',
                'description' => 'Integrate ' . $title . ' with FluentCRM and segment your members by different group, send super targeted emails, onboarding emails, cross promote more groups and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'base_title'  => 'Supercharge your Community Site by upgrading FluentCRM Pro'
            ];
        }

        if (!$recommendations) {
            return false;
        }

        return $recommendations[array_rand($recommendations)];

    }
}
