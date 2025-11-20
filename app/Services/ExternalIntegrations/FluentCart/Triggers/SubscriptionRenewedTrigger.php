<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\CartHelper;

class SubscriptionRenewedTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'fluent_cart/subscription_renewed';
        $this->priority = 20;
        $this->actionArgNum = 1;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'ribbon'      => 'subscription',
            'category' => __('FluentCart', 'fluent-crm'),
            'label' => __('Subscription Renewed', 'fluent-crm'),
            'description' => __('This will start when a subscription is renewed', 'fluent-crm'),
            'custom_icon' => 'fluentcart', // as svg
        ];
    }

    public function getFunnelSettingsDefaults()
    {
        return [
            'subscription_status' => 'subscribed',
        ];
    }

    public function getSettingsFields($funnel)
    {
        $statuses = fluentcrm_subscriber_editable_statuses(true);
        return [
            'title' => __('Subscription Renewed', 'fluent-crm'),
            'sub_title' => __('This will start when a subscription is renewed', 'fluent-crm'),
            'fields' => [
                'subscription_status' => [
                    'type' => 'select',
                    'options' => $statuses,
                    'is_multiple' => false,
                    'label' => __('Subscription Status', 'fluent-crm'),
                    'placeholder' => __('Select Status', 'fluent-crm'),
                ],
                'subscription_status_info' => [
                    'type'       => 'html',
                    'info'       => '<b>' . __('An Automated double-optin email will be sent for new subscribers', 'fluent-crm') . '</b>',
                    'dependency' => [
                        'depends_on' => 'subscription_status',
                        'operator'   => '=',
                        'value'      => 'pending',
                    ],
                ],
            ],
        ];
    }

    public function getConditionFields($funnel)
    {
        return [

            'product_ids' => [
                'type' => 'rest_selector',
                'label' => __('Target Products (Subscription Only)', 'fluent-crm'),
                'option_key' => 'fluent_cart_subscription_products',
                'is_multiple' => true,
                'help' => __('Select the products you want to include in the automation.', 'fluent-crm'),
                'inline_help' => __('You can select multiple products. If you want to run for all products, then leave it empty', 'fluent-crm'),
            ],

            'run_multiple' => [
                'type' => 'yes_no_check',
                'label' => '',
                'check_label' => __('Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)', 'fluent-crm'),
                'inline_help' => __('If you enable, then it will restart the automation for a contact if the contact already in the automation. Otherwise, It will just skip if already exist', 'fluent-crm'),
            ],

        ];
    }

    public function getFunnelConditionDefaults($funnel)
    {
        return [
            'product_ids'   => [],
            'run_multiple' => 'no'
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        $subscriptionData = $originalArgs[0];

        $subscription = $subscriptionData['subscription'];
        $order = $subscriptionData['order'];
        $customer = $subscriptionData['customer'];


        $subscriberData = CartHelper::prepareSubsciberData($customer);

        if (!is_email($subscriberData['email'])) {
            return;
        }

        $willProcess = $this->isProcessable($funnel, $order, $subscriberData);

        if (!$willProcess) {
            return;
        }

        $subscriberData = wp_parse_args($subscriberData, $funnel->settings);

        $subscriberData['status'] = $subscriberData['subscription_status'];
        unset($subscriberData['subscription_status']);

        (new \FluentCrm\App\Services\Funnel\FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id' => $order->id, // optional
        ]);

    }

    public function isProcessable($funnel, $order, $subscriberData)
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

    private function checkConditions($conditions, $order, $subscriber)
    {
        $selectedProductIds = Arr::get($conditions, 'product_ids', []);

        if (empty($selectedProductIds)) {
            return true; // No specific products, process all
        }

        $orderItems = Arr::get($order, 'order_items', []);
        // Post IDs of ordered products are the product IDs in FluentCart

        $orderedProductIds = [];
        foreach ($orderItems as $item) {
            $productId = $item->post_id;
            if ($productId) {
                $orderedProductIds[] = $productId;
            }
        }

        $productMatch = !empty(array_intersect($selectedProductIds, $orderedProductIds));

        return $productMatch; // Return true if any of the selected products match the ordered products

    }
}

