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
        $indexPrefix = $wpdb->prefix .'fc_index_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `object_type` VARCHAR(255) NOT NULL,
                `object_id` BIGINT NULL,
                `key` VARCHAR(255) NOT NULL,
                `value` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                 INDEX `{$indexPrefix}_meta_object_type_idx` (`object_type` ASC),
                 INDEX `{$indexPrefix}_meta_object_id_idx` (`object_id` ASC)
            ) $charsetCollate;";

            dbDelta($sql);
        }
    }
}
