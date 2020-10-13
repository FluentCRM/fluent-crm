<?php

namespace FluentCrmMigrations;

class FunnelSubscribers
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

        $table = $wpdb->prefix .'fc_funnel_subscribers';

        $indexPrefix = $wpdb->prefix .'fc_fsx_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `funnel_id` BIGINT UNSIGNED NULL,
                `starting_sequence_id` BIGINT UNSIGNED NULL,
                `next_sequence` BIGINT UNSIGNED NULL,
                `subscriber_id` BIGINT UNSIGNED NULL,
                `last_sequence_id` BIGINT UNSIGNED NULL,
                `next_sequence_id` BIGINT UNSIGNED NULL,
                `last_sequence_status` VARCHAR(50) DEFAULT 'pending',
                `status` VARCHAR(50) DEFAULT 'active',
                `type` VARCHAR(50) DEFAULT 'funnel',
                `last_executed_time` TIMESTAMP NULL,
                `next_execution_time` TIMESTAMP NULL,
                `notes` TEXT NULL,
                `source_trigger_name` VARCHAR(192) NULL,
                `source_ref_id` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_fidx` (`funnel_id` ASC),
                INDEX `{$indexPrefix}_fsq_idx` (`subscriber_id` ASC)
            ) $charsetCollate;";
            dbDelta($sql);
        }
    }
}
