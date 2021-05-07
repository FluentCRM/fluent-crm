<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;

class WaitTimeAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'fluentcrm_wait_times';
        $this->priority = 10;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'title'       => __('Wait X Days/Hours', 'fluent-crm'),
            'description' => __('Wait defined timespan before execute the next action', 'fluent-crm'),
            'icon' => fluentCrmMix('images/funnel_icons/wait_time.svg'),
            'settings'    => [
                'wait_time_amount' => '',
                'wait_time_unit'   => 'days'
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => __('Wait X Days/Hours', 'fluent-crm'),
            'sub_title' => __('Wait defined timespan before execute the next action', 'fluent-crm'),
            'fields'    => [
                'wait_time_amount' => [
                    'label' => __('Wait Time', 'fluent-crm'),
                    'type'  => 'input-number'
                ],
                'wait_time_unit'   => [
                    'label'   => __('Wait Time Unit', 'fluent-crm'),
                    'type'    => 'select',
                    'options' => [
                        [
                            'id'    => 'days',
                            'title' => __('Days', 'fluent-crm')
                        ],
                        [
                            'id'    => 'hours',
                            'title' => __('Hours', 'fluent-crm')
                        ],
                        [
                            'id'    => 'minutes',
                            'title' => __('Minutes', 'fluent-crm')
                        ]
                    ]
                ]
            ]
        ];
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id);
    }
}
