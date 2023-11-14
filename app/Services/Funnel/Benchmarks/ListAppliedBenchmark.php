<?php

namespace FluentCrm\App\Services\Funnel\Benchmarks;

use FluentCrm\App\Services\Funnel\BaseBenchMark;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;

class ListAppliedBenchmark extends BaseBenchMark
{
    public function __construct()
    {
        $this->triggerName = 'fluentcrm_contact_added_to_lists';
        $this->actionArgNum = 2;
        $this->priority = 20;

        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'title'       => __('List Applied', 'fluent-crm'),
            'description' => __('This will run when selected lists have been applied to a contact', 'fluent-crm'),
            'icon'        => 'fc-icon-list_applied_2',//fluentCrmMix('images/funnel_icons/list_applied.svg'),
            'settings'    => [
                'lists'       => [],
                'select_type' => 'any',
                'type'        => 'optional',
                'can_enter'   => 'yes'
            ]
        ];
    }

    public function getDefaultSettings()
    {
        return [
            'lists'       => [],
            'select_type' => 'any',
            'type'        => 'optional',
            'can_enter'   => 'yes'
        ];
    }

    public function getBlockFields($funnel)
    {
        return [
            'title'     => __('List Applied', 'fluent-crm'),
            'sub_title' => __('This will run when selected lists have been applied to a contact', 'fluent-crm'),
            'fields'    => [
                'lists'       => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'lists',
                    'is_multiple' => true,
                    'label'       => __('Select Lists', 'fluent-crm'),
                    'placeholder' => __('Select List', 'fluent-crm'),
                    'creatable' => true
                ],
                'select_type' => [
                    'label'      => __('Run When', 'fluent-crm'),
                    'type'       => 'radio',
                    'options'    => [
                        [
                            'id'    => 'any',
                            'title' => __('contact added in any of the selected Lists', 'fluent-crm')
                        ],
                        [
                            'id'    => 'all',
                            'title' => __('contact added in all of the selected lists', 'fluent-crm')
                        ]
                    ],
                    'dependency' => [
                        'depends_on' => 'lists',
                        'operator'   => '!=',
                        'value'      => []
                    ]
                ],
                'type'        => $this->benchmarkTypeField(),
                'can_enter'   => $this->canEnterField()
            ]
        ];
    }

    public function handle($benchMark, $originalArgs)
    {
        $listIds = $originalArgs[0];
        $subscriber = $originalArgs[1];
        $settings = $benchMark->settings;

        if (!$this->isListMatched($listIds, $subscriber, $settings)) {
            return; // not matched based on condition
        }

        $funnelProcessor = new FunnelProcessor();
        $funnelProcessor->startFunnelFromSequencePoint($benchMark, $subscriber);
    }

    private function isListMatched($listIds, $subscriber, $settings)
    {
        $isMatched = array_intersect($settings['lists'], $listIds);
        if (!$isMatched) {
            return false; // not in our scope
        }

        $marchType = Arr::get($settings, 'select_type');

        $subscriberLists = $subscriber->lists->pluck('id')->toArray();
        $intersection = array_intersect($listIds, $subscriberLists);

        if ($marchType === 'any') {
            // At least one funnel list id is available.
            $isMatched = !empty($intersection);
        } else {
            // All of the funnel list ids are present.
            $isMatched = count($intersection) === count($settings['lists']);
        }

        return $isMatched;

    }

}
