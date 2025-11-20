<?php

namespace FluentCrm\App\Services\ExternalIntegrations;


class BricksBuilderIntegration
{

    public function register()
    {
        add_filter('bricks/conditions/groups', [$this, 'addConditionGroup']);
        add_filter('bricks/conditions/options', [$this, 'addConditionOptions']);
        add_filter('bricks/conditions/result', [$this, 'checkCondition'], 10, 3);
    }

    public function addConditionGroup($groups)
    {
        // Ensure your group name is unique (best to prefix it)
        $groups[] = [
            'name'  => 'fluent_crm',
            'label' => esc_html__('FluentCRM', 'fluent-crm'),
        ];

        return $groups;
    }

    public function addConditionOptions($options)
    {
        // Ensure key is unique, and that group exists
        $tags = \FluentCrm\App\Models\Tag::select(['id', 'title'])->orderBy('title', 'ASC')->get();
        $lists = \FluentCrm\App\Models\Lists::select(['id', 'title'])->orderBy('title', 'ASC')->get();

        $formattedTags = [];

        foreach ($tags as $tag) {
            $formattedTags[$tag->id] = $tag->title;
        }

        $formattedLists = [];

        foreach ($lists as $list) {
            $formattedLists[$list->id] = $list->title;
        }

        $options[] = [
            'key'     => 'fluent_crm_tags',
            'label'   => esc_html__('FluentCRM Tags', 'fluent-crm'),
            'group'   => 'fluent_crm',
            'compare' => [
                'type'        => 'select',
                'options'     => [
                    '==' => esc_html__('includes in', 'fluent-crm'),
                    '!=' => esc_html__('not includes', 'fluent-crm'),
                ],
                'placeholder' => esc_html__('is', 'fluent-crm'),
            ],
            'value'   => [
                'type'        => 'select',
                'multiple'    => true,
                'options'     => $formattedTags,
                'placeholder' => esc_html__('Select Tags', 'fluent-crm'),
            ],
        ];

        $options[] = [
            'key'     => 'fluent_crm_lists',
            'label'   => esc_html__('FluentCRM Lists', 'fluent-crm'),
            'group'   => 'fluent_crm',
            'compare' => [
                'type'        => 'select',
                'options'     => [
                    '==' => esc_html__('includes in', 'fluent-crm'),
                    '!=' => esc_html__('not includes', 'fluent-crm'),
                ],
                'placeholder' => esc_html__('is', 'fluent-crm'),
            ],
            'value'   => [
                'type'        => 'select',
                'multiple'    => true,
                'options'     => $formattedLists,
                'placeholder' => esc_html__('Select Lists', 'fluent-crm'),
            ],
        ];

        return $options;
    }

    public function checkCondition($result, $condition_key, $condition)
    {
        $acceptedKeys = ['fluent_crm_tags', 'fluent_crm_lists'];
        if (!in_array($condition_key, $acceptedKeys)) {
            return $result;
        }

        // In my example, if compare is empty, we set it to '==' as default
        $compare = \FluentCrm\Framework\Support\Arr::get($condition, 'compare', '==');

        $targetIds = \FluentCrm\Framework\Support\Arr::get($condition, 'value', []);
        $targetIds = array_filter(array_map('intval', $targetIds));

        if (!$compare || !$targetIds) {
            return true;
        }

        $currentContact = fluentcrm_get_current_contact();

        if (!$currentContact) {
            return $compare != '==';
        }

        if ($condition_key == 'fluent_crm_tags') {
            $result = $currentContact->hasAnyTagId($targetIds);
        } else {
            $result = $currentContact->hasAnyListId($targetIds);
        }

        if ($compare == '==') {
            return $result;
        }

        return !$result;
    }

}
