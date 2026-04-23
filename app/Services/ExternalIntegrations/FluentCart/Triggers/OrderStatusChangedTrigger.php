<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers;

use FluentCart\App\Helpers\Status;
use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\CartHelper;

class OrderStatusChangedTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->triggerName = 'fluent_cart/order_status_changed';
        $this->priority = 20;
        $this->actionArgNum = 1;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category' => __('FluentCart', 'fluent-crm'),
            'label' => __('Order Status Changed', 'fluent-crm'),
            'description' => __('This funnel will start when an order status updates', 'fluent-crm'),
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
            'title'     => __('FluentCart Order Status Changed', 'fluent-crm'),
            'sub_title' => __('This Funnel will start when a Order status will change from one state to another', 'fluent-crm'),
            'fields'    => [
                'subscription_status'      => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => __('Subscription Status', 'fluent-crm'),
                    'placeholder' => __('Select Status', 'fluent-crm')
                ],
                'subscription_status_info' => [
                    'type'       => 'html',
                    'info'       => '<b>' . __('An Automated double-optin email will be sent for new subscribers', 'fluent-crm') . '</b>',
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
            'product_ids'   => '',
            'from_status'   => 'any',
            'to_status'     => 'any',
            'run_multiple'  => 'no'
        ];
    }

    public function getConditionFields($funnel)
    {
        $orderStatuses = Status::getOrderStatuses();

        $formattedStatuses = [[
            'id'    => 'any',
            'title' => __('Any', 'fluent-crm')
        ]];

        foreach ($orderStatuses as $statusId => $statusName) {
            $formattedStatuses[] = [
                'id' => $statusId,
                'title' => $statusName
            ];
        }

        return [
            'product_ids'     => [
                'type'        => 'rest_selector',
                'option_key'  => 'fluent_cart_products',
                'is_multiple' => true,
                'label'       => __('Target Products', 'fluent-crm'),
                'inline_help' => __('Keep it blank to run to any product status changed', 'fluent-crm'),
            ],
            'from_status' => [
                'type' => 'select',
                'label' => __('From Order Status', 'fluent-crm'),
                'help' => __('The current status that will trigger an action when it changes from this status to the \'To Order Status.\'', 'fluentcampaign-pro'),
                'options' => $formattedStatuses
            ],
            'to_status' => [
                'type' => 'select',
                'label' => __('To Order Status', 'fluent-crm'),
                'help' => __('The target status that will trigger an action when the order moves from the \'From Order Status\' to this status.', 'fluentcampaign-pro'),
                'options' => $formattedStatuses
            ],
            'run_multiple'  => [
                'type'        => 'yes_no_check',
                'label'       => '',
                'check_label' => __('Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)', 'fluentcampaign-pro'),
                'inline_help' => __('If you enable, then it will restart the automation for a contact if the contact already in the automation. Otherwise, It will just skip if already exist', 'fluentcampaign-pro')
            ]
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        $orderData = $originalArgs[0] ?? [];

        $order = Arr::get($orderData, 'order', []);
        $fromStatus = Arr::get($orderData, 'old_status', []);
        $toStatus = Arr::get($orderData, 'new_status', []);

        $customer = Arr::get($order, 'customer');

        $orderId = Arr::get($order, 'id', 0);

        $subscriberData = CartHelper::prepareSubsciberData($customer);


        if (!is_email($subscriberData['email'])) {
            return;
        }

        $willProcess = $this->isProcessable($funnel, $subscriberData, $fromStatus, $toStatus, $order);


        $willProcess = apply_filters('fluentcrm_funnel_will_process_' . $this->triggerName, $willProcess, $funnel, $subscriberData, $originalArgs);

        if (!$willProcess) {
            return;
        }

        $subscriberData['status'] = (!empty($subscriberData['subscription_status'])) ? $subscriberData['subscription_status'] : 'subscribed';
        unset($subscriberData['subscription_status']);

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id'       => $orderId
        ]);
    }

    private function isProcessable($funnel, $subscriberData, $fromStatus, $toStatus, $order)
    {
        $conditions = (array)$funnel->conditions;

        $orderItems = Arr::get($order, 'order_items', []);
        // Post IDs of ordered products are the product IDs in FluentCart

        $orderedProductIds = [];
        foreach ($orderItems as $item) {
            $productId = $item->post_id;
            if ($productId) {
                $orderedProductIds[] = $productId;
            }
        }

        $selectedProductIds = Arr::get($conditions, 'product_ids', []);

        // If no products are selected, return true
        if (empty($selectedProductIds)) {
            return true;
        }

        $productMatch = !empty($selectedProductIds) && !empty(array_intersect($selectedProductIds, $orderedProductIds));

        if (!$productMatch) {
            return false;
        }

        if($conditions['from_status'] != 'any') {
            if($conditions['from_status'] != $fromStatus) {
                return false;
            }
        }

        if($conditions['to_status'] != 'any') {
            if($conditions['to_status'] != $toStatus) {
                return false;
            }
        }

        $subscriber = FunnelHelper::getSubscriber($subscriberData['email']);

        // check run_only_one
        if ($subscriber && FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id)) {
            $multipleRun = Arr::get($conditions, 'run_multiple') == 'yes';
            if ($multipleRun) {
                FunnelHelper::removeSubscribersFromFunnel($funnel->id, [$subscriber->id]);
            } else {
                return false;
            }
        }


        return true;
    }
}
