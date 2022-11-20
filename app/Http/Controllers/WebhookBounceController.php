<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Services\ExternalIntegrations\MailComplaince\Webhook;
use FluentCrm\Framework\Request\Request;

/**
 *  WebhookBounceController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class WebhookBounceController extends Controller
{
    private $validServices = ['mailgun', 'pepipost', 'postmark', 'sendgrid', 'sparkpost'];

    public function handleBounce(Request $request, $serviceName, $securityCode)
    {
        
        if (!in_array($serviceName, $this->validServices)) {
            // This is a custom bounce handler
            return apply_filters('fluent_crm_handle_bounce_' . $serviceName, [
                'success' => 0,
                'message' => '',
                'service' => $serviceName,
                'result'  => '',
                'time'    => time()
            ], $request, $securityCode);
        }

        if ($securityCode != $this->getSecurityCode()) {
            return $this->getError();
        }

        $result = (new Webhook())->handle($serviceName, $request);

        return [
            'success' => 1,
            'message' => 'recorded',
            'service' => $serviceName,
            'result'  => $result,
            'time'    => time()
        ];

    }

    private function getSecurityCode()
    {
        $code = fluentcrm_get_option('_fc_bounce_key');

        if (!$code) {
            $code = 'fcrm_' . substr(md5(wp_generate_uuid4()), 0, 14);
            fluentcrm_update_option('_fc_bounce_key', $code);
        }

        return $code;
    }

    private function getError()
    {
        return [
            'status'  => false,
            'message' => __('Invalid Data or Security Code', 'fluent-crm')
        ];
    }
}
