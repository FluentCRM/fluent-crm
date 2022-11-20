<?php

namespace FluentCrm\App\Services\Funnel;

class ProFunnelItems
{
    public function __construct()
    {
        add_filter('fluentcrm_funnel_triggers', function ($allTriggers) {
            $triggers = $this->getProTriggers();
            return array_merge($allTriggers, $triggers);
        });

        add_filter('fluentcrm_funnel_blocks', function ($funnelBlocks) {
            $blocks = $this->getProBlocks();
            return array_merge($funnelBlocks, $blocks);
        }, 100);

    }

    private function getProTriggers()
    {
        $triggers = $this->getCrmTriggers();
        if (defined('WC_PLUGIN_FILE')) {
            $triggers['woocommerce_order_status_processing'] = [
                'category'    => __('WooCommerce', 'fluent-crm'),
                'label'       => __('New Order (Processing)', 'fluent-crm'),
                'icon'        => 'fc-icon-woo_new_order',
                'description' => __('This Funnel will start once new order will be added as processing', 'fluent-crm'),
                'disabled'    => true
            ];

            $triggers['woocommerce_order_status_completed'] = [
                'category'    => __('WooCommerce', 'fluent-crm'),
                'label'       => __('Order Completed', 'fluent-crm'),
                'icon'        => 'fc-icon-woo_order_complete',
                'description' => __('This Funnel will start once new order has been marked as completed', 'fluent-crm'),
                'disabled'    => true
            ];

            $triggers['woocommerce_order_status_refunded'] = [
                'category'    => __('WooCommerce', 'fluent-crm'),
                'label'       => __('Order Refunded', 'fluent-crm'),
                'icon'        => 'fc-icon-woo_refund',
                'description' => __('This Funnel will start once new order has been marked as completed', 'fluent-crm'),
                'disabled'    => true
            ];

            $triggers['woocommerce_order_status_changed'] = [
                'category'    => __('WooCommerce', 'fluent-crm'),
                'label'       => __('Order Status Changed', 'fluent-crm'),
                'icon'        => 'fc-icon-woo',
                'description' => __('This Funnel will start when a Order status will change from one state to another', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('WLM3_PLUGIN_VERSION')) {
            $triggers['wishlistmember_add_user_levels'] = [
                'category'    => __('Wishlist Member', 'fluent-crm'),
                'label'       => __('A member added to a membership level', 'fluent-crm'),
                'icon'        => 'fc-icon-wishlist',
                'description' => __('This funnel will start when a member is added to a level', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('TUTOR_VERSION')) {
            $triggers['tutor_course_complete_after'] = [
                'category'    => __('TutorLMS', 'fluent-crm'),
                'label'       => __('Student completes a Course', 'fluent-crm'),
                'icon'        => 'fc-icon-tutor_lms_complete_course',
                'description' => __('This Funnel will start a student completes a Course', 'fluent-crm'),
                'disabled'    => true
            ];

            $triggers['lifterlms_lesson_completed'] = [
                'category'    => __('TutorLMS', 'fluent-crm'),
                'label'       => __('Student Complete a Lesson', 'fluent-crm'),
                'icon'        => 'fc-icon-tutor_lms_complete_course',
                'description' => __('This Funnel will start a student completes a lesson', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (class_exists('\Restrict_Content_Pro')) {
            $triggers['rcp_membership_post_activate'] = [
                'category'    => __('Restrict Content Pro', 'fluent-crm'),
                'label'       => __('A member added to a membership level', 'fluent-crm'),
                'icon'        => 'fc-icon-rcp_membership_level',
                'description' => __('This funnel will start when a member is added to a level for the first time', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('PMPRO_VERSION')) {
            $triggers['pmpro_after_change_membership_level'] = [
                'category'    => __('Paid Membership Pro', 'fluent-crm'),
                'label'       => __('Membership Level assignment of a User', 'fluent-crm'),
                'icon'        => 'fc-icon-paid_membership_pro_user_level',
                'description' => __('This funnel will start when a user is assigned to specified membership levels', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('MEPR_PLUGIN_NAME')) {
            $triggers['mepr-account-is-active'] = [
                'category'    => __('MemberPress', 'fluent-crm'),
                'label'       => __('A member added to a membership level', 'fluent-crm'),
                'icon'        => 'fc-icon-memberpress_membership',
                'description' => __('This funnel will start when a membership level get activated for a member', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('LLMS_PLUGIN_FILE')) {
            $triggers['llms_user_enrolled_in_course'] = [
                'category'    => __('LifterLMS', 'fluent-crm'),
                'label'       => __('Enrollment in a course', 'fluent-crm'),
                'icon'        => 'fc-icon-lifter_lms_course_enrollment',
                'description' => __('This funnel will start when a contact has been enrolled in a course', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['llms_user_added_to_membership_level'] = [
                'category'    => __('LifterLMS', 'fluent-crm'),
                'label'       => __('Enrollment in a Membership', 'fluent-crm'),
                'icon'        => 'fc-icon-lifter_lms_membership',
                'description' => __('This Funnel will start when a student has been enrolled in a membership level', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['lifterlms_lesson_completed'] = [
                'category'    => __('LifterLMS', 'fluent-crm'),
                'label'       => __('Student completes a Lesson', 'fluent-crm'),
                'icon'        => 'fc-icon-lifter_lms_complete_lession-t2',
                'description' => __('This Funnel will start when a student completes a lesson', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (defined('LEARNDASH_VERSION')) {
            $triggers['learndash_update_course_access'] = [
                'category'    => __('LearnDash', 'fluent-crm'),
                'label'       => __('Enrolls in a Course', 'fluent-crm'),
                'icon'        => 'fc-icon-learndash_enroll_course',
                'description' => __('This funnel will start when a student is enrolled in a course', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['learndash_lesson_completed'] = [
                'category'    => __('LearnDash', 'fluent-crm'),
                'label'       => __('Completes a Lesson', 'fluent-crm'),
                'icon'        => 'fc-icon-learndash_complete_lesson',
                'description' => __('This Funnel will start a student completes a lesson', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['learndash_topic_completed'] = [
                'category'    => __('LearnDash', 'fluent-crm'),
                'label'       => __('Completes a Topic', 'fluent-crm'),
                'icon'        => 'fc-icon-learndash_complete_topic',
                'description' => __('This funnel will start when a user is completes a lesson topic', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['learndash_course_completed'] = [
                'category'    => __('LearnDash', 'fluent-crm'),
                'label'       => __('Completes a Course', 'fluent-crm'),
                'icon'        => 'fc-icon-learndash_complete_course',
                'description' => __('This Funnel will start when a student completes a course', 'fluent-crm'),
                'disabled'    => true
            ];
            $triggers['ld_added_group_access'] = [
                'category'    => __('LearnDash', 'fluent-crm'),
                'label'       => __('Enrolls in a Group', 'fluent-crm'),
                'icon'        => 'fc-icon-learndash_course_group',
                'description' => __('This funnel will start when a user is enrolled in a group', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        if (class_exists('\Easy_Digital_Downloads')) {
            $triggers['edd_update_payment_status'] = [
                'category'    => __('Easy Digital Downloads', 'fluent-crm'),
                'label'       => __('Edd - New Order Success', 'fluent-crm'),
                'icon'        => 'fc-icon-edd_new_order_success',
                'description' => __('This Funnel will start once new order will be added as successful payment', 'fluent-crm'),
                'disabled'    => true
            ];

            if (defined('EDD_SL_VERSION')) {
                $triggers['edd_sl_post_set_status'] = [
                    'category'    => __('Easy Digital Downloads', 'fluentcampaign-pro'),
                    'label'       => __('License Expired', 'fluentcampaign-pro'),
                    'description' => __('This Funnel will start a license status get marked as expired', 'fluentcampaign-pro'),
                    'disabled'    => true
                ];
            }

            if (defined('EDD_RECURRING_VERSION')) {
                $triggers['edd_recurring_add_subscription_payment'] = [
                    'category'    => __('Easy Digital Downloads', 'fluentcampaign-pro'),
                    'label'       => __('Renewal Payment Received', 'fluentcampaign-pro'),
                    'description' => __('This Funnel will start once a Renewal Payment received for an active subscription', 'fluentcampaign-pro'),
                    'icon'        => 'fc-icon-edd_new_order_success',
                    'disabled'    => true
                ];
                $triggers['edd_subscription_status_change'] = [
                    'category'    => __('Easy Digital Downloads', 'fluentcampaign-pro'),
                    'label'       => __('Recurring Subscription Expired', 'fluentcampaign-pro'),
                    'description' => __('This Funnel will start once a Recurring Subscription status changed to expired', 'fluentcampaign-pro'),
                    'icon'        => 'el-icon-circle-close',
                    'disabled'    => true
                ];
            }
        }

        if (class_exists('\Affiliate_WP')) {
            $triggers['affwp_set_affiliate_status'] = [
                'category'    => __('AffiliateWP', 'fluent-crm'),
                'label'       => __('AffiliateWP - New Affiliate Approved/Active Register', 'fluent-crm'),
                'icon'        => 'fc-icon-trigger',
                'description' => __('This Funnel will be initiated when affiliate will be approved or register as direct approved', 'fluent-crm'),
                'disabled'    => true
            ];
        }

        return $triggers;

    }

    private function getProBlocks()
    {
        $blocks = [
            'fcrm_has_contact_list'           => [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Has In Selected Lists', 'fluent-crm'),
                'description' => __('Check If the contact has specific lists', 'fluent-crm'),
                'icon'        => 'fc-icon-has_list',//fluentCrmMix('images/funnel_icons/has_list.svg'),
            ],
            'fcrm_has_contact_tag'            => [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Has Selected tags', 'fluent-crm'),
                'description' => __('Check If the contact has specific tags', 'fluent-crm'),
                'icon'        => 'fc-icon-has_list',//fluentCrmMix('images/funnel_icons/has_tag.svg')
            ],
            'fluencrm_benchmark_link_clicked' => [
                'is_pro'      => true,
                'type'        => 'benchmark',
                'title'       => __('Link Click', 'fluent-crm'),
                'description' => __('This will run once a subscriber click on this provided link', 'fluent-crm'),
                'icon'        => 'fc-icon-link_clicked',//fluentCrmMix('images/funnel_icons/link_clicked.svg'),
            ],
            'send_campaign_email'             => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('Email', 'fluent-crm'),
                'title'       => __('Send Campaign Email', 'fluent-crm'),
                'description' => __('Send an Email from your existing campaign', 'fluent-crm'),
                'icon'        => 'fc-icon-send_campaign',//fluentCrmMix('images/funnel_icons/send_campaign.svg')
            ],
            'remove_from_funnel'              => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('CRM', 'fluent-crm'),
                'title'       => __('Cancel Automations', 'fluent-crm'),
                'description' => __('Pause/Cancel another automation for contact', 'fluent-crm'),
                'icon'        => 'fc-icon-cancel_automation',
            ],
            'remove_from_email_sequence'      => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('Email', 'fluent-crm'),
                'title'       => __('Cancel Sequence Emails', 'fluent-crm'),
                'description' => __('Cancel Sequence Emails for the contact', 'fluent-crm'),
                'icon'        => 'fc-icon-cancel_sequence',//fluentCrmMix('images/funnel_icons/cancel_sequence.svg')
            ],
            'add_to_email_sequence'           => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('Email', 'fluent-crm'),
                'title'       => __('Set Sequence Emails', 'fluent-crm'),
                'description' => __('Send Automated Emails based on your Sequence settings', 'fluent-crm'),
                'icon'        => 'fc-icon-set_sequence',
            ],
            'update_contact_property'         => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('CRM', 'fluent-crm'),
                'title'       => __('Update Contact Property', 'fluent-crm'),
                'description' => __('Update custom fields or few main property of a contact', 'fluent-crm'),
                'icon'        => 'fc-icon-wp_user_meta',
            ],
            'add_contact_activity' => [
                'is_pro'      => true,
                'type'        => 'action',
                'category' => __('CRM', 'fluent-crm'),
                'title'       => __('Add Notes & Activity', 'fluent-crm'),
                'description' => __('Add Notes or Activity to the Contact Profile', 'fluent-crm'),
                'icon'        => 'fc-icon-writing'
            ],
            'outgoing_webhook' => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('CRM', 'fluent-crm'),
                'title'       => __('Outgoing Webhook', 'fluent-crm'),
                'description' => __('Send Data to external server via GET or POST Method', 'fluent-crm'),
                'icon'        => 'fc-icon-webhooks',
            ],
            'end_this_funnel'                 => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('CRM', 'fluent-crm'),
                'title'       => __('End This Funnel Here', 'fluent-crm'),
                'description' => __('No further action will run once a contact hit this point', 'fluent-crm'),
                'icon'        => 'fc-icon-end_funnel',
            ],
            'fcrm_check_user_prop'            => [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Check Contact\'s Properties', 'fluent-crm'),
                'description' => __('Check If the contact match specific data properties', 'fluent-crm'),
                'icon'        => 'fc-icon-check_contact_property_conditional',
            ],
            'user_registration_action'        => [
                'is_pro'      => true,
                'category'    => __('WordPress', 'fluent-crm'),
                'type'        => 'action',
                'title'       => __('Create WordPress User', 'fluent-crm'),
                'description' => __('Create WP User with a role if user is not already registered with contact email', 'fluent-crm'),
                'icon'        => 'fc-icon-create_wp_user',//fluentCrmMix('images/funnel_icons/user_register.svg'),
            ],
            'fcrm_update_user_meta' => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('WordPress', 'fluent-crm'),
                'title'       => __('Update WP User Meta', 'fluent-crm'),
                'description' => __('Update WordPress User Meta Data', 'fluent-crm'),
                'icon'        => 'fc-icon-wp_user_meta',//fluentCrmMix('images/funnel_icons/wp_user_meta.svg'),
            ],
            'fcrm_change_user_role' => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('WordPress', 'fluent-crm'),
                'title'       => __('Change WP User Role', 'fluent-crm'),
                'description' => __('If user exist with the contact email then you can change user role', 'fluent-crm'),
                'icon'        => 'fc-icon-wp_user_role',
            ]
        ];

        if (class_exists('\Easy_Digital_Downloads')) {
            $blocks['edd_update_payment_status_benchmark'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('New Order Success in EDD', 'fluent-crm'),
                'description' => __('This will run once new order will be placed as processing in EDD', 'fluent-crm'),
                'icon'        => 'fc-icon-edd_new_order_success',//fluentCrmMix('images/funnel_icons/new_order_edd.svg')
            ];
            $blocks['fcrm_edd_is_purchased'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Check if the contact purchased a specific product', 'fluent-crm'),
                'description' => __('Check If user purchased selected products and run sequences conditionally', 'fluent-crm'),
                'icon'        => 'fc-icon-edd_new_order_success',
            ];
        }

        if (defined('WC_PLUGIN_FILE')) {
            $blocks['fcrm_woo_is_purchased'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('Check if the contact purchased a specific product', 'fluent-crm'),
                'description' => __('Check If user purchased selected products and run sequences conditionally', 'fluent-crm'),
                'icon'        => 'fc-icon-woo_purchased',//fluentCrmMix('images/funnel_icons/woo_purchased.svg')
            ];

            $blocks['woocommerce_order_status_processing_benchmark'] = [
                'is_pro'      => true,
                'type'        => 'benchmark',
                'title'       => __('Order Received in WooCommerce', 'fluent-crm'),
                'description' => __('This will run once new order has been placed as processing', 'fluent-crm'),
                'icon'        => 'fc-icon-new_order_woo',//fluentCrmMix('images/funnel_icons/new_order_woo.svg'),
            ];
        }

        if (defined('LEARNDASH_VERSION')) {
            $blocks['fcrm_learndhash_is_in_course'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[LearnDash] Check if the contact enroll a course', 'fluent-crm'),
                'description' => __('Conditionally check if contact enrolled or completed a course', 'fluent-crm'),
                'icon'        => 'fc-icon-ld_in_course',//fluentCrmMix('images/funnel_icons/ld_in_course.svg')
            ];
            $blocks['fcrm_learndhash_is_in_group'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[LearnDash] Check if the contact is in a group', 'fluent-crm'),
                'description' => __('Conditionally check if contact is in a group', 'fluent-crm'),
                'icon'        => 'fc-icon-ld_in_group',//fluentCrmMix('images/funnel_icons/ld_in_group.svg')
            ];
            $blocks['fcrm_learndhash_add_to_course'] = [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('LearnDash', 'fluent-crm'),
                'title'       => __('Enroll To Course', 'fluent-crm'),
                'description' => __('Enroll the contact to a specific LMS Course', 'fluent-crm'),
                'icon'        => 'fc-icon-learndash_enroll_course',
            ];
            $blocks['fcrm_learndhash_add_to_group'] = [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('LearnDash', 'fluent-crm'),
                'title'       => __('Enroll To Group', 'fluent-crm'),
                'description' => __('Enroll the contact to a specific LMS Group', 'fluent-crm'),
                'icon'        => '',
            ];
        }

        if (defined('LLMS_PLUGIN_FILE')) {
            $blocks['fcrm_lifter_is_in_course'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[LifterLMS] Check if the contact enroll a course', 'fluent-crm'),
                'description' => __('Conditionally check if contact enrolled or completed a course', 'fluent-crm'),
                'icon'        => 'fc-icon-lifter_lms_course_enrollment',
            ];
            $blocks['fcrm_lifter_is_in_membership'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[LifterLMS] Check if the contact has a Membership', 'fluent-crm'),
                'description' => __('Conditionally check if contact has an active membership level', 'fluent-crm'),
                'icon'        => 'fc-icon-lifter_lms_membership',//fluentCrmMix('images/funnel_icons/lifter_has_membership.svg')
            ];
            $blocks['fcrm_lifter_add_to_course'] = [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('LifterLMS', 'fluent-crm'),
                'title'       => __('Enroll To Course', 'fluent-crm'),
                'description' => __('Enroll the contact to a specific LMS Course', 'fluent-crm'),
                'icon'        => '',
            ];
            $blocks['fcrm_lifterlms_add_to_group'] = [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => __('LifterLMS', 'fluent-crm'),
                'title'       => __('Enroll To Membership', 'fluent-crm'),
                'description' => __('Enroll the contact to a specific LMS Membership', 'fluent-crm'),
                'icon'        => '',
            ];
        }

        if (defined('TUTOR_VERSION')) {
            $blocks['fcrm_tutor_is_in_membership'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => __('[TutorLMS] Check if the contact enroll a course', 'fluent-crm'),
                'description' => __('Conditionally check if contact enrolled or completed a course', 'fluent-crm'),
                'icon'        => 'fc-icon-tutor_in_course',//fluentCrmMix('images/funnel_icons/tutor_in_course.svg')
            ];
        }

        return $blocks;

    }

    private function getCrmTriggers()
    {
        return [
            'fluentcrm_contact_added_to_tags'      => [
                'category'    => __('CRM', 'fluent-crm'),
                'label'       => __('Tag Applied', 'fluent-crm'),
                'icon'        => 'fc-icon-tag_applied',
                'description' => __('This will run when selected tags have been applied to a contact', 'fluent-crm'),
                'disabled'    => true
            ],
            'fluentcrm_contact_removed_from_tags'  => [
                'category'    => __('CRM', 'fluent-crm'),
                'label'       => __('Tag Removed', 'fluent-crm'),
                'icon'        => 'fc-icon-tag_removed',
                'description' => __('This will run when selected Tags have been removed from a contact', 'fluent-crm'),
                'disabled'    => true
            ],
            'fluentcrm_contact_removed_from_lists' => [
                'category'    => __('CRM', 'fluent-crm'),
                'label'       => __('List Removed', 'fluent-crm'),
                'icon'        => 'fc-icon-list_removed',
                'description' => __('This will run when selected lists have been removed from a contact', 'fluent-crm'),
                'disabled'    => true
            ],
            'fluentcrm_contact_added_to_lists'     => [
                'category'    => __('CRM', 'fluent-crm'),
                'label'       => __('List Applied', 'fluent-crm'),
                'icon'        => 'fc-icon-list_applied_2',
                'description' => __('This will run when selected lists have been applied to a contact', 'fluent-crm'),
                'disabled'    => true
            ],
            'user_login'                           => [
                'category'    => __('WordPress Triggers', 'fluent-crm'),
                'label'       => __('User Login', 'fluent-crm'),
                'icon'        => 'fc-icon-wp_new_user_signup',
                'description' => __('This Funnel will be initiated when a user login to your site', 'fluent-crm'),
                'disabled'    => true
            ]
        ];
    }

}
