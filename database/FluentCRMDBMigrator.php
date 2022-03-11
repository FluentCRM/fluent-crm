<?php

require_once(ABSPATH.'wp-admin/includes/upgrade.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/Subscribers.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/SubscriberMeta.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/SubscriberPivot.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/Campaigns.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/CampaignEmails.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/CampaignUrlMetrics.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/Lists.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/Tags.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/Meta.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/SubscriberNotes.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/UrlStores.php');

require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/Funnels.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/FunnelSequences.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/FunnelSubscribers.php');
require_once(FLUENTCRM_PLUGIN_PATH.'database/migrations/FunnelMetrics.php');


class FluentCRMDBMigrator
{
    public static function run($network_wide = false)
    {
        global $wpdb;
        if ( $network_wide ) {
            // Retrieve all site IDs from this network (WordPress >= 4.6 provides easy to use functions for that).
            if ( function_exists( 'get_sites' ) && function_exists( 'get_current_network_id' ) ) {
                $site_ids = get_sites( array( 'fields' => 'ids', 'network_id' => get_current_network_id() ) );
            } else {
                $site_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = $wpdb->siteid;" );
            }
            // Install the plugin for all these sites.
            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::migrate();
                restore_current_blog();
            }
        }  else {
            self::migrate();
        }
    }

    public static function migrate()
    {
        \FluentCrmMigrations\Subscribers::migrate();
        \FluentCrmMigrations\SubscriberMeta::migrate();
        \FluentCrmMigrations\SubscriberPivot::migrate();
        \FluentCrmMigrations\Campaigns::migrate();
        \FluentCrmMigrations\CampaignEmails::migrate();
        \FluentCrmMigrations\CampaignUrlMetrics::migrate();
        \FluentCrmMigrations\Lists::migrate();
        \FluentCrmMigrations\Tags::migrate();
        \FluentCrmMigrations\Meta::migrate();
        \FluentCrmMigrations\SubscriberNotes::migrate();
        \FluentCrmMigrations\UrlStores::migrate();

        \FluentCrmMigrations\Funnels::migrate();
        \FluentCrmMigrations\FunnelSequences::migrate();
        \FluentCrmMigrations\FunnelSubscribers::migrate();
        \FluentCrmMigrations\FunnelMetrics::migrate();
    }
}

if (!isset($network_wide)) {
    $network_wide = false;
}

FluentCRMDBMigrator::run($network_wide);
