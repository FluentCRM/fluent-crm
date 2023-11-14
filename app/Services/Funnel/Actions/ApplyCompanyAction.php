<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;

class ApplyCompanyAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'add_contact_to_company';
        $this->priority = 20;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'category' => __('CRM', 'fluent-crm'),
            'title'       => __('Apply Company', 'fluent-crm'),
            'description' => __('Add contact to the selected company', 'fluent-crm'),
            'icon' => 'fc-icon-apply_list',//fluentCrmMix('images/funnel_icons/apply_list.svg'),
            'settings'    => [
                'company' => null
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => __('Apply Company to the contact', 'fluent-crm'),
            'sub_title' => __('Select which list will be added to the contact', 'fluent-crm'),
            'fields'    => [
                'company' => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'companies',
                    'label'       => __('Select Company', 'fluent-crm'),
                    'placeholder' => __('Select Company', 'fluent-crm')
                ]
            ]
        ];
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        if (empty($sequence->settings['company'])) {
            FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
            return;
        }

        $company = $sequence->settings['company'];

        $renewedSubscriber = Subscriber::where('id', $subscriber->id)->first();
        $renewedSubscriber->attachCompanies([$company]);
    }
}
