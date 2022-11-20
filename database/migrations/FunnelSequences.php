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

        $indexPrefix = $wpdb->prefix . 'fc_fq_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `funnel_id` BIGINT UNSIGNED NULL,
                `parent_id` BIGINT UNSIGNED DEFAULT 0,
                `action_name` VARCHAR(192) NULL,
                `condition_type` VARCHAR(192) NULL,
                `type` VARCHAR(50) DEFAULT 'sequence',
                `title` VARCHAR(192) NULL,
                `description` VARCHAR(192) NULL,
                `status` VARCHAR(50) NULL DEFAULT 'draft',
                `conditions` TEXT,
                `settings` TEXT,
                `note` TEXT,
                `delay` INT UNSIGNED NULL,
                `c_delay` INT UNSIGNED NULL,
                `sequence` INT UNSIGNED NULL,
                `created_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_fs_idx` (`status` ASC),
                INDEX `{$indexPrefix}_fid_idx` (`funnel_id` ASC),
                KEY `c_delay` (`c_delay`),
                KEY `sequence` (`sequence`),
                KEY `action_name` (`action_name`)
            ) $charsetCollate;";

            dbDelta($sql);
        } else {
            $sequenceTable = $wpdb->prefix.'fc_funnel_sequences';
            $isMigrated = $wpdb->get_col($wpdb->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME='parent_id' AND TABLE_NAME=%s", $sequenceTable));
            if(!$isMigrated) {
                $wpdb->query("ALTER TABLE {$sequenceTable} ADD COLUMN `parent_id` bigint NOT NULL DEFAULT '0', ADD `condition_type` varchar(192) NULL AFTER `parent_id`");
            }

            $indexes = $wpdb->get_results("SHOW INDEX FROM $table");
            $indexedColumns = [];
            foreach ($indexes as $index) {
                $indexedColumns[] = $index->Column_name;
            }

            if(!in_array('action_name', $indexedColumns)) {
                $indexSql = "ALTER TABLE {$table} ADD INDEX `c_delay` (`c_delay`),
                        ADD INDEX `sequence` (`sequence`),
                        ADD INDEX `action_name` (`action_name`);";

                $wpdb->query($indexSql);
            }
        }
    }
}
