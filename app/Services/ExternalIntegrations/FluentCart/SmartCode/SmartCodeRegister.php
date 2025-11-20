<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentCart\SmartCode;

class SmartCodeRegister
{
    public static function push()
    {
        $smartCodesConfig = [
            'cart_order' => [
                'title' => __('Cart Order', 'fluent-crm'),
                'description' => __('Order-related smart codes', 'fluent-crm'),
                'shortcodes' => [
                    '{{cart_order.order_id}}' => __('Order ID', 'fluent-crm'),
                    '{{cart_order.status}}' => __('Order Status', 'fluent-crm'),
                    '{{cart_order.invoice_no}}' => __('Invoice Number', 'fluent-crm'),
                    '{{cart_order.type}}' => __('Order Type', 'fluent-crm'),
                    '{{cart_order.payment_method}}' => __('Payment Method', 'fluent-crm'),
                    '{{cart_order.payment_method_title}}' => __('Payment Method Title', 'fluent-crm'),
                    '{{cart_order.payment_status}}' => __('Payment Status', 'fluent-crm'),
                    '{{cart_order.currency}}' => __('Currency', 'fluent-crm'),
                    '{{cart_order.subtotal}}' => __('Order Subtotal', 'fluent-crm'),
                    '{{cart_order.shipping_total}}' => __('Shipping Total', 'fluent-crm'),
                    '{{cart_order.total_amount}}' => __('Total Amount', 'fluent-crm'),
                    '{{cart_order.note}}' => __('Order Note', 'fluent-crm'),
                    '{{cart_order.completed_at}}' => __('Order Completion Date', 'fluent-crm'),
                    '{{cart_order.total_refund}}' => __('Total Refund', 'fluent-crm'),
                    '{{cart_order.created_at}}' => __('Order Creation Date', 'fluent-crm'),
                    '{{cart_order.total_paid}}' => __('Total Paid Amount', 'fluent-crm'),
                    '{{cart_order.shipping_status}}' => __('Shipping Status', 'fluent-crm'),
//                    '##cart_order.order_url##' => 'Order URL',
                ]
            ],
            'cart_customer' => [
                'title' => __('Cart Customer', 'fluent-crm'),
                'description' => __('Customer-related smart codes', 'fluent-crm'),
                'shortcodes' => [
                    '{{cart_customer.user_id}}' => __('User ID', 'fluent-crm'),
                    '{{cart_customer.contact_id}}' => __('Customer Contact ID', 'fluent-crm'),
                    '{{cart_customer.email}}' => __('Customer Email', 'fluent-crm'),
                    '{{cart_customer.first_name}}' => __('Customer First Name', 'fluent-crm'),
                    '{{cart_customer.last_name}}' => __('Customer Last Name', 'fluent-crm'),
                    '{{cart_customer.status}}' => __('Customer Status', 'fluent-crm'),
                    '{{cart_customer.purchase_value}}' => __('Total Purchase Value', 'fluent-crm'),
                    '{{cart_customer.purchase_count}}' => __('Total Purchase Count', 'fluent-crm'),
                    '{{cart_customer.first_purchase_date}}' => __('First Purchase Date', 'fluent-crm'),
                    '{{cart_customer.last_purchase_date}}' => __('Last Purchase Date', 'fluent-crm'),
                    '{{cart_customer.aov}}' => __('Average Order Value (AOV)', 'fluent-crm'),
                    '{{cart_customer.notes}}' => __('Customer Notes', 'fluent-crm'),
                    '{{cart_customer.country}}' => __('Customer Country', 'fluent-crm'),
                    '{{cart_customer.city}}' => __('Customer City', 'fluent-crm'),
                    '{{cart_customer.state}}' => __('Customer State', 'fluent-crm'),
                    '{{cart_customer.postcode}}' => __('Customer Postcode', 'fluent-crm'),
                ]
            ],
//            'cart_transaction' => [
//                'title' => __('Cart Transaction'),
//                'description' => __('Transaction-related smart codes'),
//                'shortcodes' => [
//                    '{{cart_transaction.transaction_id}}' => 'Transaction ID',
//                    '{{cart_transaction.amount}}' => 'Transaction Amount',
//                    '{{cart_transaction.order_id}}' => 'Order ID',
//                    '{{cart_transaction.order_type}}' => 'Order Type',
//                    '{{cart_transaction.payment_method}}' => 'Payment Method',
//                    '{{cart_transaction.payment_mode}}' => 'Payment Mode',
//                    '{{cart_transaction.payment_method_type}}' => 'Payment Method Type',
//                    '{{cart_transaction.transaction_type}}' => 'Transaction Type',
//                    '{{cart_transaction.subscription_id}}' => 'Subscription ID',
//                    '{{cart_transaction.status}}' => 'Transaction Status',
//                    '{{cart_transaction.total}}' => 'Transaction Total',
//                    '{{cart_transaction.rate}}' => 'Exchange Rate',
//                ]
//            ],
//            'cart_receipt' => [
//                'title' => __('Cart Receipts'),
//                'description' => __('Transaction-related smart codes'),
//                'shortcodes' => [
//                    '{{cart_receipt.payment_summary}}' => 'Payment Summary',
//                ]
//            ],

        ];

        // Dynamically register filters for each smart code group

        foreach ($smartCodesConfig as $key => $config) {
            add_filter('fluent_crm_funnel_context_smart_codes', function ($codes) use ($key, $config) {
                $codes[] = [
                    'key' => $key,
                    'title' => $config['title'],
                    'description' => $config['description'],
                    'shortcodes' => $config['shortcodes']
                ];
                return $codes;
            });
        }
    }
}
