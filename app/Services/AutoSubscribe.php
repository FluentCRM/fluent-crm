<?php

namespace FluentCrm\App\Services;

class AutoSubscribe
{
    public function getRegistrationSettings()
    {
        $defaults = [
            'status'       => 'no',
            'target_list'  => '',
            'target_tags'  => [],
            'double_optin' => 'no'
        ];

        $settings = fluentcrm_get_option('user_registration_subscribe_settings', []);

        if (!$settings) {
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
    }

    public function getRegistrationFields()
    {
        return [
            'title'     => 'User Signup Optin Settings',
            'sub_title' => 'Automatically add your new user signups as subscriber in FluentCRM',
            'fields'    => [
                'status'       => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => 'Enable Create new contacts in FluentCRM when users register in WordPress',
                    'true_label'     => 'yes',
                    'false_label'    => 'no'
                ],
                'target_list'  => [
                    'type'        => 'option-selector',
                    'label'       => 'Assign List',
                    'option_key'  => 'lists',
                    'is_multiple' => false,
                    'placeholder' => 'Select Assign List',
                    'inline_help' => 'Select the list that will be assigned for new user registration in your site',
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'target_tags'  => [
                    'type'        => 'option-selector',
                    'label'       => 'Assign Tags',
                    'option_key'  => 'tags',
                    'is_multiple' => true,
                    'placeholder' => 'Select Assign Tag',
                    'inline_help' => 'Select the tags that will be assigned for new user registration in your site',
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'double_optin' => [
                    'type'           => 'inline-checkbox',
                    'label'          => 'Double Opt-In',
                    'checkbox_label' => 'Enable Double-Optin Email Confirmation',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'dependency'     => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
            ]
        ];
    }

    public function getCommentSettings()
    {
        $defaults = [
            'status'         => 'no',
            'checkbox_label' => __('Subscribe to newsletter', 'fluent-crm'),
            'auto_checked'   => 'no',
            'target_list'    => '',
            'show_only_new'  => 'yes',
            'target_tags'    => [],
            'double_optin'   => 'yes'
        ];

        $settings = fluentcrm_get_option('comment_form_subscribe_settings', []);

        if (!$settings) {
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
    }

    public function getCommentFields()
    {
        return [
            'title'     => 'Comment Form Subscription Settings',
            'sub_title' => 'Automatically add your site commenter as subscriber in FluentCRM',
            'fields'    => [
                'status'         => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'checkbox_label' => 'Enable Create new contacts in FluentCRM when a visitor add a comment in your comment form'
                ],
                'checkbox_label' => [
                    'label'       => 'Checkbox Label for Comment Form',
                    'type'        => 'input-text',
                    'placeholder' => 'Checkbox Label for Comment Form',
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'target_list'    => [
                    'type'        => 'option-selector',
                    'label'       => 'Assign List',
                    'option_key'  => 'lists',
                    'is_multiple' => false,
                    'placeholder' => 'Select Assign List',
                    'inline_help' => 'Select the list that will be assigned for comment will be made in comment forms',
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'target_tags'    => [
                    'type'        => 'option-selector',
                    'label'       => 'Assign Tags',
                    'option_key'  => 'tags',
                    'is_multiple' => true,
                    'placeholder' => 'Select Assign Tag',
                    'inline_help' => 'Select the tags that will be assigned for new comment will be made in comment forms',
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'auto_checked'   => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => 'Enable auto checked status on Comment Form subscription',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'dependency'     => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'show_only_new' => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => 'Do not show the checkbox if current user already subscribed state',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'dependency'     => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'double_optin'   => [
                    'type'           => 'inline-checkbox',
                    'label'          => 'Double Opt-In',
                    'checkbox_label' => 'Enable Double-Optin Email Confirmation',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'dependency'     => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ]
            ]
        ];
    }
}
