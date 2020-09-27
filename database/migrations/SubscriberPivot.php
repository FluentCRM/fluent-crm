<?php

namespace FluentCrmMigrations;

class SubscriberPivot
{

    /**
     * Migrate the table.
     *
     * This table will maintain many-to-many relationships
     * between subscriber & lists and subscriber & tags.
     *
     * @return void
     */
    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix .'fc_subscriber_pivot';

        $subscriberTable = $wpdb->prefix .'fc_subscribers';

        $indexPrefix = $wpdb->prefix .'fc_index_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `subscriber_id` BIGINT UNSIGNED NOT NULL,
                `object_id` BIGINT UNSIGNED NOT NULL, /*list_id or tag_id*/
                `object_type` VARCHAR(255) NOT NULL, /*list or tag*/
                `status` VARCHAR(255) NULL,
                `is_public` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_subscriber_pivot_id_idx` (`subscriber_id` ASC),
                INDEX `{$indexPrefix}_subscriber_pivot_object_id_idx` (`object_id` ASC),
                INDEX `{$indexPrefix}_subscriber_pivot_type_id_idx` (`object_type` ASC)
            ) $charsetCollate;";

            dbDelta($sql);
        }
    }
}
