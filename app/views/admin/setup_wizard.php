<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
    <meta name="viewport" content="width=device-width"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php esc_html_e('FluentCRM - Setup Wizard', 'fluent-crm'); ?></title>
    <?php do_action('admin_print_styles'); ?>
    <?php do_action('admin_head'); ?>
</head>
    <body class="fluentcrm-setup wp-core-ui">

        <div id="fluentcrm_setup_wizard"></div>
        <?php
            wp_enqueue_media(); // add media
            wp_print_scripts(); // window.wp
            do_action('admin_footer');
            wp_print_scripts('fluentcrm-setup');
        ?>

        <script>
            jQuery(document).ready(function ($) {
                if (_ && _.noConflict) {
                    _.noConflict();
                }
            });
        </script>
    </body>
</html>
