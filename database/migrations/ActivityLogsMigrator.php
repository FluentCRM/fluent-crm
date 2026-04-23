<?php

namespace FluentCrmMigrations;

class ActivityLogsMigrator
{
    /**
     * Migrate the table.
     *
     * @return void
     */
    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'fc_activity_logs';

        $indexPrefix = $wpdb->prefix . 'fc_al_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `object_type` VARCHAR(192) NOT NULL,
                `object_id` BIGINT NULL,
                `action` VARCHAR(192) NOT NULL,
                `source` VARCHAR(50) DEFAULT 'wp_admin',
                `description` TEXT NULL,
                `activity_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_u_action_idx` (`action`),
                INDEX `{$indexPrefix}_u_src_idx` (`source`)
            ) $charsetCollate;";

            if(!function_exists('dbDelta')) {
                require_once(ABSPATH.'wp-admin/includes/upgrade.php');
            }

            dbDelta($sql);
        }
    }
}
