<?php

namespace FluentCrm\App\Services\Funnel\Benchmarks;

use FluentCrm\App\Services\Funnel\BaseBenchMark;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\App\Models\Tag;
use FluentCrm\Includes\Helpers\Arr;

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
            'title'       => 'Tag Removed',
            'description' => 'This will run when selected Tags will be removed from a contact',
            'icon' => fluentCrmMix('images/funnel_icons/tag_removed.svg'),
            'settings'    => [
                'tags'        => [],
                'select_type' => 'any',
                'type'        => 'optional'
            ]
        ];
    }

    public function getDefaultSettings()
    {
        return [
            'tags'        => [],
            'select_type' => 'any',
            'type'        => 'optional'
        ];
    }

    public function getBlockFields($funnel)
    {
        return [
            'title'     => 'Tag Removed From Contact',
            'sub_title' => 'This will run when selected Tags will be removed from a contact',
            'fields'    => [
                'tags'        => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'tags',
                    'is_multiple' => true,
                    'label'       => 'Select Tags',
                    'placeholder' => 'Select a Tag'
                ],
                'select_type' => [
                    'label'      => 'Run When',
                    'type'       => 'radio',
                    'options'    => [
                        [
                            'id'    => 'any',
                            'title' => 'Run if any selected tag removed from a contact'
                        ],
                        [
                            'id'    => 'all',
                            'title' => 'Need all selected tags removed from the contact'
                        ]
                    ],
                    'dependency' => [
                        'depends_on' => 'tags',
                        'operator'   => '!=',
                        'value'      => []
                    ]
                ],
                'type'        => $this->benchmarkTypeField()
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
            $attachedListIds = Tag::whereHas('subscribers', function ($q) use ($subscriber) {
                $q->where('subscriber_id', $subscriber->id);
            })->get()->pluck('id');
            return !array_diff($settings['tags'], $attachedListIds) == $settings['tags'];
        }

        return $isMatched;

    }

}
