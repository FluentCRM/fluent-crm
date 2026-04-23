<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers;

use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\CartHelper;

class OrderRefundedTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'fluent_cart/order_fully_refunded';
        $this->priority = 20;
        $this->actionArgNum = 1;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category' => __('FluentCart', 'fluent-crm'),
            'label' => __('Order Refunded (Full)', 'fluent-crm'),
            'description' => __('This will start when a successful order is refunded', 'fluent-crm'),
            'custom_icon' => 'fluentcart', // as svg
        ];
    }

    public function getFunnelSettingsDefaults()
    {
        return [
            'subscription_status' => 'subscribed'
        ];
    }

    public function getSettingsFields($funnel)
    {
        return [
            'title' => __('Order Refunded (Full)', 'fluent-crm'),
            'sub_title' => __('This will start when an order is refunded', 'fluent-crm'),
            'fields' => [
                'subscription_status' => [
                    'type' => 'option_selectors',
                    'option_key' => 'editable_statuses',
                    'is_multiple' => false,
                    'label' => __('Subscription Status', 'fluent-crm'),
                    'placeholder' => __('Select Status', 'fluent-crm')
                ],
                'subscription_status_info' => [
                    'type'       => 'html',
                    'info'       => '<b>' . __('An Automated double-optin email will be sent for new subscribers', 'fluentcampaign-pro') . '</b>',
                    'dependency' => [
                        'depends_on' => 'subscription_status',
                        'operator'   => '=',
                        'value'      => 'pending'
                    ]
                ]
            ]
        ];
    }

    public function getFunnelConditionDefaults($funnel)
    {
        return [
            'product_ids'        => [],
            'product_categories' => [],
//            'purchase_type'      => 'all',
            'run_multiple'       => 'no'
        ];
    }

    public function getConditionFields($funnel)
    {
        return [
//            'update_type' => [
//                'type' => 'radio',
//                'label' => __('If Contact Already Exist?', 'fluent-crm'),
//                'help' => __('Please specify what will happen if the subscriber already exist in the database','fluent-crm'),
//                'options' => FunnelHelper::getUpdateOptions()
//            ],

            'product_ids' => [
                'type' => 'rest_selector',
                'label' => __('Target Products', 'fluent-crm'),
                'option_key' => 'fluent_cart_products',
                'is_multiple' => true,
                'help'        => __('Select for which products this automation will run', 'fluent-crm'),
                'inline_help' => __('Keep it blank to run to any product purchase', 'fluent-crm')
            ],

            'product_categories' => [
                'type' => 'rest_selector',
                'label' => __('Or Target Product Categories', 'fluent-crm'),
                'option_key' => 'fluent_cart_product_categories',
                'is_multiple' => true,
                'help'        => __('Select for which product category the automation will run', 'fluent-crm'),
                'inline_help' => __('Keep it blank to run to any category products', 'fluent-crm')
            ],

//            'run_only_if_coupon_applied' => [
//                'type' => 'yes_no_check',
//                'label' => '',
//                'check_label' => __('Run automation only if a coupon is applied to the order', 'fluent-crm'),
//            ],
            'run_multiple' => [
                'type' => 'yes_no_check',
                'label' => '',
                'check_label' => __('Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)', 'fluent-crm'),
                'inline_help' => __('If you enable, then it will restart the automation for a contact if the contact already in the automation. Otherwise, It will just skip if already exist', 'fluent-crm')
            ]
        ];
    }


    public function handle($funnel, $originalArgs)
    {
        $orderData = $originalArgs[0] ?? [];

        $order = Arr::get($orderData, 'order', []);
        $customer = Arr::get($order, 'customer', []);
        // $transaction = Arr::get($orderData, 'transaction', []);

        $orderId = Arr::get($order, 'id', 0);

        // Get the funnel settings and conditions
        $settings = Arr::get($funnel, 'settings', []);

        $subscriberData = CartHelper::prepareSubsciberData($customer);

        if (!is_email($subscriberData['email'])) {
            return;
        }

        $willProcess = $this->isProcessable($funnel, $order, $subscriberData);

        $willProcess = apply_filters('fluentcrm_funnel_will_process_' . $this->triggerName, $willProcess, $funnel, $subscriberData, $originalArgs);

        if (!$willProcess) {
            return;
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);

        $subscriberData['status'] = (!empty($subscriberData['subscription_status'])) ? $subscriberData['subscription_status'] : 'subscribed';
        unset($subscriberData['subscription_status']);

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id'       => $orderId
        ]);
    }

    private function isProcessable($funnel, $order, $subscriberData)
    {
        $conditions = Arr::get($funnel, 'conditions', []);
        $isProcessable = $this->checkConditions($conditions, $order, $subscriberData);

        if(!$isProcessable){
            return false;
        }

        $subscriber = FunnelHelper::getSubscriber($subscriberData['email']);

        // check run_only_one
        if ($subscriber) {
            $funnelSub = FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id);
            if ($funnelSub) {
                $multipleRun = Arr::get($conditions, 'run_multiple') == 'yes';
                if ($multipleRun) {
                    if ($funnelSub->source_ref_id == $order->id) {
                        return false;
                    }
                    FunnelHelper::removeSubscribersFromFunnel($funnel->id, [$subscriber->id]);
                }
                return $multipleRun;
            }
        }

        return true;
    }

    public function checkConditions($conditions, $order, $subscriber)
    {
        $orderItems = Arr::get($order, 'order_items', []);
        // Post IDs of ordered products are the product IDs in FluentCart

        $orderedProductIds = [];
        foreach ($orderItems as $item) {
            $productId = $item->post_id;
            if ($productId) {
                $orderedProductIds[] = $productId;
            }
        }

        $orderProductCategories = CartHelper::getProductCategoriesByIds($orderedProductIds);

        $selectedProductIds = Arr::get($conditions, 'product_ids', []);

        $selectedProductCategories = Arr::get($conditions, 'product_categories', []);

        // If no products or categories are selected, return true
        if (empty($selectedProductIds) && empty($selectedProductCategories)) {
            return true;
        }

        // Check for matches in product IDs and categories

        $productMatch = !empty($selectedProductIds) && !empty(array_intersect($selectedProductIds, $orderedProductIds));
        $categoryMatch = !empty($selectedProductCategories) && !empty(array_intersect($selectedProductCategories, $orderProductCategories));

        // Return true if either matches
        return $productMatch || $categoryMatch;
    }


}
