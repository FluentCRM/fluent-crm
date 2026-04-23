<?php

namespace FluentCrmMigrations;

class TermRelations
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

        $table = $wpdb->prefix .'fc_term_relations';
        $indexPrefix = $wpdb->prefix .'fc_tmr_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `term_id` BIGINT UNSIGNED NULL,
                `object_type` VARCHAR(192) NOT NULL,
                `object_id` BIGINT NULL,
                `settings` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                 INDEX `{$indexPrefix}_tm_idx` (`term_id` ASC),
                 INDEX `{$indexPrefix}_tm_id_type` (`object_type` ASC),
                 INDEX `{$indexPrefix}_tm_id_idx` (`object_id`)
            ) $charsetCollate;";
            dbDelta($sql);
        }
    }
}
