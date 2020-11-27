<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Stats;
use FluentCrm\Includes\Helpers\Arr;

class AdminBar
{
    public function init()
    {
        $contactPermission = apply_filters('fluentcrm_permission', 'manage_options', 'contacts', 'admin_menu');

        if (!is_admin() || !$contactPermission || !current_user_can($contactPermission)) {
            return;
        }

        add_action('admin_bar_menu', [$this, 'addGlobalSearch'], 999);

    }

    public function addGlobalSearch($adminBar)
    {
        wp_enqueue_script(
            'fluentcrm_global_seach',
            fluentCrmMix('/admin/js/global-search.js'),
            ['jquery']
        );

        $urlBase = apply_filters(
            'fluentcrm_menu_url_base',
            admin_url('admin.php?page=fluentcrm-admin#/')
        );

        $currentScreen = get_current_screen();
        $editingUserVars = null;
        if ($currentScreen && $currentScreen->id == 'user-edit') {
            $userId = Arr::get($_REQUEST, 'user_id');
            $user = get_user_by('ID', $userId);

            if ($userId && $user) {
                $crmProfile = Subscriber::where('email', $user->user_email)
                    ->orWhere('user_id', $user->ID)
                    ->first();
                if ($crmProfile) {
                    $urlBase = apply_filters('fluentcrm_menu_url_base', admin_url('admin.php?page=fluentcrm-admin#/'));
                    $crmProfileUrl = $urlBase . 'subscribers/' . $crmProfile->id;
                    $editingUserVars = [
                        'user_id'         => $userId,
                        'crm_profile_id'  => $crmProfile->id,
                        'crm_profile_url' => $crmProfileUrl
                    ];
                }
            }
        }

        wp_localize_script('fluentcrm_global_seach', 'fc_bar_vars', [
            'rest'            => $this->getRestInfo(),
            'links'           => (new Stats)->getQuickLinks(),
            'subscriber_base' => $urlBase . 'subscribers/',
            'edit_user_vars'  => $editingUserVars
        ]);

        $args = [
            'parent' => 'top-secondary',
            'id'     => 'fc_global_search',
            'title'  => 'Search Contacts',
            'href'   => '#',
            'meta'   => false
        ];

        $adminBar->add_node($args);
    }

    protected function getRestInfo()
    {
        $app = FluentCrm();

        $ns = $app['rest.namespace'];
        $v = $app['rest.version'];

        return [
            'base_url'  => esc_url_raw(rest_url()),
            'url'       => rest_url($ns . '/' . $v),
            'nonce'     => wp_create_nonce('wp_rest'),
            'namespace' => $ns,
            'version'   => $v
        ];
    }
}
