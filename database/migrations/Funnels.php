<?php

namespace FluentCrmMigrations;

class Funnels
{
    /**
     * Migrate the table.
     *
     * @param bool $isForced
     * @return void
     */
    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix .'fc_funnels';

        $indexPrefix = $wpdb->prefix .'fc_index_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `type` VARCHAR(50) NOT NULL DEFAULT 'funnel',
                `title` VARCHAR(255) NOT NULL,
                `trigger_name` VARCHAR(255) NULL,
                `status` VARCHAR(255) NULL DEFAULT 'draft',
                `conditions` TEXT,
                `settings` TEXT,
                `created_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_funnel_status_idx` (`status` ASC),
                INDEX `{$indexPrefix}_funnel_trigger_idx` (`trigger_name` ASC)
            ) $charsetCollate;";
            dbDelta($sql);
        }
    }
}
