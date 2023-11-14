<?php

namespace FluentCrm\App\Services\Funnel\Benchmarks;

use FluentCrm\App\Services\Funnel\BaseBenchMark;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;

class RemoveFromTagBenchmark extends BaseBenchMark
{
    public function __construct()
    {
        $this->triggerName = 'fluentcrm_contact_removed_from_tags';
        $this->actionArgNum = 2;
        $this->priority = 20;

        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'title'       => __('Tag Removed', 'fluent-crm'),
            'description' => __('This will run when selected Tags have been removed from a contact', 'fluent-crm'),
            'icon'        => 'fc-icon-tag_removed',//fluentCrmMix('images/funnel_icons/tag_removed.svg'),
            'settings'    => [
                'tags'        => [],
                'select_type' => 'any',
                'type'        => 'optional',
                'can_enter'   => 'yes'
            ]
        ];
    }

    public function getDefaultSettings()
    {
        return [
            'tags'        => [],
            'select_type' => 'any',
            'type'        => 'optional',
            'can_enter'   => 'yes'
        ];
    }

    public function getBlockFields($funnel)
    {
        return [
            'title'     => __('Tag Removed From Contact', 'fluent-crm'),
            'sub_title' => __('This will run when selected Tags have been removed from a contact', 'fluent-crm'),
            'fields'    => [
                'tags'        => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'tags',
                    'is_multiple' => true,
                    'label'       => __('Select Tags', 'fluent-crm'),
                    'placeholder' => __('Select a Tag', 'fluent-crm'),
                    'creatable' => true
                ],
                'select_type' => [
                    'label'      => __('Run When', 'fluent-crm'),
                    'type'       => 'radio',
                    'options'    => [
                        [
                            'id'    => 'any',
                            'title' => __('Run if any selected tag removed from a contact', 'fluent-crm')
                        ],
                        [
                            'id'    => 'all',
                            'title' => __('Need all selected tags removed from the contact', 'fluent-crm')
                        ]
                    ],
                    'dependency' => [
                        'depends_on' => 'tags',
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

        if (!$this->isTagMatched($listIds, $subscriber, $settings)) {
            return; // not matched based on condition
        }

        $funnelProcessor = new FunnelProcessor();
        $funnelProcessor->startFunnelFromSequencePoint($benchMark, $subscriber);
    }

    private function isTagMatched($tagIds, $subscriber, $settings)
    {
        $isMatched = array_intersect($settings['tags'], $tagIds);
        if (!$isMatched) {
            return false; // not in our scope
        }

        $marchType = Arr::get($settings, 'select_type');

        if ($marchType == 'all') {
            $attachedTagIds = $subscriber->tags->pluck('id')->toArray();
            return !array_diff($settings['tags'], $attachedTagIds) == $settings['tags'];
        }

        return $isMatched;
    }

}
