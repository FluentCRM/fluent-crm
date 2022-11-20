<?php

namespace FluentCrmMigrations;

class SubscriberMeta
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

        $table = $wpdb->prefix .'fc_subscriber_meta';

        $indexPrefix = $wpdb->prefix .'fc_index_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `subscriber_id` BIGINT UNSIGNED NOT NULL,
                `created_by` BIGINT UNSIGNED NOT NULL,
                `object_type` VARCHAR(50) DEFAULT 'option',
                `key` VARCHAR(192) NOT NULL,
                `value` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_s_meta_id_idx` (`subscriber_id` ASC),
                INDEX `{$indexPrefix}_s_ot_idx` (`object_type` ASC)
            ) $charsetCollate;";

            dbDelta($sql);
        } else {

            $indexes = $wpdb->get_results("SHOW INDEX FROM $table");
            $indexedColumns = [];
            foreach ($indexes as $index) {
                $indexedColumns[] = $index->Column_name;
            }

            if(!in_array('object_type', $indexedColumns)) {
                $sql = "ALTER TABLE {$table} ADD INDEX `object_type` (`object_type`);";
                $wpdb->query($sql);
            }
        }
    }
}
