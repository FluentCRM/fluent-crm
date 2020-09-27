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
            'title'       => 'Wait X Days/Hours',
            'description' => 'Wait defined timespan before execute the next action',
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
            'title'     => 'Wait X Days/Hours',
            'sub_title' => 'Wait defined timespan before execute the next action',
            'fields'    => [
                'wait_time_amount' => [
                    'label' => 'Wait Time',
                    'type'  => 'input-number'
                ],
                'wait_time_unit'   => [
                    'label'   => 'Wait Time Unit',
                    'type'    => 'select',
                    'options' => [
                        [
                            'id'    => 'days',
                            'title' => 'Days'
                        ],
                        [
                            'id'    => 'hours',
                            'title' => 'Hours'
                        ],
                        [
                            'id'    => 'minutes',
                            'title' => 'Minutes'
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
