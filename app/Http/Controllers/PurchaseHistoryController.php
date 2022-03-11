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
        $provider = $this->request->get('provider');
        $subscriberId = intval($this->request->get('id'));
        $subscriber = Subscriber::where('id', $subscriberId)->first();

        $data = apply_filters('fluentcrm_get_purchase_history_'.$provider, [
            'orders' => [],
            'total' => 0
        ], $subscriber);

        return $this->sendSuccess([
            'orders' => $data
        ]);
    }

}
