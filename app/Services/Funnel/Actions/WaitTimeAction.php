<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\Framework\Support\Arr;

class WaitTimeAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'fluentcrm_wait_times';
        $this->priority = 10;
        parent::__construct();

        add_filter('fluentcrm_funnel_sequence_filtered_' . $this->actionName, array($this, 'gettingAction'), 10, 2);
    }

    public function getBlock()
    {
        return [
            'category'    => __('CRM', 'fluent-crm'),
            'title'       => __('Wait X Days/Hours', 'fluent-crm'),
            'description' => __('Wait defined timespan before execute the next action', 'fluent-crm'),
            'icon'        => 'fc-icon-wait_time',
            'settings'    => [
                'wait_type'         => 'unit_wait',
                'wait_time_amount'  => '',
                'wait_time_unit'    => 'days',
                'is_timestamp_wait' => '',
                'wait_date_time'    => '',
                'to_day'            => [],
                'to_day_time'       => ''
            ]
        ];
    }

    public function gettingAction($sequence, $funnel)
    {
        if (empty($sequence['settings']['wait_type'])) {
            if (Arr::get($sequence, 'settings.is_timestamp_wait') == 'yes') {
                $sequence['settings']['wait_type'] = 'timestamp_wait';
            } else {
                $sequence['settings']['wait_type'] = 'unit_wait';
            }

            $sequence['settings']['to_day'] = [];
            $sequence['settings']['to_day_time'] = '';
        }

        return $sequence;
    }

    public function getBlockFields()
    {
        return [
            'title'     => __('Wait X Days/Hours', 'fluent-crm'),
            'sub_title' => __('Wait defined timespan before execute the next action', 'fluent-crm'),
            'fields'    => [
                'wait_type'        => [
                    'type'    => 'radio_buttons',
                    'label'   => 'Waiting Type',
                    'options' => [
                        [
                            'id'    => 'unit_wait',
                            'title' => 'Wait for a specific period'
                        ],
                        [
                            'id'    => 'timestamp_wait',
                            'title' => 'Wait until a specific date-time'
                        ],
                        [
                            'id'    => 'to_day',
                            'title' => 'To a day of the week'
                        ]
                    ]
                ],
                'wait_time_amount' => [
                    'label'         => __('Wait Time', 'fluent-crm'),
                    'type'          => 'input-number',
                    'wrapper_class' => 'fc_2col_inline pad-r-20',
                    'dependency'    => [
                        'depends_on' => 'wait_type',
                        'value'      => 'unit_wait',
                        'operator'   => '=',
                    ],
                ],
                'wait_time_unit'   => [
                    'label'         => __('Wait Time Unit', 'fluent-crm'),
                    'type'          => 'select',
                    'wrapper_class' => 'fc_2col_inline',
                    'options'       => [
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
                    'dependency'    => [
                        'depends_on' => 'wait_type',
                        'value'      => 'unit_wait',
                        'operator'   => '=',
                    ],
                ],
                'wait_date_time'   => [
                    'label'       => __('Specify Date and Time', 'fluent-crm'),
                    'type'        => 'date_time',
                    'placeholder' => __('Select Date & Time', 'fluent-crm'),
                    'inline_help' => __('Please input date and time and this step will be executed after that time (TimeZone will be as per your WordPress Date Time Zone)', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'wait_type',
                        'value'      => 'timestamp_wait',
                        'operator'   => '=',
                    ]
                ],
                'to_day'           => [
                    'type'     => 'checkboxes',
                    'label'    => 'Wait until next day(s) of the week',
                    'wrapper_class' => 'fc_2col_inline pad-r-20',
                    'options'  => [
                        [
                            'id'    => 'Mon',
                            'title' => 'Mon'
                        ],
                        [
                            'id'    => 'Tues',
                            'title' => 'Tues'
                        ],
                        [
                            'id'    => 'Wed',
                            'title' => 'Wed'
                        ],
                        [
                            'id'    => 'Thu',
                            'title' => 'Thu'
                        ],
                        [
                            'id'    => 'Fri',
                            'title' => 'Fri'
                        ],
                        [
                            'id'    => 'Sat',
                            'title' => 'Sat'
                        ],
                        [
                            'id'    => 'Sun',
                            'title' => 'Sun'
                        ]
                    ],
                    'dependency'  => [
                        'depends_on' => 'wait_type',
                        'value'      => 'to_day',
                        'operator'   => '=',
                    ]
                ],
                'to_day_time'      => [
                    'label'          => 'Time of the day',
                    'type'           => 'time_selector',
                    'placeholder'    => 'Select Time',
                    'wrapper_class' => 'fc_2col_inline',
                    'picker_options' => [
                        'start' => '00:00',
                        'step'  => '00:10',
                        'end'   => '23:59'
                    ],
                    'dependency'  => [
                        'depends_on' => 'wait_type',
                        'value'      => 'to_day',
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
