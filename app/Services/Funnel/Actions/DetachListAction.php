<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;

class DetachListAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'detach_contact_from_list';
        $this->priority = 22;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'category' => __('CRM', 'fluent-crm'),
            'title'       => __('Remove From List', 'fluent-crm'),
            'description' => __('Remove this contact from the selected lists', 'fluent-crm'),
            'icon' => 'fc-icon-removed_list',//fluentCrmMix('images/funnel_icons/list_remove.svg'),
            'settings'    => [
                'lists' => []
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => __('Remove Contact from the Selected Lists', 'fluent-crm'),
            'sub_title' => __('Select Lists that you want to remove from targeted Contact', 'fluent-crm'),
            'fields'    => [
                'lists' => [
                    'type'        => 'option_selectors',
                    'option_key' => 'lists',
                    'is_multiple' => true,
                    'label'       => __('Select Lists', 'fluent-crm'),
                    'placeholder' => __('Select List', 'fluent-crm'),
                    'creatable' => true
                ]
            ]
        ];
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        if (empty($sequence->settings['lists']) || !is_array($sequence->settings['lists'])) {
            FunnelHelper::changefunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
            return;
        }

        $lists = $sequence->settings['lists'];

        $renewedSubscriber = Subscriber::where('id', $subscriber->id)->first();
        $renewedSubscriber->detachLists($lists);

        //FunnelHelper::changefunnelSubSequenceStatus($funnelSubscriberId, $sequence->id);
    }
}
