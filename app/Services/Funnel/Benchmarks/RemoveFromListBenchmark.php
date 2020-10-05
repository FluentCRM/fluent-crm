<?php

namespace FluentCrm\App\Services\Funnel\Benchmarks;

use FluentCrm\App\Services\Funnel\BaseBenchMark;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\App\Models\Lists;
use FluentCrm\Includes\Helpers\Arr;

class RemoveFromListBenchmark extends BaseBenchMark
{
    public function __construct()
    {
        $this->triggerName = 'fluentcrm_contact_removed_from_lists';
        $this->actionArgNum = 2;
        $this->priority = 40;

        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'title'       => 'List Removed',
            'description' => 'This will run when selected lists will be removed from a contact',
            'icon' => fluentCrmMix('images/funnel_icons/list_removed.svg'),
            'settings'    => [
                'lists'       => [],
                'select_type' => 'any',
                'type'        => 'optional'
            ]
        ];
    }

    public function getDefaultSettings()
    {
        return [
            'lists'       => [],
            'select_type' => 'any',
            'type'        => 'optional'
        ];
    }

    public function getBlockFields($funnel)
    {
        return [
            'title'     => 'List Removed From Contact',
            'sub_title' => 'This will run when selected lists will be removed from a contact',
            'fields'    => [
                'lists'       => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'lists',
                    'is_multiple' => true,
                    'label'       => 'Select Lists',
                    'placeholder' => 'Select List'
                ],
                'select_type' => [
                    'label'      => 'Run When',
                    'type'       => 'radio',
                    'options'    => [
                        [
                            'id'    => 'any',
                            'title' => 'contact removed from any of the selected Lists'
                        ],
                        [
                            'id'    => 'all',
                            'title' => 'contact removed from all of the selected lists'
                        ]
                    ],
                    'dependency' => [
                        'depends_on' => 'lists',
                        'operator'   => '!=',
                        'value'      => []
                    ]
                ],
                'type'        => [
                    'label'       => 'Benchmark type',
                    'type'        => 'radio',
                    'options'     => [
                        [
                            'id'    => 'optional',
                            'title' => '[Optional Point] This is an optional trigger point'
                        ],
                        [
                            'id'    => 'required',
                            'title' => '[Essential Point] Select IF this step is required for processing further actions'
                        ]
                    ],
                    'inline_help' => 'If you select [Optional Point] it will work as an Optional Trigger otherwise, it will wait for full-fill this action'
                ]
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

        if ($marchType == 'all') {
            $attachedListIds = Lists::whereHas('subscribers', function ($q) use ($subscriber) {
                $q->where('subscriber_id', $subscriber->id);
            })->get()->pluck('id');
            return !array_diff($settings['lists'], $attachedListIds) == $settings['lists'];
        }

        return $isMatched;

    }

}
