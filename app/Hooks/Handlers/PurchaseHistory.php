<?php

namespace FluentCrm\App\Hooks\Handlers;


use FluentCrm\App\Models\Subscriber;

/**
 *  PurchaseHistory Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class PurchaseHistory
{
    public function getCommerceStatWidget($subscriber)
    {
        $commerceProvider = apply_filters('fluentcrm_commerce_provider', '');

        if ($commerceProvider) {
            $stats = apply_filters('fluent_crm/contact_purchase_stat_' . $commerceProvider, [], $subscriber->id);
            if (!$stats) {
                return false;
            }

            $html = '<ul class="fc_full_listed">';
            foreach ($stats as $stat) {
                $html .= '<li><span class="fc_list_sub">' . $stat['title'] . '</span> <span class="fc_list_value">' . $stat['value'] . '</span></li>';
            }
            $html .= '</ul>';

            return [
                'title'   => 'Customer Summary',
                'content' => $html
            ];
        }

        if (defined('WC_PLUGIN_FILE')) {
            $summary = $this->getWooCustomerSummary($subscriber);
            if($summary) {
                return [
                    'title'   => 'Customer Summary',
                    'content' => $summary
                ];
            }
            return false;
        }

        if (class_exists('\Easy_Digital_Downloads')) {

            $customer = fluentCrmDb()->table('edd_customers')
                ->where('email', $subscriber->email);
            if($subscriber->user_id) {
                $customer = $customer->orWhere('user_id', $subscriber->user_id);
            }
            $customer =  $customer->first();
            if(!$customer) {
                return false;
            }

            $summaryData = [
                'order_count'     => $customer->purchase_count,
                'lifetime_value'  => number_format($customer->purchase_value, 2),
                'avg_value'       => ($customer->purchase_count) ? round($customer->purchase_value / $customer->purchase_count, 2) : 'n/a',
                'stat_avg_count'  => 0,
                'stat_avg_spend'  => 0,
                'stat_avg_value'  => 0,
                'currency_sign'   => edd_currency_symbol(),
                'first_order_date' => $customer->date_created
            ];

            $html = $this->formatSummaryData($summaryData, true);

            return [
                'title'   => 'Customer Summary',
                'content' => $html
            ];
        }

        return false;
    }

    public function wooOrders($data, $subscriber)
    {
        if (!defined('WC_PLUGIN_FILE')) {
            return $data;
        }

        $hasRecount = defined('FLUENTCAMPAIGN') && \FluentCampaign\App\Services\Commerce\Commerce::isEnabled('woo');

        $app = fluentCrm();

        if ($hasRecount && $app->request->get('will_recount') == 'yes') {
            (new \FluentCampaign\App\Services\Integrations\WooCommerce\DeepIntegration)->syncCustomerBySubscriber($subscriber);
        }

        $page = (int)$app->request->get('page', 1);
        $per_page = (int)$app->request->get('per_page', 10);

        $args = array(
            'limit'    => $per_page,
            'offset'   => $per_page * ($page - 1),
            'paginate' => true
        );

        $args['customer'] = $subscriber->email;

        $customer_orders = wc_get_orders($args);

        if (empty($customer_orders->orders) && empty($args['customer'])) {
            $args['customer'] = $subscriber->email;
            $customer_orders = wc_get_orders($args);
        }

        $formattedOrders = [];
        $lastOrder = false;
        foreach ($customer_orders->orders as $order) {
            if (!$lastOrder) {
                $lastOrder = $order;
            }

            $item_count = $order->get_item_count() - $order->get_item_count_refunded();
            $actionsHtml = '<a target="_blank" href="' . $order->get_edit_order_url() . '">' . __('View Order', 'fluent-crm') . '</a>';
            $formattedOrders[] = [
                'order'   => '#' . $order->get_order_number(),
                'date'    => esc_html(wc_format_datetime($order->get_date_created())),
                'status'  => $order->get_status(),
                'total'   => wp_kses_post(sprintf(_n('%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'fluent-crm'), $order->get_formatted_order_total(), $item_count)),
                'actions' => $actionsHtml
            ];
        }

        $sidebarHtml = apply_filters('fluent_crm/woo_purchase_sidebar_html', '', $subscriber, $page);

        if ($sidebarHtml == '' && $page == 1 && $formattedOrders) {

            $customerQuery = fluentCrmDb()->table('wc_customer_lookup')
                ->where('email', $subscriber->email);

            if ($subscriber->user_id) {
                $customerQuery = $customerQuery->orWhere('user_id', $subscriber->user_id);
            }

            $customer = $customerQuery->first();

            if ($customer) {
                $statuses = wc_get_is_paid_statuses();
                $statuses = array_map(function ($status) {
                    return 'wc-' . $status;
                }, $statuses);

                $orderStats = fluentCrmDb()->table('wc_order_stats')
                    ->where('customer_id', $customer->customer_id)
                    ->whereIn('status', $statuses)
                    ->get();

                $orderIds = [];
                foreach ($orderStats as $order) {
                    $orderIds[] = $order->order_id;
                }

                $orderIds = array_unique($orderIds);

                $orderedProducts = fluentCrmDb()->table('wc_order_product_lookup')
                    ->select([
                        'posts.ID', 'posts.post_title', 'posts.guid'
                    ])
                    ->join('posts', 'wc_order_product_lookup.product_id', '=', 'posts.ID')
                    ->whereIn('order_id', $orderIds)
                    ->groupBy('posts.ID')
                    ->get();

                $sidebarHtml = '<h3>Purchased Products</h3><ul class="fc_full_listed">';

                foreach ($orderedProducts as $product) {
                    $sidebarHtml .= '<li><a target="_blank" rel="nofollow" href="' . $product->guid . '">' . $product->post_title . '</a></li>';
                }

                $sidebarHtml .= '</ul>';
            }
        }

        return [
            'data'         => $formattedOrders,
            'sidebar_html' => $sidebarHtml,
            'total'        => $customer_orders->total,
            'has_recount'  => $hasRecount,
            'columns_config' => [
                'order' => [
                    'label' => __('Order', 'fluent-crm'),
                    'width' => '100px'
                ],
                'date' => [
                    'label' => __('Date', 'fluent-crm')
                ],
                'status' => [
                    'label' => __('Status', 'fluent-crm'),
                    'width' => '100px'
                ],
                'total' => [
                    'label' => __('Total', 'fluent-crm'),
                    'width' => '160px'
                ],
                'actions' => [
                    'label' => __('Actions', 'fluent-crm'),
                    'width' => '100px'
                ]
            ]
        ];
    }

    public function getWooCustomerSummary($subscriber)
    {
        $customerQuery = fluentCrmDb()->table('wc_customer_lookup')
            ->where('email', $subscriber->email);

        if ($subscriber->user_id) {
            $customerQuery = $customerQuery->orWhere('user_id', $subscriber->user_id);
        }

        $customer = $customerQuery->first();

        if ($customer) {
            $statuses = wc_get_is_paid_statuses();
            $statuses = array_map(function ($status) {
                return 'wc-' . $status;
            }, $statuses);

            $orderStats = fluentCrmDb()->table('wc_order_stats')
                ->where('customer_id', $customer->customer_id)
                ->whereIn('status', $statuses)
                ->get();

            if(!$orderStats) {
                return false;
            }

            $lifetimeValue = 0;
            $orderIds = [];

            $firstOrderDate = null;
            $lastOrderDate = null;

            foreach ($orderStats as $order) {
                if(!$firstOrderDate) {
                    $firstOrderDate = $order->date_created;
                }

                if(!$lastOrderDate) {
                    $lastOrderDate = $order->date_created;
                }

                if(strtotime($order->date_created) < strtotime($firstOrderDate)) {
                    $firstOrderDate = $order->date_created;
                }

                if(strtotime($order->date_created) > strtotime($lastOrderDate)) {
                    $lastOrderDate = $order->date_created;
                }

                $lifetimeValue += $order->total_sales;
                $orderIds[] = $order->order_id;
            }

            $orderIds = array_unique($orderIds);

            $orderCount = count($orderIds);


            $data_store = \WC_Data_Store::load('report-customers-stats');
            $stat = $data_store->get_data();

            $summaryData = [
                'order_count'        => $orderCount,
                'lifetime_value'     => $lifetimeValue,
                'avg_value'          => round($lifetimeValue / $orderCount, 2),
                'stat_avg_count'     => $stat->avg_orders_count,
                'stat_avg_spend'     => $stat->avg_total_spend,
                'stat_avg_value'     => $stat->avg_avg_order_value,
                'currency_sign'      => get_woocommerce_currency_symbol(),
                'last_order_date' => $lastOrderDate,
                'first_order_date' => $firstOrderDate,
            ];

            return $this->formatSummaryData($summaryData, true);
        }

        return false;
    }

    public function eddOrders($data, $subscriber)
    {
        if (!class_exists('\Easy_Digital_Downloads')) {
            return $data;
        }

        $app = fluentCrm();
        $page = (int)$app->request->get('page', 1);
        set_query_var('paged', $page);

        $hasRecount = defined('FLUENTCAMPAIGN') && \FluentCampaign\App\Services\Commerce\Commerce::isEnabled('edd');

        if ($hasRecount && $app->request->get('will_recount') == 'yes') {
            (new \FluentCampaign\App\Services\Integrations\Edd\DeepIntegration)->syncCustomerBySubscriber($subscriber);
        }

        $per_page = (int)$app->request->get('per_page', 10);
        $customer = new \EDD_Customer($subscriber->email);

        if (!$customer || !$customer->id) {
            return $data;
        }

        $lasOrderData = '';

        /*
         * Handle for EDD3
         */
        if (function_exists('\edd_get_orders')) {
            $totalCount = fluentCrmDb()->table('edd_orders')
                ->where('customer_id', $customer->id)
                ->count();

            if (!$totalCount) {
                return $data;
            }

            $orders = fluentCrmDb()->table('edd_orders')
                ->where('customer_id', $customer->id)
                ->orderBy('id', 'DESC')
                ->limit($per_page)
                ->offset(($page - 1) * $per_page)
                ->get();

            $formattedOrders = [];

            foreach ($orders as $order) {
                $orderActionHtml = '<a target="_blank" href="' . add_query_arg('id', $order->id, admin_url('edit.php?post_type=download&page=edd-payment-history&view=view-order-details')) . '">' . __('View Order', 'fluent-crm') . '</a>';
                $formattedOrders[] = [
                    'order'  => '#' . $order->id,
                    'date'   => date_i18n(get_option('date_format'), strtotime($order->date_created)),
                    'status' => $order->status,
                    'total'  => edd_currency_filter(edd_format_amount($order->total)),
                    'action' => $orderActionHtml
                ];
            }

            if ($orders) {
                $lasOrderData = date_i18n(get_option('date_format'), strtotime($orders[0]->date_created));
            }

        } else {
            $totalCount = count($customer->get_payment_ids());
            if (!$totalCount) {
                return $data;
            }

            $payments = edd_get_payments([
                'customer_id' => $customer->id,
                'customer'    => $customer->id,
                'number'      => $per_page,
                'offset'      => ($page - 1) * $per_page
            ]);

            $formattedOrders = [];
            if ($payments) {
                foreach ($payments as $payment) {
                    if (!$payment instanceof \EDD_Payment) {
                        $payment = new \EDD_Payment($payment->ID);
                    }
                    $orderActionHtml = '<a target="_blank" href="' . add_query_arg('id', $payment->ID, admin_url('edit.php?post_type=download&page=edd-payment-history&view=view-order-details')) . '">' . __('View Order', 'fluent-crm') . '</a>';
                    $formattedOrders[] = [
                        'order'  => '#' . $payment->number,
                        'date'   => date_i18n(get_option('date_format'), strtotime($payment->date)),
                        'status' => $payment->status_nicename,
                        'total'  => edd_currency_filter(edd_format_amount($payment->total)),
                        'action' => $orderActionHtml
                    ];
                }
                $lasOrderData = date_i18n(get_option('date_format'), strtotime($payments[0]->post_date));
            }
        }

        $beforeHtml = apply_filters('fluent_crm/edd_purchase_sidebar_html', '', $subscriber, $page);

//        if (!$beforeHtml && $subscriber->user_id && $page == 1 && $formattedOrders) {
//            $summaryData = [
//                'order_count'     => $customer->purchase_count,
//                'lifetime_value'  => $customer->purchase_value,
//                'avg_value'       => ($customer->purchase_count) ? round($customer->purchase_value / $customer->purchase_count, 2) : 'n/a',
//                'stat_avg_count'  => 0,
//                'stat_avg_spend'  => 0,
//                'stat_avg_value'  => 0,
//                'currency_sign'   => edd_currency_symbol(),
//                'last_order_date' => $lasOrderData
//            ];
//            $beforeHtml = $this->formatSummaryData($summaryData);
//        }

        return [
            'data'         => $formattedOrders,
            'total'        => $totalCount,
            'sidebar_html' => $beforeHtml,
            'has_recount'  => $hasRecount,
            'columns_config' => [
                'order' => [
                    'label' => __('Order', 'fluent-crm'),
                    'width' => '100px'
                ],
                'date' => [
                    'label' => __('Date', 'fluent-crm')
                ],
                'status' => [
                    'label' => __('Status', 'fluent-crm'),
                    'width' => '140px'
                ],
                'total' => [
                    'label' => __('Total', 'fluent-crm'),
                    'width' => '120px'
                ],
                'action' => [
                    'label' => __('Actions', 'fluent-crm'),
                    'width' => '100px'
                ]
            ]
        ];
    }

    public function payformSubmissions($data, $subscriber)
    {
        if (!defined('WPPAYFORM_VERSION')) {
            return $data;
        }
        $app = fluentCrm();
        $page = intval($app->request->get('page', 1));
        $per_page = intval($app->request->get('per_page', 10));
        $query = fluentCrmDb()->table('wpf_submissions')
            ->select([
                'wpf_submissions.id',
                'wpf_submissions.form_id',
                'wpf_submissions.currency',
                'wpf_submissions.payment_status',
                'wpf_submissions.payment_total',
                'wpf_submissions.payment_method',
                'wpf_submissions.created_at',
                'posts.post_title'
            ])
            ->join('posts', 'posts.ID', '=', 'wpf_submissions.form_id')
            ->where(function ($query) use ($subscriber) {
                $query->where('wpf_submissions.customer_email', '=', $subscriber->email);
                if ($subscriber->user_id) {
                    $query->orWhere('wpf_submissions.user_id', '=', $subscriber->user_id);
                }
            })
            ->where('wpf_submissions.payment_total', '>', 0)
            ->limit($per_page)
            ->offset($per_page * ($page - 1))
            ->orderBy('wpf_submissions.id', 'desc');

        $total = $query->count();
        $submissions = $query->get();
        $formattedSubmissions = [];
        foreach ($submissions as $submission) {
            $submissionUrl = admin_url('admin.php?page=wppayform.php#/edit-form/' . $submission->form_id . '/entries/' . $submission->id . '/view');
            $actionUrl = '<a target="_blank" href="' . $submissionUrl . '">View Submission</a>';
            $formattedSubmissions[] = [
                'id'             => '#' . $submission->id,
                'Form Title'     => $submission->post_title,
                'Payment Total'  => wpPayFormFormatMoney($submission->payment_total, $subscriber->form_id),
                'Payment Status' => $submission->payment_status,
                'Payment Method' => $submission->payment_method,
                'Submitted At'   => $submission->created_at,
                'action'         => $actionUrl
            ];
        }

        return [
            'total' => $total,
            'data'  => $formattedSubmissions
        ];

    }

    public function formatSummaryData($data, $bodyOnly = false)
    {
        $blocks = [];
        if (!empty($data['first_order_date'])) {
            $blocks['Customer Since'] = date(get_option('date_format'), strtotime($data['first_order_date']));
        }

        if (!empty($data['last_order_date'])) {
            $blocks['Last Order'] = date(get_option('date_format'), strtotime($data['last_order_date']));
        }

        $blocks['Order Count (paid)'] = $data['order_count'] . $this->getPercentChangeHtml($data['order_count'], $data['stat_avg_count']);
        $blocks['Lifetime Value'] = $data['currency_sign'] . $data['lifetime_value'];
        $blocks['AOV'] = $data['currency_sign'] . $data['avg_value'] . $this->getPercentChangeHtml($data['avg_value'], $data['stat_avg_value']);


        $html = '<div class="fc_payment_summary"><h3 class="history_title">' . esc_html__("Customer Summary", "fluent-crm") . '</h3><div class="fc_history_widget">';

        $body = '';


        $body .= '<ul class="fc_full_listed">';
        foreach ($blocks as $title => $block) {
            $body .= '<li><span class="fc_list_sub">' . $title . '</span><span class="fc_list_value">' . $block . '</span></li>';
        }

        if (!empty($data['purchased_products'])) {
            $body .= '<li><b>' . esc_html__("Purchased Products", "fluent-crm") . '</b><hr /><ul class="fc_list">';
            foreach ($data['purchased_products'] as $product) {
                $body .= '<li><a target="_blank" rel="nofollow" href="' . $product->guid . '">' . $product->post_title . '</a></li>';
            }
            $body .= '</ul></li>';
        }

        $body .= '</ul>';

        if($bodyOnly) {
            return $body;
        }

        return $body.'</div></div>';
    }

    private function getPercentChangeHtml($value, $refValue)
    {
        if (!$refValue || !$value) {
            return '';
        }
        $change = $value - $refValue;
        $percentChange = absint(ceil($change / $refValue * 100));
        if ($change >= 0) {
            return '<span class="el-icon-caret-top fc_positive fc_change_ref">' . $percentChange . '%' . '</span>';
        } else {
            return '<span class="el-icon-caret-bottom fc_negative fc_change_ref">' . $percentChange . '%' . '</span>';
        }
    }

}
