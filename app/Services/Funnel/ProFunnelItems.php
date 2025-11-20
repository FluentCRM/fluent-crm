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
                'category'    => 'WooCommerce',
                'label'       => 'New Order (Processing)',
                'icon'        => 'fc-icon-woo_new_order',
                'description' => 'This funnel will start once a new order will be added as processing',
                'disabled'    => true
            ];

            $triggers['woocommerce_order_status_completed'] = [
                'category'    => 'WooCommerce',
                'label'       => 'Order Completed',
                'icon'        => 'fc-icon-woo_order_complete',
                'description' => 'This funnel will start when an order is completed',
                'disabled'    => true
            ];

            $triggers['woocommerce_order_status_refunded'] = [
                'category'    => 'WooCommerce',
                'label'       => 'Order Refunded',
                'icon'        => 'fc-icon-woo_refund',
                'description' => 'This funnel will start when an order is refunded',
                'disabled'    => true
            ];

            $triggers['woocommerce_order_status_changed'] = [
                'category'    => 'WooCommerce',
                'label'       => 'Order Status Changed',
                'icon'        => 'fc-icon-woo',
                'description' => 'This funnel will start when an order status changes',
                'disabled'    => true
            ];
        }

        if (defined('WCS_INIT_TIMESTAMP')) {
            $triggers['woocommerce_subscription_status_active'] = [
                'category'    => 'WooCommerce',
                'label'       => 'Subscription activated',
                'icon'        => 'fc-icon-woo_order_complete',
                'description' => 'This funnel will start when a WooCommerce subscription begins or its status changes to active.',
                'disabled'    => true
            ];

            $triggers['woocommerce_subscription_renewal_payment_complete'] = [
                'category'    => 'WooCommerce',
                'label'       => 'Renewal Payment Received',
                'icon'        => 'fc-icon-woo_order_complete',
                'description' => 'This funnel will start when a recurring payment received for a subscription',
                'disabled'    => true
            ];

            $triggers['woocommerce_subscription_renewal_payment_failed'] = [
                'category'    => 'WooCommerce',
                'label'       => 'Renewal Payment Failed',
                'icon'        => 'fc-icon-woo_refund',
                'description' => 'This funnel will start when a subscription payment fails',
                'disabled'    => true
            ];

            $triggers['woocommerce_subscription_status_cancelled'] = [
                'category'    => 'WooCommerce',
                'label'       => 'WooCommerce Subscription Cancelled',
                'icon'        => 'fc-icon-woo_refund',
                'description' => 'This funnel will start when a WooCommerce subscription is cancelled.',
                'disabled'    => true
            ];
        }

        if (defined('WLM3_PLUGIN_VERSION')) {
            $triggers['wishlistmember_add_user_levels'] = [
                'category'    => 'Wishlist Member',
                'label'       => 'Membership Enrolled',
                'icon'        => 'fc-icon-wishlist',
                'description' => 'This funnel runs when a member is added to a membership level',
                'disabled'    => true
            ];
        }

        if (defined('TUTOR_VERSION')) {
            $triggers['tutor_after_enrolled'] = [
                'category'    => 'TutorLMS',
                'label'       => 'Course Enrolled',
                'icon'        => 'fc-icon-tutor_lms_enrollment_course',
                'description' => 'This funnel runs when a student is enrolled in a course',
                'disabled'    => true
            ];

            $triggers['tutor_course_complete_after'] = [
                'category'    => 'TutorLMS',
                'label'       => 'Course Completed',
                'icon'        => 'fc-icon-tutor_lms_complete_course',
                'description' => 'This funnel runs when a student completes a course',
                'disabled'    => true
            ];

            $triggers['tutor_lesson_completed_after'] = [
                'category'    => 'TutorLMS',
                'label'       => 'Lesson Completed',
                'icon'        => 'fc-icon-tutor_lms_complete_course',
                'description' => 'This funnel runs when a student completes a lesson',
                'disabled'    => true
            ];
        }

        if (class_exists('\Restrict_Content_Pro')) {
            $triggers['rcp_membership_post_activate'] = [
                'category'    => 'Restrict Content Pro',
                'label'       => 'Membership Enrolled',
                'icon'        => 'fc-icon-rcp_membership_level',
                'description' => 'This funnel runs when a member is added to a membership level',
                'disabled'    => true
            ];

            $triggers['rcp_transition_membership_status_expired'] = [
                'category'    => 'Restrict Content Pro',
                'label'       => 'Membership Expired',
                'icon'        => 'fc-icon-rcp_membership_cancle',
                'description' => 'This funnel runs when a membership expires',
                'disabled'    => true
            ];

            $triggers['rcp_membership_post_cancel'] = [
                'category'    => 'Restrict Content Pro',
                'label'       => 'Membership Cancelled',
                'icon'        => 'fc-icon-rcp_membership_cancle',
                'description' => 'This funnel runs when a membership is cancelled',
                'disabled'    => true
            ];
        }

        if (defined('PMPRO_VERSION')) {
            $triggers['pmpro_after_change_membership_level'] = [
                'category'    => 'Paid Membership Pro',
                'label'       => 'Membership Enrolled',
                'icon'        => 'fc-icon-paid_membership_pro_user_level',
                'description' => 'This funnel runs when a member is added to a membership level',
                'disabled'    => true
            ];

            $triggers['pmpro_membership_post_membership_expiry'] = [
                'category'    => 'Paid Membership Pro',
                'label'       => 'Membership Level Expired',
                'icon'        => 'fc-icon-membership_level_ex_pmp',
                'description' => 'This funnel runs when a membership expires',
                'disabled'    => true
            ];
        }

        if (defined('MEPR_PLUGIN_NAME')) {
            $triggers['mepr-account-is-active'] = [
                'category'    => 'MemberPress',
                'label'       => 'Membership Enrolled',
                'icon'        => 'fc-icon-memberpress_membership',
                'description' => 'This funnel runs when a member is added to a membership level',
                'disabled'    => true
            ];

            $triggers['mepr-event-transaction-expired'] = [
                'category'    => 'MemberPress',
                'label'       => 'Subscription expired',
                'icon'        => 'el-icon-circle-close',
                'description' => 'This funnel runs when a subscription expires',
                'disabled'    => true
            ];
        }

        if (defined('LLMS_PLUGIN_FILE')) {
            $triggers['llms_user_enrolled_in_course'] = [
                'category'    => 'LifterLMS',
                'label'       => 'Course Enrolled',
                'icon'        => 'fc-icon-lifter_lms_course_enrollment',
                'description' => 'This funnel runs when a contact is enrolled in a course',
                'disabled'    => true
            ];

            $triggers['lifterlms_course_completed'] = [
                'category'    => 'LifterLMS',
                'label'       => 'Course Completed',
                'icon'        => 'fc-icon-lifter_lms_complete_course',
                'description' => 'This funnel runs when a student completes a course',
                'disabled'    => true
            ];

            $triggers['llms_user_added_to_membership_level'] = [
                'category'    => 'LifterLMS',
                'label'       => 'Joined Membership',
                'icon'        => 'fc-icon-lifter_lms_membership',
                'description' => 'This funnel runs when a student has been enrolled in a membership level',
                'disabled'    => true
            ];

            $triggers['lifterlms_lesson_completed'] = [
                'category'    => 'LifterLMS',
                'label'       => 'Lesson Completed',
                'icon'        => 'fc-icon-lifter_lms_complete_lession-t2',
                'description' => 'This funnel runs when a student completes a lesson',
                'disabled'    => true
            ];
        }

        if (defined('LEARNDASH_VERSION')) {
            $triggers['learndash_update_course_access'] = [
                'category'    => 'LearnDash',
                'label'       => 'Course Enrolled',
                'icon'        => 'fc-icon-learndash_enroll_course',
                'description' => 'This funnel runs when a student is enrolled in a course',
                'disabled'    => true
            ];

            $triggers['learndash_lesson_completed'] = [
                'category'    => 'LearnDash',
                'label'       => 'Lesson Completed',
                'icon'        => 'fc-icon-learndash_complete_lesson',
                'description' => 'This funnel runs a student completes a lesson',
                'disabled'    => true
            ];

            $triggers['learndash_topic_completed'] = [
                'category'    => 'LearnDash',
                'label'       => 'Topic Completed',
                'icon'        => 'fc-icon-learndash_complete_topic',
                'description' => 'This funnel runs when a student completes a lesson topic',
                'disabled'    => true
            ];

            $triggers['learndash_course_completed'] = [
                'category'    => 'LearnDash',
                'label'       => 'Course Completed',
                'icon'        => 'fc-icon-learndash_complete_course',
                'description' => 'This funnel runs when a student completes a course',
                'disabled'    => true
            ];

            $triggers['ld_added_group_access'] = [
                'category'    => 'LearnDash',
                'label'       => 'Group Enrolled',
                'icon'        => 'fc-icon-learndash_course_group',
                'description' => 'This funnel runs when a user is enrolled in a group',
                'disabled'    => true
            ];

            $triggers['simulated_learndash_update_course_removed'] = [
                'category'    => 'LearnDash',
                'label'       => 'Course Left',
                'icon'        => 'fc-icon-learndash_enroll_course',
                'description' => 'This funnel runs when a student leaves a course',
                'disabled'    => true
            ];
        }

        if (class_exists('\Easy_Digital_Downloads')) {
            $triggers['edd_update_payment_status'] = [
                'category'    => 'Easy Digital Downloads',
                'label'       => 'Edd - New Order Success',
                'icon'        => 'fc-icon-edd_new_order_success',
                'description' => 'This funnel will start once new order payment is successful',
                'disabled'    => true
            ];

            if (defined('EDD_SL_VERSION')) {
                $triggers['edd_sl_post_set_status'] = [
                    'category'    => 'Easy Digital Downloads',
                    'label'       => 'License Expired',
                    'description' => 'This funnel will start when a license gets expired',
                    'disabled'    => true
                ];
            }

            if (defined('EDD_RECURRING_VERSION')) {
                $triggers['edd_recurring_add_subscription_payment'] = [
                    'category'    => 'Easy Digital Downloads',
                    'label'       => 'Renewal Payment Received',
                    'description' => 'This funnel will start when a renewal payment is received for an active subscription',
                    'icon'        => 'fc-icon-edd_new_order_success',
                    'disabled'    => true
                ];

                $triggers['edd_subscription_status_change'] = [
                    'category'    => 'Easy Digital Downloads',
                    'label'       => 'Recurring Subscription Expired',
                    'description' => 'This funnel will start when a recurring subscription gets expired',
                    'icon'        => 'el-icon-circle-close',
                    'disabled'    => true
                ];
            }
        }

        if (class_exists('\Affiliate_WP')) {
            $triggers['affwp_set_affiliate_status'] = [
                'category'    => 'AffiliateWP',
                'label'       => 'New Affiliate Joined',
                'icon'        => 'fc-icon-trigger',
                'description' => 'This funnel will be initiated when a new affiliate gets approved/registered directly',
                'disabled'    => true
            ];
        }

        if (defined('SURECART_PLUGIN_FILE')) {
            $triggers['fluent_surecart_purchase_created_wrap'] = [
                'category'    => 'SureCart',
                'label'       => 'SureCart - New Order Success',
                'icon'        => 'el-icon-shopping-cart-full',
                'description' => 'This funnel will start when new order payment is successful',
                'disabled'    => true
            ];

            $triggers['fluent_surecart_purchase_refund_wrap'] = [
                'category'    => 'SureCart',
                'label'       => 'SureCart - Order Revoked',
                'icon'        => 'el-icon-sold-out',
                'description' => 'This funnel will start when order will be revoked',
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
                'title'       => 'Has In Selected Lists',
                'description' => 'Check If the contact has specific lists',
                'icon'        => 'fc-icon-has_list',
            ],
            'fcrm_has_contact_tag'            => [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => 'Has Selected tags',
                'description' => 'Check If the contact has specific tags',
                'icon'        => 'fc-icon-has_list',
            ],
            'fluencrm_benchmark_link_clicked' => [
                'is_pro'      => true,
                'type'        => 'benchmark',
                'title'       => 'Link Click',
                'description' => 'This will run once a subscriber click on this provided link',
                'icon'        => 'fc-icon-link_clicked',
            ],
            'send_campaign_email'             => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'Email',
                'title'       => 'Send Campaign Email',
                'description' => 'Send an Email from your existing campaign',
                'icon'        => 'fc-icon-send_campaign',
            ],
            'remove_from_funnel'              => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'CRM',
                'title'       => 'Cancel Automations',
                'description' => 'Pause/Cancel another automation for contact',
                'icon'        => 'fc-icon-cancel_automation',
            ],
            'remove_from_email_sequence'      => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'Email',
                'title'       => 'Cancel Sequence Emails',
                'description' => 'Cancel Sequence Emails for the contact',
                'icon'        => 'fc-icon-cancel_sequence',//fluentCrmMix('images/funnel_icons/cancel_sequence.svg')
            ],
            'add_to_email_sequence'           => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'Email',
                'title'       => 'Set Sequence Emails',
                'description' => 'Send Automated Emails based on your Sequence settings',
                'icon'        => 'fc-icon-set_sequence',
            ],
            'update_contact_property'         => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'CRM',
                'title'       => 'Update Contact Property',
                'description' => 'Update custom fields or few main property of a contact',
                'icon'        => 'fc-icon-wp_user_meta',
            ],
            'add_contact_activity' => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'CRM',
                'title'       => 'Add Notes & Activity',
                'description' => 'Add Notes or Activity to the Contact Profile',
                'icon'        => 'fc-icon-writing'
            ],
            'outgoing_webhook' => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'CRM',
                'title'       => 'Outgoing Webhook',
                'description' => 'Send Data to external server via GET or POST Method',
                'icon'        => 'fc-icon-webhooks',
            ],
            'end_this_funnel'                 => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'CRM',
                'title'       => 'End This Funnel Here',
                'description' => 'No further action will run once a contact hit this point',
                'icon'        => 'fc-icon-end_funnel',
            ],
            'fcrm_check_user_prop'            => [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => 'Check Contact\'s Properties',
                'description' => 'Check If the contact match specific data properties',
                'icon'        => 'fc-icon-check_contact_property_conditional',
            ],
            'user_registration_action'        => [
                'is_pro'      => true,
                'category'    => 'WordPress',
                'type'        => 'action',
                'title'       => 'Create WordPress User',
                'description' => 'Create WP User with a role if user is not already registered with contact email',
                'icon'        => 'fc-icon-create_wp_user',
            ],
            'fcrm_update_user_meta' => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'WordPress',
                'title'       => 'Update WP User Meta',
                'description' => 'Update WordPress User Meta Data',
                'icon'        => 'fc-icon-wp_user_meta',
            ],
            'fcrm_change_user_role' => [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'WordPress',
                'title'       => 'Change WP User Role',
                'description' => 'If user exist with the contact email then you can change user role',
                'icon'        => 'fc-icon-wp_user_role',
            ]
        ];

        if (class_exists('\Easy_Digital_Downloads')) {
            $blocks['edd_update_payment_status_benchmark'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => 'New Order Success in EDD',
                'description' => 'This will run once new order will be placed as processing in EDD',
                'icon'        => 'fc-icon-edd_new_order_success',
            ];
            $blocks['fcrm_edd_is_purchased'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => 'Check if the contact purchased a specific product',
                'description' => 'Check If user purchased selected products and run sequences conditionally',
                'icon'        => 'fc-icon-edd_new_order_success',
            ];
        }

        if (defined('WC_PLUGIN_FILE')) {
            $blocks['fcrm_woo_is_purchased'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => 'Check if the contact purchased a specific product',
                'description' => 'Check If user purchased selected products and run sequences conditionally',
                'icon'        => 'fc-icon-woo_purchased',
            ];

            $blocks['woocommerce_order_status_processing_benchmark'] = [
                'is_pro'      => true,
                'type'        => 'benchmark',
                'title'       => 'Order Received in WooCommerce',
                'description' => 'This will run once new order has been placed as processing',
                'icon'        => 'fc-icon-new_order_woo',
            ];
        }

        if (defined('LEARNDASH_VERSION')) {
            $blocks['fcrm_learndhash_is_in_course'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => '[LearnDash] Check if the contact enroll a course',
                'description' => 'Conditionally check if contact enrolled or completed a course',
                'icon'        => 'fc-icon-ld_in_course',
            ];
            $blocks['fcrm_learndhash_is_in_group'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => '[LearnDash] Check if the contact is in a group',
                'description' => 'Conditionally check if contact is in a group',
                'icon'        => 'fc-icon-ld_in_group',
            ];
            $blocks['fcrm_learndhash_add_to_course'] = [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'LearnDash',
                'title'       => 'Enroll To Course',
                'description' => 'Enroll the contact to a specific LMS Course',
                'icon'        => 'fc-icon-learndash_enroll_course',
            ];
            $blocks['fcrm_learndhash_add_to_group'] = [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'LearnDash',
                'title'       => 'Enroll To Group',
                'description' => 'Enroll the contact to a specific LMS Group',
                'icon'        => '',
            ];
        }

        if (defined('LLMS_PLUGIN_FILE')) {
            $blocks['fcrm_lifter_is_in_course'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => '[LifterLMS] Check if the contact enroll a course',
                'description' => 'Conditionally check if contact enrolled or completed a course',
                'icon'        => 'fc-icon-lifter_lms_course_enrollment',
            ];
            $blocks['fcrm_lifter_is_in_membership'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => '[LifterLMS] Check if the contact has a Membership',
                'description' => 'Conditionally check if contact has an active membership level',
                'icon'        => 'fc-icon-lifter_lms_membership',
            ];
            $blocks['fcrm_lifter_add_to_course'] = [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'LifterLMS',
                'title'       => 'Enroll To Course',
                'description' => 'Enroll the contact to a specific LMS Course',
                'icon'        => '',
            ];
            $blocks['fcrm_lifterlms_add_to_group'] = [
                'is_pro'      => true,
                'type'        => 'action',
                'category'    => 'LifterLMS',
                'title'       => 'Enroll To Membership',
                'description' => 'Enroll the contact to a specific LMS Membership',
                'icon'        => '',
            ];
        }

        if (defined('TUTOR_VERSION')) {
            $blocks['fcrm_tutor_is_in_membership'] = [
                'is_pro'      => true,
                'type'        => 'conditional',
                'title'       => '[TutorLMS] Check if the contact enroll a course',
                'description' => 'Conditionally check if contact enrolled or completed a course',
                'icon'        => 'fc-icon-tutor_in_course',
            ];
        }

        if (defined('SURECART_PLUGIN_FILE')) {
            $blocks['fluent_surecart_purchase_created_wrap'] = [
                'is_pro'      => true,
                'type'        => 'benchmark',
                'title'       => 'Order Received in SureCart',
                'description' => 'This will run once new order has been placed as processing',
                'icon'        => 'el-icon-shopping-cart-full',
            ];
        }

        return $blocks;

    }

    private function getCrmTriggers()
    {
        return [
            'fluentcrm_contact_added_to_lists'     => [
                'category'    => 'CRM',
                'label'       => 'List Applied',
                'icon'        => 'fc-icon-list_applied_2',
                'description' => 'This will run when selected lists have been applied to a contact',
                'disabled'    => true
            ],

            'fluentcrm_contact_removed_from_lists' => [
                'category'    => 'CRM',
                'label'       => 'List Removed',
                'icon'        => 'fc-icon-list_removed',
                'description' => 'This will run when selected lists have been removed from a contact',
                'disabled'    => true
            ],

            'fluentcrm_contact_added_to_tags'      => [
                'category'    => 'CRM',
                'label'       => 'Tag Applied',
                'icon'        => 'fc-icon-tag_applied',
                'description' => 'This will run when selected tags have been applied to a contact',
                'disabled'    => true
            ],

            'fluentcrm_contact_removed_from_tags'  => [
                'category'    => 'CRM',
                'label'       => 'Tag Removed',
                'icon'        => 'fc-icon-tag_removed',
                'description' => 'This will run when selected Tags have been removed from a contact',
                'disabled'    => true
            ],

            'fluent_crm/contact_birthday'      => [
                'category'    => 'CRM',
                'label'       => 'Contact\'s Birthday',
                'icon'        => '', // 'fc-icon-tag_applied',
                'description' => 'Funnel will be initiated on the day of contact\'s birthday',
                'disabled'    => true
            ],

            'fluent_crm/contact_created'      => [
                'category'    => 'CRM',
                'label'       => 'Contact Created',
                'icon'        => '', // 'fc-icon-tag_applied',
                'description' => 'This will run when a new contact will be added',
                'disabled'    => true
            ],

            'fluent_crm/company_applied'      => [
                'category'    => 'CRM',
                'label'       => 'Company Applied',
                'icon'        => '', // 'fc-icon-tag_applied',
                'description' => 'This will run when selected companies have been applied to a contact',
                'disabled'    => true
            ],

            'fluent_crm/company_removed'      => [
                'category'    => 'CRM',
                'label'       => 'Company Removed',
                'icon'        => '', // 'fc-icon-tag_applied',
                'description' => 'This will run when selected companies have been removed from a contact',
                'disabled'    => true
            ],

            'user_login'      => [
                'category'    => 'WordPress Triggers',
                'label'       => 'User Login',
                'icon'        => 'fc-icon-wp_new_user_signup',
                'description' => 'This Funnel will be initiated when a user login to your site',
                'disabled'    => true
            ]
        ];
    }

}
