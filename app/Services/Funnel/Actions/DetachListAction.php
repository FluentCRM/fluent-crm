<?php

namespace FluentCrm\App\Services\Funnel\Actions;

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
            'title'       => 'Remove From List',
            'description' => 'Remove this contact from the selected lists',
            'icon' => fluentCrmMix('images/funnel_icons/list_remove.svg'),
            'settings'    => [
                'lists' => []
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => 'Remove Contact from the Selected Lists',
            'sub_title' => 'Select Lists that you want to remove from targeted Contact',
            'fields'    => [
                'lists' => [
                    'type'        => 'option_selectors',
                    'option_key' => 'lists',
                    'is_multiple' => true,
                    'label'       => 'Select Lists',
                    'placeholder' => 'Select List'
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

        $subscriber->lists()->detach($lists);
        FunnelHelper::changefunnelSubSequenceStatus($funnelSubscriberId, $sequence->id);
    }
}
