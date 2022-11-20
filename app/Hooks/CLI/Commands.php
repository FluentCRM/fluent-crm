<?php

namespace FluentCrm\App\Hooks\CLI;

use FluentCampaign\App\Services\Commerce\Commerce;
use FluentCampaign\App\Services\Commerce\ContactRelationItemsModel;
use FluentCampaign\App\Services\Commerce\ContactRelationModel;
use FluentCampaign\App\Services\Integrations\Edd\EddCommerceHelper;
use FluentCrm\App\Hooks\Handlers\ActivationHandler;
use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Tag;

class Commands
{
    public function stats($args, $assoc_args)
    {
        $overallStats = [
            [
                'title' => __('All Contacts', 'fluent-crm'),
                'count' => Subscriber::count(),
            ],
            [
                'title' => __('Subscribers', 'fluent-crm'),
                'count' => Subscriber::where('status', 'subscribed')->count(),
            ],
            [
                'title' => __('Campaigns', 'fluent-crm'),
                'count' => Campaign::count(),
            ],
            [
                'title' => __('Automations', 'fluent-crm'),
                'count' => Funnel::count(),
            ],
            [
                'title' => __('All Emails', 'fluent-crm'),
                'count' => CampaignEmail::count(),
            ],
            [
                'title' => __('Send Emails', 'fluent-crm'),
                'count' => CampaignEmail::where('status', 'sent')->count(),
            ],
            [
                'title' => __('Scheduled Emails', 'fluent-crm'),
                'count' => CampaignEmail::where('status', 'scheduled')->count(),
            ]
        ];

        $format = \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

        \WP_CLI\Utils\format_items(
            $format,
            $overallStats,
            ['title', 'count']
        );
    }

    public function sync_edd_customers($args, $assoc_args)
    {
        if (!class_exists('\Easy_Digital_Downloads')) {
            \WP_CLI::error('Easy Digital Downloads is not installed');
        }

        $tags = \WP_CLI\Utils\get_flag_value($assoc_args, 'tags', '');
        $lists = \WP_CLI\Utils\get_flag_value($assoc_args, 'lists', '');
        $contactStatus = \WP_CLI\Utils\get_flag_value($assoc_args, 'contact_status', 'subscribed');
        $fire_event = \WP_CLI\Utils\get_flag_value($assoc_args, 'event', 'no');

        if (!in_array($contactStatus, ['pending', 'subscribed'])) {
            \WP_CLI::error('Possible contact_status value: pending|subscribed');
        }

        $formattedTags = [];
        $formattedTagNames = [];

        if ($tags) {
            $tags = array_unique(array_filter(array_filter(explode(',', $tags), 'trim'), 'absint'));
            if ($tags) {
                $allTags = Tag::whereIn('id', $tags)->get();
                foreach ($allTags as $tag) {
                    $formattedTagNames[] = $tag->title . ' (' . $tag->id . ')';
                    $formattedTags[] = $tag->id;
                }
            }
        }

        $formattedListNames = [];
        $formattedLists = [];

        if (trim($lists)) {
            $lists = array_unique(array_filter(array_filter(explode(',', $lists), 'trim'), 'absint'));
            if (count($lists)) {
                $allLists = Lists::whereIn('id', $lists)->get();
                foreach ($allLists as $list) {
                    $formattedListNames[] = $list->title . ' (' . $list->id . ')';
                    $formattedLists[] = $list->id;
                }
            }
        }

        if (!defined('FLUENTCAMPAIGN')) {
            \WP_CLI::error('FluentCRM Pro is required');
        }

        $isMigrated = Commerce::isMigrated(true);

        if (!$isMigrated) {
            \WP_CLI::line('Migrating Initial Database');
            Commerce::migrate();
            \WP_CLI::line('Initial Database Migration done. Going to next step...');
        } else {
            \WP_CLI::line('Initial Database exist. Going to next step...');
            Commerce::resetModuleData('edd');
        }

        $customersTotal = fluentCrmDb()->table('edd_customers')->count();

        \WP_CLI\Utils\format_items(
            'table',
            [
                [
                    'status' => __('Completed', 'fluent-crm'),
                    'count'  => fluentCrmDb()->table('posts')->where('post_type', 'edd_payment')->where('post_status', 'publish')->count()
                ],
                [
                    'status' => __('Processing', 'fluent-crm'),
                    'count'  => fluentCrmDb()->table('posts')->where('post_type', 'edd_payment')->where('post_status', 'processing')->count()
                ],
                [
                    'status' => __('Subscription Payments', 'fluent-crm'),
                    'count'  => fluentCrmDb()->table('posts')->where('post_type', 'edd_payment')->where('post_status', 'edd_subscription')->count()
                ],
                [
                    'status' => __('Customer Counts', 'fluent-crm'),
                    'count'  => $customersTotal
                ]
            ],
            ['status', 'count']
        );

        \WP_CLI::line('The following Tags, Lists & Status will be applied to the customers:');
        \WP_CLI\Utils\format_items('yaml', [
            [
                'type'  => __('Tags', 'fluent-crm'),
                'Value' => $formattedTagNames
            ],
            [
                'type'  => __('Lists', 'fluent-crm'),
                'Value' => $formattedListNames
            ],
            [
                'type'  => __('Status', 'fluent-crm'),
                'Value' => $contactStatus
            ]
        ], ['type', 'Value']);

        if ($contactStatus == 'pending') {
            \WP_CLI::line('---');
            \WP_CLI::line('A Double optin email will be sent to contacts who does not have subscribed status');
        }

        \WP_CLI::confirm('Do you want to continue?');

        \WP_CLI::line('Nice! Starting data syncing');

        $limit = 30;
        $offset = 0;
        $processingStatus = true;

        if ($fire_event == 'no') {
            if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
                define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
            }
        }

        $skippedContacts = [];
        $resultItems = [];
        $progress = \WP_CLI\Utils\make_progress_bar('Synced Customers', $customersTotal);

        while ($processingStatus) {
            $customers = fluentCrmDb()->table('edd_customers')
                ->limit($limit)
                ->offset($offset)
                ->get();

            $offset += $limit;

            if (!$customers) {
                $processingStatus = false;
            } else {
                foreach ($customers as $customer) {
                    $result = \FluentCampaign\App\Services\Integrations\Edd\EddCommerceHelper::syncCommerceCustomer($customer, $contactStatus, ['edd_subscription', 'processing', 'publish'], $formattedTags, $formattedLists);
                    if ($result) {
                        $progress->tick();
                        $resultItems[] = [
                            'contact_id'     => $result['relation']->subscriber_id,
                            'email'          => $result['subscriber']->email,
                            'status'         => $result['subscriber']->status,
                            'lifetime_value' => $result['relation']->total_order_value,
                            'order_count'    => $result['relation']->total_order_count,
                        ];
                    } else {
                        $skippedContacts[] = [
                            'name'  => $customer->name,
                            'email' => $customer->email,
                            'value' => number_format($customer->purchase_value, 2, '.', '')
                        ];
                    }
                }
            }
        }

        Commerce::enableModule('edd');
        Commerce::cacheStoreAverage('edd');

        $relationCount = ContactRelationModel::provider('edd')->count();
        \WP_CLI::line(sprintf('Awesome! %d customers has been synced', $relationCount));

        if ($skippedContacts) {
            \WP_CLI::line(sprintf('%d contacts has been skipped', count($skippedContacts)));

            fwrite(STDOUT, 'Show Skipped contacts? yes/no' . ' ');
            $value = strtolower(trim(fgets(STDIN)));

            if ($value == 'yes') {
                \WP_CLI\Utils\format_items(
                    'table',
                    $skippedContacts,
                    ['name', 'email', 'value']
                );
            }
        }

        \WP_CLI::line('Nice. All Done');

    }

    public function disable_edd_sync()
    {
        $module = 'edd';
        Commerce::disableModule($module);
        ContactRelationModel::provider($module)->delete();
        ContactRelationItemsModel::provider($module)->delete();

        if(!ContactRelationModel::first()) {
            global $wpdb;
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}fc_contact_relations");
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}fc_contact_relation_items");
        }

        fluentcrm_update_option('_'.$module.'_customer_sync_count', 0);
        fluentcrm_delete_option('_'.$module.'_store_average');
        \WP_CLI::line('EDD Data with FluentCRM has been removed');
    }

    /*
     * Usage: wp fluent_crm sync_woo_customers --tags=TAG_IDS --lists=LISTS_IDS
     * tags and lists needs to be comma separated values
     * If you just want to sync just run:
     * wp fluent_crm sync_woo_customers
     */
    public function sync_woo_customers($args, $assoc_args)
    {
        if (!defined('WC_PLUGIN_FILE')) {
            \WP_CLI::error('WooCommerce is not installed');
        }

        $tags = \WP_CLI\Utils\get_flag_value($assoc_args, 'tags', '');
        $lists = \WP_CLI\Utils\get_flag_value($assoc_args, 'lists', '');
        $contactStatus = \WP_CLI\Utils\get_flag_value($assoc_args, 'contact_status', 'subscribed');
        $fire_event = \WP_CLI\Utils\get_flag_value($assoc_args, 'event', 'no');

        if (!in_array($contactStatus, ['pending', 'subscribed'])) {
            \WP_CLI::error('Possible contact_status value: pending|subscribed');
        }

        $formattedTags = [];
        $formattedLists = [];

        $formattedTagNames = [];
        $formattedListNames = [];

        if ($tags) {
            $tags = array_unique(array_filter(array_filter(explode(',', $tags), 'trim'), 'absint'));
            if ($tags) {
                $allTags = Tag::whereIn('id', $tags)->get();
                foreach ($allTags as $tag) {
                    $formattedTagNames[] = $tag->title . ' (' . $tag->id . ')';
                    $formattedTags[] = $tag->id;
                }
            }
        }

        if ($lists) {
            $lists = array_unique(array_filter(array_filter(explode(',', $lists), 'trim'), 'absint'));
            if ($lists) {
                $allLists = Lists::whereIn('id', $lists)->get();
                foreach ($allLists as $list) {
                    $formattedListNames[] = $list->title . ' (' . $list->id . ')';
                    $formattedLists[] = $list->id;
                }
            }
        }

        if (!defined('FLUENTCAMPAIGN')) {
            \WP_CLI::error('FluentCRM Pro is required for this command');
        }

        $isMigrated = Commerce::isMigrated(true);

        if (!$isMigrated) {
            \WP_CLI::line('Migrating Initial Database');
            Commerce::migrate();
            \WP_CLI::line('Initial Database Migration done. Going to next step...');
        } else {
            \WP_CLI::line('Initial Database exist. Going to next step...');
            Commerce::resetModuleData('woo');
        }

        $customersTotal = fluentCrmDb()->table('wc_customer_lookup')->count();

        $orderStats = [];

        $statuses = wc_get_is_paid_statuses();

        foreach ($statuses as $status) {
            $orderStats[] = [
                'status' => ucfirst($status),
                'count'  => fluentCrmDb()->table('posts')->where('post_type', 'shop_order	')->where('post_status', 'wc-' . $status)->count()
            ];
        }

        \WP_CLI\Utils\format_items('table', $orderStats, ['status', 'count']);

        \WP_CLI::line('The following Tags, Lists & Status will be applied to the customers:');
        \WP_CLI\Utils\format_items('yaml', [
            [
                'type'  => __('Tags', 'fluent-crm'),
                'Value' => $formattedTagNames
            ],
            [
                'type'  => __('Lists', 'fluent-crm'),
                'Value' => $formattedListNames
            ],
            [
                'type'  => __('Status', 'fluent-crm'),
                'Value' => $contactStatus
            ]
        ], ['type', 'Value']);

        if ($contactStatus == 'pending') {
            \WP_CLI::line('---');
            \WP_CLI::line('A Double optin email will be sent to contacts who does not have subscribed status');
        }

        \WP_CLI::confirm('Do you want to continue?');

        \WP_CLI::line('Nice! Starting data syncing');

        $limit = 10;
        $offset = 0;
        $processingStatus = true;

        if ($fire_event == 'no') {
            if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
                define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
            }
        }

        Commerce::resetModuleData('woo');

        $skippedContacts = [];
        $resultItems = [];
        $progress = \WP_CLI\Utils\make_progress_bar('Synced Customers', $customersTotal);

        $processedOrdersCount = 0;
        while ($processingStatus) {
            $customers = fluentCrmDb()->table('wc_customer_lookup')
                ->orderBy('customer_id', 'ASC')
                ->limit($limit)
                ->offset($offset)
                ->get();

            $offset += 10;

            if (!$customers) {
                $processingStatus = false;
            } else {
                foreach ($customers as $customer) {
                    $result = \FluentCampaign\App\Services\Integrations\WooCommerce\WooSyncHelper::syncCommerceCustomer($customer, $contactStatus, $statuses, $formattedTags, $formattedLists);
                    if ($result) {
                        $processedOrdersCount += $result['orders_count'];
                        $progress->tick();
                        $resultItems[] = [
                            'contact_id'     => $result['relation']->subscriber_id,
                            'email'          => $result['subscriber']->email,
                            'status'         => $result['subscriber']->status,
                            'lifetime_value' => $result['relation']->total_order_value,
                            'order_count'    => $result['relation']->total_order_count,
                        ];
                    } else {
                        $skippedContacts[] = [
                            'name'  => $customer->first_name . ' ' . $customer->last_name,
                            'email' => $customer->email
                        ];
                    }
                }
            }
        }

        Commerce::enableModule('woo');
        Commerce::cacheStoreAverage('woo');

        $relationCount = ContactRelationModel::provider('woo')->count();
        \WP_CLI::line(sprintf('Awesome! %d customers has been synced', $relationCount));
        \WP_CLI::line(sprintf('%d orders has been synced', $processedOrdersCount));

        if ($skippedContacts) {
            \WP_CLI::line(sprintf('%d contacts has been skipped', count($skippedContacts)));

            fwrite(STDOUT, 'Show Skipped contacts? yes/no' . ': ');
            $value = strtolower(trim(fgets(STDIN)));

            if ($value == 'yes') {
                \WP_CLI\Utils\format_items(
                    'table',
                    $skippedContacts,
                    ['name', 'email']
                );
            }
        }

        \WP_CLI::line('Nice. All Done');
    }

    public function disable_woo_sync()
    {
        $module = 'woo';
        Commerce::disableModule($module);
        ContactRelationModel::provider($module)->delete();
        ContactRelationItemsModel::provider($module)->delete();

        if(!ContactRelationModel::first()) {
            global $wpdb;
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}fc_contact_relations");
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}fc_contact_relation_items");
        }

        fluentcrm_update_option('_'.$module.'_customer_sync_count', 0);
        fluentcrm_delete_option('_'.$module.'_store_average');
        \WP_CLI::line('WooCommerce Data with FluentCRM has been removed');
    }

    public function edd_stats($args, $assoc_args)
    {

        $isHelp = $type = \WP_CLI\Utils\get_flag_value($assoc_args, 'commands', '');

        if (!class_exists('\Easy_Digital_Downloads')) {
            \WP_CLI::error('Easy Digital Downloads is not installed');
        }

        if (!defined('FLUENTCAMPAIGN')) {
            \WP_CLI::error('FluentCRM Pro is required');
        }

        if (!Commerce::isMigrated()) {
            \WP_CLI::error('Data is not migrated yet. Please sync the data first');
        }

        $type = \WP_CLI\Utils\get_flag_value($assoc_args, 'type', '');
        $productId = \WP_CLI\Utils\get_flag_value($assoc_args, 'product_id', '');
        $period = \WP_CLI\Utils\get_flag_value($assoc_args, 'period', '');

        if ($type == 'overall') {
            $items = EddCommerceHelper::stats();
            \WP_CLI\Utils\format_items(
                'table',
                $items,
                ['type', 'amount']
            );
        } else if ($type == 'products' && !$productId) {
            $productItems = EddCommerceHelper::productsStats($period);
            \WP_CLI\Utils\format_items(
                'table',
                $productItems,
                ['id', 'name', 'formatted_sales', 'percent']
            );
        } else if ($type == 'products' && $productId) {
            if ($productId == 'all') {
                $uniqueProducts = ContactRelationItemsModel::provider('edd')->groupBy('item_id')
                    ->select('item_id')
                    ->get();

                foreach ($uniqueProducts as $uniqueProduct) {
                    $this->showEddProductReport($uniqueProduct->item_id, $period);
                }
            } else {
                $this->showEddProductReport($productId, $period);
            }
        } else if ($type == 'license_stats') {
            $items = EddCommerceHelper::getLicenseStats();
            \WP_CLI\Utils\format_items(
                'table',
                $items,
                ['label', 'count']
            );
        } else if ($type == 'license_sites') {
            $items = EddCommerceHelper::getLicenseActivations();

            \WP_CLI\Utils\format_items(
                'table',
                $items,
                ['label', 'activated_sites']
            );

        } else {
            \WP_CLI::line('Possible Commands:');
            \WP_CLI::line('--type=overall');
            \WP_CLI::line('--type=products');
            \WP_CLI::line('--type=products --product_id=PRODUCT_ID|all');
            \WP_CLI::line('--type=license_stats');
            \WP_CLI::line('--type=license_sites');
        }
    }

    private function showEddProductReport($productId, $period = '')
    {
        if (!$productId || !is_numeric($productId)) {
            \WP_CLI::error('--product_id=PRODUCT_ID (int) parameter is required');
        }

        $stats = EddCommerceHelper::productStat($productId, $period);

        $post = get_post($productId);
        \WP_CLI::line('-----');
        \WP_CLI::line(sprintf('Stats for %s', $post->post_title));

        \WP_CLI\Utils\format_items(
            'table',
            $stats,
            ['name', 'formatted_sales', 'percent', 'count', 'type']
        );
    }

    public function reset_db()
    {

        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            \WP_CLI::error('WP_DEBUG must be enabled');
            return;
        }

        \WP_CLI::confirm('Do you really want to remove all the contacts and related data?');

        fwrite(STDOUT, 'Please Type "yes" if you really want to do this? yes/no' . ': ');
        $value = strtolower(trim(fgets(STDIN)));

        if ($value != 'yes') {
            return;
        }

        $tables = [
            'fc_campaign_emails',
            'fc_campaigns',
            'fc_campaign_url_metrics',
            'fc_funnel_metrics',
            'fc_funnels',
            'fc_funnel_sequences',
            'fc_funnel_subscribers',
            'fc_meta',
            'fc_subscriber_meta',
            'fc_subscriber_notes',
            'fc_subscriber_pivot',
            'fc_subscribers',
            'fc_url_stores',
        ];

        if (defined('FLUENTCAMPAIGN')) {
            $tables[] = 'fc_sequence_tracker';
            $tables[] = 'fc_contact_relations';
            $tables[] = 'fc_contact_relation_items';
        }


        global $wpdb;
        foreach ($tables as $table) {
            \WP_CLI::line('Droping Table: ' . $table);
            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . $table);
        }
        $options = [
            '_fluentcrm_commerce_modules'
        ];

        foreach ($options as $option) {
            delete_option($option);
        }

        // All tables are delete now let's run the migration
        (new ActivationHandler)->handle(false);

        if (defined('FLUENTCAMPAIGN_PLUGIN_URL')) {
            \FluentCampaign\App\Migration\Migrate::run(false);
        }

        \WP_CLI::line('All FluentCRM Database Tables have been truncated');
    }

    public function edd_add_ltd_tag($args, $assoc_args)
    {
        if (empty($assoc_args) || count($assoc_args) != 2) {
            \WP_CLI::line('use --product=productId --tag=tagID');
            return;
        }

        if (empty($assoc_args['product']) || empty($assoc_args['tag'])) {
            \WP_CLI::line('use --product=productId --tag=tagID');
            return;
        }

        $productId = $assoc_args['product'];
        $tagId = $assoc_args['tag'];

        if (empty($productId) || empty($tagId)) {
            \WP_CLI::line('use --product=productId --tag=tagID');
            return;
        }

        if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
            define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
        }

        $licenses = fluentCrmDb()->table('edd_licenses')
            ->select(['user_id'])
            ->where('download_id', $productId)
            ->where('expiration', 0)
            ->where('status', '!=', 'disabled')
            ->get();


        $tag = Tag::find($tagId);

        if (!$tag) {
            \WP_CLI::line('Provided tag could not be found');
            return;
        }

        if (!$licenses) {
            \WP_CLI::line('No users found');
            return;
        }

        \WP_CLI::confirm(count($licenses) . " users found. Are you sure to add tag to those users?", $assoc_args);


        $completedCount = 0;
        foreach ($licenses as $license) {
            if (!$license->user_id) {
                continue;
            }

            $userId = $license->user_id;

            $subscriber = FluentCrmApi('contacts')->getContactByUserRef($userId);

            if (!$subscriber) {
                \WP_CLI::line('No user found ' . $userId);
                continue;
            }
            $subscriber->attachTags([$tagId]);
            $completedCount++;
        }

        \WP_CLI::line('Total Done: ' . $completedCount);

    }

    public function edd_add_price_tag($args, $assoc_args)
    {
        if (empty($assoc_args) || count($assoc_args) != 3) {
            \WP_CLI::line('use --product=productId --price_id=PRICEID --tag=tagID');
            return;
        }

        if (empty($assoc_args['product']) || empty($assoc_args['tag']) || empty($assoc_args['price_id'])) {
            \WP_CLI::line('use --product=productId --tag=tagID');
            return;
        }

        $productId = $assoc_args['product'];
        $tagId = $assoc_args['tag'];
        $priceId = $assoc_args['price_id'];

        if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
            define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
        }

        $licenses = fluentCrmDb()->table('edd_licenses')
            ->select(['user_id'])
            ->where('download_id', $productId)
            ->where('price_id', $priceId)
            ->where('status', '!=', 'disabled')
            ->get();


        $tag = Tag::find($tagId);

        if (!$tag) {
            \WP_CLI::line('Provided tag could not be found');
            return;
        }

        if (!$licenses) {
            \WP_CLI::line('No users found');
            return;
        }

        \WP_CLI::confirm(count($licenses) . " users found. Are you sure to add tag to those users?", $assoc_args);


        $completedCount = 0;
        foreach ($licenses as $license) {
            if (!$license->user_id) {
                continue;
            }

            $userId = $license->user_id;

            $subscriber = FluentCrmApi('contacts')->getContactByUserRef($userId);

            if (!$subscriber) {
                \WP_CLI::line('No user found ' . $userId);
                continue;
            }
            $subscriber->attachTags([$tagId]);
            $completedCount++;
        }

        \WP_CLI::line('Total Done: ' . $completedCount);
    }

    /*
     * wp fluent_crm activate_license --key=YOUR_LICENSE_KEY
     */
    public function activate_license($args, $assoc_args)
    {
        if (empty($assoc_args['key'])) {
            \WP_CLI::line('use --key=LICENSE_KEY to activate the license');
            return;
        }

        $licenseKey = trim(sanitize_text_field($assoc_args['key']));

        if (!class_exists('\FluentCampaign\App\Services\PluginManager\LicenseManager')) {
            \WP_CLI::line('FluentCRM Pro is required');
            return;
        }

        \WP_CLI::line('Validating License, Please wait');

        $licenseManager = new \FluentCampaign\App\Services\PluginManager\LicenseManager();
        $response = $licenseManager->activateLicense($licenseKey);

        if (is_wp_error($response)) {
            \WP_CLI::error($response->get_error_message());
            return;
        }

        \WP_CLI::line('Your license key has been successfully updated');
        \WP_CLI::line('Your License Status: ' . $response['status']);
        \WP_CLI::line('Expire Date: ' . $response['expires']);
        return;
    }

    public function license_status()
    {

        if (!class_exists('\FluentCampaign\App\Services\PluginManager\LicenseManager')) {
            \WP_CLI::line('FluentCRM Pro is required');
            return;
        }

        \WP_CLI::line('Fetching License details, Please wait');

        $licenseManager = new \FluentCampaign\App\Services\PluginManager\LicenseManager();
        $licenseManager->verifyRemoteLicense(true);
        $response = $licenseManager->getLicenseDetails();

        \WP_CLI::line('Your License Status: ' . $response['status']);
        \WP_CLI::line('Expires: ' . $response['expires']);
        return;
    }
}
