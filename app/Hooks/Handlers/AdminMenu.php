<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Tag;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\TransStrings;

class AdminMenu
{
    public $version = FLUENTCRM_PLUGIN_VERSION;

    public function init()
    {
        add_action('admin_menu', array($this, 'addMenu'));

        if (isset($_GET['page']) && $_GET['page'] == 'fluentcrm-admin' && is_admin()) {
            $this->mayBeRedirect();
            add_action('admin_enqueue_scripts', array($this, 'loadAssets'));
        }
    }

    public function addMenu()
    {
        $permission = apply_filters('fluentcrm_permission', 'manage_options', 'admin_menu', 'all');
        $contactPermission = apply_filters('fluentcrm_permission', 'manage_options', 'contacts', 'admin_menu');
        $campaignPermission = apply_filters('fluentcrm_permission', 'manage_options', 'campaign', 'admin_menu');
        $formsPermission = apply_filters('fluentcrm_permission', 'manage_options', 'forms', 'admin_menu');
        $automationsPermission = apply_filters('fluentcrm_permission', 'manage_options', 'automations', 'admin_menu');
        $settingsPermission = apply_filters('fluentcrm_permission', 'manage_options', 'settings', 'admin_menu');

        if (!$permission) {
            $permission = 'manage_options';
        }

        if (!current_user_can($permission)) {
            return;
        }

        add_menu_page(
            __('FluentCRM', 'fluent-crm'),
            __('FluentCRM', 'fluent-crm'),
            $permission,
            'fluentcrm-admin',
            array($this, 'render'),
            $this->getMenuIcon(),
            2
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Dashboard', 'fluent-crm'),
            __('Dashboard', 'fluent-crm'),
            $permission,
            'fluentcrm-admin',
            array($this, 'render')
        );

        if ($contactPermission) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Contacts', 'fluent-crm'),
                __('Contacts', 'fluent-crm'),
                $contactPermission,
                'fluentcrm-admin#/subscribers',
                array($this, 'render')
            );
        }


        if ($campaignPermission) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Campaigns', 'fluent-crm'),
                __('Campaigns', 'fluent-crm'),
                $campaignPermission,
                'fluentcrm-admin#/email/campaigns',
                array($this, 'render')
            );

            add_submenu_page(
                'fluentcrm-admin',
                __('Email Sequences', 'fluent-crm'),
                __('Email Sequences', 'fluent-crm'),
                $campaignPermission,
                'fluentcrm-admin#/email/sequences',
                array($this, 'render')
            );
        }

        if ($formsPermission) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Forms', 'fluent-crm'),
                __('Forms', 'fluent-crm'),
                $formsPermission,
                'fluentcrm-admin#/forms',
                array($this, 'render')
            );
        }

        if ($automationsPermission) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Automations', 'fluent-crm'),
                __('Automations', 'fluent-crm'),
                $automationsPermission,
                'fluentcrm-admin#/funnels',
                array($this, 'render')
            );
        }

        if ($settingsPermission) {
            add_submenu_page(
                'fluentcrm-admin',
                __('Settings', 'fluent-crm'),
                __('Settings', 'fluent-crm'),
                $settingsPermission,
                'fluentcrm-admin#/settings',
                array($this, 'render')
            );
        }

    }

    public function render()
    {
        $app = FluentCrm();

        do_action('fluentcrm_loading_app');

        wp_enqueue_script(
            'fluentcrm_admin_app_start',
            fluentCrmMix('/admin/js/start.js'),
            array('fluentcrm_admin_app_boot'),
            $this->version
        );

        $urlBase = apply_filters('fluentcrm_menu_url_base', admin_url('admin.php?page=fluentcrm-admin#/'));

        $contactPermission = apply_filters('fluentcrm_permission', 'manage_options', 'contacts', 'admin_menu');
        $campaignPermission = apply_filters('fluentcrm_permission', 'manage_options', 'campaign', 'admin_menu');
        $formsPermission = apply_filters('fluentcrm_permission', 'manage_options', 'forms', 'admin_menu');
        $automationsPermission = apply_filters('fluentcrm_permission', 'manage_options', 'automations', 'admin_menu');
        $settingsPermission = apply_filters('fluentcrm_permission', 'manage_options', 'settings', 'admin_menu');

        $menuItems = [
            [
                'key'       => 'dashboard',
                'label'     => __('Dashboard', 'fluent-crm'),
                'permalink' => $urlBase
            ]
        ];

        if ($contactPermission && current_user_can($contactPermission)) {
            $contactMenu = [
                'key'       => 'contacts',
                'label'     => __('Contacts', 'fluent-crm'),
                'permalink' => $urlBase . 'subscribers',
                'sub_items' => [
                    [
                        'key'       => 'all_contacts',
                        'label'     => __('All Contacts', 'fluent-crm'),
                        'permalink' => $urlBase . 'subscribers'
                    ]
                ]
            ];

            $listPermission = apply_filters('fluentcrm_permission', 'manage_options', 'lists', 'admin_menu');
            if ($listPermission && current_user_can($listPermission)) {
                $contactMenu['sub_items'][] = [
                    'key'       => 'lists',
                    'label'     => __('Lists', 'fluent-crm'),
                    'permalink' => $urlBase . 'contact-groups/lists'
                ];
            }

            $tagsPermission = apply_filters('fluentcrm_permission', 'manage_options', 'tags', 'admin_menu');
            if ($tagsPermission && current_user_can($tagsPermission)) {
                $contactMenu['sub_items'][] = [
                    'key'       => 'tags',
                    'label'     => __('Tags', 'fluent-crm'),
                    'permalink' => $urlBase . 'contact-groups/tags'
                ];
            }

            $dynamicSegmentsPermission = apply_filters('fluentcrm_permission', 'manage_options', 'dynamic_segments', 'admin_menu');
            if ($dynamicSegmentsPermission && current_user_can($dynamicSegmentsPermission)) {
                $contactMenu['sub_items'][] = [
                    'key'       => 'dynamic_segments',
                    'label'     => __('Segments', 'fluent-crm'),
                    'permalink' => $urlBase . 'contact-groups/dynamic-segments'
                ];
            }

            $menuItems[] = $contactMenu;
        }

        if ($campaignPermission && current_user_can($campaignPermission)) {
            $campaignMenu = [
                'key'       => 'campaigns',
                'label'     => __('Email Campaigns', 'fluent-crm'),
                'permalink' => $urlBase . 'email/campaigns',
                'sub_items' => [
                    [
                        'key'       => 'all_campaigns',
                        'label'     => __('All Campaigns', 'fluent-crm'),
                        'permalink' => $urlBase . 'email/campaigns'
                    ]
                ]
            ];

            $emailSequencePermission = apply_filters('fluentcrm_permission', 'manage_options', 'email_sequences', 'admin_menu');
            if ($emailSequencePermission && current_user_can($emailSequencePermission)) {
                $campaignMenu['sub_items'][] = [
                    'key'       => 'email_sequences',
                    'label'     => __('Email Sequences', 'fluent-crm'),
                    'permalink' => $urlBase . 'email/sequences'
                ];
            }

            $templatesPermission = apply_filters('fluentcrm_permission', 'manage_options', 'templates', 'admin_menu');
            if ($templatesPermission && current_user_can($templatesPermission)) {
                $campaignMenu['sub_items'][] = [
                    'key'       => 'email_templates',
                    'label'     => __('Email Templates', 'fluent-crm'),
                    'permalink' => $urlBase . 'email/templates'
                ];
            }

            $menuItems[] = $campaignMenu;
        }

        if ($formsPermission && current_user_can($formsPermission)) {
            $menuItems[] = [
                'key'       => 'forms',
                'label'     => __('Forms', 'fluent-crm'),
                'permalink' => $urlBase . 'forms'
            ];
        }

        if ($automationsPermission && current_user_can($automationsPermission)) {
            $menuItems[] = [
                'key'       => 'funnels',
                'label'     => __('Automations', 'fluent-crm'),
                'permalink' => $urlBase . 'funnels'
            ];
        }

        if ($settingsPermission && current_user_can($settingsPermission)) {
            $menuItems[] = [
                'key'       => 'settings',
                'label'     => __('Settings', 'fluent-crm'),
                'permalink' => $urlBase . 'settings'
            ];
        }


        if (!defined('FLUENTCAMPAIGN')) {
            $menuItems[] = [
                'key'       => 'get_pro',
                'label'     => 'Get Pro',
                'permalink' => 'https://fluentcrm.com',
                'class'     => 'pro_link'
            ];
        }

        $menuItems = apply_filters('fluentcrm_menu_items', $menuItems);

        $app['view']->render('admin.menu_page', [
            'menuItems' => $menuItems,
            'logo'      => FLUENTCRM_PLUGIN_URL . 'assets/images/fluentcrm-logo.svg',
            'base_url'  => $urlBase
        ]);
    }

    public function mayBeRedirect()
    {
        if (fluentcrm_get_option('fluentcrm_setup_wizard_ran') != 'yes') {
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

        add_action('wp_print_scripts', function () {
            $isSkip = apply_filters('fluentcrm_skip_no_conflict', false);

            if ($isSkip) {
                return;
            }

            global $wp_scripts;
            if (!$wp_scripts) {
                return;
            }

            $pluginUrl = plugins_url();
            foreach ($wp_scripts->queue as $script) {
                $src = $wp_scripts->registered[$script]->src;
                if (strpos($src, $pluginUrl) !== false && !strpos($src, 'fluent-crm') !== false) {
                    wp_dequeue_script($wp_scripts->registered[$script]->handle);
                }
            }
        }, 1);

        $app = FluentCrm();
        $isRtl = is_rtl();

        $this->emailBuilderBlockInit();
        wp_enqueue_script('fluentcrm_admin_app_boot', fluentCrmMix('admin/js/boot.js'), array('jquery', 'moment'), $this->version);
        wp_enqueue_script('fluentcrm_global_admin.js', fluentCrmMix('admin/js/global_admin.js'), array('jquery'), $this->version);

        $adminAppCss = 'admin/css/fluentcrm-admin.css';
        $appGlobalCss = 'admin/css/app_global.css';
        if ($isRtl) {
            $adminAppCss = 'admin/css/fluentcrm-admin-rtl.css';
            $appGlobalCss = 'admin/css/app_global-rtl.css';
        }

        wp_enqueue_style('fluentcrm_admin_app', fluentCrmMix($adminAppCss));
        wp_enqueue_style('fluentcrm_app_global', fluentCrmMix($appGlobalCss));

        wp_enqueue_script('fluentcrm-chartjs', fluentCrmMix('libs/chartjs/Chart.min.js'));
        wp_enqueue_script('fluentcrm-vue-chartjs', fluentCrmMix('libs/chartjs/vue-chartjs.min.js'));

        $tags = Tag::get();
        $formattedTags = [];
        foreach ($tags as $tag) {
            $formattedTags[] = [
                'value' => $tag->id,
                'label' => $tag->title
            ];
        }

        wp_localize_script('fluentcrm_admin_app_boot', 'fcAdmin', array(
            'images_url'                => $app['url.assets.images'],
            'ajaxurl'                   => admin_url('admin-ajax.php'),
            'slug'                      => FLUENTCRM,
            'rest'                      => $this->getRestInfo($app),
            'countries'                 => apply_filters('fluentcrm-countries', []),
            'subscriber_statuses'       => fluentcrm_subscriber_statuses(),
            'contact_types'             => fluentcrm_contact_types(),
            'purchase_providers'        => Helper::getPurchaseHistoryProviders(),
            'form_submission_providers' => $app->applyCustomFilters('form_submission_providers', []),
            'support_tickets_providers' => $app->applyCustomFilters('support_tickets_providers', []),
            'activity_types'            => fluentcrm_activity_types(),
            'profile_sections'          => Helper::getProfileSections(),
            'globalSmartCodes'          => Helper::getGlobalSmartCodes(),
            'addons'                    => Helper::getActivatedFeatures(),
            'email_template_designs'    => Helper::getEmailDesignTemplates(),
            'contact_prefixes'          => Helper::getContactPrefixes(),
            'server_time'               => current_time('mysql'),
            'crm_pro_url'               => 'https://fluentcrm.com/?utm_source=plugin&utm_medium=admin&utm_campaign=promo',
            'require_verify_request'    => apply_filters('fluentcrm_is_require_verify', false),
            'trans'                     => TransStrings::getStrings(),
            'available_tags' => $formattedTags
        ));
    }

    protected function getRestInfo($app)
    {
        $ns = $app['rest.namespace'];
        $v = $app['rest.version'];

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
        global $current_screen;

        $current_screen->is_block_editor(true);

        $script_handle = 'fc_block_editor_scripts';

        $dependencies = array(
            'lodash',
            'wp-block-editor',
            'wp-block-library',
            'wp-blocks',
            'wp-components',
            'wp-data',
            'wp-dom-ready',
            'wp-editor',
            'wp-element',
            'wp-format-library',
            'wp-i18n',
            'wp-media-utils',
            'wp-plugins',
            'wp-polyfill',
            'wp-primitives'
        );
        $version = '1eba519655f7064279764e7f9bdd4984';

        wp_enqueue_script($script_handle, fluentCrmMix('block_editor/index.js'), $dependencies, $version);

        // Inline the Editor Settings.
        $settings = $this->emailBuilderSettings();
        wp_add_inline_script($script_handle, 'window.fceSettings = ' . wp_json_encode($settings) . ';');

        // Preload server-registered block schemas.
        wp_add_inline_script(
            'wp-blocks',
            'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode($settings) . ');'
        );

        // Editor default styles.
        add_filter('user_can_richedit', '__return_true');
        wp_tinymce_inline_scripts();
        wp_enqueue_editor();
        wp_enqueue_media();

        wp_enqueue_script('wp-format-library');
        wp_enqueue_style('wp-format-library');

        $css = 'block_editor/index.css';
        if (is_rtl()) {
            $css = 'block_editor/index-rtl.css';
        }
        // Styles.
        wp_enqueue_style(
            'fc_block_editor_styles', // Handle.
            fluentCrmMix($css), // Block editor CSS.
            array('wp-edit-blocks'), // Dependency to include the CSS after it.
            $version
        );
    }

    private function emailBuilderSettings()
    {
        $image_size_names = apply_filters(
            'image_size_names_choose',
            array(
                'thumbnail' => __('Thumbnail'),
                'medium'    => __('Medium'),
                'large'     => __('Large'),
                'full'      => __('Full Size'),
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


        $settings = array(
            'disableCustomColors'         => false,
            'disableCustomFontSizes'      => false,
            'disableCustomGradients'      => true,
            'disableGradients'            => true,
            'gradients'                   => [],
            'alignWide'                   => false,
            'allowedMimeTypes'            => get_allowed_mime_types(),
            'imageSizes'                  => $available_image_sizes,
            'isRTL'                       => is_rtl(),
            'maxUploadFileSize'           => $max_upload_size,
            'allowedBlockTypes'           => array(
                'core/paragraph',
                'core/heading',
                'core/buttons',
                'core/image',
                'core/columns',
                'core/list',
//                'core/quote',
                'core/freeform',
                'core/html',
                //	'core/latest-posts',
                'core/spacer',
                'core/subhead',
                'core/table',
                'core/verse',
                'core/group',
                'core/column',
                'core/button',
                'core/media-text',
                'core/buttons',
                'core/rss',
                'fluentcrm/conditional-group'
            ),
            '__experimentalBlockPatterns' => []
        );

        $themePref = Helper::getThemePrefScheme();

        //   vddd($themePref);

        if ($themePref['colors']) {
            $settings['colors'] = $themePref['colors'];
        }

        if ($themePref['font_sizes']) {
            $settings['fontSizes'] = $themePref['font_sizes'];
        }

        return $settings;
    }

    private function getMenuIcon()
    {
        return 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg width="100%" height="100%" viewBox="0 0 300 235" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g><path d="M300,0c0,0 -211.047,56.55 -279.113,74.788c-12.32,3.301 -20.887,14.466 -20.887,27.221l0,38.719c0,0 169.388,-45.387 253.602,-67.952c27.368,-7.333 46.398,-32.134 46.398,-60.467c0,-7.221 0,-12.309 0,-12.309Z"/><path d="M184.856,124.521c0,-0 -115.6,30.975 -163.969,43.935c-12.32,3.302 -20.887,14.466 -20.887,27.221l0,38.719c0,0 83.701,-22.427 138.458,-37.099c27.368,-7.334 46.398,-32.134 46.398,-60.467c0,-7.221 0,-12.309 0,-12.309Z"/></g></svg>');
    }
}
