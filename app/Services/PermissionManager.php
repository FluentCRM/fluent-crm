<?php

namespace FluentCrm\App\Services;

class PermissionManager
{
    public static function getReadablePermissions()
    {
        return apply_filters('fluent_crm/readable_permissions', [
            'fcrm_view_dashboard'             => [
                'title'   => __('CRM Dashboard', 'fluent-crm'),
                'depends' => [],
                'group'   => 'dashboard'
            ],
            'fcrm_read_contacts'              => [
                'title'   => __('Contacts Read', 'fluent-crm'),
                'depends' => [],
                'group'   => 'contacts'
            ],
            'fcrm_manage_contacts'            => [
                'title'   => __('Contacts Add/Update/Import', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_contacts'
                ],
                'group'   => 'contacts'
            ],
            'fcrm_manage_contacts_delete'     => [
                'title'   => __('Contacts Delete', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_contacts'
                ],
                'group'   => 'contacts'
            ],
            'fcrm_manage_contacts_export'     => [
                'title'   => __('Contacts Export', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_contacts'
                ],
                'group'   => 'contacts'
            ],
            'fcrm_manage_contact_cats'        => [
                'title'   => __('Contact Tags/List/Companies/Segment Create or Update', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_contacts'
                ],
                'group'   => 'segments'
            ],
            'fcrm_manage_contact_cats_delete' => [
                'title'   => __('Contact Tags/List/Companies/Segment Delete', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_contacts'
                ],
                'group'   => 'segments'
            ],
            'fcrm_read_emails'                => [
                'title'   => __('Emails Read', 'fluent-crm'),
                'depends' => [],
                'group'   => 'emailing'
            ],
            'fcrm_manage_emails'              => [
                'title'   => __('Emails Write/Send', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_emails'
                ],
                'group'   => 'emailing'
            ],
            'fcrm_manage_email_templates'     => [
                'title'   => __('Email Templates Manage', 'fluent-crm'),
                'depends' => [],
                'group'   => 'emailing'
            ],
            'fcrm_manage_email_delete'        => [
                'title'   => __('Emails Delete', 'fluent-crm'),
                'depends' => [],
                'group'   => 'emailing'
            ],
            'fcrm_manage_forms'               => [
                'title'   => __('Manage Forms', 'fluent-crm'),
                'depends' => [],
                'group'   => 'forms'
            ],
            'fcrm_read_funnels'               => [
                'title'   => __('Automation Read', 'fluent-crm'),
                'depends' => [],
                'group'   => 'automations'
            ],
            'fcrm_write_funnels'              => [
                'title'   => __('Automation Write/Edit/Delete', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_funnels'
                ],
                'group'   => 'automations'
            ],
            'fcrm_delete_funnels'             => [
                'title'   => __('Automation Delete', 'fluent-crm'),
                'depends' => [
                    'fcrm_read_funnels'
                ],
                'group'   => 'automations'
            ],
            'fcrm_manage_settings'            => [
                'title'   => __('Manage CRM Settings', 'fluent-crm'),
                'depends' => [],
                'group'   => 'settings'
            ]
        ]);
    }

    public static function pluginPermissions()
    {
        return apply_filters('fluent_crm/plugin_permissions', [
            'fcrm_view_dashboard',
            'fcrm_read_contacts',
            'fcrm_manage_contacts',
            'fcrm_manage_contacts_delete', // New
            'fcrm_manage_contacts_export', // New
            'fcrm_manage_contact_cats',
            'fcrm_manage_contact_cats_delete', // New
            'fcrm_read_emails',
            'fcrm_manage_emails',
            'fcrm_manage_email_delete', // New
            'fcrm_manage_email_templates',
            'fcrm_manage_forms',
            'fcrm_read_funnels',
            'fcrm_write_funnels',
            'fcrm_delete_funnels', // New
            'fcrm_manage_settings'
        ]);
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
            $permissions = $pluginPermission;
        } else {
            $permissions = array_values(array_intersect(array_keys($user->allcaps), $pluginPermission));
        }

        $permissions = apply_filters('fluent_crm/user_permissions', $permissions, $user);
        return array_values($permissions);
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
            return apply_filters('fluentcrm_current_admin_can', true, $permission);
        }

        if (defined('FLUENTCAMPAIGN')) {
            return current_user_can($permission);
        }

        return false;
    }
}
