<?php

namespace FluentCrmMigrations;

class CampaignEmails
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

        $table = $wpdb->prefix . 'fc_campaign_emails';

        $indexPrefix = $wpdb->prefix . 'fc_index_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `campaign_id` BIGINT UNSIGNED NULL,
                `email_type` VARCHAR(255) NULL DEFAULT 'campaign',
                `subscriber_id` BIGINT UNSIGNED NULL, /*Nullable because ondelete set null*/
                `email_subject_id` BIGINT UNSIGNED NULL, /*FK subjects.id*/
                `email_address` VARCHAR(255) NOT NULL,
                `email_subject` VARCHAR(255) NULL,
                `email_body` LONGTEXT NULL,
                `email_headers` TEXT NULL,
                `is_open` TINYINT(1) NOT NULL DEFAULT 0,
                `is_parsed` TINYINT(1) NOT NULL DEFAULT 0,
                `click_counter` INT NULL,
                `status` VARCHAR(255) NOT NULL DEFAULT 'draft', /*sent, scheduled, pending, bounced,failed*/
                `note` TEXT NULL, /* To keep the failed message */
                `scheduled_at` TIMESTAMP NULL, /*for scheduled email (check status)*/
                `email_hash` VARCHAR(255) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_campaign_id_idx` (`campaign_id` DESC),
                INDEX `{$indexPrefix}_subscriber_id_idx` (`subscriber_id` DESC),
                INDEX `{$indexPrefix}_email_type_idx` (`email_type` ASC),
                INDEX `{$indexPrefix}_email_status_idx` (`status` ASC)
            ) $charsetCollate;";

            dbDelta($sql);
        }
    }
}