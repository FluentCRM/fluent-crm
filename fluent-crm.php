<?php
/*
Plugin Name:  FluentCRM
Plugin URI:   https://fluentcrm.io
Description:  CRM and Email Newsletter Plugin for WordPress
Version:      1.0.0
Author:       WPManageNinja Team
Author URI:   https://fluentcrm.io
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  fluentcrm
Domain Path:  /language
*/

require_once("fluentcrm_boot.php");

register_activation_hook(
    __FILE__, array('FluentCrm\Includes\Activator', 'handle')
);

register_deactivation_hook(
    __FILE__, array('FluentCrm\Includes\Deactivator', 'handle')
);

add_action('plugins_loaded', function() {
    do_action('fluentcrm_loaded', new \FluentCrm\Includes\Core\Application);
});

// Handle Newtwork new Site Activation
add_action('wpmu_new_blog', function ($blogId) {
    switch_to_blog($blogId);
    \FluentCrm\Includes\Activator::handle(false);
    restore_current_blog();
});

add_action('init', function () {
//    $contact = FluentCrmApi('contacts')->getContact('cep.jewel@gmail.com');
//    vddd($contact->lists);
});
