<?php
/*
Plugin Name:  FluentCRM - Marketing Automation For WordPress
Plugin URI:   https://fluentcrm.com
Description:  CRM and Email Newsletter Plugin for WordPress
Version:      2.0.3
Author:       Fluent CRM
Author URI:   https://fluentcrm.com
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  fluent-crm
Domain Path:  /language
*/

require_once("fluentcrm_boot.php");

add_action('plugins_loaded', function () {
    do_action('fluentcrm_loaded', new \FluentCrm\Includes\Core\Application);
});

register_activation_hook(
    __FILE__, array('FluentCrm\Includes\Activator', 'handle')
);

register_deactivation_hook(
    __FILE__, array('FluentCrm\Includes\Deactivator', 'handle')
);

// Handle Newtwork new Site Activation
add_action('wpmu_new_blog', function ($blogId) {
    switch_to_blog($blogId);
    \FluentCrm\Includes\Activator::handle(false);
    restore_current_blog();
});

/*
 * Thanks for checking the source code
 * Please check at PHP API Here: https://github.com/FluentCRM/fluent-crm/wiki/PHP-API
*/
