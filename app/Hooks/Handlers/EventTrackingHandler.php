<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\EventTracker;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

class EventTrackingHandler
{
    public function register()
    {
        add_filter('fluentcrm_ajax_options_event_tracking_keys', [$this, 'getEventTrackingKeyOptions'], 10, 1);
        add_action('fluentcrm_contacts_filter_event_tracking', [$this, 'applyEventTrackingFilter'], 10, 2);
        add_action('fluent_crm/track_event_activity', [$this, 'trackEventActivity'], 10, 2);

        add_filter('fluent_crm/subscriber_info_widgets', [$this, 'addSubscriberInfoWidgets'], 10, 2);
        add_filter('fluent_crm/subscriber_info_widget_event_tracking', [$this, 'addSubscriberInfoWidgets'], 10, 2);

        add_filter('fluentcrm_advanced_filter_options', [$this, 'addEventTrackingFilterOptions'], 10, 1);

        add_filter('fluent_crm/event_tracking_condition_groups', [$this, 'addEventTrackingConditionOptions'], 10, 1);

        add_filter('fluentcrm_automation_condition_groups', function ($groups) {
            if (!Helper::isExperimentalEnabled('event_tracking')) {
                return $groups;
            }

            $groups['event_tracking'] = [
                'label'    => __('Event Tracking', 'fluent-crm'),
                'value'    => 'event_tracking',
                'children' => $this->getConditionItems()
            ];

            return $groups;
        });
    }

    public function getEventTrackingKeyOptions($options = [])
    {
        $items = EventTracker::select(['event_key'])
            ->groupBy('event_key')
            ->orderBy('event_key', 'ASC')
            ->get();

        $formattedItems = [];

        foreach ($items as $item) {
            $formattedItems[] = [
                'id'    => $item->event_key,
                'title' => $item->event_key
            ];
        }

        return $formattedItems;
    }

    public function applyEventTrackingFilter($query, $filters)
    {
        if (!Helper::isExperimentalEnabled('event_tracking')) {
            return $query;
        }

        foreach ($filters as $filter) {
            if (empty($filter['value']) && $filter['value'] === '') {
                continue;
            }

            $relation = 'trackingEvents';

            $filterProp = $filter['property'];

            if ($filterProp == 'event_tracking_key') {
                $operator = $filter['operator'];
                $values = $filter['value'];
                if ($operator == 'not_in') {
                    $query->whereDoesntHave($relation, function ($q) use ($values) {
                        $q->whereIn('event_key', $values);
                    });
                } else {
                    $query->whereHas($relation, function ($q) use ($values) {
                        $q->whereIn('event_key', $values);
                    });
                }
                continue;
            }

            if ($filterProp == 'event_tracking_title') {
                $operator = $filter['operator'];

                if ($operator == '=') {
                    $query->whereHas($relation, function ($q) use ($filter) {
                        $q->where('title', $filter['value']);
                    });
                } else if ($operator == '!=') {
                    $query->whereDoesntHave($relation, function ($q) use ($filter) {
                        $q->where('title', $filter['value']);
                    });
                } else if ($operator == 'contains') {
                    $query->whereHas($relation, function ($q) use ($filter) {
                        $q->where('title', 'LIKE', '%' . $filter['value'] . '%');
                    });
                } else if ($operator == 'not_contains') {
                    $query->whereDoesntHave($relation, function ($q) use ($filter) {
                        $q->where('title', 'LIKE', '%' . $filter['value'] . '%');
                    });
                }
                continue;
            }

            if ($filterProp == 'event_tracking_value') {

                $eventKey = Arr::get($filter, 'extra_value');
                if (!$eventKey) {
                    continue;
                }

                $operator = $filter['operator'];

                if ($operator == '=') {
                    $query->whereHas($relation, function ($q) use ($filter, $eventKey) {
                        $q->where('value', $filter['value'])
                            ->where('event_key', $eventKey);
                    });
                } else if ($operator == '!=') {
                    $query->whereDoesntHave($relation, function ($q) use ($filter, $eventKey) {
                        $q->where('value', $filter['value'])
                            ->where('event_key', $eventKey);
                    });
                } else if (in_array($operator, ['<', '>'])) {

                    $query->whereHas($relation, function ($q) use ($filter, $eventKey, $operator) {
                        $q->where('value', $operator, (int)$filter['value'])
                            ->where('event_key', $eventKey);
                    });
                } else if ($operator == 'contains') {
                    $query->whereHas($relation, function ($q) use ($filter, $eventKey) {
                        $q->where('value', 'LIKE', '%' . $filter['value'] . '%')
                            ->where('event_key', $eventKey);
                    });
                } else if ($operator == 'not_contains') {
                    $query->whereDoesntHave($relation, function ($q) use ($filter, $eventKey) {
                        $q->where('value', 'LIKE', '%' . $filter['value'] . '%')
                            ->where('event_key', $eventKey);
                    });
                }
                continue;
            }

            if ($filterProp == 'event_tracking_key_count') {

                $eventKey = Arr::get($filter, 'extra_value');
                if (!$eventKey) {
                    continue;
                }

                $operator = $filter['operator'];

                if ($operator == '=') {
                    $query->whereHas($relation, function ($q) use ($filter, $eventKey) {
                        $q->where('counter', $filter['value'])
                            ->where('event_key', $eventKey);
                    });
                } else if ($operator == '!=') {
                    $query->whereDoesntHave($relation, function ($q) use ($filter, $eventKey) {
                        $q->where('counter', $filter['value'])
                            ->where('event_key', $eventKey);
                    });
                } else if (in_array($operator, ['<', '>'])) {

                    $query->whereHas($relation, function ($q) use ($filter, $eventKey, $operator) {
                        $q->where('counter', $operator, (int)$filter['value'])
                            ->where('event_key', $eventKey);
                    });
                }

                continue;
            }
        }

        return $query;
    }

    public function trackEventActivity($data, $repeatable = true)
    {
        return FluentCrmApi('event_tracker')->track($data, $repeatable);
    }

    public function addSubscriberInfoWidgets($widgets, $subscriber)
    {
        if (!Helper::isExperimentalEnabled('event_tracking')) {
            return $widgets;
        }

        $events = EventTracker::where('subscriber_id', $subscriber->id)
            ->orderBy('updated_at', 'DESC')
            ->paginate();

        if ($events->isEmpty()) {
            return $widgets;
        }

        $html = '<div class="fc_scrolled_lists"><ul class="fc_full_listed fc_event_tracking_lists">';
        foreach ($events as $event) {
            $html .= '<li>';
            $html .= '<div class="el-badge"><p class="fc_type">' . esc_attr($event->event_key) . '</p><sup class="el-badge__content is-fixed">' . $event->counter . '</sup></div>';
            $html .= '<p class="fl_event_title"><b>' . esc_html($event->title) . '</b></p>';
            if ($event->value) {
                $html .= '<p class="fc_value">' . wp_kses_post($event->value) . '</p>';
            }
            $html .= '<span class="fc_date">' . $event->updated_at . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul></div>';

        $widgets['event_tracking'] = [
            'title'          => __('Event Tracking', 'fluent-crm'),
            'content'        => $html,
            'has_pagination' => $events->total() > $events->perPage(),
            'total'          => $events->total(),
            'per_page'       => $events->perPage(),
            'current_page'   => $events->currentPage()
        ];

        return $widgets;
    }

    public function addEventTrackingFilterOptions($groups)
    {
        if (!Helper::isExperimentalEnabled('event_tracking')) {
            return $groups;
        }

        $groups['event_tracking'] = [
            'label'    => __('Event Tracking', 'fluent-crm'),
            'value'    => 'event_tracking',
            'children' => $this->getConditionItems()
        ];

        return $groups;
    }

    public function addEventTrackingConditionOptions($items)
    {
        if (!Helper::isExperimentalEnabled('event_tracking')) {
            return $items;
        }

        return [
            [
                'label'    => __('Event Tracking', 'fluent-crm'),
                'value'    => 'event_tracking',
                'children' => $this->getConditionItems()
            ],
            [
                'label'    => __('Contact Segment', 'fluent-crm'),
                'value'    => 'segment',
                'children' => [
                    [
                        'label'             => __('Type', 'fluent-crm'),
                        'value'             => 'contact_type',
                        'type'              => 'selections',
                        'component'         => 'options_selector',
                        'option_key'        => 'contact_types',
                        'is_multiple'       => false,
                        'is_singular_value' => true
                    ],
                    [
                        'label'       => __('Tags', 'fluent-crm'),
                        'value'       => 'tags',
                        'type'        => 'selections',
                        'component'   => 'options_selector',
                        'option_key'  => 'tags',
                        'is_multiple' => true,
                    ],
                    [
                        'label'       => __('Lists', 'fluent-crm'),
                        'value'       => 'lists',
                        'type'        => 'selections',
                        'component'   => 'options_selector',
                        'option_key'  => 'lists',
                        'is_multiple' => true,
                    ]
                ],
            ]
        ];
    }

    private function getConditionItems()
    {
        return [
            [
                'label'              => __('Event Key', 'fluent-crm'),
                'value'              => 'event_tracking_key',
                'type'               => 'selections',
                'component'          => 'ajax_selector',
                'option_key'         => 'event_tracking_keys',
                'is_multiple'        => true,
                'custom_operators'   => [
                    'in'     => 'in',
                    'not_in' => 'not in'
                ],
                'creatable'          => true,
                'experimental_cache' => true,
                'help'               => 'Match one or more tracking events for your contacts.'
            ],
            [
                'label'            => __('Event Occurrence Count', 'fluent-crm'),
                'value'            => 'event_tracking_key_count',
                'type'             => 'composite_optioned_compare',
                'help'             => 'The provided value for your selected event will be matched with the event occurrence count',
                'ajax_selector'    => [
                    'label'              => 'For Event Key',
                    'option_key'         => 'event_tracking_keys',
                    'experimental_cache' => true,
                    'is_multiple'        => false,
                    'placeholder'        => 'Select Event Key'
                ],
                'value_config'     => [
                    'label'       => 'Event Count',
                    'type'        => 'input_text',
                    'data_type'   => 'number',
                    'placeholder' => 'Event Value'
                ],
                'custom_operators' => [
                    '='  => 'equal',
                    '!=' => 'not equal',
                    '>'  => 'greater than',
                    '<'  => 'less than'
                ],
            ],
            [
                'label'            => __('Event Value', 'fluent-crm'),
                'value'            => 'event_tracking_value',
                'type'             => 'composite_optioned_compare',
                'help'             => 'The compare value will be matched with selected event & last recorded value of the selected event key',
                'ajax_selector'    => [
                    'label'              => 'For Event Key',
                    'option_key'         => 'event_tracking_keys',
                    'experimental_cache' => true,
                    'is_multiple'        => false,
                    'placeholder'        => 'Select Event Key'
                ],
                'value_config'     => [
                    'label'       => 'Compare Value',
                    'type'        => 'input_text',
                    'placeholder' => 'Event Value',
                    'data_type'   => 'number',
                ],
                'custom_operators' => [
                    '='            => 'equal',
                    '!='           => 'not equal',
                    'contains'     => 'includes',
                    'not_contains' => 'does not includes',
                    '>'            => 'greater than',
                    '<'            => 'less than'
                ],
            ],
            [
                'label' => __('Event Title', 'fluent-crm'),
                'value' => 'event_tracking_title',
                'type'  => 'text',
                'help'  => 'Match by tracking event title'
            ],
        ];
    }
}
