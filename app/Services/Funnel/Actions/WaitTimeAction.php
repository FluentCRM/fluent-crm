<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Services\Funnel\BaseAction;

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
            'category' => __('CRM', 'fluent-crm'),
            'title'       => __('Wait X Days/Hours', 'fluent-crm'),
            'description' => __('Wait defined timespan before execute the next action', 'fluent-crm'),
            'icon'        => 'fc-icon-wait_time',
            'settings'    => [
                'wait_time_amount'  => '',
                'wait_time_unit'    => 'days',
                'is_timestamp_wait' => '',
                'wait_date_time'    => ''
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => __('Wait X Days/Hours', 'fluent-crm'),
            'sub_title' => __('Wait defined timespan before execute the next action', 'fluent-crm'),
            'fields'    => [
                'wait_time_amount'  => [
                    'label'      => __('Wait Time', 'fluent-crm'),
                    'type'       => 'input-number',
                    'dependency' => [
                        'depends_on' => 'is_timestamp_wait',
                        'value'      => 'yes',
                        'operator'   => '!=',
                    ],
                ],
                'wait_time_unit'    => [
                    'label'      => __('Wait Time Unit', 'fluent-crm'),
                    'type'       => 'select',
                    'options'    => [
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
                    ],
                    'dependency' => [
                        'depends_on' => 'is_timestamp_wait',
                        'value'      => 'yes',
                        'operator'   => '!=',
                    ],
                ],
                'is_timestamp_wait' => [
                    'check_label' => __('Wait till a specific date and time', 'fluent-crm'),
                    'type'        => 'yes_no_check'
                ],
                'wait_date_time'    => [
                    'label'       => __('Specify Date and Time', 'fluent-crm'),
                    'type'        => 'date_time',
                    'placeholder' => __('Select Date & Time', 'fluent-crm'),
                    'inline_help' => __('Please input date and time and this step will be executed after that time (TimeZone will be as per your WordPress Date Time Zone)', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'is_timestamp_wait',
                        'value'      => 'yes',
                        'operator'   => '=',
                    ]
                ]
            ]
        ];
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        //FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id);
    }
}
