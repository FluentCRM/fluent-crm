<?php

namespace FluentCrm\App\Services;

class PermissionManager
{
    public static function getReadablePermissions()
    {
        return [
            'fcrm_view_dashboard'         => [
                'title'   => __('CRM Dashboard', 'fluent-crm'),
                'depends' => []
            ],
            'fcrm_read_contacts'          => [
                'title'   => __('Contacts Read', 'fluent-crm'),
                'depends' => []
            ],
            'fcrm_manage_contacts'        => [
                'title'   => __('Contacts Edit/Update/Delete', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_contacts'
                ]
            ],
            'fcrm_manage_contact_cats'    => [
                'title'   => __('Contact Tags/List/Segment Manage', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_contacts'
                ]
            ],
            'fcrm_read_emails'            => [
                'title'   => __('Emails Read', 'fluent-crm'),
                'depends' => []
            ],
            'fcrm_manage_emails'          => [
                'title'   => __('Emails Write/Send/Delete', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_emails'
                ]
            ],
            'fcrm_manage_email_templates' => [
                'title'   => __('Email Templates Manage', 'fluent-crm'),
                'depends' => []
            ],
            'fcrm_manage_forms'           => [
                'title'   => __('Manage Forms', 'fluent-crm'),
                'depends' => []
            ],
            'fcrm_read_funnels'           => [
                'title'   => __('Automation Read', 'fluent-crm'),
                'depends' => []
            ],
            'fcrm_write_funnels'          => [
                'title'   => __('Automation Write/Edit/Delete', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_funnels'
                ]
            ],
            'fcrm_manage_settings'        => [
                'title'   => 'Settings Manage',
                'depends' => []
            ]
        ];
    }

    public static function pluginPermissions()
    {
        return [
            'fcrm_view_dashboard',
            'fcrm_read_contacts',
            'fcrm_manage_contacts',
            'fcrm_manage_contact_cats',
            'fcrm_read_emails',
            'fcrm_manage_emails',
            'fcrm_manage_email_templates',
            'fcrm_manage_forms',
            'fcrm_read_funnels',
            'fcrm_write_funnels',
            'fcrm_manage_settings'
        ];
    }

    public static function attachPermissions($user, $permissions)
    {
        if (is_numeric($user)) {
            $user = get_user_by('ID', $user);
        }

        if (!$user) {
            return false;
        }

        if (user_can($user, 'manage_options')) {
            return $user;
        }

        $allPermissions = self::pluginPermissions();
        foreach ($allPermissions as $permission) {
            $user->remove_cap($permission);
        }

        $permissions = array_intersect($allPermissions, $permissions);

        foreach ($permissions as $permission) {
            $user->add_cap($permission);
        }

        return $user;
    }

    public static function getUserPermissions($user = false)
    {
        if (is_numeric($user)) {
            $user = get_user_by('ID', $user);
        }

        if (!$user) {
            return [];
        }

        $pluginPermission = self::pluginPermissions();

        if ($user->has_cap('manage_options')) {
            $pluginPermission[] = 'administrator';
            return $pluginPermission;
        }

        return array_values(array_intersect(array_keys($user->allcaps), $pluginPermission));
    }

    public static function currentUserPermissions($cached = true)
    {
        static $permissions;

        if ($permissions && $cached) {
            return $permissions;
        }

        $permissions = self::getUserPermissions(get_current_user_id());

        return $permissions;
    }

    public static function currentUserCan($permission)
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        if (defined('FLUENTCAMPAIGN')) {
            return current_user_can($permission);
        }

        return false;
    }
}
