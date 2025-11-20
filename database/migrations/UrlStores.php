<?php

namespace FluentCrmMigrations;

class UrlStores
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

        $table = $wpdb->prefix .'fc_url_stores';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `url` TEXT NOT NULL,
                `short` VARCHAR(50) NOT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                KEY `short` (`short`)
            ) $charsetCollate;";
            dbDelta($sql);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table");
            $indexedColumns = [];
            foreach ($indexes as $index) {
                $indexedColumns[] = $index->Column_name;
            }

            if(!in_array('short', $indexedColumns)) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql = "ALTER TABLE {$table} ADD INDEX `short` (`short`);";
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->query($sql);
            }

            // change column type from tinytext to text - for already installed sites
            $column_name = 'url';
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $dataType = $wpdb->get_row("describe {$table} {$column_name}");
            if($dataType->Type == 'tinytext') {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql = "ALTER TABLE {$table} MODIFY {$column_name} TEXT NOT NULL;";
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->query($sql);
            }
        }
    }
}
