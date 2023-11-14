<?php

namespace FluentCrm\App\Services;

use FluentCrm\App\Services\Funnel\FunnelHelper;

class RoleBasedTagging
{
    public function getSettings($withFiller = false)
    {
        $settings = fluentcrm_get_option('role_based_tagging_settings', []);
        $tagMappings = [];
        if (!empty($settings['tag_mappings'])) {
            $tagMappings = $settings['tag_mappings'];
        }

        if ($withFiller) {
            $userRoles = FunnelHelper::getUserRoles();
            foreach ($userRoles as $role) {
                if (empty($tagMappings[$role['id']])) {
                    $tagMappings[$role['id']] = [
                        'add_tags'    => [],
                        'remove_tags' => []
                    ];
                }
            }
            $settings['tag_mappings'] = $tagMappings;
        }

        $defaults = [
            'status'       => 'no',
            'tag_mappings' => $tagMappings
        ];

        if (!$settings) {
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
    }

    public function getFields()
    {
        return [
            'title'     => __('WP User Role Based Tag Mapping', 'fluent-crm'),
            'sub_title' => __('Assign or Remove tags when a contact assign to a user role.', 'fluent-crm'),
            'fields'    => [
                'status'       => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => __('Enable Role Based Tag Mapping', 'fluent-crm'),
                    'true_label'     => 'yes',
                    'false_label'    => 'no'
                ],
                'tag_mappings' => [
                    'type'             => 'tag-add-remove-mapping',
                    'label'            => __('Map Role and associate tags', 'fluent-crm'),
                    'selector_label'   => __('Target User Role', 'fluent-crm'),
                    'add_tag_label'    => __('Tags to be added', 'fluent-crm'),
                    'remove_tag_label' => __('Tags to be removed', 'fluent-crm'),
                    'selector_options' => FunnelHelper::getUserRoles(),
                    'dependency'       => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ]
            ]
        ];
    }

}
