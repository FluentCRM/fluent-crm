<?php
/**
 * Setup wizard class
 *
 * Intial Setup Wizard for FluentCRM
 *
 */

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\Includes\Request\Request;
use FluentCrm\Includes\Helpers\Arr;

class SetupController extends Controller
{
    public function CompleteWizard(Request $request)
    {
        $installFluentForm = $request->get('install_fluentform', 'no');

        if ($installFluentForm == 'yes' && !defined('FLUENTFORM')) {
            $this->installFluentForm();
        }

        $shareEssential = $request->get('share_essentials', 'no');
        if ($shareEssential == 'yes') {
            fluentcrm_update_option('_fluentcrm_share_essential', $shareEssential);
        }

        $optinEmail = $request->get('optin_email', 'no');
        if ($optinEmail && is_email($optinEmail)) {
            $this->shareEmail($optinEmail);
        }

        return $this->sendSuccess([
            'message' => 'Installation has been completed'
        ]);
    }

    public function handleFluentFormInstall()
    {
        $this->installFluentForm();
        return [
            'ff_config' => [
                'is_installed'     => defined('FLUENTFORM'),
                'create_form_link' => admin_url('admin.php?page=fluent_forms#add=1')
            ],
            'message'   => __('Fluent Forms has been installed and activated', 'fluent-crm')
        ];
    }

    private function shareEmail($optinEmail)
    {
        $user = get_user_by('ID', get_current_user_id());
        $data = [
            'answers'    => [
                'website' => site_url(),
                'email'   => $optinEmail,
                'name'    => $user->display_name
            ],
            'questions'  => [
                'website' => 'website',
                'email'   => 'email',
                'name'    => 'name'
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
            'name'      => 'Fluent Forms',
            'repo-slug' => 'fluentform',
            'file'      => 'fluentform.php',
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
