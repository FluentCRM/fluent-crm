<?php

namespace FluentCrmMigrations;

class CompaniesMigrator
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

        $table = $wpdb->prefix .'fc_companies';

        $indexPrefix = $wpdb->prefix .'fc_index_';

        if(!function_exists('dbDelta')) {
            require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `hash` VARCHAR(90) NULL,
                `owner_id` BIGINT UNSIGNED NULL,
                `name` VARCHAR(192) NULL,
                `industry` VARCHAR(192) NULL,
                `email` VARCHAR(190) NULL,
                `timezone` VARCHAR(192) NULL,
                `address_line_1` VARCHAR(192) NULL,
                `address_line_2` VARCHAR(192) NULL,
                `postal_code` VARCHAR(192) NULL,
                `city` VARCHAR(192) NULL,
                `state` VARCHAR(192) NULL,
                `country` VARCHAR(192) NULL,
                `employees_number` INT UNSIGNED NULL DEFAULT 0,
                `description` LONGTEXT NULL,
                `phone` VARCHAR(50) NULL,
                `type` VARCHAR(50) DEFAULT '', /*active, archived*/
                `logo` VARCHAR(192) NULL,
                `website` VARCHAR(192) NULL,
                `linkedin_url` VARCHAR(192) NULL,
                `facebook_url` VARCHAR(192) NULL,
                `twitter_url` VARCHAR(192) NULL,
                `date_of_start` DATE NULL,
                `meta` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                 INDEX `{$indexPrefix}_com_owner_id_idx` (`owner_id`),
                 INDEX `{$indexPrefix}_com_industry_idx` (`industry`),
                 INDEX `{$indexPrefix}_com_type_idx` (`type`),
                 INDEX `{$indexPrefix}_com_name_idx` (`name`)
            ) $charsetCollate;";
            dbDelta($sql);
        } else {
            // check if meta is available in the column
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table", ARRAY_A);
            $allColumns = array_column($columns, 'Field');

            if (!in_array('meta', $allColumns)) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN `meta` LONGTEXT NULL AFTER `linkedin_url`");
            }

            if (!in_array('date_of_start', $allColumns)) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN `date_of_start` DATE NULL AFTER `linkedin_url`");
            }

            if (!in_array('facebook_url', $allColumns)) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN `facebook_url` VARCHAR(192) NULL AFTER `linkedin_url`");
            }

            if(!in_array('twitter_url', $allColumns)) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN `twitter_url` VARCHAR(192) NULL AFTER `linkedin_url`");
            }

        }
    }
}
