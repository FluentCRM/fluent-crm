<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Services\Stats;
use FluentCrm\App\Http\Controllers\DashboardController;

class AdminBar
{
    public function init()
    {
        if (!current_user_can('manage_options')) {
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

        wp_localize_script('fluentcrm_global_seach', 'fc_bar_vars', [
            'rest' => $this->getRestInfo(),
            'links' => (new Stats)->getQuickLinks(),
            'subscriber_base' => $urlBase.'subscribers/'
        ]);

        $args = [
            'parent' => 'top-secondary',
            'id'     => 'fc_global_search',
            'title'  => 'Search Contact',
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
            'version'   => $v,
        ];
    }
}
