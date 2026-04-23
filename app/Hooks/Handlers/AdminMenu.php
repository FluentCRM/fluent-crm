<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Meta;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Tag;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\PermissionManager;
use FluentCrm\App\Services\TransStrings;
use FluentCrm\Framework\Support\Arr;

/**
 * Admin Menu Class
 *
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class AdminMenu
{
    public $version = FLUENTCRM_PLUGIN_VERSION;

    protected static $mainScriptLoaded = false;

    public function init()
    {

        add_action('admin_menu', array($this, 'addMenu'));

        if (isset($_GET['page']) && $_GET['page'] == 'fluentcrm-admin' && is_admin()) {
            $this->mayBeRedirect();
            $this->maybeInitExperimentalNavigation();

            // Maybe we have to update the database tables
            UpgradationHandler::maybeUpdateDbTables();

            add_action('admin_enqueue_scripts', array($this, 'loadAssets'), 1);
        }

    }

    public function addMenu()
    {
        $permissions = PermissionManager::currentUserPermissions();

        if (!$permissions) {
            return;
        }

        $dashboardPermission = 'fcrm_view_dashboard';
        $isAdmin = false;
        if (in_array('administrator', $permissions)) {
            $dashboardPermission = 'manage_options';
            $isAdmin = true;
        }

        $title = __('FluentCRM', 'fluent-crm');
        if (defined('FLUENTCAMPAIGN')) {
            $title = __('FluentCRM Pro', 'fluent-crm');
        }
        add_menu_page(
            $title,
            $title,
            $dashboardPermission,
            'fluentcrm-admin',
            array($this, 'render'),
            $this->getMenuIcon(),
            2
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Dashboard', 'fluent-crm'),
            __('Dashboard', 'fluent-crm'),
            $dashboardPermission,
            'fluentcrm-admin',
            array($this, 'render')
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Contacts', 'fluent-crm'),
            __('Contacts', 'fluent-crm'),
            ($isAdmin) ? $dashboardPermission : 'fcrm_read_contacts',
            'fluentcrm-admin#/subscribers',
            array($this, 'render')
        );

        if (in_array('fcrm_read_contacts', $permissions)) {
            if (Helper::isCompanyEnabled()) {
                add_submenu_page(
                    'fluentcrm-admin',
                    __('Companies', 'fluent-crm'),
                    __('Companies', 'fluent-crm'),
                    ($isAdmin) ? $dashboardPermission : 'fcrm_manage_contact_cats',
                    'fluentcrm-admin#/contact-groups/companies',
                    array($this, 'render')
                );
            }

            add_submenu_page(
                'fluentcrm-admin',
                __('Lists', 'fluent-crm'),
                __('Lists', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_manage_contact_cats',
                'fluentcrm-admin#/contact-groups/lists',
                array($this, 'render')
            );

            add_submenu_page(
                'fluentcrm-admin',
                __('Tags', 'fluent-crm'),
                __('Tags', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_manage_contact_cats',
                'fluentcrm-admin#/contact-groups/tags',
                array($this, 'render')
            );

        }

        if (in_array('fcrm_read_emails', $permissions)) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Campaigns', 'fluent-crm'),
                __('Campaigns', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_read_emails',
                'fluentcrm-admin#/email/campaigns',
                array($this, 'render')
            );

            add_submenu_page(
                'fluentcrm-admin',
                __('Recurring Campaigns', 'fluent-crm'),
                __('Recurring Campaigns', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_read_emails',
                'fluentcrm-admin#/email/recurring-campaigns',
                array($this, 'render')
            );

            add_submenu_page(
                'fluentcrm-admin',
                __('Email Sequences', 'fluent-crm'),
                __('Email Sequences', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_read_emails',
                'fluentcrm-admin#/email/sequences',
                array($this, 'render')
            );

            add_submenu_page(
                'fluentcrm-admin',
                __('Email Templates', 'fluent-crm'),
                __('Email Templates', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_read_emails',
                'fluentcrm-admin#/email/templates',
                array($this, 'render')
            );
        }

        if (in_array('fcrm_manage_forms', $permissions)) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Forms', 'fluent-crm'),
                __('Forms', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_manage_forms',
                'fluentcrm-admin#/forms',
                array($this, 'render')
            );
        }

        if (in_array('fcrm_read_funnels', $permissions)) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Automations', 'fluent-crm'),
                __('Automations', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_read_funnels',
                'fluentcrm-admin#/funnels',
                array($this, 'render')
            );
        }

        do_action('fluent_crm/after_core_menu_items', $permissions, $isAdmin);

        if (in_array('fcrm_manage_settings', $permissions)) {

            add_submenu_page(
                'fluentcrm-admin',
                __('Settings', 'fluent-crm'),
                __('Settings', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_manage_settings',
                'fluentcrm-admin#/settings',
                array($this, 'render')
            );

            add_submenu_page(
                'fluentcrm-admin',
                __('Reports', 'fluent-crm'),
                __('Reports', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_manage_settings',
                'fluentcrm-admin#/reports',
                array($this, 'render')
            );

            add_submenu_page(
                'fluentcrm-admin',
                __('Addons', 'fluent-crm'),
                __('Addons', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_manage_settings',
                'fluentcrm-admin#/add-ons',
                array($this, 'render')
            );
        }

        if (!defined('FLUENTMAIL_PLUGIN_VERSION')) {
            add_submenu_page(
                'fluentcrm-admin',
                __('SMTP', 'fluent-crm'),
                __('SMTP', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_view_dashboard',
                'fluentcrm-admin#/settings/smtp_settings',
                array($this, 'render')
            );
        }

        if (in_array('fcrm_view_dashboard', $permissions)) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Help', 'fluent-crm'),
                __('Help', 'fluent-crm'),
                ($isAdmin) ? $dashboardPermission : 'fcrm_view_dashboard',
                'fluentcrm-admin#/documentation',
                array($this, 'render')
            );
        }

    }

    public function render()
    {
        $this->changeFooter();
        $app = FluentCrm();

        /**
         * FluentCRM Admin App Loading Hook
         */
        do_action('fluentcrm_loading_app');

        wp_enqueue_script(
            'fluentcrm_admin_app_start',
            fluentCrmMix('/admin/js/start.js'),
            array('fluentcrm_admin_app_boot'),
            $this->version
        );

        $urlBase = fluentcrm_menu_url_base();

        $menuItems = $this->getMenuItems($urlBase);

        $app['view']->render('admin.menu_page', [
            'menuItems' => $menuItems,
            'logo'      => FLUENTCRM_PLUGIN_URL . 'assets/images/fluentcrm-logo.svg',
            'base_url'  => $urlBase
        ]);
    }

    public function changeFooter()
    {
        add_filter('admin_footer_text', function ($content) {
            $url = 'https://fluentcrm.com';
            $extraHtml = '';
            if (!defined('DISABLE_WP_CRON')) {
                $extraHtml = ' ' . sprintf(__('Server-Side Cron Job is not enabled %1sView Documentation%2s.', 'fluent-crm'), '<a style="font-weight: 500;" target="_blank" rel="noopener" href="https://fluentcrm.com/docs/fluentcrm-cron-job-basics-and-checklist/">', '</a>');
            }

            return sprintf(wp_kses(__('Thank you for using <a href="%s">FluentCRM</a>.', 'fluent-crm'), array('a' => array('href' => array()))), esc_url($url)) . '<span title="based on your WP timezone settings" style="margin-left: 10px;" data-timestamp="' . current_time('timestamp') . '" id="fc_server_timestamp"></span>. ' . $extraHtml;
        });

        add_filter('update_footer', function ($text) {
            if (defined('FLUENTCAMPAIGN_PLUGIN_VERSION') && FLUENTCRM_PLUGIN_VERSION != FLUENTCAMPAIGN_PLUGIN_VERSION) {
                return FLUENTCRM_PLUGIN_VERSION . ' & ' . FLUENTCAMPAIGN_PLUGIN_VERSION;
            }
            return FLUENTCRM_PLUGIN_VERSION;
        });
    }

    public function getMenuItems($urlBase = null)
    {
        if (!$urlBase) {
            $urlBase = fluentcrm_menu_url_base();
        }

        $permissions = PermissionManager::currentUserPermissions();

        $menuItems = [
            [
                'key'       => 'dashboard',
                'label'     => __('Dashboard', 'fluent-crm'),
                'permalink' => $urlBase
            ]
        ];

        if (in_array('fcrm_read_contacts', $permissions)) {
            $contactMenu = [
                'key'          => 'contacts',
                'label'        => __('Contacts', 'fluent-crm'),
                'permalink'    => $urlBase . 'subscribers',
                'layout_class' => 'fc_2_col_menu',
                'sub_items'    => [
                    [
                        'key'         => 'all_contacts',
                        'label'       => __('All Contacts', 'fluent-crm'),
                        'permalink'   => $urlBase . 'subscribers',
                        'description' => __('Browse all your subscribers and customers', 'fluent-crm')
                    ]
                ]
            ];

            if (in_array('fcrm_manage_contact_cats', $permissions)) {

                if (Helper::isCompanyEnabled()) {
                    $contactMenu['sub_items'][] = [
                        'key'         => 'companies',
                        'label'       => __('Companies', 'fluent-crm'),
                        'permalink'   => $urlBase . 'contact-groups/companies',
                        'description' => __('Browse and Manage contact business/companies', 'fluent-crm')
                    ];
                }

                $contactMenu['sub_items'][] = [
                    'key'         => 'lists',
                    'label'       => __('Lists', 'fluent-crm'),
                    'permalink'   => $urlBase . 'contact-groups/lists',
                    'description' => __('Browse and Manage your lists associate with contact', 'fluent-crm')
                ];
                $contactMenu['sub_items'][] = [
                    'key'         => 'tags',
                    'label'       => __('Tags', 'fluent-crm'),
                    'permalink'   => $urlBase . 'contact-groups/tags',
                    'description' => __('Browse and Manage your tags associate with contact', 'fluent-crm')
                ];
                $contactMenu['sub_items'][] = [
                    'key'         => 'dynamic_segments',
                    'label'       => __('Segments', 'fluent-crm'),
                    'permalink'   => $urlBase . 'contact-groups/dynamic-segments',
                    'description' => __('Manage your dynamic contact segments', 'fluent-crm')
                ];
            }

            $menuItems[] = $contactMenu;
        }

        if (in_array('fcrm_read_emails', $permissions)) {
            $campaignMenu = [
                'key'          => 'campaigns',
                'label'        => __('Emails', 'fluent-crm'),
                'permalink'    => $urlBase . 'email/campaigns',
                'layout_class' => 'fc_2_col_menu'
            ];

            $campaignMenu['sub_items'] = [
                [
                    'key'         => 'all_campaigns',
                    'label'       => __('All Campaigns', 'fluent-crm'),
                    'permalink'   => $urlBase . 'email/campaigns',
                    'description' => __('Send Email Broadcast to your selected subscribers by tags, lists or segment', 'fluent-crm')
                ],
                [
                    'key'         => 'recurring_campaigns',
                    'label'       => __('Recurring Campaigns', 'fluent-crm'),
                    'permalink'   => $urlBase . 'email/recurring-campaigns',
                    'description' => __('Send automated daily or weekly emails of your dynamic data like new blog posts', 'fluent-crm')
                ],
                [
                    'key'         => 'email_sequences',
                    'label'       => __('Email Sequences', 'fluent-crm'),
                    'permalink'   => $urlBase . 'email/sequences',
                    'description' => __('Create Multiple Emails and Send in order as a Drip Email Campaign', 'fluent-crm')
                ],
                [
                    'key'         => 'email_templates',
                    'label'       => __('Email Templates', 'fluent-crm'),
                    'permalink'   => $urlBase . 'email/templates',
                    'description' => __('Create email templates to use as a starting point in your emails', 'fluent-crm')
                ],
                [
                    'key'         => 'all_emails',
                    'label'       => __('All Emails', 'fluent-crm'),
                    'permalink'   => $urlBase . 'email/all-emails',
                    'description' => __('Find all the emails that are being sent or scheduled by FluentCRM', 'fluent-crm')
                ]
            ];

            $menuItems[] = $campaignMenu;
        }

        if (in_array('fcrm_manage_forms', $permissions)) {
            $menuItems[] = [
                'key'       => 'forms',
                'label'     => __('Forms', 'fluent-crm'),
                'permalink' => $urlBase . 'forms'
            ];
        }

        if (in_array('fcrm_read_funnels', $permissions)) {
            $menuItems[] = [
                'key'       => 'funnels',
                'label'     => __('Automations', 'fluent-crm'),
                'permalink' => $urlBase . 'funnels'
            ];
        }

        /**
         * Filters the core menu items for FluentCRM.
         *
         * This filter allows modification of the core menu items in the FluentCRM admin menu.
         *
         * @param array $menuItems The current menu items.
         * @param array $permissions The permissions associated with the menu items.
         * @return array The filtered menu items.
         */
        $menuItems = apply_filters('fluent_crm/core_menu_items', $menuItems, $permissions);

        if (in_array('fcrm_manage_settings', $permissions)) {

            $menuItems[] = [
                'key'       => 'reports',
                'label'     => __('Reports', 'fluent-crm'),
                'permalink' => $urlBase . 'reports'
            ];

            $menuItems[] = [
                'key'       => 'settings',
                'label'     => __('Settings', 'fluent-crm'),
                'permalink' => $urlBase . 'settings'
            ];
        }

        if (!defined('FLUENTCAMPAIGN')) {
            $menuItems[] = [
                'key'       => 'get_pro',
                'label'     => __('Get Pro', 'fluent-crm'),
                'permalink' => 'https://fluentcrm.com?utm_source=dashboard&utm_medium=plugin&utm_campaign=pro&utm_id=wp',
                'class'     => 'pro_link'
            ];
        }

        /**
         * Filter the menu items for FluentCRM.
         *
         * This filter allows modification of the menu items in the FluentCRM admin menu.
         *
         * @param array $menuItems An array of menu items for FluentCRM.
         * @return array The filtered array of menu items.
         */
        return apply_filters('fluent_crm/menu_items', $menuItems);

    }

    public function mayBeRedirect()
    {
        if (fluentcrm_get_option('fluentcrm_setup_wizard_ran') != 'yes' && !isset($_GET['setup_complete'])) {
            if (current_user_can('manage_options')) {
                wp_safe_redirect(admin_url('index.php?page=fluentcrm-setup'));
                exit();
            }
        }
    }

    public function loadAssets()
    {
        if (!isset($_GET['page']) || $_GET['page'] != 'fluentcrm-admin') {
            return;
        }
        /*
         * LearnPress loads all their JS weired way on every Admin Pages
         */
        if (defined('LEARNPRESS_VERSION')) {
            add_filter('learn-press/admin-default-scripts', function ($scripts) {
                return [];
            });
        }

        $this->loadCssJs();
    }

    public function loadCssJs()
    {
        $this->unloadOtherScripts();

        wp_enqueue_script('fluentcrm_global_admin', fluentCrmMix('admin/js/global_admin.js'), array('jquery'), $this->version);
        wp_enqueue_script('fluentcrm_admin_app_boot', fluentCrmMix('admin/js/boot.js'), array('moment'), $this->version);

        // Ensure block editor styles are loaded
        wp_enqueue_style('wp-block-editor');
        
        $this->emailBuilderBlockInit();

        /**
         * Action Hook when global admin scripts are loaded
         */
        do_action('fluent_crm/global_appjs_loaded');

        $footerHook = 'admin_footer';
        if (!is_admin()) {
            $footerHook = 'wp_footer';
        }

        if (Helper::isExperimentalEnabled('quick_contact_navigation')) {
            wp_enqueue_script('fluentcrm-contact_navigations', fluentCrmMix('admin/js/contact-navigations.js'), [], FLUENTCRM_PLUGIN_VERSION, true);
            add_action($footerHook, function () {
                echo '<div ref="fluent_contact_nav" id="fluent_contact_nav"><fluent-contact-nav v-if="appReady" @prev="goPrev()" @next="goNext()" :subscriber="subscriber"></fluent-contact-nav></div>';
            }, 99999);
        }

        add_action($footerHook, function () {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (_ && _.noConflict) {
                        if (window._.each.length == 2) {
                            window.lodash = _.noConflict();
                            console.log('_.noConflict() Loaded');
                        }
                    }
                });
            </script>
            <?php
        }, 99999);

        $this->loadCss();

        wp_enqueue_script('fluentcrm-chartjs', fluentCrmMix('libs/chartjs/Chart.min.js'), [], $this->version, true);
        wp_enqueue_script('fluentcrm-vue-chartjs', fluentCrmMix('libs/chartjs/vue-chartjs.min.js'), [], $this->version, true);
        wp_enqueue_script('dompurify', fluentCrmMix('libs/purify/purify.min.js'), [], $this->version, true);

        $inlineCss = Helper::generateThemePrefCss();
        wp_add_inline_style('fluentcrm_app_global', $inlineCss);

        remove_action('admin_print_scripts', 'print_emoji_detection_script');

        add_filter('tiny_mce_plugins', function ($plugins) {
            if (is_array($plugins)) {
                return array_diff($plugins, array('wpemoji'));
            }
            return array();
        });

        wp_localize_script('fluentcrm_admin_app_boot', 'fcAdmin', $this->getAdminVars());
    }

    public function getAdminVars()
    {
        $app = FluentCrm();

        $tags = Tag::orderBy('title', 'ASC')->get();
        $formattedTags = [];
        foreach ($tags as $tag) {
            $formattedTags[] = [
                'id'    => strval($tag->id),
                'title' => $tag->title,
                'slug'  => $tag->slug
            ];
        }

        $lists = Lists::orderBy('title', 'ASC')->get();
        $formattedLists = [];
        foreach ($lists as $list) {
            $formattedLists[] = [
                'id'    => strval($list->id),
                'title' => $list->title,
                'slug'  => $list->slug
            ];
        }

        $currentUser = wp_get_current_user();

        $activatedFeatures = Helper::getActivatedFeatures();

        $postTypes = get_post_types(['public' => true], 'objects');
        unset($postTypes['attachment']);

        $formattedPostTypes = [];

        foreach ($postTypes as $postTypeName => $postType) {
            $formattedPostTypes[] = [
                'id'    => $postTypeName,
                'title' => $postType->label
            ];
        }


        $data = array(
            'images_url'                          => fluentCrmMix('images'),
            'ajaxurl'                             => admin_url('admin-ajax.php'),
            'slug'                                => FLUENTCRM,
            'rest'                                => $this->getRestInfo($app),
            /**
             * Filters the list of countries in FluentCRM.
             *
             * This filter allows you to modify the list of countries used in the application.
             *
             * @param array An array of countries.
             */
            'countries'                           => apply_filters('fluent_crm/countries', []),
            'contact_types'                       => fluentcrm_contact_types(),
            'purchase_providers'                  => Helper::getPurchaseHistoryProviders(),
            /**
             * Filters the form submission providers in FluentCRM.
             *
             * This filter allows you to modify the list of form submission providers.
             *
             * @param array An array of form submission providers.
             */
            'form_submission_providers'           => apply_filters('fluent_crm/form_submission_providers', []),
            /**
             * Filters the list of support ticket providers in FluentCRM.
             *
             * This filter allows you to modify the array of support ticket providers.
             *
             * @param array An array of support ticket providers.
             */
            'support_tickets_providers'           => apply_filters('fluentcrm-support_tickets_providers', []),
            'activity_types'                      => fluentcrm_activity_types(),
            'profile_sections'                    => Helper::getProfileSections(),
            'globalSmartCodes'                    => Helper::getGlobalSmartCodes(),
            'extendedSmartCodes'                  => Helper::getExtendedSmartCodes(),
            'addons'                              => $activatedFeatures,
            'email_template_designs'              => Helper::getEmailDesignTemplates(),
            'contact_prefixes'                    => Helper::getContactPrefixes(),
            'contact_custom_fields'               => fluentcrm_get_custom_contact_fields(),
            'server_time'                         => current_time('mysql'),
            'crm_pro_url'                         => 'https://fluentcrm.com/?utm_source=plugin&utm_medium=admin&utm_campaign=promo',
            /**
             * Determine if request verification is required in FluentCRM.
             *
             * This filter allows you to specify whether request verification is required.
             * By default, it is set to false.
             *
             * @param bool Whether request verification is required. Default false.
             */
            'require_verify_request'              => apply_filters('fluentcrm_is_require_verify', false),
            'trans'                               => TransStrings::getStrings(),
            'has_fluentsmtp'                      => defined('FLUENTMAIL'),
            /**
             * Determine if FluentMail suggestion should be disabled in FluentCRM.
             *
             * This filter allows customization of the FluentMail suggestion feature in FluentCRM.
             *
             * @return bool True if FluentMail suggestion is disabled, false otherwise.
             */
            'disable_fluentmail_suggest'          => apply_filters('fluent_crm/fluentmail_suggest', defined('FLUENTMAIL')),
            'verified_senders'                    => $this->getVerifiedSenders(),
            'has_smart_link'                      => $this->hasSmartLink(),
            'auth'                                => [
                'permissions' => PermissionManager::currentUserPermissions(),
                'first_name'  => $currentUser->first_name,
                'last_name'   => $currentUser->last_name,
                'email'       => $currentUser->user_email,
                'avatar'      => get_avatar($currentUser->user_email, 128),
                'user_id'     => $currentUser->ID
            ],
            'is_rtl'                              => fluentcrm_is_rtl(),
            'icons'                               => [
                'trigger_icon' => 'fc-icon-trigger',
            ],
            /**
             * Define the funnel category icons in FluentCRM.
             *
             * This filter allows you to change the icons used for different funnel categories in FluentCRM.
             *
             * @param array An associative array where the keys are funnel categories and the values are the corresponding icon classes.
             *    Default categories and icons:
             *    - 'wordpresstriggers'    => 'fc-icon-wordpress'
             *    - 'woocommerce'          => 'fc-icon-woo'
             *    - 'lifterlms'            => 'fc-icon-lifter_lms'
             *    - 'easydigitaldownloads' => 'fc-icon-edd'
             *    - 'learndash'            => 'fc-icon-learndash'
             *    - 'memberpress'          => 'fc-icon-memberpress'
             *    - 'paidmembershippro'    => 'fc-icon-paid_membership_pro'
             *    - 'restrictcontentpro'   => 'fc-icon-restric_content'
             *    - 'tutorlms'             => 'fc-icon-tutorlms'
             *    - 'wishlistmember'       => 'fc-icon-wishlist'
             *    - 'surecart'             => 'el-icon-shopping-cart-2'
             */
            'funnel_cat_icons'                    => apply_filters('fluent_crm/funnel_icons', [
                'wordpresstriggers'    => 'fc-icon-wordpress',
                'woocommerce'          => 'fc-icon-woo',
                'lifterlms'            => 'fc-icon-lifter_lms',
                'easydigitaldownloads' => 'fc-icon-edd',
                'learndash'            => 'fc-icon-learndash',
                'memberpress'          => 'fc-icon-memberpress',
                'paidmembershippro'    => 'fc-icon-paid_membership_pro',
                'restrictcontentpro'   => 'fc-icon-restric_content',
                'tutorlms'             => 'fc-icon-tutorlms',
                'wishlistmember'       => 'fc-icon-wishlist',
                'surecart'             => 'el-icon-shopping-cart-2',
                'fluentforms'          => 'fc-icon-fluentforms'
            ]),
            'advanced_filter_options'             => Helper::getAdvancedFilterOptions(),
            /**
             * Modify the advanced filter suggestions in FluentCRM.
             *
             * This filter allows you to modify the suggestions provided for the advanced filter.
             * @return array Modified array of suggestions for the advanced filter.
             */
            'advanced_filter_suggestions'         => apply_filters('fluentcrm_advanced_filter_suggestions', []),
            /**
             * Define the commerce provider in FluentCRM.
             *
             * This filter allows you to change the commerce provider used in FluentCRM.
             *
             * @param string The current commerce provider. Default is an empty string.
             */
            'commerce_provider'                   => apply_filters('fluentcrm_commerce_provider', ''),
            /**
             * Define the currency sign used in FluentCRM.
             *
             * This filter allows you to change the currency sign used in the FluentCRM plugin.
             *
             * @param string The current currency sign. Default is an empty string.
             */
            'commerce_currency_sign'              => apply_filters('fluentcrm_currency_sign', ''),
            'disable_time_diff'                   => Helper::isExperimentalEnabled('classic_date_time'),
            'wp_date_time_format'                 => $this->getDefaultDateTimeFormatForMoment(),
            'disable_ai'                          => Helper::isExperimentalEnabled('disable_visual_ai'),
            'app_version'                         => FLUENTCRM_PLUGIN_VERSION,
            'available_tags'                      => $formattedTags,
            'available_lists'                     => $formattedLists,
            'available_funnel_label_colors'       => Helper::funnelLabelColors(),
            'available_contact_statuses'          => fluentcrm_subscriber_statuses(true),
            'available_contact_editable_statuses' => fluentcrm_subscriber_editable_statuses(true),
            'available_contact_types'             => fluentcrm_contact_types(true),
            'available_custom_fields'             => fluentcrm_get_option('contact_custom_fields', []),
            'contact_sample_csv'                  => fluentCrmMix('sample.csv'),
            'global_email_footer'                 => Helper::getEmailFooterContent(),
            'experimentals'                       => Helper::getExperimentalSettings(),
            'publicPostTypes'                     => $formattedPostTypes,
            'has_woo'                             => defined('WC_PLUGIN_FILE'),
            'debugs'                              => [
                '_fc_last_automation_processor' => get_option('_fc_last_funnel_processor_ran'),
                '_fcrm_last_scheduler'          => fluentCrmGetOptionCache('_fcrm_last_scheduler')
            ],
            /**
             * Determine the custom contact bulk actions in FluentCRM.
             *
             * This filter allows you to add or modify the bulk actions available for contacts in the FluentCRM admin interface.
             *
             * @param array An array of custom bulk actions for contacts.
             */
            'custom_contact_bulk_actions'         => apply_filters('fluent_crm/custom_contact_bulk_actions', [])
        );

        if (Arr::get($activatedFeatures, 'company_module')) {
            $data['company_categories'] = Helper::companyCategories();
            $data['company_types'] = Helper::companyTypes();
            $data['company_profile_sections'] = Helper::getCompanyProfileSections();
            $data['company_custom_fields'] = fluentcrm_get_custom_company_fields();
        }

        /**
         * Filter the admin variables for FluentCRM.
         *
         * This filter allows modification of the admin variables used in FluentCRM.
         *
         * @param array $data The array of admin variables.
         * @return array The filtered array of admin variables.
         */
        return apply_filters('fluent_crm/admin_vars', $data);
    }

    public function loadCss()
    {
        $isRtl = fluentcrm_is_rtl();
        $adminAppCss = 'admin/css/fluentcrm-admin.css';
        $appGlobalCss = 'admin/css/app_global.css';
        if ($isRtl) {
            $adminAppCss = 'admin/css/fluentcrm-admin-rtl.css';
            $appGlobalCss = 'admin/css/app_global-rtl.css';
            wp_enqueue_style('fluentcrm_app_rtl', fluentCrmMix('admin/css/admin_rtl.css'), [], $this->version);
        }

        wp_enqueue_style('fluentcrm_admin_app', fluentCrmMix($adminAppCss), array(), $this->version);
        wp_enqueue_style('fluentcrm_app_global', fluentCrmMix($appGlobalCss), array(), $this->version);
    }

    protected function getRestInfo($app)
    {
        $ns = $app->config->get('app.rest_namespace');
        $v = $app->config->get('app.rest_version');

        $restUrl = rest_url($ns . '/' . $v);
        $restUrl = rtrim($restUrl, '/\\');
        return [
            'base_url'  => esc_url_raw(rest_url()),
            'url'       => $restUrl,
            'nonce'     => wp_create_nonce('wp_rest'),
            'namespace' => $ns,
            'version'   => $v,
        ];
    }

    public function emailBuilderBlockInit()
    {

        wp_enqueue_script('underscore_js', includes_url('js/underscore.min.js'), [], '1.13.6', false);

        if (function_exists('wp_enqueue_media')) {
            // Editor default styles.
            add_filter('user_can_richedit', '__return_true');
            wp_tinymce_inline_scripts();
            wp_enqueue_editor();
            wp_enqueue_media();
        }

        global $current_screen;

        if ($current_screen) {
            $current_screen->is_block_editor(false);
        }

        $script_handle = 'fc_block_editor_scripts';

        $dependencies = array(
            'lodash',
            'wp-api-fetch',
            'wp-block-editor',
            'wp-block-library',
            'wp-blocks',
            'wp-components',
            'wp-compose',
            'wp-data',
            'wp-deprecated',
            'wp-dom',
            'wp-dom-ready',
            'wp-element',
            'wp-format-library',
            'wp-hooks',
            'wp-html-entities',
            'wp-i18n',
            'wp-keyboard-shortcuts',
            'wp-keycodes',
            'wp-media-utils',
            'wp-preferences',
            'wp-primitives',
            'wp-rich-text',
            'wp-url'
        );

        $version = FLUENTCRM_PLUGIN_VERSION;

        global $wp_version;
        if (version_compare($wp_version, '5.9') >= 0) {
            $assetFolder = 'block_editor';
        } else {
            $assetFolder = 'block_editor_58';
            $dependencies[] = 'wp-editor';
        }

        wp_enqueue_script($script_handle, apply_filters('fluent_crm/block_editor_index_js_url', fluentCrmMix($assetFolder . '/index.js')), $dependencies, $version, true);

        if (defined('WC_PLUGIN_FILE')) {
            wp_enqueue_script(
                'fc_block_woo_product',
                apply_filters('fluent_crm/block_editor_woo_js_url', fluentCrmMix($assetFolder . '/woo-product-index.js')),
                array(),
                $version,
                true
            );
        }

        wp_enqueue_script(
            'fc_block_latest_post',
            apply_filters('fluent_crm/block_editor_latest_post_js_url', fluentCrmMix($assetFolder . '/latest-post-index.js')),
            array(),
            $version,
            true
        );

        // Inline the Editor Settings.
        $settings = $this->emailBuilderSettings();
        wp_add_inline_script($script_handle, 'window.fceSettings = ' . wp_json_encode($settings) . ';');

        // Preload server-registered block schemas.
        wp_add_inline_script(
            'wp-blocks',
            'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode($settings) . ');'
        );

        wp_enqueue_script('wp-format-library');
        wp_enqueue_style('wp-format-library');

        $css = 'block_editor/index.css';
        if (fluentcrm_is_rtl()) {
            $css = 'block_editor/index-rtl.css';
        }
        // Styles.
        wp_enqueue_style(
            'fc_block_editor_styles', // Handle.
            apply_filters('fluent_crm/block_editor_css_url', fluentCrmMix($css)), // Block editor CSS.
            array('wp-edit-blocks'), // Dependency to include the CSS after it.
            $version
        );
    }

    private function emailBuilderSettings()
    {

        if (class_exists('\WP_Block_Editor_Context')) {
            $registery = new \WP_Block_Editor_Context([
                'name' => 'fluent_crm_email'
            ]);
        } else {
            $registery = 'post';
        }

        $coreSettings = get_block_editor_settings([], $registery);

        $wordpressCoreTypography = $coreSettings['__experimentalFeatures']['typography'];
        $coreExperimentalSpacing = $coreSettings['__experimentalFeatures']['spacing'];

        /**
         * Filter the image size names for the FluentCRM Email Composer.
         *
         * This filter allows you to modify the list of image sizes that are available
         * in the media library when selecting an image size.
         *
         * @param array {
         *     An associative array of image size labels keyed by their size name.
         *
         * @type string $thumbnail Label for the thumbnail size.
         * @type string $medium Label for the medium size.
         * @type string $large Label for the large size.
         * @type string $full Label for the full size.
         * }
         */
        $image_size_names = apply_filters('image_size_names_choose', array(
                'thumbnail' => __('Thumbnail', 'fluent-crm'),
                'medium'    => __('Medium', 'fluent-crm'),
                'large'     => __('Large', 'fluent-crm'),
                'full'      => __('Full Size', 'fluent-crm'),
            )
        );

        $available_image_sizes = array();
        foreach ($image_size_names as $image_size_slug => $image_size_name) {
            $available_image_sizes[] = array(
                'slug' => $image_size_slug,
                'name' => $image_size_name,
            );
        }

        $max_upload_size = wp_max_upload_size();
        if (!$max_upload_size) {
            $max_upload_size = 0;
        }

        $allowedBlocks = array(
            'core/paragraph',
            'core/heading',
            'core/buttons',
            'core/image',
            'core/columns',
            'core/list',
            'core/list-item',
            'core/freeform',
            'core/html',
            'core/spacer',
            'core/subhead',
            'core/table',
            'core/verse',
            'core/group',
            'core/column',
            'core/buttons',
            'core/button',
            'core/rss',
            'fluentcrm/conditional-group'
        );

        if (defined('WC_PLUGIN_FILE')) {
            $allowedBlocks[] = 'fluentcrm/woo-product';
        }

        if (defined('FLUENTCAMPAIGN')) {
            $allowedBlocks[] = 'fluent-crm/latest-posts';
            $allowedBlocks[] = 'fluent-crm/products';
        }

        /**
         * Determine the allowed block types for FluentCRM.
         *
         * This filter allows you to modify the list of allowed block types in FluentCRM.
         *
         * @param array $allowedBlocks An array of allowed block types.
         * @return array Modified array of allowed block types.
         */
        $allowedBlocks = apply_filters('fluent_crm/allowed_block_types', $allowedBlocks);
        $themePref = Helper::getThemePrefScheme();

        $color_palette = Helper::getThemeColorPalette();

        $settings = array(
            'gradients'                         => [],
            'alignWide'                         => false,
            'allowedMimeTypes'                  => get_allowed_mime_types(),
            'imageSizes'                        => $available_image_sizes,
            'isRTL'                             => fluentcrm_is_rtl(),
            'maxUploadFileSize'                 => $max_upload_size,
            'allowedBlockTypes'                 => $allowedBlocks,
            '__experimentalBlockPatterns'       => [],
            '__experimentalFeatures'            => [
                'appearanceTools' => true,
                'border'          => [
                    'color'  => false,
                    'radius' => true,
                    'style'  => false,
                    'width'  => false
                ],
                'color'           => [
                    'background'       => true,
                    'customDuotone'    => false,
                    'defaultGradients' => false,
                    'defaultPalette'   => true,
                    'duotone'          => [],
                    'gradients'        => [],
                    'link'             => false,
                    'palette'          => [
                        'theme' => $color_palette,
                        'default' => $themePref['colors']
                    ],
                    'text'             => true
                ],
                'spacing'         => $coreExperimentalSpacing,
                'typography'      => $wordpressCoreTypography,
                'blocks'          => [
                    'core/button'    => [
                        'border'     => [
                            'radius' => true,
                            "style"  => true,
                            "width"  => true
                        ],
                        'typography' => [
                            'fontSizes' => []
                        ]
                    ],
                    'core/paragraph' => [
                        'spacing' => [
                            'margin'  => false,
                            'padding' => false
                        ]
                    ],
                    'core/heading'   => [
                        'spacing' => [
                            'margin'  => false,
                            'padding' => false
                        ]
                    ],
                    'core/list-item'   => [
                        'spacing' => [
                            'margin'  => false
                        ],
                        'typography' => [
                            'lineHeight' => true
                        ]
                    ],
                    'core/image'   => [
                        'spacing' => [
                            'margin'  => false
                        ]
                    ]
                ]
            ],
            '__experimentalSetIsInserterOpened' => true,
            'disableCustomColors'               => get_theme_support('disable-custom-colors'),
            'disableCustomFontSizes'            => false,
            'disableCustomGradients'            => true,
            'enableCustomLineHeight'            => get_theme_support('custom-line-height'),
            'enableCustomSpacing'               => get_theme_support('custom-spacing'),
            'enableCustomUnits'                 => false,
            'keepCaretInsideBlock'              => false,
        );

        $settings['fontSizes'] = Arr::get($themePref, 'font_sizes', []);

        return $settings;
    }

    private function getMenuIcon()
    {
        return 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg width="100%" height="100%" viewBox="0 0 300 235" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g><path d="M300,0c0,0 -211.047,56.55 -279.113,74.788c-12.32,3.301 -20.887,14.466 -20.887,27.221l0,38.719c0,0 169.388,-45.387 253.602,-67.952c27.368,-7.333 46.398,-32.134 46.398,-60.467c0,-7.221 0,-12.309 0,-12.309Z"/><path d="M184.856,124.521c0,-0 -115.6,30.975 -163.969,43.935c-12.32,3.302 -20.887,14.466 -20.887,27.221l0,38.719c0,0 83.701,-22.427 138.458,-37.099c27.368,-7.334 46.398,-32.134 46.398,-60.467c0,-7.221 0,-12.309 0,-12.309Z"/></g></svg>');
    }

    private function getVerifiedSenders()
    {
        $verifiedSenders = [];
        if (defined('FLUENTMAIL')) {
            $smtpSettings = get_option('fluentmail-settings', []);
            if ($smtpSettings && count($smtpSettings['mappings'])) {
                $verifiedSenders = array_keys($smtpSettings['mappings']);
            }
        }

        /**
         * Determine the list of verified email senders in FluentCRM.
         *
         * This filter allows modification of the array of verified email senders.
         *
         * @param array $verifiedSenders An array of verified email senders.
         * @return array Filtered array of verified email senders.
         */
        return apply_filters('fluent_crm/verfied_email_senders', $verifiedSenders);
    }

    private function hasSmartLink()
    {
        if (!defined('FLUENTCAMPAIGN')) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'fc_smart_links';
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));

        if ($wpdb->get_var($query) == $table_name) {
            return true;
        }

        return false;
    }

    public function maybeInitExperimentalNavigation()
    {
        if (!Helper::isExperimentalEnabled('full_navigation')) {
            return;
        }

        add_action('admin_print_styles', function () {
            ?>
            <style>
                html.wp-toolbar {
                    padding-top: 0px !important;
                }

                #wpadminbar {
                    display: none !important;
                    height: 0 !important;
                    visibility: hidden !important;
                }

                #adminmenumain {
                    display: none !important;
                    height: 0 !important;
                    visibility: hidden !important;
                }

                .fc_full_navigation #wpbody {
                    padding-left: 200px;
                }

                #wpbody {
                    padding-top: 0 !important;
                }

                .fc_full_navigation .fc_full_menu_wrap a:hover {
                    color: white;
                }

                #wpcontent, #wpfooter {
                    margin-left: 0 !important;
                }

                #wpfooter {
                    padding-left: 220px !important;
                }

                .fc_full_navigation .fc_full_menu_wrap {
                    position: fixed;
                    width: 200px;
                    left: 0;
                    top: 0;
                    bottom: 0;
                    background-color: #1e1e1e;
                    color: white;
                    z-index: 1;
                }

                .fc-navigation-header a, .fc_backto_wp a {
                    display: flex;
                    align-items: center;
                    border: none;
                    border-radius: 0;
                    height: auto;
                    gap: 10px;
                    color: #ccc;
                    font-weight: 600;
                }

                .fc-navigation-header {
                    padding: 10px;
                }

                .fc-navigation-header a svg {
                    fill: white;
                    width: 36px;
                    height: 36px;
                }

                .fc_backto_wp {
                    margin: 15px 15px;
                }

                .fc_backto_wp a:hover {
                    color: white;
                }

                .fc_backto_wp a svg {
                    fill: white;
                    width: 16px;
                    height: 16px;
                }

                .fc_menu_items {
                    margin-left: 10px;
                    max-height: calc(100vh - 110px);
                    overflow: auto;
                }

                .fc_menu_items li {
                    list-style: none;
                    padding: 0 10px 0 0;
                    margin-bottom: 0;
                    display: block;
                }

                .fc_menu_items a {
                    color: #ccc;
                    text-decoration: none;
                    padding: 6px 10px;
                    display: block;
                }

                .fc_menu_items a:hover {
                    color: white;
                }

                .fc_menu_items a.active_item {
                    background-color: var(--wp-admin-theme-color, #007cba);
                    color: rgb(255, 255, 255);
                    border-radius: 2px;
                }

                .fc_menu_items h3 {
                    color: white;
                    padding: 0 5px;
                    margin: 8px 0;
                }

                ul.fc_sub_items {
                    padding-left: 15px;
                    margin-bottom: 5px;
                }

                .fc_hide_full_menu .fc_full_menu_wrap {
                    display: none !important;
                }

                .fc_hide_full_menu #adminmenumain {
                    display: block !important;
                    height: auto !important;
                    visibility: visible !important;
                }

                .fc_hide_full_menu #adminmenu {
                    margin: 0;
                    width: 160px;
                }

                .fc_hide_full_menu.sticky-menu #adminmenuwrap {
                    position: relative;
                }

                .fc_hide_full_menu #wpbody {
                    padding-left: 160px;
                }

                .fc_hide_full_menu #adminmenumain #adminmenuwrap {
                    width: 160px;
                }

                .fc_hide_full_menu #adminmenumain .wp-submenu.wp-submenu-wrap {
                    width: 100%;
                }


                @media all and (max-width: 768px) {
                    .fc_full_menu_wrap {
                        height: 56px;
                        overflow: hidden;
                        width: 56px !important;
                    }

                    #fc_site_heading span {
                        display: none;
                    }

                    .fc_full_navigation #wpbody {
                        padding-left: 0px;
                    }

                    .fluentcrm_menu_logo_holder {
                        margin-left: 60px;
                    }

                    .fc_full_menu_open.fc_full_menu_wrap {
                        width: 200px !important;
                        height: 100vh;
                        overflow: auto;
                    }
                }

            </style>
            <?php
        });

        add_filter('admin_body_class', function ($classes) {
            return $classes . ' fc_full_navigation';
        });

        add_action('in_admin_header', function () {
            $app = FluentCrm();

            $urlBase = fluentcrm_menu_url_base();

            $permissions = PermissionManager::currentUserPermissions();

            if (!$permissions) {
                return;
            }

            $dashboardPermission = 'fcrm_view_dashboard';
            $isAdmin = false;
            if (in_array('administrator', $permissions)) {
                $dashboardPermission = 'manage_options';
                $isAdmin = true;
            }

            $contactsMenu = [
                'subscribers'      => [
                    'key'   => 'subscribers',
                    'title' => 'All Contacts',
                    'uri'   => $urlBase . 'subscribers'
                ],
                'companies'        => [
                    'key'   => 'companies',
                    'title' => 'Companies',
                    'uri'   => $urlBase . 'contact-groups/companies'
                ],
                'lists'            => [
                    'key'   => 'lists',
                    'title' => 'Lists',
                    'uri'   => $urlBase . 'contact-groups/lists'
                ],
                'tags'             => [
                    'key'   => 'tags',
                    'title' => 'Tags',
                    'uri'   => $urlBase . 'contact-groups/tags'
                ],
                'dynamic_segments' => [
                    'key'   => 'dynamic_segments',
                    'title' => 'Dynamic Segments',
                    'uri'   => $urlBase . 'contact-groups/dynamic-segments'
                ]
            ];

            if (!Helper::isCompanyEnabled()) {
                unset($contactsMenu['companies']);
            }

            /**
             * Define the full sidebar menu items for the FluentCRM admin menu.
             *
             * @param array {
             *     An array of sidebar menu items.
             *
             * @type array $menu_item {
             *         An array of individual menu item properties.
             *
             * @type string $key The unique key for the menu item.
             * @type string $page_title The title of the page.
             * @type string $menu_title The title of the menu item.
             * @type string $capability The capability required to view the menu item.
             * @type string $uri The URL for the menu item.
             * @type bool $is_parent Whether the menu item has child items.
             * @type array $children An array of child menu items.
             * @param array $permissions An array of permissions for the current user.
             */

            $fullSideBarCoreMenus = [
                [
                    'key'        => 'dashboard',
                    'page_title' => __('Dashboard', 'fluent-crm'),
                    'menu_title' => __('Dashboard', 'fluent-crm'),
                    'capability' => $dashboardPermission,
                    'uri'        => $urlBase
                ],
                [
                    'is_parent'  => true,
                    'page_title' => __('Contacts', 'fluent-crm'),
                    'menu_title' => __('Contacts', 'fluent-crm'),
                    'capability' => ($isAdmin) ? $dashboardPermission : 'fcrm_read_contacts',
                    'children'   => array_values($contactsMenu)
                ],
                [
                    'is_parent'  => true,
                    'page_title' => __('Emails', 'fluent-crm'),
                    'menu_title' => __('Emails', 'fluent-crm'),
                    'capability' => ($isAdmin) ? $dashboardPermission : 'fcrm_read_emails',
                    'children'   => [
                        [
                            'key'   => 'campaigns',
                            'title' => 'Email Campaigns',
                            'uri'   => $urlBase . 'email/campaigns'
                        ],
                        [
                            'key'   => 'recurring_campaigns',
                            'title' => 'Recurring Campaigns',
                            'uri'   => $urlBase . 'email/recurring-campaigns'
                        ],
                        [
                            'key'   => 'email-sequences',
                            'title' => 'Email Sequences',
                            'uri'   => $urlBase . 'email/sequences'
                        ],
                        [
                            'key'   => 'templates',
                            'title' => 'Email Templates',
                            'uri'   => $urlBase . 'email/templates'
                        ],
                        [
                            'key'   => 'all_emails',
                            'title' => 'All Email Activities',
                            'uri'   => $urlBase . 'email/all-emails'
                        ]
                    ]
                ],
                [
                    'key'        => 'forms',
                    'page_title' => __('Forms', 'fluent-crm'),
                    'menu_title' => __('Forms', 'fluent-crm'),
                    'capability' => ($isAdmin) ? $dashboardPermission : 'fcrm_manage_forms',
                    'uri'        => $urlBase . 'forms'
                ],
                [
                    'key'        => 'funnels',
                    'page_title' => __('Automations', 'fluent-crm'),
                    'menu_title' => __('Automations', 'fluent-crm'),
                    'capability' => $dashboardPermission,
                    'uri'        => $urlBase . 'funnels'
                ],
            ];

            $menuItems = apply_filters('fluent_crm/sidebar_core_menu_items', $fullSideBarCoreMenus, $permissions);

            $otherSidebarMenus = [
                [
                    'key'        => 'reports',
                    'page_title' => __('Reports', 'fluent-crm'),
                    'menu_title' => __('Reports', 'fluent-crm'),
                    'capability' => $dashboardPermission,
                    'uri'        => $urlBase . 'reports'
                ],
                [
                    'key'        => 'settings',
                    'page_title' => __('Settings', 'fluent-crm'),
                    'menu_title' => __('Settings', 'fluent-crm'),
                    'capability' => ($isAdmin) ? $dashboardPermission : 'fcrm_manage_settings',
                    'uri'        => $urlBase . 'settings'
                ],
                [
                    'key'        => 'addons',
                    'page_title' => __('Addons', 'fluent-crm'),
                    'menu_title' => __('Addons', 'fluent-crm'),
                    'capability' => ($isAdmin) ? $dashboardPermission : 'fcrm_manage_settings',
                    'uri'        => $urlBase . 'add-ons'
                ],
                [
                    'key'        => 'documentation',
                    'page_title' => __('Help', 'fluent-crm'),
                    'menu_title' => __('Help', 'fluent-crm'),
                    'capability' => ($isAdmin) ? $dashboardPermission : 'fcrm_manage_settings',
                    'uri'        => $urlBase . 'documentation'
                ]
            ];

            // now merge the core and other menus
            $fullSideBarMenus = array_merge($menuItems, $otherSidebarMenus);

            $fullSideBarMenus = apply_filters('fluent_crm/full_sidebar_menu_items', $fullSideBarMenus, $permissions);

            $app['view']->render('admin.experimental_menu', [
                'menu_items' => $fullSideBarMenus,
                'logo'       => Arr::get(fluentcrmGetGlobalSettings('business_settings', []), 'logo')
            ]);

        });

        add_action('admin_footer', function () {
            ?>
            <script>
                document.addEventListener('fc_route_changed', function (e) {
                    jQuery('.fc_menu_items li a').removeClass('active_item');
                    let targetClassName = e.detail.route_to.name;

                    if (e.detail.route_to.meta.active_menu == 'settings') {
                        targetClassName = 'settings';
                    }

                    if (!jQuery('.fc_menu_items li a.fc_key_' + targetClassName).length) {
                        targetClassName = e.detail.route_to.meta.parent || e.detail.route_to.meta.active_menu;
                    }

                    jQuery('.fc_menu_items li a.fc_key_' + targetClassName).addClass('active_item');

                    jQuery('.fc_full_menu_wrap').removeClass('fc_full_menu_open');
                });

                document.addEventListener("DOMContentLoaded", () => {
                    document.getElementById('fc_site_heading').addEventListener('click', function (e) {
                        var width = window.innerWidth
                            || document.documentElement.clientWidth
                            || document.body.clientWidth;

                        if (width > 768) {
                            return;
                        }
                        e.preventDefault();

                        jQuery('.fc_full_menu_wrap').toggleClass('fc_full_menu_open');

                    }, false);

                    jQuery('.fc_nav_header_title').on('click', function (e) {
                        if (window.innerWidth > 700) {
                            e.preventDefault();
                            jQuery('body').toggleClass('fc_hide_full_menu');
                        }
                    });

                });

            </script>
            <?php
        }, 100);
    }

    private function getDefaultDateTimeFormatForMoment()
    {

        $phpFormat = get_option('date_format') . ' ' . get_option('time_format');

        $replacements = [
            'A' => 'A',      // for the sake of escaping below
            'a' => 'a',      // for the sake of escaping below
            'B' => '',       // Swatch internet time (.beats), no equivalent
            'c' => 'YYYY-MM-DD[T]HH:mm:ssZ', // ISO 8601
            'D' => 'ddd',
            'd' => 'DD',
            'e' => 'zz',     // deprecated since version 1.6.0 of moment.js
            'F' => 'MMMM',
            'G' => 'H',
            'g' => 'h',
            'H' => 'HH',
            'h' => 'hh',
            'I' => '',       // Daylight Saving Time? => moment().isDST();
            'i' => 'mm',
            'j' => 'D',
            'L' => '',       // Leap year? => moment().isLeapYear();
            'l' => 'dddd',
            'M' => 'MMM',
            'm' => 'MM',
            'N' => 'E',
            'n' => 'M',
            'O' => 'ZZ',
            'o' => 'YYYY',
            'P' => 'Z',
            'r' => 'ddd, DD MMM YYYY HH:mm:ss ZZ', // RFC 2822
            'S' => 'o',
            's' => 'ss',
            'T' => 'z',      // deprecated since version 1.6.0 of moment.js
            't' => '',       // days in the month => moment().daysInMonth();
            'U' => 'X',
            'u' => 'SSSSSS', // microseconds
            'v' => 'SSS',    // milliseconds (from PHP 7.0.0)
            'W' => 'W',      // for the sake of escaping below
            'w' => 'e',
            'Y' => 'YYYY',
            'y' => 'YY',
            'Z' => '',       // time zone offset in minutes => moment().zone();
            'z' => 'DDD',
        ];

        // Converts escaped characters.
        foreach ($replacements as $from => $to) {
            $replacements['\\' . $from] = '[' . $from . ']';
        }

        $format = strtr($phpFormat, $replacements);

        /**
         * Determine the date and time format used in FluentCRM.
         *
         * This filter allows you to modify the date and time format used in FluentCRM.
         *
         * @param string $format The current date and time format.
         * @return string The modified date and time format.
         */
        return apply_filters('fluent_crm/moment_date_time_format', $format);
    }

    private function unloadOtherScripts()
    {
        /**
         * Determine whether to skip the no-conflict mode in FluentCRM.
         *
         * This filter allows you to skip the no-conflict mode by returning true.
         * By default, it returns false, meaning the no-conflict mode is not skipped.
         *
         * @return bool Whether to skip the no-conflict mode. Default is false.
         */
        $isSkip = apply_filters('fluent_crm/skip_no_conflict', false);
        if ($isSkip) {
            return;
        }

        /**
         * Define the list of approved slugs for FluentCRM assets.
         *
         * This filter allows modification of the list of slugs that are approved for FluentCRM assets.
         *
         * @param array $approvedSlugs An array of approved slugs for FluentCRM assets.
         */
        $approvedSlugs = apply_filters('fluent_crm_asset_listed_slugs', [
            '\/gutenberg\/'
        ]);
        $approvedSlugs[] = 'fluent-crm';
        $approvedSlugs = array_unique($approvedSlugs);
        $approvedSlugs = implode('|', $approvedSlugs);

        $pluginUrl = str_replace(['http:', 'https:'], '', plugins_url());

        add_filter('script_loader_src', function ($src, $handle) use ($approvedSlugs, $pluginUrl) {
            if (!$src) {
                return $src;
            }

            if ($handle == 'underscore') {
                return false;
            }

            $willSkip = (strpos($src, $pluginUrl) !== false) && !preg_match('/' . $approvedSlugs . '/', $src);
            if ($willSkip) {
                return false;
            }
            return $src;
        }, 1, 2);

        add_action('wp_print_scripts', function () {
            global $wp_scripts;
            if (!$wp_scripts) {
                return;
            }

            /**
             * Define the list of approved slugs for FluentCRM assets.
             *
             * This filter allows modification of the list of slugs that are approved for FluentCRM assets.
             *
             * @param array $approvedSlugs An array of approved slugs for FluentCRM assets.
             */
            $approvedSlugs = apply_filters('fluent_crm_asset_listed_slugs', [
                '\/gutenberg\/'
            ]);

            $approvedSlugs[] = 'fluent-crm';

            $approvedSlugs = array_unique($approvedSlugs);

            $approvedSlugs = implode('|', $approvedSlugs);

            $pluginUrl = plugins_url();

            $pluginUrl = str_replace(['http:', 'https:'], '', $pluginUrl);

            foreach ($wp_scripts->queue as $script) {
                if (empty($wp_scripts->registered[$script]) || empty($wp_scripts->registered[$script]->src)) {
                    continue;
                }

                $src = $wp_scripts->registered[$script]->src;
                $isMatched = (strpos($src, $pluginUrl) !== false) && !preg_match('/' . $approvedSlugs . '/', $src);
                if (!$isMatched) {
                    continue;
                }

                wp_dequeue_script($wp_scripts->registered[$script]->handle);
            }
        }, 1);
    }
}
