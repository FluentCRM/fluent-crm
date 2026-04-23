<?php

namespace FluentCrmMigrations;

class SubscriberEventTracking
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

        $table = $wpdb->prefix . 'fc_event_tracking';

        $indexPrefix = $wpdb->prefix . 'fc_et_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `subscriber_id` BIGINT UNSIGNED NOT NULL,
                `counter` INT(11) UNSIGNED NULL DEFAULT 1,
                `created_by` BIGINT UNSIGNED NULL,
                `provider` VARCHAR(50) DEFAULT 'custom',
                `event_key` VARCHAR(192) NOT NULL,
                `title` VARCHAR(192) NOT NULL,
                `value` TEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_s_id_idx` (`subscriber_id`),
                INDEX `{$indexPrefix}_s_idx` (`event_key`),
                INDEX `{$indexPrefix}_s_idx_title` (`title`)
            ) $charsetCollate;";

            if(!function_exists('dbDelta')) {
                require_once(ABSPATH.'wp-admin/includes/upgrade.php');
            }

            dbDelta($sql);
        }
    }
}
