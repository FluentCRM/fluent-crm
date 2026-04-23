<?php

namespace FluentCrmMigrations;

class Terms
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

        $table = $wpdb->prefix .'fc_terms';
        $indexPrefix = $wpdb->prefix .'fc_tms_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `parent_id` BIGINT UNSIGNED NULL,
                `taxonomy_name` VARCHAR(50) NOT NULL,
                `slug` VARCHAR(100) NOT NULL,
                `title` TEXT NULL,
                `position` DECIMAL(10,2) DEFAULT 1.00 NOT NULL,
                `description` LONGTEXT NULL,
                `settings` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                 INDEX `{$indexPrefix}_tm_idx` (`taxonomy_name` ASC),
                 INDEX `{$indexPrefix}_tm_id_slug` (`slug` ASC),
                 INDEX `{$indexPrefix}_tm_id_pid` (`parent_id` )
            ) $charsetCollate;";
            dbDelta($sql);
        }
    }
}
