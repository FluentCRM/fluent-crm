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
            'stats'             => $overallStats,
            'sales'             => apply_filters('fluentcrm_sales_stats', []),
            'dashboard_notices' => apply_filters('fluentcrm_dashboard_notices', []),
            'onboarding'        => $stats->getOnboardingStat(),
            'quick_links'       => $stats->getQuickLinks(),
            'ff_config'         => [
                'is_installed'     => defined('FLUENTFORM'),
                'create_form_link' => admin_url('admin.php?page=fluent_forms#add=1')
            ],
            'recommendation'    => $this->recommendation()
        ];
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
                'title'       => 'Do more with WoCommerce + FluentCRM',
                'description' => 'Integrate FluentCRM with WooCommerce and segment your customers by purchase behavior, send super targeted emails, onboarding emails, cross promotions and many more.',
                'btn_text'    => 'Upgrade to Pro',
                'learn_more'  => 'https://fluentcrm.com/integrations/woocommerce-marketing-automation/',
                'base_title'  => 'Supercharge your WooCommerce store by upgrading FluentCRM Pro'
            ];
            $recommendations[] = [
                'provider'    => 'WooCommerce',
                'title'       => 'Do more with WoCommerce + FluentCRM',
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

        if(!$recommendations) {
            return false;
        }

        return $recommendations[array_rand($recommendations)];

    }
}
