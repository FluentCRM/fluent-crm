<?php

namespace FluentCrm\App\Hooks\Handlers;

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

        $customer_orders = wc_get_orders(
            apply_filters(
                'woocommerce_my_account_my_orders_query',
                array(
                    'billing_email' => $subscriber->email,
                    'limit'         => $per_page,
                    'offset'        => $per_page * ($page - 1),
                    'order'         => 'DESC',
                    'paginate'      => true
                )
            )
        );

        $formattedOrders = [];
        foreach ($customer_orders->orders as $customer_order) {
            $order = wc_get_order($customer_order);
            $item_count = $order->get_item_count() - $order->get_item_count_refunded();
            $actionsHtml = '<a target="_blank" href="' . $order->get_edit_order_url() . '">' . __('View Order Details', 'fluent-crm') . '</a>';
            $formattedOrders[] = [
                'order'   => '#' . $order->get_order_number(),
                'date'    => esc_html(wc_format_datetime($order->get_date_created())),
                'status'  => $order->get_status(),
                'total'   => wp_kses_post(sprintf(_n('%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce'), $order->get_formatted_order_total(), $item_count)),
                'actions' => $actionsHtml
            ];
        }

        return [
            'data'  => $formattedOrders,
            'total' => $customer_orders->total
        ];
    }

    public function eddOrders($data, $subscriber)
    {
        if (!class_exists('\Easy_Digital_Downloads')) {
            return $data;
        }

        $app = fluentCrm();
        $page = intval($app->request->get('page', 1));
        $per_page = intval($app->request->get('per_page', 1));
        $customer = new \EDD_Customer($subscriber->email);
        if (!$customer) {
            return $data;
        }
        $orders = edd_get_users_purchases($subscriber->email, $per_page, true, 'any');
        $formattedOrders = [];
        if($orders) {
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

        return [
            'data'  => $formattedOrders,
            'total' => count($customer->get_payment_ids())
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
        $query = wpFluent()->table('wpf_submissions')
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
            $submissionUrl = admin_url('admin.php?page=wppayform.php#/edit-form/'.$submission->form_id.'/entries/'.$submission->id.'/view');
            $actionUrl = '<a target="_blank" href="'.$submissionUrl.'">View Submission</a>';
            $formattedSubmissions[] = [
                'id' => '#'.$submission->id,
                'Form Title' => $submission->post_title,
                'Payment Total' => wpPayFormFormatMoney($submission->payment_total, $subscriber->form_id),
                'Payment Status' => $submission->payment_status,
                'Payment Method' => $submission->payment_method,
                'Submitted At' => $submission->created_at,
                'action' => $actionUrl
            ];
        }

        return [
            'total' => $total,
            'data' => $formattedSubmissions
        ];

    }
}
