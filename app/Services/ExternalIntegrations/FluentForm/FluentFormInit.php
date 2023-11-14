<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentForm;

use FluentCrm\Framework\Support\Arr;
use FluentForm\App\Helpers\Helper;

class FluentFormInit
{
    public function init()
    {
        if (defined('FLUENTFORM_FRAMEWORK_UPGRADE')) {
            new \FluentCrm\App\Services\ExternalIntegrations\FluentForm\Bootstrap();
        }

        add_filter('fluentform/submissions_widgets', array($this, 'pushContactWidget'), 10, 3);
    }

    public function pushContactWidget($widgets, $resources, $submission)
    {
        $userId = $submission->user_id;

        if (!$userId) {
            $userInputs = json_decode($submission->response, true);

            if (!$userInputs) {
                return $widgets;
            }

            $maybeEmail = Arr::get($userInputs, 'email');

            if (!$maybeEmail) {
                $emailField = Helper::getFormMeta($submission->form_id, '_primary_email_field');
                if (!$emailField) {
                    return $widgets;
                }
                $maybeEmail = Arr::get($userInputs, $emailField);
            }
        } else {
            $maybeEmail = $userId;
        }

        if (!$maybeEmail) {
            return $widgets;
        }

        $profileHtml = fluentcrm_get_crm_profile_html($maybeEmail, true);
        if (!$profileHtml) {
            return $widgets;
        }

        $widgets['fluent_crm'] = [
            'title'   => __('FluentCRM Profile', 'fluent-crm'),
            'content' => $profileHtml
        ];
        return $widgets;
    }
    
}
