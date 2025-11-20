<?php

namespace FluentCrmMigrations;

class Tags
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

        $table = $wpdb->prefix .'fc_tags';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = "CREATE TABLE $table (
                `id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `title` VARCHAR(192) NOT NULL,
                `slug` VARCHAR(192) NOT NULL,
                `description` TINYTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL
            ) $charsetCollate;";

            dbDelta($sql);
        }
    }
}
