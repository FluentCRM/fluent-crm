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
            'title'     => __('User Signup Optin Settings', 'fluent-crm'),
            'sub_title' => __('Automatically add your new user signups as subscriber in FluentCRM', 'fluent-crm'),
            'fields'    => [
                'status'       => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => __('Enable Create new contacts in FluentCRM when users register in WordPress', 'fluent-crm'),
                    'true_label'     => 'yes',
                    'false_label'    => 'no'
                ],
                'target_list'  => [
                    'type'        => 'option-selector',
                    'label'       => __('Assign List', 'fluent-crm'),
                    'option_key'  => 'lists',
                    'is_multiple' => false,
                    'creatable'   => true,
                    'placeholder' => __('Select Assign List', 'fluent-crm'),
                    'inline_help' => __('Select the list that will be assigned for new user registration in your site', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'target_tags'  => [
                    'type'        => 'option-selector',
                    'label'       => __('Assign Tags', 'fluent-crm'),
                    'option_key'  => 'tags',
                    'is_multiple' => true,
                    'creatable'   => true,
                    'placeholder' => __('Select Assign Tag', 'fluent-crm'),
                    'inline_help' => __('Select the tags that will be assigned for new user registration in your site', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'double_optin' => [
                    'type'           => 'inline-checkbox',
                    'label'          => __('Double Opt-In', 'fluent-crm'),
                    'checkbox_label' => __('Enable Double-Optin Email Confirmation', 'fluent-crm'),
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
            'title'     => __('Comment Form Subscription Settings', 'fluent-crm'),
            'sub_title' => __('Automatically add your site commenter as subscriber in FluentCRM', 'fluent-crm'),
            'fields'    => [
                'status'         => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'checkbox_label' => __('Enable Create new contacts in FluentCRM when a visitor add a comment in your comment form', 'fluent-crm')
                ],
                'checkbox_label' => [
                    'label'       => __('Checkbox Label for Comment Form', 'fluent-crm'),
                    'type'        => 'input-text',
                    'placeholder' => __('Checkbox Label for Comment Form', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'target_list'    => [
                    'type'        => 'option-selector',
                    'label'       => __('Assign List', 'fluent-crm'),
                    'option_key'  => 'lists',
                    'is_multiple' => false,
                    'placeholder' => __('Select Assign List', 'fluent-crm'),
                    'inline_help' => __('Select the list that will be assigned for comment will be made in comment forms', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'target_tags'    => [
                    'type'        => 'option-selector',
                    'label'       => __('Assign Tags', 'fluent-crm'),
                    'option_key'  => 'tags',
                    'is_multiple' => true,
                    'placeholder' => __('Select Assign Tag', 'fluent-crm'),
                    'inline_help' => __('Select the tags that will be assigned for new comment will be made in comment forms', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'auto_checked'   => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => __('Enable auto checked status on Comment Form subscription', 'fluent-crm'),
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'dependency'     => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'show_only_new'  => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => __('Do not show the checkbox if current user already subscribed state', 'fluent-crm'),
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
                    'label'          => __('Double Opt-In', 'fluent-crm'),
                    'checkbox_label' => __('Enable Double-Optin Email Confirmation', 'fluent-crm'),
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

    public function getUserSyncSettings()
    {
        $defaults = [
            'status'                        => 'no',
            'delete_contact_on_user_delete' => 'no'
        ];

        $settings = fluentcrm_get_option('user_syncing_settings', []);

        if (!$settings) {
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
    }

    public function getUserSyncFields()
    {
        return [
            'title'     => __('Auto Sync User Data and Contact Data', 'fluent-crm'),
            'sub_title' => __('Automatically Sync your WP User Data and Fluent CRM Contact Data', 'fluent-crm'),
            'fields'    => [
                'status'                        => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'checkbox_label' => __('Enable Sync between WP User Data and Fluent CRM Contact Data', 'fluent-crm')
                ],
                'delete_contact_on_user_delete' => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'checkbox_label' => __('Delete FluentCRM contact on WP User delete', 'fluent-crm')
                ]
            ]
        ];
    }

    public function getWooCheckoutSettings()
    {
        $defaults = [
            'auto_checkout_fill' => 'no',
            'status'         => 'no',
            'checkbox_label' => __('Sign me up for the newsletter!', 'fluent-crm'),
            'auto_checked'   => 'no',
            'target_list'    => '',
            'show_only_new'  => 'yes',
            'target_tags'    => [],
            'double_optin'   => 'yes'
        ];

        $settings = fluentcrm_get_option('woo_checkout_form_subscribe_settings', []);

        if (!$settings) {
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
    }

    public function getWooCheckoutFields()
    {
        return [
            'title'     => __('Woocommerce Checkout Subscription Field', 'fluent-crm'),
            'sub_title' => __('Add a subscription box to WooCommerce Checkout Form', 'fluent-crm'),
            'fields'    => [
                'auto_checkout_fill' => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'checkbox_label' => __('Automatically fill WooCommerce Checkout field value with current contact data', 'fluent-crm')
                ],
                'status'             => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'checkbox_label' => __('Enable Subscription Checkbox to WooCommerce Checkout Page', 'fluent-crm')
                ],
                'checkbox_label'     => [
                    'label'       => __('Checkbox Label for Checkout checkbox', 'fluent-crm'),
                    'type'        => 'input-text',
                    'placeholder' => __('Checkbox Label for Checkout checkbox', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'target_list'        => [
                    'type'        => 'option-selector',
                    'label'       => __('Assign List', 'fluent-crm'),
                    'option_key'  => 'lists',
                    'is_multiple' => false,
                    'placeholder' => __('Select Assign List', 'fluent-crm'),
                    'inline_help' => __('Select the list that will be assigned when checkbox checked', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'target_tags'        => [
                    'type'        => 'option-selector',
                    'label'       => __('Assign Tags', 'fluent-crm'),
                    'option_key'  => 'tags',
                    'is_multiple' => true,
                    'placeholder' => __('Select Assign Tag', 'fluent-crm'),
                    'inline_help' => __('Select the tags that will be assigned when checkbox checked', 'fluent-crm'),
                    'dependency'  => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'auto_checked'       => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => __('Enable auto checked status on checkout page checkbox', 'fluent-crm'),
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'dependency'     => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'show_only_new'      => [
                    'type'           => 'inline-checkbox',
                    'label'          => '',
                    'checkbox_label' => __('Do not show the checkbox if current user already in subscribed state', 'fluent-crm'),
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'dependency'     => [
                        'depends_on' => 'status',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ],
                'double_optin'       => [
                    'type'           => 'inline-checkbox',
                    'label'          => __('Double Opt-In', 'fluent-crm'),
                    'checkbox_label' => __('Enable Double-Optin Email Confirmation', 'fluent-crm'),
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
