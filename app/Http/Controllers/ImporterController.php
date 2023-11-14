<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;
use FluentCrm\App\Models\Subscriber;

/**
 *  ImporterController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class ImporterController extends Controller
{
    public function getDrivers()
    {
        $drivers = apply_filters('fluent_crm/import_providers', [
            'csv'   => [
                'label'    => __('CSV File', 'fluent-crm'),
                'logo'     => fluentCrmMix('images/csv.svg'),
                'disabled' => false
            ],
            'users' => [
                'label'    => __('WordPress Users', 'fluent-crm'),
                'logo'     => fluentCrmMix('images/wordpress.svg'),
                'disabled' => false
            ]
        ]);

        if ($proDrivers = $this->getProDrivers()) {
            $drivers = array_merge($drivers, $proDrivers);
        }

        return [
            'drivers' => $drivers
        ];
    }

    public function getDriver(Request $request, $driver)
    {
        if ($driver == 'users') {
            return $this->processUserDriver($request);
        }

        $response = apply_filters('fluent_crm/get_import_driver_' . $driver, false, $request);

        if (!$response || is_wp_error($response)) {
            $message = __('Sorry no driver found for this import', 'fluent-crm');
            if (is_wp_error($response)) {
                $message = $response->get_error_message();
            }
            return $this->sendError([
                'message' => $message
            ]);
        }

        return $response;
    }

    public function importData(Request $request, $driver)
    {
        $config = $request->get('config');
        $page = $request->getSafe('importing_page', '', 'intval');

        if ($driver == 'users') {
            return $this->processUserImport($config, $page);
        }

        $response = apply_filters('fluent_crm/post_import_driver_' . $driver, false, $config, $page);

        if (!$response || is_wp_error($response)) {
            $message = __('Sorry no driver found for this import', 'fluent-crm');
            if (is_wp_error($response)) {
                $message = $response->get_error_message();
            }
            return $this->sendError([
                'message' => $message
            ]);
        }

        return $response;
    }

    private function processUserDriver($request)
    {
        $summary = $request->get('summary');

        if ($summary) {
            $config = $request->get('config');

            $userQuery = new \WP_User_Query([
                'role__in' => Arr::get($config, 'roles'),
                'number'   => 5,
                'fields'   => ['ID', 'display_name', 'user_email'],
            ]);

            $users = $userQuery->get_results();
            $total = $userQuery->get_total();

            $formattedUsers = [];

            foreach ($users as $user) {
                $formattedUsers[] = [
                    'name'  => $user->display_name,
                    'email' => $user->user_email
                ];
            }

            return $this->send([
                'import_info' => [
                    'subscribers'       => $formattedUsers,
                    'total'             => $total,
                    'has_list_config'   => true,
                    'has_tag_config'    => true,
                    'has_status_config' => true,
                    'has_update_config' => true,
                    'has_silent_config' => true
                ]]);
        }

        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . '/wp-admin/includes/user.php');
        }
        $roles = \get_editable_roles();

        $formattedRoles = [];

        foreach ($roles as $roleKey => $role) {
            $formattedRoles[] = [
                'id'    => $roleKey,
                'label' => $role['name']
            ];
        }

        return [
            'config' => [
                'roles' => []
            ],
            'fields' => [
                'roles' => [
                    'label'              => __('Select User Roles', 'fluent-crm'),
                    'inline_help'        => __('Please check the user roles that you want to import as contact', 'fluent-crm'),
                    'type'               => 'checkbox-group',
                    'options'            => $formattedRoles,
                    'input_class'        => 'fluentcrm_2col_labels',
                    'has_all_selector'   => true,
                    'all_selector_label' => __('All', 'fluent-crm')
                ]
            ],
            'labels' => [
                'step_2' => __('Next [Review Data]', 'fluent-crm'),
                'step_3' => __('Import Users Now', 'fluent-crm')
            ]
        ];
    }

    private function processUsers($users, $inputs)
    {
        $subscribers = [];
        foreach ($users as $user) {
            $subscriber = Helper::getWPMapUserInfo($user);
            $subscriber['source'] = 'wp_users';
            if ($subscriber['email']) {
                $subscribers[] = $subscriber;
            }
        }

        $sendDoubleOptin = Arr::get($inputs, 'double_optin_email') == 'yes';

        return Subscriber::import(
            $subscribers,
            Arr::get($inputs, 'tags', []),
            Arr::get($inputs, 'lists', []),
            Arr::get($inputs, 'update', ''),
            Arr::get($inputs, 'status', ''),
            $sendDoubleOptin
        );
    }

    private function processUserImport($config, $page)
    {
        $inputs = Arr::only($config, [
            'map', 'tags', 'lists', 'roles', 'update', 'status', 'double_optin_email', 'import_silently'
        ]);


        $limit = apply_filters('fluent_crm/process_subscribers_per_request', 100);

        $userQuery = new \WP_User_Query([
            'role__in' => $inputs['roles'],
            'number'   => $limit,
            'offset'   => ($page - 1) * $limit
        ]);

        if (Arr::get($inputs, 'import_silently') == 'yes') {
            if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
                define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
            }
        }

        $total = $userQuery->get_total();
        $users = $userQuery->get_results();
        if ($users) {
            $this->processUsers($users, $inputs);
        }

        $hasRecords = !!count($users);

        return $this->sendSuccess([
            'page_total'   => ceil($total / $limit),
            'record_total' => $total,
            'has_more'     => $hasRecords,
            'current_page' => $page,
            'next_page'    => $page + 1
        ]);
    }

    private function getProDrivers()
    {
        $drivers = [];

        if (defined('FLUENTCAMPAIGN')) {
            return $drivers;
        }

        if (defined('LLMS_PLUGIN_FILE')) {
            $drivers['lifterlms'] = [
                'label'            => __('LifterLMS', 'fluent-crm'),
                'logo'             => fluentCrmMix('images/lifterlms.png'),
                'disabled'         => true,
                'disabled_message' => __('Import LifterLMS students by course and groups then segment by associate tags. This is a pro feature. Please upgrade to activate this feature', 'fluent-crm')
            ];
        }

        if (defined('LEARNDASH_VERSION')) {
            $drivers['learndash'] = [
                'label'            => __('LearnDash', 'fluent-crm'),
                'logo'             => fluentCrmMix('images/learndash.png'),
                'disabled'         => true,
                'disabled_message' => __('Import LearnDash students by course and groups then segment by associate tags. This is a pro feature. Please upgrade to activate this feature', 'fluent-crm')
            ];
        }

        if (defined('TUTOR_VERSION')) {
            $drivers['tutorlms'] = [
                'label'            => __('TutorLMS', 'fluent-crm'),
                'logo'             => fluentCrmMix('images/tutorlms.jpg'),
                'disabled'         => true,
                'disabled_message' => __('Import TutorLMS students by course then segment by associate tags. This is a pro feature. Please upgrade to activate this feature', 'fluent-crm')
            ];
        }

        if (defined('PMPRO_VERSION')) {
            $drivers['pmpro'] = [
                'label'            => __('Paid Membership Pro', 'fluent-crm'),
                'logo'             => fluentCrmMix('images/pmpro.png'),
                'disabled'         => true,
                'disabled_message' => __('Import Paid Membership Pro members by membership levels then segment by associate tags. This is a pro feature. Please upgrade to activate this feature', 'fluent-crm')
            ];
        }

        if (defined('WLM3_PLUGIN_VERSION')) {
            $drivers['wishlist_member'] = [
                'label'            => __('Wishlist member', 'fluent-crm'),
                'logo'             => fluentCrmMix('images/wishlist_member.png'),
                'disabled'         => true,
                'disabled_message' => __('Import Wishlist members by membership levels then segment by associate tags. This is a pro feature. Please upgrade to activate this feature', 'fluent-crm')
            ];
        }

        if (class_exists('\Restrict_Content_Pro')) {
            $drivers['rcp'] = [
                'label'            => __('Restrict Content Pro', 'fluent-crm'),
                'logo'             => fluentCrmMix('images/rcp.png'),
                'disabled'         => true,
                'disabled_message' => __('Import Restrict Content Pro members by membership levels then segment by associate tags. This is a pro feature. Please upgrade to activate this feature', 'fluent-crm')
            ];
        }

        if (defined('BP_REQUIRED_PHP_VERSION') && function_exists('\buddypress')) {

            $pluginName = 'BuddyPress';
            $logo = fluentCrmMix('images/buddypress.png');

            if (defined('BP_PLATFORM_VERSION')) {
                $pluginName = 'BuddyBoss';
                $logo = fluentCrmMix('images/buddyboss.svg');
            }

            $drivers['buddypress'] = [
                'label'            => $pluginName,
                'logo'             => $logo,
                'disabled'         => true,
                'disabled_message' => sprintf(__('Import %s members by member groups and member types then segment by associate tags. This is a pro feature. Please upgrade to activate this feature', 'fluent-crm'), $pluginName)
            ];
        }

        if (defined('LP_PLUGIN_FILE')) {
            $drivers['learnpress'] = [
                'label'            => __('LearnPress', 'fluent-crm'),
                'logo'             => fluentCrmMix('images/learnpress.png'),
                'disabled'         => true,
                'disabled_message' => __('Import LearnPress students by course then segment by associate tags. This is a pro feature. Please upgrade to activate this feature', 'fluent-crm')
            ];
        }

        return $drivers;
    }
}
