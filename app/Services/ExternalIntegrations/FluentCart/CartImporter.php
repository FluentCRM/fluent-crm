<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentCart;

use Automattic\WooCommerce\Blocks\BlockTypes\Cart;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Tag;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Services\Helper;

class CartImporter
{
    // Customers processed per request (page size)
    const PER_PAGE = 150;
    // public function __construct()
    // {
    //     $this->importKey = 'fluent_cart';
    //     parent::__construct();
    // }


    private static function getPluginName()
    {
        return 'FluentCart';
    }

    // public function getInfo()
    // {
    //     return [
    //         'label'    => $this->getPluginName(),
    //         'logo'     => fluentCrmMix('images/woo.svg'),
    //         'disabled' => false
    //     ];
    // }

    public static function processUserDriver($config, $request)
    {
        $summary = $request->get('summary');

        if ($summary) {

            // Initialize defaults to avoid undefined variables in fallback returns
            $formattedCustomers = [];
            $total = 0;

            $config = $request->get('config');

            $type = Arr::get($config, 'import_type');

            if ($type == 'customers_sync') {
                // $customersQuery = fluentCrmDb()->table('wc_customer_lookup');
                // $total = $customersQuery->count();
                // $formattedUsers = $customersQuery->select(['first_name', 'last_name', 'email'])->limit(5)->get();

                // foreach ($formattedUsers as $formattedUser) {
                //     $formattedUser->name = trim($formattedUser->first_name . ' ' . $formattedUser->last_name);
                // }

            } else if ($type == 'product_tags') {
                $productIds = [];
                foreach ($config['product_type_maps'] as $map) {
                    $productIds[] = absint($map['field_key']);
                }

                // get customers who purchased these products from fluent cart 
                // get orderIds from 

                $customers = CartHelper::getCustomersByProductIds($productIds, 0, 100000);

                $total = count($customers);

                fluentcrm_update_option('_fluent_cart_product_tag_total_count', $total);


                $formattedCustomers = [];
                foreach ($customers as $customer) {
                    $formattedCustomers[] = [
                        'name'  => $customer->first_name . ' ' . $customer->last_name,
                        'email' => $customer->email
                    ];
                }

                return [
                    'import_info' => [
                        'subscribers'       => $formattedCustomers,
                        'total'             => $total,
                        'has_tag_config'    => false,
                        'has_list_config'   => true,
                        'has_status_config' => false,
                        'has_update_config' => false,
                        'has_silent_config' => true
                    ]
                ];
            }

            return [
                'import_info' => [
                    'subscribers'       => $formattedCustomers,
                    'total'             => $total,
                    'has_tag_config'    => true,
                    'has_list_config'   => true,
                    'has_status_config' => true,
                    'has_update_config' => false,
                    'has_silent_config' => true
                ]
            ];
        }

        $importType = 'customers_sync';

        $importTitle = sprintf(__('Sync %s Customers Now', 'fluent-crm'), self::getPluginName());

        if(defined('FLUENTCART_VERSION')) {
            $importType = 'product_tags';
            $importTitle = sprintf(__('Import %s Customers Now', 'fluent-crm'), self::getPluginName());
        }

        $configFields = [
            'config' => [
                'import_type'       => $importType,
                'product_type_maps' => [
                    [
                        'field_key'   => '',
                        'field_value' => ''
                    ]
                ]
            ],
            'fields' => [
                'product_type_maps' => [
                    'label'              => __('Please map your Product and associate FluentCRM Tags', 'fluentcampaign-pro'),
                    'type'               => 'form-many-drop-down-mapper',
                    'local_label'        => sprintf(__('Select %s Product', 'fluentcampaign-pro'), self::getPluginName()),
                    'remote_label'       => __('Select FluentCRM Tag that will be applied', 'fluentcampaign-pro'),
                    'local_placeholder'  => sprintf(__('Select %s Product', 'fluentcampaign-pro'), self::getPluginName()),
                    'remote_placeholder' => __('Select FluentCRM Tag', 'fluentcampaign-pro'),
                    'field_ajax_selector' => [
                        'option_key' => 'fluent_cart_products'
                    ],
                    'value_option_selector' => [
                        'option_key'   => 'tags',
                        'creatable' => true
                    ],
                    'dependency'         => [
                        'depends_on' => 'import_type',
                        'operator'   => '=',
                        'value'      => 'product_tags'
                    ]
                ],
                'sync_import_html'  => [
                    'type'       => 'html-viewer',
                    'heading'    => 'FluentCart Data Sync',
                    'info'       => __('You can sync all your FluentCart Customers into FluentCRM and all future customers and purchase data will be synced.', 'fluent-crm').'<br />'.__('After this sync you can import by product by product and provide appropriate tags', 'fluent-crm'),
                    'dependency' => [
                        'depends_on' => 'import_type',
                        'operator'   => '=',
                        'value'      => 'customers_sync'
                    ]
                ]
            ],
            'labels' => [
                'step_2' => __('Next [Review Data]', 'fluent-crm'),
                'step_3' => $importTitle
            ]
        ];

        return $configFields;
    }


    public static function importData($returnData, $config, $page)
    {
        $inputs = Arr::only($config, [
            'lists', 'tags', 'status', 'double_optin_email', 'import_silently'
        ]);

        $inputs = wp_parse_args($inputs, [
            'lists'              => [],
            'tags'               => [],
            // keep backward compatibility but use `status` key everywhere
            'status'             => 'subscribed',
            'double_optin_email' => 'no',
            'import_silently'    => 'yes'
        ]);

        if (Arr::get($inputs, 'import_silently') == 'yes') {
            if (!defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
                define('FLUENTCRM_DISABLE_TAG_LIST_EVENTS', true);
            }
        }

        $sendDoubleOptin = Arr::get($inputs, 'double_optin_email') == 'yes';
        $contactStatus = Arr::get($inputs, 'status', 'subscribed');

        $productTagMaps = [];

        $productIds = [];
        foreach ($config['product_type_maps'] as $map) {
            $productId = absint($map['field_key']);
            $productIds[] = $productId;
            if (!isset($productTagMaps[$productId])) {
                $productTagMaps[$productId] = [];
            }
            $productTagMaps[$productId][] = absint($map['field_value']);
        }

        $productIds = array_unique($productIds);


        $startTime = time();
        $runTime = 20; // seconds

        // normalize and initialize paging
        $perPage = self::PER_PAGE;
        $page = max(1, absint($page));

        // reset counters on first page to start a fresh run
        if ($page === 1) {
            fluentcrm_update_option('_fluent_cart_sync_count', 0);
            fluentcrm_update_option('_fluent_cart_import_current_page', 1);
        }

        $customers = CartHelper::getCustomersByProductIds($productIds, ($page - 1) * $perPage, $perPage);

        // Even if the current page has no customers (e.g., last sparse page),
        // advance the page pointer to avoid getting stuck reprocessing the same page.
        if (empty($customers)) {
            fluentcrm_update_option('_fluent_cart_import_current_page', $page);
            return self::getSyncStatus();
        }

        $importedCustomers = [];
        // pushing all customers without tags
        foreach ($customers as $customer) {
            $subscribers = CartHelper::prepareSubsciberData($customer);
            if($customer->user_id) {
                $subscribers = Helper::getWPMapUserInfo($customer->user_id);
            }
            Subscriber::import(
                [$subscribers],
                [],
                Arr::get($inputs, 'lists', []),
                true,
                $contactStatus,
                $sendDoubleOptin
            );

            // keeping track of imported customers
            $purchasedProducts = self::purchasedProductsOfImportedCustomer($customer->id, $productIds);
            $assignedTags = self::mapCustomerTags($purchasedProducts, $productTagMaps);

            $importedCustomers[] = [
                'customer_id' => $customer->id,
                'email' => $subscribers['email'],
                'user_id' => $customer->user_id,
                'purchased_products' => $purchasedProducts,
                'tags' => $assignedTags
            ];

            // update tags for the subscriber
            if ($assignedTags) {
                 $importedSubscriber = Subscriber::where('email', $subscribers['email'])->first();
                 if ($importedSubscriber) {
                     $importedSubscriber->attachTags($assignedTags);
                 }
            }

        }

        $totalSynced = count($importedCustomers) + (($page - 1) * $perPage);

        fluentcrm_update_option('_fluent_cart_sync_count', $totalSynced);
        fluentcrm_update_option('_fluent_cart_import_current_page', $page);

         // check time limit

        if (time() - $startTime > $runTime) {
            return self::getSyncStatus();
        }

        return self::getSyncStatus();
    }


    private static function mapCustomerTags($purchasedProducts, $productTagMaps)
    {
        $assignedTags = [];
        foreach ($purchasedProducts as $purchasedProduct) {
            if (isset($productTagMaps[$purchasedProduct])) {
                $assignedTags = array_merge($productTagMaps[$purchasedProduct], $assignedTags); // 
            }
        }

        return array_values(array_unique($assignedTags)); // return only unique tags
    }

    private static function purchasedProductsOfImportedCustomer($customerId, $productIds)
    {
        $allProductsIds =  CartHelper::getPurchasedProductsByCustomerId($customerId);
        // check in products
        return array_intersect($allProductsIds, $productIds); // return only those products which are in productIds
    }

    private static function getSyncStatus()
    {
        $total = fluentcrm_get_option('_fluent_cart_product_tag_total_count', 0);
        $perPage = self::PER_PAGE;

        // Calculate page-based progress for the frontend
        $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
        $currentPage = (int) fluentcrm_get_option('_fluent_cart_import_current_page', 1);
        $currentPage = max(1, min($currentPage, $totalPages));

        $hasMore = $currentPage < $totalPages;

        return [
            // Total number of pages to import
            'page_total'   => $totalPages,
            // Total records for reference
            'record_total' => $total,
            'has_more'     => $hasMore,
            'current_page' => $currentPage,
            'next_page'    => $hasMore ? ($currentPage + 1) : 0,
            'reload_page'  => !$hasMore
        ];
    }

}
