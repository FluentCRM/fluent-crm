<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentForm;

use FluentCrm\Includes\Helpers\Arr;
use FluentForm\App\Helpers\Helper;

class FluentFormInit
{
    public function init()
    {
        new \FluentCrm\App\Services\ExternalIntegrations\FluentForm\Bootstrap(wpFluentForm());
        add_filter('fluentform_single_entry_widgets', array($this, 'pushContactWidget'), 10, 2);
    }

    public function pushContactWidget($widgets, $entryData)
    {
        $userId = $entryData['submission']->user_id;
        if($userId) {
            $maybeEmail = Arr::get($entryData['submission']->user, 'email');
            if(!$maybeEmail) {
                $maybeEmail = $userId;
            }
        } else {
            $userInputs = $entryData['submission']->user_inputs;
            $maybeEmail = Arr::get($userInputs, 'email');
            if(!$maybeEmail) {
                $emailField = Helper::getFormMeta($entryData['submission']->form_id, '_primary_email_field');
                if(!$emailField) {
                    return $widgets;
                }
                $maybeEmail = Arr::get($userInputs, $emailField);
            }
        }

        if(!$maybeEmail) {
            return $widgets;
        }

        $profileHtml = fluentcrm_get_crm_profile_html($maybeEmail, true);
        if(!$profileHtml) {
            return $widgets;
        }

        $widgets['fluent_crm'] = [
            'title' => __('FluentCRM Profile', 'fluent-crm'),
            'content' => $profileHtml
        ];
        return $widgets;
    }
}
