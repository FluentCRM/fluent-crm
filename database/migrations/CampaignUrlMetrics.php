<?php

namespace FluentCrmMigrations;

class CampaignUrlMetrics
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

        $table = $wpdb->prefix .'fc_campaign_url_metrics';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `url_id` BIGINT UNSIGNED NULL,
                `campaign_id` BIGINT UNSIGNED NULL,
                `subscriber_id` BIGINT UNSIGNED NULL,
                `type` VARCHAR(50) NULL DEFAULT 'click',  /*view/click*/
                `ip_address` VARCHAR(30) NULL,
                `country` VARCHAR(40) NULL,
                `city` VARCHAR(40) NULL,
                `counter` INT UNSIGNED NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                KEY `url_id` (`url_id`),
                KEY `campaign_id` (`campaign_id`),
                KEY `subscriber_id` (`subscriber_id`),
                KEY `type` (`type`)
            ) $charsetCollate;";

            dbDelta($sql);
        } else {

            $indexes = $wpdb->get_results("SHOW INDEX FROM $table");
            $indexedColumns = [];
            foreach ($indexes as $index) {
                $indexedColumns[] = $index->Column_name;
            }

            if(!in_array('subscriber_id', $indexedColumns)) {
                $indexSql = "ALTER TABLE {$table} ADD INDEX `url_id` (`url_id`),
                        ADD INDEX `campaign_id` (`campaign_id`),
                        ADD INDEX `subscriber_id` (`subscriber_id`),
                        ADD INDEX `type` (`type`);";

                $wpdb->query($indexSql);
            }
        }
    }
}
