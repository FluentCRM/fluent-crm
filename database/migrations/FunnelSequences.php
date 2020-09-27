<?php

namespace FluentCrmMigrations;

class FunnelSequences
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

        $table = $wpdb->prefix . 'fc_funnel_sequences';

        $indexPrefix = $wpdb->prefix . 'fc_index_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `funnel_id` BIGINT UNSIGNED NULL,
                `action_name` VARCHAR(255) NULL,
                `type` VARCHAR(255) DEFAULT 'sequence',
                `title` VARCHAR(255) NULL,
                `description` VARCHAR(255) NULL,
                `status` VARCHAR(255) NULL DEFAULT 'draft',
                `conditions` TEXT,
                `settings` TEXT,
                `note` TEXT,
                `delay` INT UNSIGNED NULL,
                `c_delay` INT UNSIGNED NULL,
                `sequence` INT UNSIGNED NULL,
                `created_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_funnel_sequence_status_idx` (`status` ASC),
                INDEX `{$indexPrefix}_funnel_id_idx` (`funnel_id` ASC)
            ) $charsetCollate;";

            dbDelta($sql);
        }
    }
}
