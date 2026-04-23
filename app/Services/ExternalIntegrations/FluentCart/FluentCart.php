<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentCart;

use FluentCart\Api\ModuleSettings;
use FluentCart\App\Helpers\Helper;
use FluentCart\App\Models\Customer;
use FluentCart\App\Models\Order;
use FluentCrm\App\Models\Subscriber;

use FluentCrm\App\Services\ExternalIntegrations\FluentCart\SmartCode\SmartCodeParser;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\SmartCode\SmartCodeRegister;

use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\OrderCanceledTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\OrderPaidTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\OrderDeliveredTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\OrderRefundedTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\OrderShippedTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\OrderStatusChangedTrigger;

use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\SubscriptionActivatedTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\SubscriptionCancelledTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\SubscriptionEndOfTermTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\SubscriptionExpiredTrigger;
use FluentCrm\App\Services\ExternalIntegrations\FluentCart\Triggers\SubscriptionRenewedTrigger;


class FluentCart
{
    public function init()
    {
        $this->addAutomations();
        $this->addHooks();

        SmartCodeRegister::push();


    }

    public function addAutomations()
    {
        new OrderPaidTrigger();
        new OrderShippedTrigger();
        new OrderDeliveredTrigger();
        new OrderRefundedTrigger();
        new OrderCanceledTrigger();

        // new OrderStatusChangedTrigger(); // Disabled for now as will be available in future
        new SubscriptionExpiredTrigger();

        //subscription activated
        new SubscriptionActivatedTrigger();

        //subscription cancelled
        new SubscriptionCancelledTrigger();

        //subscription renewed
        new SubscriptionRenewedTrigger();

        //subscription end of term(completed)
        new SubscriptionEndOfTermTrigger();
    }

    public function addHooks()
    {

        add_filter('fluent_crm/get_import_driver_fluent_cart', [CartImporter::class, 'processUserDriver'], 10, 2);
        add_filter('fluent_crm/post_import_driver_fluent_cart', [CartImporter::class, 'importData'], 10, 3);

        add_filter('fluentcrm_ajax_options_fluent_cart_products', [$this, 'getProducts'], 10, 3);
        add_filter('fluentcrm_ajax_options_fluent_cart_product_categories', [$this, 'getProductCategories'], 10, 3);
        add_filter('fluentcrm_ajax_options_fluent_cart_subscription_products', [$this, 'getSubscriptionProducts'], 10, 3);

        add_filter('fluent_crm/funnel_icons', [$this, 'addCartIcon'], 10 , 1);
        add_filter('fluent_crm/purchase_history_fluent_cart', [$this, 'purchaseHistory'], 10, 2);

        add_filter('fluent_crm/smartcode_group_callback_cart_order', [SmartCodeParser::class, 'parseCartOrder'], 10, 4);
        add_filter('fluent_crm/smartcode_group_callback_cart_customer', [SmartCodeParser::class, 'parseCartCustomer'], 10, 4);
//        add_filter('fluent_crm/smartcode_group_callback_cart_transaction', [SmartCodeParser::class, 'parseCartTransaction'], 10, 4);
        add_filter('fluent_crm/smartcode_group_callback_cart_receipt', [SmartCodeParser::class, 'parseCartReceipt'], 10, 4);


        add_filter('fluentcrm_automation_condition_groups', array($this, 'addAutomationConditions'), 10, 2);
        add_filter('fluentcrm_automation_conditions_assess_fluent_cart', array($this, 'assessAutomationConditions'), 10, 3);
        // add_filter('fluentcrm_automation_conditions_assess_woo_order', array($this, 'assessAutomationOrderConditions'), 10, 5);
    }

    public function getProducts($items, $search, $ids)
    {
        return CartHelper::getFluentCartProducts($items, $search, $ids);
    }

    public function getProductCategories($items, $search, $ids)
    {
        return CartHelper::getFluentCartProductCategories($items, $search, $ids);
    }

    public function getSubscriptionProducts($items, $search, $ids)
    {
        return CartHelper::getFluentCartSubscriptionProducts($items, $search, $ids);
    }

    public function addCartIcon($icons)
    {
        $icons['fluentcart'] = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g id="surface1">
                                <path style=" stroke:none;fill-rule:nonzero;fill:rgb(100%,100%,100%);fill-opacity:1;" d="M 2.398438 0 L 21.601562 0 C 22.925781 0 24 1.074219 24 2.398438 L 24 21.601562 C 24 22.925781 22.925781 24 21.601562 24 L 2.398438 24 C 1.074219 24 0 22.925781 0 21.601562 L 0 2.398438 C 0 1.074219 1.074219 0 2.398438 0 Z M 2.398438 0 "/>
                                <path style=" stroke:none;fill-rule:nonzero;fill:rgb(0%,0%,62.352943%);fill-opacity:1;" d="M 10.925781 16.476562 L 3.769531 16.476562 L 4.894531 13.878906 C 5.222656 13.117188 5.972656 12.625 6.804688 12.625 L 15.328125 12.625 L 14.746094 13.964844 C 14.085938 15.488281 12.585938 16.476562 10.925781 16.476562 Z M 10.925781 16.476562 "/>
                                <path style=" stroke:none;fill-rule:nonzero;fill:rgb(0%,0%,62.352943%);fill-opacity:1;" d="M 16.851562 11.394531 L 6.789062 11.394531 L 7.367188 10.054688 C 8.027344 8.53125 9.53125 7.542969 11.191406 7.542969 L 19.886719 7.542969 L 18.761719 10.140625 C 18.433594 10.902344 17.683594 11.394531 16.851562 11.394531 Z M 16.851562 11.394531 "/>
                                </g>
                                </svg>';
        return $icons;
    }

    public function purchaseHistory($data, $subscriber)
    {
        $customer = Customer::where('email', $subscriber->email)->first();
        if (!$customer) {
            return [];
        }

        $ordersQuery = Order::with('appliedCoupons')->where('customer_id', $customer->id);
        $totalCount = $ordersQuery->count();

        if (!$totalCount) {
            return [];
        }

        // Pagination params (using super global to avoid dependency on request wrapper here)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) { $page = 1; }
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        if ($perPage < 1) { $perPage = 10; }

        $orders = $ordersQuery
            ->orderBy('id', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        // Use a helper method for formatting
        $formattedOrders = $this->formatOrders($orders);

        return [
            'data' => $formattedOrders,
            'total' => $totalCount,
            'sidebar_html' => $this->getSidebarHtml($subscriber),
            'after_html' => '',
            'has_recount' => false,
            'columns_config' => [
                'order' => ['label' => __('Order', 'fluent-crm'), 'width' => '100px', 'sortable' => true, 'key' => 'id'],
                'date' => ['label' => __('Date', 'fluent-crm'), 'sortable' => true, 'key' => 'created_at'],
//                'coupon' => ['label' => __('Coupon', 'fluent-crm'), 'sortable' => true, 'key' => 'created_at'],
                'status' => ['label' => __('Status', 'fluent-crm'), 'width' => '140px', 'sortable' => false],
//                'payment' => ['label' => __('Payment', 'fluent-crm'), 'width' => '140px', 'sortable' => false, 'key' => 'payment_status'],
                'total' => ['label' => __('Total', 'fluent-crm'), 'width' => '120px', 'sortable' => true, 'key' => 'total'],
                'action' => ['label' => __('Actions', 'fluent-crm'), 'width' => '100px', 'sortable' => false],
            ],
        ];

    }

    private function formatOrders($orders)
    {
        $formattedOrders = [];

        foreach ($orders as $order) {
            $orderActionHtml = '<a target="_blank" href="' . admin_url('admin.php?page=fluent-cart#/orders/' . $order->id . '/view') . '">' . __('View Order', 'fluent-crm') . '</a>';
            $coupons = implode(', ', array_column($order['appliedCoupons']->toArray(), 'code'));
            $formattedOrders[] = [
                'order' => '#' . $order->id,
                'date' => date_i18n(get_option('date_format'), strtotime($order->created_at)),
                'status' => $order->status,
//                'payment' => $order->payment_status,
//                'coupons' => $coupons,
                'total' => Helper::toDecimal($order->total_amount), // Adjust if using a different helper
                'action' => $orderActionHtml,
            ];
        }

        return $formattedOrders;
    }

    private function getSidebarHtml($subscriber = null)
    {
        // We will build a similar widget like WooCommerce's purchase sidebar
        // Show a quick customer summary + recently purchased products (flat list of items)
        $customer = null;
        if ($subscriber && !empty($subscriber->email)) {
            $customer = Customer::where('email', $subscriber->email)->first();
        }

        if (!$customer) {
            // Fallback: Just return the static block as before
            return '<div class="fluent-crm-sidebar-content">'
                . '<h3>' . __('Product History', 'fluent-crm') . '</h3>'
                . '<p>' . __('View your purchase history from FluentCart.', 'fluent-crm') . '</p>'
                . '</div>';
        }

        // Aggregate order stats
        $ordersQuery = Order::where('customer_id', $customer->id);
        $orderCount = (clone $ordersQuery)->count();

        if (!$orderCount) {
            return '<div class="fluent-crm-sidebar-content">'
                . '<h3>' . __('Product History', 'fluent-crm') . '</h3>'
                . '<p>' . __('No purchases found for this contact in FluentCart.', 'fluent-crm') . '</p>'
                . '</div>';
        }

        $totalSpent = (clone $ordersQuery)->sum('total_amount');
        $firstOrder = (clone $ordersQuery)->orderBy('id', 'ASC')->value('created_at');
        $lastOrder = (clone $ordersQuery)->orderBy('id', 'DESC')->value('created_at');

        // Fetch recent purchased items (latest 15 order items across latest orders)
        // We'll eager load order_items for performance
        $recentOrders = (clone $ordersQuery)
            ->with(['order_items' => function($q){
                $q->orderBy('id', 'DESC');
            }])
            ->orderBy('id', 'DESC')
            ->limit(200)
            ->get();

        $items = [];
        foreach ($recentOrders as $order) {
            foreach ($order->order_items as $orderItem) {
                $items[] = [
                    'name' => isset($orderItem->title) ? $orderItem->title : __('(Product)', 'fluent-crm'),
                    'price' => isset($orderItem->line_total) ? Helper::toDecimal($orderItem->line_total) : 0,
                    'created_at' => $order->created_at,
                    'order_id' => $order->id,
                ];
                if (count($items) >= 15) {
                    break 2; // Exit both loops once we have enough
                }
            }
        }

        $html = '<div class="fluent-crm-sidebar-content fc_payment_summary">';
        $html .= '<h3 class="history_title">' . __('Order Summary', 'fluent-crm') . '</h3>';
        $html .= '<div class="fc_history_widget"><ul class="fc_full_listed">';
        $html .= '<li><span class="fc_list_sub">' . __('Total Orders', 'fluent-crm') . '</span><span class="fc_list_value">' . intval($orderCount) . '</span></li>';
        $html .= '<li><span class="fc_list_sub">' . __('Total Spent', 'fluent-crm') . '</span><span class="fc_list_value">' . esc_html(Helper::toDecimal($totalSpent)) . '</span></li>';
        if ($firstOrder) {
            $html .= '<li><span class="fc_list_sub">' . __('First Order', 'fluent-crm') . '</span><span class="fc_list_value">' . date_i18n(get_option('date_format'), strtotime($firstOrder)) . '</span></li>';
        }
        if ($lastOrder) {
            $html .= '<li><span class="fc_list_sub">' . __('Last Order', 'fluent-crm') . '</span><span class="fc_list_value">' . date_i18n(get_option('date_format'), strtotime($lastOrder)) . '</span></li>';
        }
        $html .= '</ul></div>';

        $html .= '<h3 class="history_title">' . __('Purchased Products', 'fluent-crm') . '</h3>';
        $html .= '<div class="fc_history_widget"><ul class="fc_full_listed max_height_550">';
        foreach ($items as $item) {
            $orderUrl = admin_url('admin.php?page=fluent-cart#/orders/' . $item['order_id'] . '/view');
            $badges = '<span class="fc_purchase_badge fc_badge_price">' . esc_html(Helper::toDecimal($item['price'])) . '</span>';
            $badges .= '<span class="fc_purchase_badge fc_badge_date"><a target="_blank" rel="noopener" href="' . esc_url($orderUrl) . '">' . date_i18n(get_option('date_format'), strtotime($item['created_at'])) . '</a></span>';
            $html .= '<li class="fc_product_name">' . esc_html($item['name']) . ' ' . $badges . '</li>';
        }
        if (!$items) {
            $html .= '<li>' . __('No purchased products found.', 'fluent-crm') . '</li>';
        }
        $html .= '</ul></div>';

        $html .= '</div>';

        return $html;
    }


     public function addAutomationConditions($groups)
    {
        $conditionItems = [
            [
                'value'             => 'commerce_exist',
                'label'             => __('Is a customer?', 'fluent-cart'),
                'type'              => 'selections',
                'is_multiple'       => false,
                'disable_values'    => true,
                'value_description' => __('This filter will check if a contact has at least one shop order or not', 'fluent-cart'),
                'custom_operators'  => [
                    'exist'     => __('Yes', 'fluent-cart'),
                    'not_exist' => __('No', 'fluent-cart'),
                ]
            ],
            [
                'value' => 'ltv',
                'label' => __('Lifetime Value', 'fluent-cart'),
                'type'  => 'numeric'
            ],
            [
                'value' => 'aov',
                'label' => __('Average Order Value', 'fluent-cart'),
                'type'  => 'numeric',
            ],
            [
                'value' => 'first_purchase_date',
                'label' => __('First Order Date', 'fluent-cart'),
                'type'  => 'dates'
            ],
            [
                'value' => 'last_purchase_date',
                'label' => __('Last Order Date', 'fluent-cart'),
                'type'  => 'dates'
            ],
            [
                'value'            => 'purchased_items',
                'label'            => __('Products', 'fluent-cart'),
                'type'             => 'selections',
                'component'        => 'product_selector',
                'is_multiple'      => true,
                'custom_operators' => [
                    'exist'     => __('purchased', 'fluent-cart'),
                    'not_exist' => __('not purchased', 'fluent-cart'),
                ],
                'help'             => __('Will filter the contacts who have at least one order', 'fluent-cart')
            ],
            [
                'value'             => 'variation_purchased',
                'label'             => __('Product Variations', 'fluent-cart'),
                'type'              => 'cascade_selections',
                'provider'          => 'fct_variations',
                'is_multiple'       => true,
                'value_description' => __('This filter will check if a contact has purchased at least one specific product variation or not', 'fluent-cart'),
                'custom_operators'  => [
                    'exist'     => __('purchased', 'fluent-cart'),
                    'not_exist' => __('not purchased', 'fluent-cart'),
                ]
            ],
            [
                'value'            => 'purchased_categories',
                'label'            => __('Product Categories', 'fluent-cart'),
                'type'             => 'selections',
                'component'        => 'tax_selector',
                'taxonomy'         => 'product-categories',
                'is_multiple'      => true,
                'disabled'         => true,
                'help'             => __('Will filter the contacts who have at least one order', 'fluent-cart'),
                'custom_operators' => [
                    'exist'     => __('purchased', 'fluent-cart'),
                    'not_exist' => __('not purchased', 'fluent-cart'),
                ]
            ],
            [
                'value'            => 'commerce_coupons',
                'label'            => __('Used Coupons', 'fluent-cart'),
                'type'             => 'selections',
                'component'        => 'ajax_selector',
                'option_key'       => 'fct_coupons',
                'is_multiple'      => true,
                'disabled'         => true,
                'custom_operators' => [
                    'exist'     => __('in', 'fluent-cart'),
                    'not_exist' => __('not in', 'fluent-cart'),
                ],
                'help'             => __('Will filter the contacts who have at least one order', 'fluent-cart')
            ]
        ];

        if (ModuleSettings::isActive('license')) {
            $conditionItems[] = [
                'value'            => 'active_licenses',
                'label'            => __('Active Licenses', 'fluent-cart'),
                'type'             => 'selections',
                'component'        => 'product_selector',
                'is_multiple'      => true,
                'custom_operators' => [
                    'exist'     => __('have', 'fluent-cart'),
                    'not_exist' => __('do not have', 'fluent-cart'),
                ],
                'help'             => __('Will filter the contacts who have at least one active licenses or not', 'fluent-cart')
            ];
            $conditionItems[] = [
                'value'             => 'active_variation_licenses',
                'label'             => __('Active Variation Licenses', 'fluent-cart'),
                'type'              => 'cascade_selections',
                'provider'          => 'fct_variations',
                'is_multiple'       => true,
                'value_description' => __('This filter will check if a contact has at least one specific variation license or not', 'fluent-cart'),
                'custom_operators'  => [
                    'exist'     => __('have', 'fluent-cart'),
                    'not_exist' => __('do not have', 'fluent-cart'),
                ]
            ];
            $conditionItems[] = [
                'value'            => 'expired_licenses',
                'label'            => __('Expired Licenses', 'fluent-cart'),
                'type'             => 'selections',
                'component'        => 'product_selector',
                'is_multiple'      => true,
                'custom_operators' => [
                    'exist'     => __('have', 'fluent-cart'),
                    'not_exist' => __('do not have', 'fluent-cart'),
                ],
                'help'             => __('Will filter the contacts who have at least one expired licenses or not', 'fluent-cart')
            ];
            $conditionItems[] = [
                'value'             => 'expired_variation_licenses',
                'label'             => __('Expired Variation Licenses', 'fluent-cart'),
                'type'              => 'cascade_selections',
                'provider'          => 'fct_variations',
                'is_multiple'       => true,
                'value_description' => __('This filter will check if a contact has at least one specific variation expired license or not', 'fluent-cart'),
                'custom_operators'  => [
                    'exist'     => __('have', 'fluent-cart'),
                    'not_exist' => __('do not have', 'fluent-cart'),
                ]
            ];
            $conditionItems[] = [
                'value'             => 'license_exist',
                'label'             => __('Has any active license?', 'fluent-cart'),
                'type'              => 'selections',
                'is_multiple'       => false,
                'disable_values'    => true,
                'value_description' => __('Check if contacts has any active license from any products', 'fluent-cart'),
                'custom_operators'  => [
                    'exist'     => __('Yes', 'fluent-cart'),
                    'not_exist' => __('No', 'fluent-cart'),
                ]
            ];
        }

        $groups['fluent_cart'] = [
            'label'    => __('FluentCart', 'fluent-crm'),
            'value'    => 'fluent_cart',
            'children' => $conditionItems
        ];

        return $groups;
    }

    public function assessAutomationConditions($result, $conditions, $subscriber)
    {
        $legacyConditions = [];
        // if (Commerce::isEnabled('woo')) {
            $formattedConditions = [];

            $commerceProps = [
                'commerce_exist',
                'ltv', // lifetime value
                'aov', // average order value
                'first_purchase_date',
                'last_purchase_date',
                'purchased_items', // products purchased
                'variation_purchased', // product variations purchased
                'purchased_categories', // product categories 
                'commerce_coupons', // used coupons
            ];

            foreach ($conditions as $condition) {
                $prop = $condition['data_key'];
                $operator = $condition['operator'];
                if (in_array($prop, $commerceProps)) {
                    $formattedConditions[] = [
                        'operator' => $operator,
                        'value'    => $condition['data_value'],
                        'property' => $prop,
                    ];
                } else {
                    $legacyConditions[] = $condition;
                }
            }

            if ($formattedConditions) {
                $hasSubscriber = Subscriber::where('id', $subscriber->id)->where(function ($q) use ($formattedConditions) {
                    do_action_ref_array('fluentcrm_contacts_filter_fluent_cart', [&$q, $formattedConditions]);
                })->first();
                if (!$hasSubscriber) {
                    return false;
                }
            }
        // } else {
        //     $legacyConditions = $conditions;
        // }

        if ($legacyConditions) {
            $cartCustomer = Customer::query()
                ->where('email', $subscriber->email)
                ->when($subscriber->user_id, function ($q) use ($subscriber) {
                    return $q->orWhere('user_id', $subscriber->user_id);
                })
                ->first();

            if (!$cartCustomer) {
                return false;
            }
        }

        return $result;
    }

}
