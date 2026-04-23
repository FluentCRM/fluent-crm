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

        $indexPrefix = $wpdb->prefix . 'fc_cam_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `campaign_id` BIGINT UNSIGNED NULL,
                `email_type` VARCHAR(50) NULL DEFAULT 'campaign',
                `subscriber_id` BIGINT UNSIGNED NULL, /*Nullable because ondelete set null*/
                `email_subject_id` BIGINT UNSIGNED NULL, /*FK subjects.id*/
                `email_address` VARCHAR(192) NOT NULL,
                `email_subject` VARCHAR(192) NULL,
                `email_body` LONGTEXT NULL,
                `email_headers` TEXT NULL,
                `is_open` TINYINT(1) NOT NULL DEFAULT 0,
                `is_parsed` TINYINT(1) NOT NULL DEFAULT 0,
                `click_counter` INT NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'draft', /*sent, scheduled, pending, bounced,failed*/
                `note` TEXT NULL, /* To keep the failed message */
                `scheduled_at` TIMESTAMP NULL, /*for scheduled email (check status)*/
                `email_hash` VARCHAR(192) NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_cid_idx` (`campaign_id` ASC),
                INDEX `{$indexPrefix}_sid_idx` (`subscriber_id` ASC),
                INDEX `{$indexPrefix}_et_idx` (`email_type` ASC),
                INDEX `{$indexPrefix}_estidx` (`status` ASC),
                INDEX `{$indexPrefix}_emtidx` (`email_hash` ASC),
                INDEX `{$indexPrefix}_scheduled_at` (`scheduled_at`),
                INDEX `{$indexPrefix}_updated_at` (`updated_at`),
                INDEX `{$indexPrefix}sc_at_status` (`scheduled_at`, `status`)
            ) $charsetCollate;";

            dbDelta($sql);
        } else {
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table");
            $indexedColumns = [];
            foreach ($indexes as $index) {
                $indexedColumns[] = $index->Column_name;
            }

            if(!in_array('scheduled_at', $indexedColumns)) {
                $wpdb->query("ALTER TABLE {$table} ADD INDEX `scheduled_at` (`scheduled_at`);");
            }

            if(!in_array('updated_at', $indexedColumns)) {
                $wpdb->query("ALTER TABLE {$table} ADD INDEX {$indexPrefix}_updated_at (`updated_at`);");
            }

            if(!in_array('email_hash', $indexedColumns)) {
                $wpdb->query("ALTER TABLE {$table} ADD INDEX `{$indexPrefix}_emtidx` (`email_hash`);");
            }
        }
    }
}
