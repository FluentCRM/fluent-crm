<?php

namespace FluentCrmMigrations;

class Campaigns
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

        $table = $wpdb->prefix .'fc_campaigns';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `parent_id` BIGINT UNSIGNED NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'campaign',
                `title` VARCHAR(255) NOT NULL,
                `available_urls` TEXT NULL,
                `slug` VARCHAR(255) NOT NULL,
                `status` VARCHAR(255) NOT NULL,
                `template_id` BIGINT(20) UNSIGNED NULL,
                `email_subject` VARCHAR(255),
                `email_pre_header` VARCHAR(255),
                `email_body` LONGTEXT NOT NULL,
                `recipients_count` INT NOT NULL DEFAULT 0,
                `delay` INT(11) NULL DEFAULT 0,
                `utm_status` TINYINT(1) NULL DEFAULT 0,
                `utm_source` VARCHAR(255) NULL,
                `utm_medium` VARCHAR(255) NULL,
                `utm_campaign` VARCHAR(255) NULL,
                `utm_term` VARCHAR(255) NULL,
                `utm_content` VARCHAR(255) NULL,
                `design_template` VARCHAR(255) NULL,
                `scheduled_at` TIMESTAMP NULL,
                `settings` LONGTEXT null,
                `created_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL
            ) $charsetCollate;";
            dbDelta($sql);
        }
    }
}
