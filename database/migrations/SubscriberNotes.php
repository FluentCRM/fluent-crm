<?php

namespace FluentCrmMigrations;

class SubscriberNotes
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

        $table = $wpdb->prefix .'fc_subscriber_notes';

        $indexPrefix = $wpdb->prefix .'fc_sn_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `subscriber_id` BIGINT UNSIGNED NOT NULL,
                `parent_id` BIGINT UNSIGNED NULL,
                `created_by` BIGINT UNSIGNED NULL,
                `status` VARCHAR(50) DEFAULT 'open',
                `type` VARCHAR(50) DEFAULT 'note',
                `is_private` TINYINT DEFAULT 1,
                `title` VARCHAR(192) NULL,
                `description` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_s_id_idx` (`subscriber_id` ASC),
                INDEX `{$indexPrefix}_s_idx` (`status` ASC),
                KEY `type` (`type`)
            ) $charsetCollate;";

            dbDelta($sql);
        } else {
            $charsetCollate = $wpdb->collate;
            $sql = "ALTER TABLE {$table} CHANGE `description` `description` longtext COLLATE '".$charsetCollate."' NULL AFTER `title`;";
            $wpdb->query($sql);

            $indexes = $wpdb->get_results("SHOW INDEX FROM $table");
            $indexedColumns = [];
            foreach ($indexes as $index) {
                $indexedColumns[] = $index->Column_name;
            }

            if(!in_array('type', $indexedColumns)) {
                $indexSql = "ALTER TABLE `{$table}` ADD INDEX `type` (`type`);";
                $wpdb->query($indexSql);
            }
        }
    }
}
