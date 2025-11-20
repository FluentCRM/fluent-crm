<?php

namespace FluentCrm\App\Services\Funnel\Benchmarks;

use FluentCrm\App\Services\Funnel\BaseBenchMark;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;

class TagAppliedBenchmark extends BaseBenchMark
{
    public function __construct()
    {
        $this->triggerName = 'fluentcrm_contact_added_to_tags';
        $this->actionArgNum = 2;
        $this->priority = 20;

        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'title'       => __('Tag Applied', 'fluent-crm'),
            'description' => __('This will run when selected Tags have been applied to a contact', 'fluent-crm'),
            'icon'        => 'fc-icon-tag_applied',//fluentCrmMix('images/funnel_icons/tag-applied.svg'),
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
            'title'     => __('Tag Applied', 'fluent-crm'),
            'sub_title' => __('This will run when selected Tags have been applied to a contact', 'fluent-crm'),
            'fields'    => [
                'tags'        => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'tags',
                    'creatable'   => true,
                    'is_multiple' => true,
                    'label'       => __('Select Tags', 'fluent-crm'),
                    'placeholder' => __('Select Tags', 'fluent-crm'),
                ],
                'select_type' => [
                    'label'      => __('Run When', 'fluent-crm'),
                    'type'       => 'radio',
                    'options'    => [
                        [
                            'id'    => 'any',
                            'title' => __('contact added in any of the selected Tags', 'fluent-crm')
                        ],
                        [
                            'id'    => 'all',
                            'title' => __('contact added in all of the selected Tags', 'fluent-crm')
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
        $tagIds = $originalArgs[0];
        $subscriber = $originalArgs[1];
        $settings = $benchMark->settings;

        $subscriberTags = $subscriber->tags->pluck('id')->toArray();
        $benchmarkTags = Arr::get($settings, 'tags', []);

        if (!$this->isTagMatched($subscriberTags, $benchmarkTags, $settings)) {
            return; // not matched based on condition
        }

        $funnelProcessor = new FunnelProcessor();
        $funnelProcessor->startFunnelFromSequencePoint($benchMark, $subscriber);
    }

    private function isTagMatched($subscriberTags, $benchmarkTags, $settings)
    {
        $matchType = Arr::get($settings, 'select_type');

        if (empty($benchmarkTags)) {
            return false;
        }
        // find common elements between the two arrays
        $intersection = array_intersect($benchmarkTags, $subscriberTags);

        if ($matchType === 'any') {
            // At least one funnel list id is available.
            $isMatched = !empty($intersection);
        } else {
            // All of the funnel list ids are present.
            $isMatched = count($intersection) === count($settings['tags']);
        }

        return $isMatched;
    }


    public function assertCurrentGoalState($asserted, $benchmark, $funnelSubscriber)
    {
        if (!$funnelSubscriber || !$funnelSubscriber->subscriber) {
            return $asserted;
        }

        $subscriberTags = $funnelSubscriber->subscriber->tags->pluck('id')->toArray();
        $benchmarkTags = Arr::get($benchmark->settings, 'tags', []);

        $benchmarkSettings= $benchmark->settings;

        return $this->isTagMatched($subscriberTags, $benchmarkTags, $benchmarkSettings);
        
    }
}
