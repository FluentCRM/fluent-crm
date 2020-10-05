<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Http\Controllers\PurchaseHistoryController;
use FluentCrm\App\Services\Helper;

class AdminMenu
{
    public $version = '1.0.1';

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
        $permission = 'manage_options';
        add_menu_page(
            __('FluentCRM', 'fluentcrm'),
            __('FluentCRM', 'fluentcrm'),
            $permission,
            'fluentcrm-admin',
            array($this, 'render'),
            $this->getMenuIcon(),
            2
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Dashboard', 'fluentform'),
            __('Dashboard', 'fluentform'),
            $permission,
            'fluentcrm-admin',
            array($this, 'render')
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Contacts', 'fluentform'),
            __('Contacts', 'fluentform'),
            $permission,
            'fluentcrm-admin#/subscribers',
            array($this, 'render')
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Campaigns', 'fluentform'),
            __('Campaigns', 'fluentform'),
            $permission,
            'fluentcrm-admin#/email/campaigns',
            array($this, 'render')
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Forms', 'fluentform'),
            __('Forms', 'fluentform'),
            $permission,
            'fluentcrm-admin#/forms',
            array($this, 'render')
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Funnels', 'fluentform'),
            __('Funnels', 'fluentform'),
            $permission,
            'fluentcrm-admin#/funnels',
            array($this, 'render')
        );

        add_submenu_page(
            'fluentcrm-admin',
            __('Settings', 'fluentform'),
            __('Settings', 'fluentform'),
            $permission,
            'fluentcrm-admin#/settings',
            array($this, 'render')
        );

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

        $menuItems = [
            [
                'key'       => 'dashboard',
                'label'     => __('Dashboard', 'fluentcrm'),
                'permalink' => $urlBase
            ],
            [
                'key'       => 'contacts',
                'label'     => __('Contacts', 'fluentcrm'),
                'permalink' => $urlBase . 'subscribers',
                'sub_items' => [
                    [
                        'key'       => 'all_contacts',
                        'label'     => __('All Contacts', 'fluentcrm'),
                        'permalink' => $urlBase . 'subscribers'
                    ],
                    [
                        'key'       => 'lists',
                        'label'     => __('Lists', 'fluentcrm'),
                        'permalink' => $urlBase . 'contact-groups/lists'
                    ],
                    [
                        'key'       => 'tags',
                        'label'     => __('Tags', 'fluentcrm'),
                        'permalink' => $urlBase . 'contact-groups/tags'
                    ],
                    [
                        'key'       => 'dynamic_segments',
                        'label'     => __('Segments', 'fluentcrm'),
                        'permalink' => $urlBase . 'contact-groups/dynamic-segments'
                    ]
                ]
            ],
            [
                'key'       => 'campaigns',
                'label'     => __('Email Campaigns', 'fluentcrm'),
                'permalink' => $urlBase . 'email/campaigns',
                'sub_items' => [
                    [
                        'key'       => 'all_campaigns',
                        'label'     => __('All Campaigns', 'fluentcrm'),
                        'permalink' => $urlBase . 'email/campaigns'
                    ],
                    [
                        'key'       => 'email_sequences',
                        'label'     => __('Email Sequences', 'fluentcrm'),
                        'permalink' => $urlBase . 'email/sequences'
                    ],
                    [
                        'key'       => 'email_templates',
                        'label'     => __('Email Templates', 'fluentcrm'),
                        'permalink' => $urlBase . 'email/templates'
                    ]
                ]
            ],
            [
                'key'       => 'forms',
                'label'     => __('Forms', 'fluentcrm'),
                'permalink' => $urlBase . 'forms'
            ],
            [
                'key'       => 'funnels',
                'label'     => __('Funnels', 'fluentcrm'),
                'permalink' => $urlBase . 'funnels'
            ],
            [
                'key'       => 'settings',
                'label'     => __('Settings', 'fluentcrm'),
                'permalink' => $urlBase . 'settings'
            ]
        ];

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

        $this->emailBuilderBlockInit();
        wp_enqueue_script('fluentcrm_admin_app_boot', fluentCrmMix('admin/js/boot.js'), array('jquery', 'moment'), $this->version);
        wp_enqueue_script('fluentcrm_global_admin.js', fluentCrmMix('admin/js/global_admin.js'), array('jquery'), $this->version);

        wp_enqueue_style('fluentcrm_admin_app', fluentCrmMix('admin/css/fluentcrm-admin.css'));
        wp_enqueue_style('fluentcrm_app_global', fluentCrmMix('admin/css/app_global.css'));

        wp_enqueue_script('fluentcrm-chartjs', fluentCrmMix('libs/chartjs/Chart.min.js'));
        wp_enqueue_script('fluentcrm-vue-chartjs', fluentCrmMix('libs/chartjs/vue-chartjs.min.js'));

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
            'require_verify_request'    => apply_filters('fluentcrm_is_require_verify', false)
        ));
    }

    protected function getRestInfo($app)
    {
        $ns = $app['rest.namespace'];
        $v = $app['rest.version'];

        return [
            'base_url'  => esc_url_raw(rest_url()),
            'url'       => rest_url($ns . '/' . $v),
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

        // Styles.
        wp_enqueue_style(
            'fc_block_editor_styles', // Handle.
            fluentCrmMix('block_editor/index.css'), // Block editor CSS.
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
            'isRTL'                       => false,
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
                'core/rss'
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
