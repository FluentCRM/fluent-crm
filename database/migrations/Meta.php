<?php

namespace FluentCrmMigrations;

class Meta
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

        $table = $wpdb->prefix .'fc_meta';
        $indexPrefix = $wpdb->prefix .'fc_mt_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `object_type` VARCHAR(50) NOT NULL,
                `object_id` BIGINT NULL,
                `key` VARCHAR(192) NOT NULL,
                `value` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                 INDEX `{$indexPrefix}_mt_idx` (`object_type` ASC),
                 INDEX `{$indexPrefix}_mto_id_idx` (`object_id` ASC)
            ) $charsetCollate;";

            dbDelta($sql);
        }
    }
}
