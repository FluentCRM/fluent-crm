<?php

namespace FluentCrm\App\Services\Funnel;

class ProFunnelItems
{
    public function __construct()
    {
        $this->initProTriggers();
        $this->initBlocks();
    }

    private function initProTriggers()
    {
        $triggers = $this->getCrmTriggers();
        if (defined('WC_PLUGIN_FILE')) {
            $triggers['woocommerce_order_status_processing'] = [
                'category'    => 'WooCommerce',
                'label'       => __('WooCommerce - New Order', 'fluent-crm'),
                'description' => __('This Funnel will start once new order will be added as processing', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('WLM3_PLUGIN_VERSION')) {
            $triggers['wishlistmember_add_user_levels'] = [
                'category'    => 'Wishlist Member',
                'label'       => __('A member added to a membership level', 'fluent-crm'),
                'description' => __('This funnel will start when a member is added to a level', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('TUTOR_VERSION')) {
            $triggers['tutor_course_complete_after'] = [
                'category'    => 'TutorLMS',
                'label'       => __('Student completes a Course', 'fluent-crm'),
                'description' => __('This Funnel will start a student completes a Course', 'fluent-crm'),
                'disabled'    => true
            ];

            $triggers['lifterlms_lesson_completed'] = [
                'category'    => 'TutorLMS',
                'label'       => __('Student Complete a Lesson', 'fluent-crm'),
                'description' => __('This Funnel will start a student completes a lesson', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (class_exists('\Restrict_Content_Pro')) {
            $triggers['rcp_membership_post_activate'] = [
                'category'    => 'Restrict Content Pro',
                'label'       => __('A member added to a membership level', 'fluent-crm'),
                'description' => __('This funnel will start when a member is added to a level for the first time', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('PMPRO_VERSION')) {
            $triggers['pmpro_after_change_membership_level'] = [
                'category'    => 'Paid Membership Pro',
                'label'       => __('Membership Level assignment of a User', 'fluent-crm'),
                'description' => __('This funnel will start when a user is assigned to specified membership levels', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('MEPR_PLUGIN_NAME')) {
            $triggers['mepr-account-is-active'] = [
                'category'    => 'MemberPress',
                'label'       => __('A member added to a membership level', 'fluent-crm'),
                'description' => __('This funnel will start when a membership level get activated for a member', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('LLMS_PLUGIN_FILE')) {
            $triggers['llms_user_enrolled_in_course'] = [
                'category'    => 'LifterLMS',
                'label'       => __('Enrollment in a course', 'fluent-crm'),
                'description' => __('This funnel will start when a contact has been enrolled in a course', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['llms_user_added_to_membership_level'] = [
                'category'    => 'LifterLMS',
                'label'       => __('Enrollment in a Membership', 'fluent-crm'),
                'description' => __('This Funnel will start when a student has been enrolled in a membership level', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['lifterlms_lesson_completed'] = [
                'category'    => 'LifterLMS',
                'label'       => __('Student completes a Lesson', 'fluent-crm'),
                'description' => __('This Funnel will start when a student completes a lesson', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['lifterlms_lesson_completed'] = [
                'category'    => 'LifterLMS',
                'label'       => __('Student completes a Course', 'fluent-crm'),
                'description' => __('This Funnel will start a student completes a Course', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('LEARNDASH_VERSION')) {
            $triggers['learndash_update_course_access'] = [
                'category'    => 'LearnDash',
                'label'       => __('Enrolls in a Course', 'fluent-crm'),
                'description' => __('This funnel will start when a student is enrolled in a course', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['learndash_lesson_completed'] = [
                'category'    => 'LearnDash',
                'label'       => __('Completes a Lesson', 'fluent-crm'),
                'description' => __('This Funnel will start a student completes a lesson', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['learndash_topic_completed'] = [
                'category'    => 'LearnDash',
                'label'       => __('Completes a Topic', 'fluent-crm'),
                'description' => __('This funnel will start when a user is completes a lesson topic', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['learndash_course_completed'] = [
                'category'    => 'LearnDash',
                'label'       => __('Completes a Course', 'fluent-crm'),
                'description' => 'This Funnel will start when a student completes a course',
                'disabled'    => true
            ];
            $triggers['ld_added_group_access'] = [
                'category'    => 'LearnDash',
                'label'       => __('Enrolls in a Group', 'fluent-crm'),
                'description' => __('This funnel will start when a user is enrolled in a group', 'fluent-crm')
            ];
        }

        if (class_exists('\Easy_Digital_Downloads')) {
            $triggers['edd_update_payment_status'] = [
                'category'    => 'Easy Digital Downloads',
                'label'       => __('Edd - New Order Success', 'fluent-crm'),
                'description' => __('This Funnel will start once new order will be added as successful payment', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (class_exists('\Affiliate_WP')) {
            $triggers['affwp_set_affiliate_status'] = [
                'category'    => 'AffiliateWP',
                'label'       => __('AffiliateWP - New Affiliate Approved/Active Register', 'fluent-crm'),
                'description' => __('This Funnel will be initiated when affiliate will be approved or register as direct approved', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        add_filter('fluentcrm_funnel_triggers', function ($allTriggers) use ($triggers) {
            return array_merge($allTriggers, $triggers);
        });

    }

    private function initBlocks()
    {
        $blocks = [
            'fcrm_has_contact_list'           => [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Has In Selected Lists', 'fluent-crm'),
                'description' => __('Check If the contact has specific lists', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/has_list.svg'),
            ],
            'fcrm_has_contact_tag'            => [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Has Selected tags', 'fluent-crm'),
                'description' => __('Check If the contact has specific tags', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/has_tag.svg')
            ],
            'fluencrm_benchmark_link_clicked' => [
                'is_pro'      => true,
                'type'        => 'benchmark',
                'title'       => __('Link Click', 'fluent-crm'),
                'description' => __('This will run once a subscriber click on this provided link', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/link_clicked.svg'),
            ],
            'send_campaign_email'             => [
                'is_pro'      => true,
                'type'        => 'action',
                'title'       => __('Send Campaign Email', 'fluent-crm'),
                'description' => __('Send an Email from your existing campaign', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/send_campaign.svg')
            ],
            'remove_from_funnel'              => [
                'is_pro'      => true,
                'type'        => 'action',
                'title'       => __('Cancel Automations', 'fluent-crm'),
                'description' => __('Pause/Cancel another automation for contact', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/cancel_automation.svg'),
            ],
            'remove_from_email_sequence'      => [
                'is_pro'      => true,
                'type'        => 'action',
                'title'       => __('Cancel Sequence Emails', 'fluent-crm'),
                'description' => __('Cancel Sequence Emails for the contact', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/cancel_sequence.svg')
            ],
            'add_to_email_sequence'           => [
                'is_pro'      => true,
                'type'        => 'action',
                'title'       => __('Set Sequence Emails', 'fluent-crm'),
                'description' => __('Send Automated Emails based on your Sequence settings', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/set_sequence.svg')
            ],
            'update_contact_property' => [
                'is_pro'      => true,
                'type'        => 'action',
                'title'       => __('Update Contact Property', 'fluent-crm'),
                'description' => __('Update custom fields or few main property of a contact', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/contact_update.svg')
            ],
            'end_this_funnel'                 => [
                'is_pro'      => true,
                'type'        => 'action',
                'title'       => __('End This Funnel Here', 'fluent-crm'),
                'description' => __('No further action will run once a contact hit this point', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/end_funnel.svg')
            ],
            'fcrm_check_user_prop' => [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title' => __('Check Contact\'s Properties', 'fluent-crm'),
                'description' => __('Check If the contact match specific data properties', 'fluent-crm'),
                'icon'             => fluentCrmMix('images/funnel_icons/has_tag.svg'),
            ]
        ];

        if (class_exists('\Easy_Digital_Downloads')) {
            $blocks['edd_update_payment_status_benchmark'] = [
                'is_pro'      => true,
                'type'        => 'benchmark',
                'title'       => __('New Order Success in EDD', 'fluent-crm'),
                'description' => __('This will run once new order will be placed as processing in EDD', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/new_order_edd.svg')
            ];
            $blocks['fcrm_edd_is_purchased'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Check if the contact purchased a specific product', 'fluent-crm'),
                'description' => __('Check If user purchased selected products and run sequences conditionally', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/edd_purchased.svg')
            ];
        }

        if (defined('WC_PLUGIN_FILE')) {
            $blocks['fcrm_woo_is_purchased'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Check if the contact purchased a specific product', 'fluent-crm'),
                'description' => __('Check If user purchased selected products and run sequences conditionally', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/woo_purchased.svg')
            ];

            $blocks['woocommerce_order_status_processing_benchmark'] = [
                'is_pro'      => true,
                'type'        => 'benchmark',
                'title'       => __('Order Received in WooCommerce', 'fluent-crm'),
                'description' => __('This will run once new order has been placed as processing', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/new_order_woo.svg'),
            ];
        }

        if (defined('LEARNDASH_VERSION')) {
            $blocks['fcrm_learndhash_is_in_course'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[LearnDash] Check if the contact enroll a course', 'fluent-crm'),
                'description' => __('Conditionally check if contact enrolled or completed a course', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/ld_in_course.svg')
            ];
            $blocks['fcrm_learndhash_is_in_group'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[LearnDash] Check if the contact is in a group', 'fluent-crm'),
                'description' => __('Conditionally check if contact is in a group', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/ld_in_group.svg')
            ];
        }

        if (defined('LLMS_PLUGIN_FILE')) {
            $blocks['fcrm_lifter_is_in_course'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[LifterLMS] Check if the contact enroll a course', 'fluent-crm'),
                'description' => __('Conditionally check if contact enrolled or completed a course', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/lifter_in_course.svg')
            ];
            $blocks['fcrm_lifter_is_in_membership'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[LifterLMS] Check if the contact has a Membership', 'fluent-crm'),
                'description' => __('Conditionally check if contact has an active membership level', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/lifter_has_membership.svg')
            ];
        }

        if (defined('TUTOR_VERSION')) {
            $blocks['fcrm_tutor_is_in_membership'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[TutorLMS] Check if the contact enroll a course', 'fluent-crm'),
                'description' => __('Conditionally check if contact enrolled or completed a course', 'fluent-crm'),
                'icon'        => fluentCrmMix('images/funnel_icons/tutor_in_course.svg')
            ];
        }

        add_filter('fluentcrm_funnel_blocks', function ($funnelBlocks) use ($blocks) {
            return array_merge($funnelBlocks, $blocks);
        }, 100);

    }

    private function getCrmTriggers()
    {
        return [
            'fluentcrm_contact_added_to_tags'      => [
                'category'    => 'CRM',
                'label'       => __('Tag Applied', 'fluent-crm'),
                'description' => __('This will run when selected tags have been applied to a contact', 'fluent-crm'),
                'disabled'    => true
            ],
            'fluentcrm_contact_removed_from_tags'  => [
                'category'    => 'CRM',
                'label'       => __('Tag Removed', 'fluent-crm'),
                'description' => __('This will run when selected Tags have been removed from a contact', 'fluent-crm'),
                'disabled'    => true
            ],
            'fluentcrm_contact_removed_from_lists' => [
                'category'    => 'CRM',
                'label'       => __('List Removed', 'fluent-crm'),
                'description' => __('This will run when selected lists have been removed from a contact', 'fluent-crm'),
                'disabled'    => true
            ],
            'fluentcrm_contact_added_to_lists'     => [
                'category'    => 'CRM',
                'label'       => __('List Applied', 'fluent-crm'),
                'description' => __('This will run when selected lists have been applied to a contact', 'fluent-crm'),
                'disabled'    => true
            ]
        ];
    }

}
