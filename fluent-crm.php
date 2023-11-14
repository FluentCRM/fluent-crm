<?php defined('ABSPATH') or die;

/**
 * Plugin Name:  FluentCRM - Marketing Automation For WordPress
 * Plugin URI:   https://fluentcrm.com
 * Description:  CRM and Email Newsletter Plugin for WordPress
 * Version:      2.8.33
 * Author:       WP Email Newsletter Team - FluentCRM
 * Author URI:   https://fluentcrm.com
 * License:      GPLv2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  fluent-crm
 * Domain Path:  /language
*/

if(defined('FLUENTCRM')) {
    return;
}

define('FLUENTCRM', 'fluentcrm');
define('FLUENTCRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FLUENTCRM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FLUENTCRM_PLUGIN_VERSION', '2.8.33');
define('FLUENTCRM_MIN_PRO_VERSION', '2.8.33');
define('FLUENTCRM_FRAMEWORK_VERSION', 3);
if(!defined('FLUENTCRM_UPLOAD_DIR')) {
    define('FLUENTCRM_UPLOAD_DIR', '/fluentcrm');
}

require __DIR__ . '/vendor/autoload.php';

call_user_func(function ($bootstrap) {
    $bootstrap(__FILE__);
}, require(__DIR__ . '/boot/app.php'));

add_filter('plugin_row_meta', 'fluentcrm_plugin_row_meta', 10, 2);

function fluentcrm_plugin_row_meta($links, $file)
{
    if ('fluent-crm/fluent-crm.php' == $file) {
        $row_meta = array(
            'docs'    => '<a rel="noopener" href="https://fluentcrm.com/docs/" style="color: #23c507;font-weight: 600;" aria-label="' . esc_attr(esc_html__('View FluentCRM Documentation', 'fluent-crm')) . '" target="_blank">' . esc_html__('Docs & FAQs', 'fluent-crm') . '</a>',
            'support' => '<a rel="noopener" href="https://wpmanageninja.com/support-tickets/#/" style="color: #23c507;font-weight: 600;" aria-label="' . esc_attr(esc_html__('Get Support', 'fluent-crm')) . '" target="_blank">' . esc_html__('Support', 'fluent-crm') . '</a>',
            'developer_docs' => '<a rel="noopener" href="https://developers.fluentcrm.com" style="color: #23c507;font-weight: 600;" aria-label="' . esc_attr(esc_html__('Developer Docs', 'fluent-crm')) . '" target="_blank">' . esc_html__('Developer Docs', 'fluent-crm') . '</a>',
        );

        if(!defined('FLUENTCAMPAIGN')) {
            $row_meta['pro'] = '<a rel="noopener" href="https://fluentcrm.com" style="color: #7742e6;font-weight: bold;" aria-label="' . esc_attr(esc_html__('Upgrade to Pro', 'fluent-crm')) . '" target="_blank">' . esc_html__('Upgrade to Pro', 'fluent-crm') . '</a>';
        }
        return array_merge($links, $row_meta);
    }
    return (array)$links;
}
