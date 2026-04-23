<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentCart\SmartCode;

class SmartCodeRegister
{
    public static function push()
    {
        $smartCodesConfig = [
            'cart_order' => [
                'title' => __('Cart Order'),
                'description' => __('Order-related smart codes'),
                'shortcodes' => [
                    '{{cart_order.order_id}}' => 'Order ID',
                    '{{cart_order.status}}' => 'Order Status',
                    '{{cart_order.invoice_no}}' => 'Invoice Number',
                    '{{cart_order.type}}' => 'Order Type',
                    '{{cart_order.payment_method}}' => 'Payment Method',
                    '{{cart_order.payment_method_title}}' => 'Payment Method Title',
                    '{{cart_order.payment_status}}' => 'Payment Status',
                    '{{cart_order.currency}}' => 'Currency',
                    '{{cart_order.subtotal}}' => 'Order Subtotal',
                    '{{cart_order.shipping_total}}' => 'Shipping Total',
                    '{{cart_order.total_amount}}' => 'Total Amount',
                    '{{cart_order.note}}' => 'Order Note',
                    '{{cart_order.completed_at}}' => 'Order Completion Date',
                    '{{cart_order.total_refund}}' => 'Total Refund',
                    '{{cart_order.created_at}}' => 'Order Creation Date',
                    '{{cart_order.total_paid}}' => 'Total Paid Amount',
                    '{{cart_order.shipping_status}}' => 'Shipping Status',
//                    '##cart_order.order_url##' => 'Order URL',
                ]
            ],
            'cart_customer' => [
                'title' => __('Cart Customer'),
                'description' => __('Customer-related smart codes'),
                'shortcodes' => [
                    '{{cart_customer.user_id}}' => 'User ID',
                    '{{cart_customer.contact_id}}' => 'Customer Contact ID',
                    '{{cart_customer.email}}' => 'Customer Email',
                    '{{cart_customer.first_name}}' => 'Customer First Name',
                    '{{cart_customer.last_name}}' => 'Customer Last Name',
                    '{{cart_customer.status}}' => 'Customer Status',
                    '{{cart_customer.purchase_value}}' => 'Total Purchase Value',
                    '{{cart_customer.purchase_count}}' => 'Total Purchase Count',
                    '{{cart_customer.first_purchase_date}}' => 'First Purchase Date',
                    '{{cart_customer.last_purchase_date}}' => 'Last Purchase Date',
                    '{{cart_customer.aov}}' => 'Average Order Value (AOV)',
                    '{{cart_customer.notes}}' => 'Customer Notes',
                    '{{cart_customer.country}}' => 'Customer Country',
                    '{{cart_customer.city}}' => 'Customer City',
                    '{{cart_customer.state}}' => 'Customer State',
                    '{{cart_customer.postcode}}' => 'Customer Postcode',
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
