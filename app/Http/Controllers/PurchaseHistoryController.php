<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;

/**
 *  PurchaseHistoryController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class PurchaseHistoryController extends Controller
{
    public function historyProviders()
    {
        return $this->sendSuccess([
            'providers' => Helper::getPurchaseHistoryProviders()
        ]);
    }

    public function getOrders()
    {
        $provider = $this->request->getSafe('provider');
        $subscriberId = $this->request->getSafe('id', 'intval');
        $subscriber = Subscriber::findOrFail($subscriberId);

        /**
         * Determine the purchase history data for a specific provider in FluentCRM.
         *
         * The dynamic portion of the hook name, `$provider`, refers to the purchase history provider.
         *
         * @since 1.0.0
         *
         * @param array {
         *     The purchase history data.
         *
         *     @type array $orders List of orders.
         *     @type int   $total  Total number of orders.
         * }
         * @param object $subscriber The subscriber object.
         */
        $data = apply_filters('fluent_crm/purchase_history_'.$provider, [
            'orders' => [],
            'total' => 0
        ], $subscriber);

        return $this->sendSuccess([
            'orders' => $data
        ]);
    }

}
