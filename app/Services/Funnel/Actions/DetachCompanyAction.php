<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;

class DetachCompanyAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'detach_contact_from_company';
        $this->priority = 22;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'category' => __('CRM', 'fluent-crm'),
            'title'       => __('Remove From Company', 'fluent-crm'),
            'description' => __('Remove this contact from the selected company', 'fluent-crm'),
            'icon' => 'fc-icon-removed_list',//fluentCrmMix('images/funnel_icons/list_remove.svg'),
            'settings'    => [
                'company' => null
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => __('Remove Contact from the Selected Company', 'fluent-crm'),
            'sub_title' => __('Select Company that you want to remove from targeted Contact', 'fluent-crm'),
            'fields'    => [
                'company' => [
                    'type'        => 'option_selectors',
                    'option_key' => 'companies',
                    'label'       => __('Select Company', 'fluent-crm'),
                    'placeholder' => __('Select Company', 'fluent-crm'),
                ]
            ]
        ];
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        if (empty($sequence->settings['company'])) {
            FunnelHelper::changefunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
            return;
        }

        $company = $sequence->settings['company'];

        $renewedSubscriber = Subscriber::where('id', $subscriber->id)->first();
        $renewedSubscriber->detachCompanies([$company]);
    }
}
