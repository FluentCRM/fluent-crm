<?php

namespace FluentCrm\App\Services;

class PermissionManager
{
    public static function getReadablePermissions()
    {
        /**
         * Filter the readable permissions for FluentCRM.
         *
         * This filter allows modification of the readable permissions array used in FluentCRM.
         *
         * @since 2.8.02
         *
         * @param array {
         *     An associative array of permissions.
         *
         *     @type array {
         *         @type string $title   The title of the permission.
         *         @type array  $depends An array of permissions that this permission depends on.
         *         @type string $group   The group to which this permission belongs.
         *     }
         * }
         */
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
        /**
         * Filter the permissions available in FluentCRM.
         *
         * This filter allows modification of the permissions used within the FluentCRM plugin.
         *
         * @since 2.8.02
         *
         * @param array {
         *     An array of permissions.
         *
         *     @type string 'fcrm_view_dashboard'             Permission to view the dashboard.
         *     @type string 'fcrm_read_contacts'              Permission to read contacts.
         *     @type string 'fcrm_manage_contacts'            Permission to manage contacts.
         *     @type string 'fcrm_manage_contacts_delete'     Permission to delete contacts.
         *     @type string 'fcrm_manage_contacts_export'     Permission to export contacts.
         *     @type string 'fcrm_manage_contact_cats'        Permission to manage contact categories.
         *     @type string 'fcrm_manage_contact_cats_delete' Permission to delete contact categories.
         *     @type string 'fcrm_read_emails'                Permission to read emails.
         *     @type string 'fcrm_manage_emails'              Permission to manage emails.
         *     @type string 'fcrm_manage_email_delete'        Permission to delete emails.
         *     @type string 'fcrm_manage_email_templates'     Permission to manage email templates.
         *     @type string 'fcrm_manage_forms'               Permission to manage forms.
         *     @type string 'fcrm_read_funnels'               Permission to read funnels.
         *     @type string 'fcrm_write_funnels'              Permission to write funnels.
         *     @type string 'fcrm_delete_funnels'             Permission to delete funnels.
         *     @type string 'fcrm_manage_settings'            Permission to manage settings.
         * }
         */
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

        /**
         * Filter the user permissions in FluentCRM.
         *
         * This filter allows modification of the user permissions array.
         *
         * @since 2.7.0
         *
         * @param array  $permissions The current permissions array.
         * @param object $user        The current user object.
         */
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
            /**
             * Filter whether the current admin has the specified permission.
             *
             * This filter allows you to modify the permission check for the current admin.
             *
             * @since 2.8.30
             *
             * @param bool   Whether the current admin has the specified permission. Default true.
             * @param string $permission The permission to check.
             */
            return apply_filters('fluentcrm_current_admin_can', true, $permission);
        }

        if (defined('FLUENTCAMPAIGN')) {
            return current_user_can($permission);
        }

        return false;
    }
}
