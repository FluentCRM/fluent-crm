<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;

class ApplyListAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'add_contact_to_list';
        $this->priority = 20;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'title'       => 'Apply List',
            'description' => 'Add this contact to the selected lists',
            'icon' => fluentCrmMix('images/funnel_icons/apply_list.svg'),
            'settings'    => [
                'lists' => []
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => 'Apply List to the contact',
            'sub_title' => 'Select which list will be added to the contact',
            'fields'    => [
                'lists' => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'lists',
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
            FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
            return;
        }

        $lists = $sequence->settings['lists'];
        $lists = array_combine($lists, array_fill(
            0, count($lists), ['object_type' => 'FluentCrm\App\Models\Lists']
        ));

        $subscriber->lists()->sync($lists, false);
        FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
    }
}
