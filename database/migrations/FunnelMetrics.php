<?php

namespace FluentCrmMigrations;

class FunnelMetrics
{
    /**
     * Migrate the table.
     *
     * @param bool $isForced
     * @return void
     */
    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'fc_funnel_metrics';

        $indexPrefix = $wpdb->prefix . 'fc_fmx_';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `funnel_id` BIGINT UNSIGNED NULL,
                `sequence_id` BIGINT UNSIGNED NULL,
                `subscriber_id` BIGINT UNSIGNED NULL,
                `benchmark_value` BIGINT UNSIGNED DEFAULT 0,
                `benchmark_currency` VARCHAR(10) DEFAULT 'USD',
                `status` VARCHAR(50) DEFAULT 'completed',
                `notes` TEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `{$indexPrefix}_m_idx` (`funnel_id` ASC),
                INDEX `{$indexPrefix}_ms__idx` (`subscriber_id` ASC)
            ) $charsetCollate;";
            dbDelta($sql);
        }
    }
}
