<?php
/**
 * Setup wizard class
 *
 * Intial Setup Wizard for FluentCRM
 *
 */

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Services\PermissionManager;
use FluentCrm\App\Services\TransStrings;
use FluentCrm\Includes\Helpers\Arr;

/**
 * The class
 */
class SetupWizard
{
    /**
     * Hook in tabs.
     */
    public function __construct()
    {
        if (apply_filters('fluentcrm_setup_wizard', true) && current_user_can('manage_options')) {
            fluentcrm_update_option('fluentcrm_setup_wizard_ran', 'yes');
            //  add_action('wp_loaded', array($this, 'setup_wizard'), 9999);

            $this->setup_wizard();
        }
    }

    /**
     * Show the setup wizard
     */
    public function setup_wizard()
    {
        add_filter('user_can_richedit', '__return_true');

        if (current_user_can('upload_files')) {
            wp_enqueue_script('media-upload');
        }
        add_thickbox();

        wp_enqueue_editor();
        wp_enqueue_media();

        wp_enqueue_style(
            'fluentcrm-setup',
            fluentCrmMix('admin/css/setup-wizard.css'), ['dashicons']
        );

        wp_register_script(
            'fluentcrm-boot',
            fluentCrmMix('admin/js/boot.js'), ['jquery'], date('Ymd'), true
        );

        wp_register_script(
            'fluentcrm-setup',
            fluentCrmMix('admin/js/setup-wizard.js'), ['fluentcrm-boot'], date('Ymd'), true
        );

        $existingSettings = get_option(FLUENTCRM . '-global-settings');
        $businessSettings = Arr::get($existingSettings, 'business_settings', []);

        $currentUser = wp_get_current_user();

        wp_localize_script('fluentcrm-boot', 'fcAdmin', [
            'ajaxurl'           => admin_url('admin-ajax.php'),
            'slug'              => FLUENTCRM,
            'rest'              => $this->getRestInfo(FluentCrm()),
            'trans'             => TransStrings::getStrings(),
            'dashboard_url'     => admin_url('admin.php?page=fluentcrm-admin&setup_complete=' . time()),
            'business_settings' => (object) $businessSettings,
            'has_fluentform'    => defined('FLUENTFORM'),
            'auth' => [
                'permissions' => PermissionManager::currentUserPermissions(),
                'first_name' => $currentUser->first_name,
                'last_name' => $currentUser->last_name,
                'email' => $currentUser->user_email,
                'avatar' => get_avatar($currentUser->user_email, 128)
            ],
        ]);

        $this->outputHtml();
    }

    /**
     * Setup Wizard HTML
     */
    public function outputHtml()
    {
        ob_start();
        fluentCrm('view')->render('admin.setup_wizard');
        exit();
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
}
