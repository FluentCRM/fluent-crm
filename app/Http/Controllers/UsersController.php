<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Sanitize;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;

/**
 *  UsersController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class UsersController extends Controller
{
    /**
     * Get all the users.
     * @param \FluentCrm\Framework\Request\Request $request
     * @return \WP_REST_Response
     */
    public function index(Request $request)
    {
        $roles = $request->getSafe('roles', []);
        $limit = $request->limit ?: 5;
        $fields = $request->fields ?: ['ID', 'display_name', 'user_email'];

        $userQuery = new \WP_User_Query([
            'role__in' => $roles,
            'number'   => $limit,
            'fields'   => $fields,
        ]);

        $users = $userQuery->get_results();

        $total = $userQuery->get_total();

        return $this->send([
            'users' => $users,
            'total' => $total
        ]);
    }

    public function import(Request $request)
    {
        $inputs = $request->only([
            'map', 'tags', 'lists', 'roles', 'update', 'new_status', 'double_optin_email', 'import_silently'
        ]);

        $limit = apply_filters('fluent_crm/process_subscribers_per_request', 100);
        $page = absint($request->get('page', 1));

        $userQuery = new \WP_User_Query([
            'role__in' => $inputs['roles'],
            'number'   => $limit,
            'offset'   => ($page - 1) * $limit
        ]);

        if (Arr::get($inputs, 'import_silently') == 'yes') {
            if(!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
                define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
            }
        }

        $total = $userQuery->get_total();
        $users = $userQuery->get_results();
        if($users) {
            $this->processUsers($users, $inputs);
        }

        $hasRecords = !!count($users);

        return $this->sendSuccess([
            'message'      => __('Processing', 'fluent-crm'),
            'page_total'   => ceil($total / $limit),
            'record_total' => $total,
            'has_more'     => $hasRecords,
            'current_page' => $page,
            'next_page'    => $page + 1
        ]);

    }

    private function processUsers($users, $inputs)
    {
        $subscribers = [];
        foreach ($users as $user) {
            $subscriber = Helper::getWPMapUserInfo($user);
            $subscriber['source'] = 'wp_users';
            if ($subscriber['email']) {
                $subscribers[] = Sanitize::contact($subscriber);
            }
        }

        $sendDoubleOptin = Arr::get($inputs, 'double_optin_email') == 'yes';

        return Subscriber::import(
            $subscribers,
            $inputs['tags'],
            $inputs['lists'],
            $inputs['update'],
            $inputs['new_status'],
            $sendDoubleOptin
        );
    }

    public function roles()
    {
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . '/wp-admin/includes/user.php');
        }
        $roles = \get_editable_roles();

        return [
            'roles' => $roles
        ];
    }
}
