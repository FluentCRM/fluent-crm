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

        $subscriberTable = $wpdb->prefix .'fc_subscribers';

        $indexPrefix = $wpdb->prefix .'fc_index_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `subscriber_id` BIGINT UNSIGNED NOT NULL,
                `parent_id` BIGINT UNSIGNED NULL,
                `created_by` BIGINT UNSIGNED NULL,
                `status` VARCHAR(255) DEFAULT 'open',
                `type` VARCHAR(255) DEFAULT 'note',
                `is_private` TINYINT DEFAULT 1,
                `title` VARCHAR(255) NULL,
                `description` tinytext NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_subscriber_note_id_idx` (`subscriber_id` DESC),
                INDEX `{$indexPrefix}_subscriber_id_idx` (`status` DESC)
            ) $charsetCollate;";

            dbDelta($sql);
        }
    }
}
