<?php
/**
 * Setup wizard class
 *
 * Intial Setup Wizard for FluentCRM
 *
 */

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\Framework\Request\Request;
use FluentCrm\Framework\Support\Arr;

/**
 *  SetupController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class SetupController extends Controller
{
    public function CompleteWizard(Request $request)
    {
        $installFluentForm = $request->get('install_fluentform', 'no');

        if ($installFluentForm == 'yes' && !defined('FLUENTFORM')) {
            $this->installFluentForm();
        }

        $optinEmail = $request->get('optin_email', 'no');
        if ($optinEmail && is_email($optinEmail)) {
            $this->shareEmail($optinEmail);
        }

        $shareEssential = $request->get('share_essentials', 'no');
        if ($shareEssential == 'yes') {
            fluentcrm_update_option('_fluentcrm_share_essential', $shareEssential);
        }

        return $this->sendSuccess([
            'message' => __('Installation has been completed', 'fluent-crm')
        ]);
    }

    public function handleFluentFormInstall()
    {
        $this->installFluentForm();
        return [
            'ff_config'    => [
                'is_installed'     => defined('FLUENTFORM'),
                'create_form_link' => admin_url('admin.php?page=fluent_forms#add=1')
            ],
            'is_installed' => defined('FLUENTFORM'),
            'message'      => __('Fluent Forms has been installed and activated', 'fluent-crm')
        ];
    }

    public function handleFluentSmtpInstall()
    {
        if (!current_user_can('install_plugins')) {
            return $this->sendError([
                'message' => __('Sorry! you do not have permission to install plugin', 'fluent-crm')
            ]);
        }

        $this->installFluentSMTP();

        return [
            'is_installed' => defined('FLUENTMAIL'),
            'config_url'   => admin_url('options-general.php?page=fluent-mail#/'),
            'message'      => __('FluentSMTP plugin has been installed and activated successfully', 'fluent-crm')
        ];

    }

    public function handleFluentConnectInstall()
    {
        if (!current_user_can('install_plugins')) {
            return $this->sendError([
                'message' => __('Sorry! you do not have permission to install plugin', 'fluent-crm')
            ]);
        }

        $plugin_id = 'fluent-connect';
        $plugin = [
            'name'      => __('Fluent Connect', 'fluent-crm'),
            'repo-slug' => 'fluent-connect',
            'file'      => 'fluent-connect.php',
        ];
        $this->backgroundInstaller($plugin, $plugin_id);

        return [
            'is_installed' => defined('FLUENT_CONNECT_PLUGIN_VERSION'),
            'message'      => __('FluentConnect plugin has been installed and activated successfully', 'fluent-crm')
        ];

    }

    public function handleFluentSupportInstall()
    {
        if (!current_user_can('install_plugins')) {
            return $this->sendError([
                'message' => __('Sorry! you do not have permission to install plugin', 'fluent-crm')
            ]);
        }

        $plugin_id = 'fluent-support';
        $plugin = [
            'name'      => __('Fluent Support', 'fluent-crm'),
            'repo-slug' => 'fluent-support',
            'file'      => 'fluent-support.php',
        ];
        $this->backgroundInstaller($plugin, $plugin_id);

        return [
            'is_installed' => defined('FLUENT_SUPPORT_VERSION'),
            'message'      => __('Fluent Support plugin has been installed and activated successfully', 'fluent-crm')
        ];

    }

    private function shareEmail($optinEmail)
    {
        $user = get_user_by('ID', get_current_user_id());
        $data = [
            'answers'    => [
                'website'    => site_url(),
                'email'      => $optinEmail,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'name'       => $user->display_name
            ],
            'questions'  => [
                'website'    => 'website',
                'first_name' => 'first_name',
                'last_name'  => 'last_name',
                'email'      => 'email',
                'name'       => 'name'
            ],
            'user'       => [
                'email' => $optinEmail
            ],
            'fb_capture' => 1,
            'form_id'    => 54
        ];

        $url = add_query_arg($data, 'https://wpmanageninja.com/');

        wp_remote_post($url);
    }

    private function installFluentForm()
    {
        $plugin_id = 'fluentform';
        $plugin = [
            'name'      => __('Fluent Forms', 'fluent-crm'),
            'repo-slug' => 'fluentform',
            'file'      => 'fluentform.php',
        ];
        $this->backgroundInstaller($plugin, $plugin_id);
    }

    private function installFluentSMTP()
    {
        $plugin_id = 'fluent-smtp';
        $plugin = [
            'name'      => __('FluentSMTP', 'fluent-crm'),
            'repo-slug' => 'fluent-smtp',
            'file'      => 'fluent-smtp.php',
        ];
        $this->backgroundInstaller($plugin, $plugin_id);
    }

    private function backgroundInstaller($plugin_to_install, $plugin_id)
    {
        if (!empty($plugin_to_install['repo-slug'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            WP_Filesystem();

            $skin = new \Automatic_Upgrader_Skin();
            $upgrader = new \WP_Upgrader($skin);
            $installed_plugins = array_reduce(array_keys(\get_plugins()), array($this, 'associate_plugin_file'), array());
            $plugin_slug = $plugin_to_install['repo-slug'];
            $plugin_file = isset($plugin_to_install['file']) ? $plugin_to_install['file'] : $plugin_slug . '.php';
            $installed = false;
            $activate = false;

            // See if the plugin is installed already.
            if (isset($installed_plugins[$plugin_file])) {
                $installed = true;
                $activate = !is_plugin_active($installed_plugins[$plugin_file]);
            }

            // Install this thing!
            if (!$installed) {
                // Suppress feedback.
                ob_start();

                try {
                    $plugin_information = plugins_api(
                        'plugin_information',
                        array(
                            'slug'   => $plugin_slug,
                            'fields' => array(
                                'short_description' => false,
                                'sections'          => false,
                                'requires'          => false,
                                'rating'            => false,
                                'ratings'           => false,
                                'downloaded'        => false,
                                'last_updated'      => false,
                                'added'             => false,
                                'tags'              => false,
                                'homepage'          => false,
                                'donate_link'       => false,
                                'author_profile'    => false,
                                'author'            => false,
                            ),
                        )
                    );

                    if (is_wp_error($plugin_information)) {
                        throw new \Exception($plugin_information->get_error_message());
                    }

                    $package = $plugin_information->download_link;
                    $download = $upgrader->download_package($package);

                    if (is_wp_error($download)) {
                        throw new \Exception($download->get_error_message());
                    }

                    $working_dir = $upgrader->unpack_package($download, true);

                    if (is_wp_error($working_dir)) {
                        throw new \Exception($working_dir->get_error_message());
                    }

                    $result = $upgrader->install_package(
                        array(
                            'source'                      => $working_dir,
                            'destination'                 => WP_PLUGIN_DIR,
                            'clear_destination'           => false,
                            'abort_if_destination_exists' => false,
                            'clear_working'               => true,
                            'hook_extra'                  => array(
                                'type'   => 'plugin',
                                'action' => 'install',
                            ),
                        )
                    );

                    if (is_wp_error($result)) {
                        throw new \Exception($result->get_error_message());
                    }

                    $activate = true;

                } catch (\Exception $e) {
                }

                // Discard feedback.
                ob_end_clean();
            }

            wp_clean_plugins_cache();

            // Activate this thing.
            if ($activate) {
                try {
                    $result = activate_plugin($installed ? $installed_plugins[$plugin_file] : $plugin_slug . '/' . $plugin_file);

                    if (is_wp_error($result)) {
                        throw new \Exception($result->get_error_message());
                    }
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function associate_plugin_file($plugins, $key)
    {
        $path = explode('/', $key);
        $filename = end($path);
        $plugins[$filename] = $key;
        return $plugins;
    }
}
