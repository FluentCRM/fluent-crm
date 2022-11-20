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

        $indexPrefix = $wpdb->prefix .'fc_fn_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `type` VARCHAR(50) NOT NULL DEFAULT 'funnel',
                `title` VARCHAR(192) NOT NULL,
                `trigger_name` VARCHAR(150) NULL,
                `status` VARCHAR(50) NULL DEFAULT 'draft',
                `conditions` TEXT,
                `settings` TEXT,
                `created_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_f_idx` (`status` ASC),
                INDEX `{$indexPrefix}_ft_idx` (`trigger_name` ASC),
                KEY `type` (`type`)
            ) $charsetCollate;";
            dbDelta($sql);
        } else {
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table");
            $indexedColumns = [];
            foreach ($indexes as $index) {
                $indexedColumns[] = $index->Column_name;
            }

            if(!in_array('type', $indexedColumns)) {
                $wpdb->query("ALTER TABLE {$table} ADD INDEX `type` (`type`);");
            }
        }
    }
}
