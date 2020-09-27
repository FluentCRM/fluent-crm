<?php

namespace FluentCrm\App\Hooks\Handlers;


class Integrations
{
    public function register()
    {
        if(defined('FLUENTFORM')) {
            new \FluentCrm\App\Services\ExternalIntegrations\FluentForm\Bootstrap(wpFluentForm());
        }
    }
}
