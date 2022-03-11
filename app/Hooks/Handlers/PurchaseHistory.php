<?php

namespace FluentCrm\App\Hooks\Handlers;

/**
 *  PurchaseHistory Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class PurchaseHistory
{
    public function wooOrders($data, $subscriber)
    {
        if (!defined('WC_PLUGIN_FILE')) {
            return $data;
        }

        $app = fluentCrm();
        $page = intval($app->request->get('page', 1));
        $per_page = intval($app->request->get('per_page', 10));

        $args = array(
            'billing_email' => $subscriber->email,
            'limit'         => $per_page,
            'offset'        => $per_page * ($page - 1),
            'paginate'      => true
        );


        if ($userId = $subscriber->getWpUserId()) {
            $args['customer_id'] = $userId;
            unset($args['billing_email']);
        }

        $customer_orders = wc_get_orders($args);

        $formattedOrders = [];
        $lastOrder = false;
        foreach ($customer_orders->orders as $order) {
            if (!$lastOrder) {
                $lastOrder = $order;
            }

            $item_count = $order->get_item_count() - $order->get_item_count_refunded();
            $actionsHtml = '<a target="_blank" href="' . $order->get_edit_order_url() . '">' . __('View Order Details', 'fluent-crm') . '</a>';
            $formattedOrders[] = [
                'order'   => '#' . $order->get_order_number(),
                'date'    => esc_html(wc_format_datetime($order->get_date_created())),
                'status'  => $order->get_status(),
                'total'   => wp_kses_post(sprintf(_n('%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'fluent-crm'), $order->get_formatted_order_total(), $item_count)),
                'actions' => $actionsHtml
            ];
        }

        $sidebarHtml = apply_filters('fluentcrm_woo_purchase_sidebar_html', '', $subscriber, $page);

        if ($sidebarHtml == '' && $page == 1 && $formattedOrders) {
            $customerQuery = fluentCrmDb()->table('wc_customer_lookup')
                ->where('email', $subscriber->email);
            if ($subscriber->user_id) {
                $customerQuery = $customerQuery->orWhere('user_id', $subscriber->user_id);
            }

            $customer = $customerQuery->first();
            if ($customer) {
                $orderStats = fluentCrmDb()->table('wc_order_stats')
                    ->where('customer_id', $customer->customer_id)
                    ->get();

                $lifetimeValue = 0;
                $orderCount = count($orderStats);
                $orderIds = [];
                foreach ($orderStats as $order) {
                    $lifetimeValue += $order->net_total;
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
                    'last_order_date'    => $lastOrder->get_date_created('db'),
                    'purchased_products' => $orderedProducts
                ];
                $sidebarHtml = $this->formatSummaryData($summaryData);
            }
        }

        return [
            'data'         => $formattedOrders,
            'sidebar_html' => $sidebarHtml,
            'total'        => $customer_orders->total
        ];
    }

    public function eddOrders($data, $subscriber)
    {
        if (!class_exists('\Easy_Digital_Downloads')) {
            return $data;
        }

        $app = fluentCrm();
        $page = intval($app->request->get('page', 1));
        set_query_var('paged', $page);

        $per_page = intval($app->request->get('per_page', 10));
        $customer = new \EDD_Customer($subscriber->email);
        if (!$customer) {
            return $data;
        }
        $orders = edd_get_users_purchases($subscriber->email, $per_page, true, 'any');
        $formattedOrders = [];
        if ($orders) {
            foreach ($orders as $order) {
                $payment = new \EDD_Payment($order->ID);
                $orderActionHtml = '<a target="_blank" href="' . add_query_arg('id', $payment->ID, admin_url('edit.php?post_type=download&page=edd-payment-history&view=view-order-details')) . '">' . __('View Order Details', 'fluent-crm') . '</a>';
                $formattedOrders[] = [
                    'order'  => '#' . $payment->number,
                    'date'   => date_i18n(get_option('date_format'), strtotime($payment->date)),
                    'status' => $payment->status_nicename,
                    'total'  => edd_currency_filter(edd_format_amount($payment->total)),
                    'action' => $orderActionHtml
                ];
            }
        }

        $beforeHtml = apply_filters('fluentcrm_edd_purchase_sidebar_html', '', $subscriber, $page);

        if (!$beforeHtml && $subscriber->user_id && $page == 1 && $formattedOrders) {
            $summaryData = [
                'order_count'     => $customer->purchase_count,
                'lifetime_value'  => $customer->purchase_value,
                'avg_value'       => round($customer->purchase_value / $customer->purchase_count, 2),
                'stat_avg_count'  => 0,
                'stat_avg_spend'  => 0,
                'stat_avg_value'  => 0,
                'currency_sign'   => edd_currency_symbol(),
                'last_order_date' => $orders[0]->post_date
            ];
            $beforeHtml = $this->formatSummaryData($summaryData);
        }

        return [
            'data'         => $formattedOrders,
            'total'        => count($customer->get_payment_ids()),
            'sidebar_html' => $beforeHtml
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

    public function formatSummaryData($data)
    {
        $blocks = [
            'Orders'      => $data['order_count'] . $this->getPercentChangeHtml($data['order_count'], $data['stat_avg_count']),
            'Total Spend' => $data['currency_sign'] . $data['lifetime_value'],
            'AOV'         => $data['currency_sign'] . $data['avg_value'] . $this->getPercentChangeHtml($data['avg_value'], $data['stat_avg_value'])
        ];

        if (isset($data['last_order_date'])) {
            $blocks['Last Ordered'] = human_time_diff(strtotime($data['last_order_date'])) . ' ago';
        }


        $html = '<div class="fc_payment_summary"><h3 class="history_title">' . esc_html__("Customer Summary", "fluent-crm") . '</h3>';
        $html .= '<div class="fc_history_widget"><ul class="fc_full_listed">';
        foreach ($blocks as $title => $block) {
            $html .= '<li><span class="fc_list_sub">' . $title . '</span><span class="fc_list_value">' . $block . '</span></li>';
        }

        if (!empty($data['purchased_products'])) {
            $html .= '<li><b>' . esc_html__("Purchased Products", "fluent-crm") . '</b><hr /><ul class="fc_list">';
            foreach ($data['purchased_products'] as $product) {
                $html .= '<li><a target="_blank" rel="nofollow" href="' . $product->guid . '">' . $product->post_title . '</a></li>';
            }
            $html .= '</ul></li>';
        }

        $html .= '</ul></div></div>';
        return $html;
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
