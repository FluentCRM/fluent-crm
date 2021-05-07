<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Request\Request;

class UsersController extends Controller
{
    /**
     * Get all the users.
     * @param \FluentCrm\Includes\Request\Request $request
     * @return \WP_REST_Response
     */
    public function index(Request $request)
    {
        $roles = $request->get('roles', []);
        $limit = $request->limit ?: 5;
        $fields = $request->fields ?: ['ID', 'display_name', 'user_email'];

        $users = get_users([
            'role__in' => $roles,
            'fields'   => $fields,
            'number'   => $limit
        ]);

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
            'map', 'tags', 'lists', 'roles', 'update', 'new_status', 'double_optin_email'
        ]);

        $limit = apply_filters('fluentcrm_process_subscribers_per_request', 100);
        $page = intval($request->get('page', 1));

        $userQuery = new \WP_User_Query([
            'role__in' => $inputs['roles'],
            'number'   => $limit,
            'offset'   => ($page - 1) * $limit
        ]);

        $total = $userQuery->get_total();
        $users = $userQuery->get_results();
        $this->processUsers($users, $inputs);
        $hasRecords = !!count($users);

        return $this->sendSuccess([
            'message'      => 'Processing',
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
                $subscribers[] = $subscriber;
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
