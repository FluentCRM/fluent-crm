<?php

namespace FluentCrmMigrations;

class Subscribers
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

        $table = $wpdb->prefix .'fc_subscribers';

        $indexPrefix = $wpdb->prefix .'fc_index_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `user_id` BIGINT UNSIGNED NULL,
                `hash` VARCHAR(90) NULL,
                `contact_owner` BIGINT UNSIGNED NULL,
                `company_id` BIGINT UNSIGNED NULL,
                `prefix` VARCHAR(192) NULL,
                `first_name` VARCHAR(192) NULL,
                `last_name` VARCHAR(192) NULL,
                `email` VARCHAR(190) NOT NULL UNIQUE,
                `timezone` VARCHAR(192) NULL,
                `address_line_1` VARCHAR(192) NULL,
                `address_line_2` VARCHAR(192) NULL,
                `postal_code` VARCHAR(192) NULL,
                `city` VARCHAR(192) NULL,
                `state` VARCHAR(192) NULL,
                `country` VARCHAR(192) NULL,
                `ip` VARCHAR(20) NULL,
                `latitude` DECIMAL(10, 8) NULL,
                `longitude` DECIMAL(10, 8) NULL,
                `total_points` INT UNSIGNED NOT NULL DEFAULT 0,
                `life_time_value` INT UNSIGNED NOT NULL DEFAULT 0,
                `phone` VARCHAR(50) NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'subscribed', /*subscribed, unsubscribed, pending, bounced*/
                `contact_type` VARCHAR(50) DEFAULT 'lead', /*lead, customer*/
                `source` VARCHAR(50) NULL,
                `avatar` VARCHAR(192) NULL,
                `date_of_birth` DATE NULL,
                `created_at` TIMESTAMP NULL,
                `last_activity` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                 INDEX `{$indexPrefix}_subscriber_user_id_idx` (`user_id` ASC),
                 INDEX `{$indexPrefix}_subscriber_status_idx` (`status` ASC)
            ) $charsetCollate;";

            dbDelta($sql);
        }
    }
}
