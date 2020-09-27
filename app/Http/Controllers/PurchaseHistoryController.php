<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;

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

        $data = $this->app->applyCustomFilters('get_purchase_history_'.$provider, [
            'orders' => [],
            'total' => 0
        ], $subscriber);

        return $this->sendSuccess([
            'orders' => $data
        ]);
    }

}
