<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Imagetoolbar" content="No"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Update your preferences', 'fluent-crm') ?></title>
    <meta name="robots" content="noindex">
    <?php
        wp_head();
        do_action('fluent_crm/manage_subscription_head', $subscriber);
    ?>
</head>
<body class="fc_unsub">
<div class="fluentcrm_unsubscribe_wrapper">
    <div class="fluentcrm_un_title">
        <?php if (!empty($business['logo'])): ?>
            <div class="fluentcrm_un_logo_wrapper">
                <img src="<?php echo esc_url($business['logo']); ?>" alt="<?php echo esc_attr($business['business_name']); ?>"/>
            </div>
        <?php else: ?>
        <h3><?php echo esc_html($business['business_name']); ?></h3>
        <?php endif; ?>
    </div>
    <div class="fluentcrm_un_form_wrapper">
        <h3><?php esc_html_e('Update your preferences', 'fluent-crm'); ?></h3>
        <?php require_once FLUENTCRM_PLUGIN_PATH.'app/views/external/manage_subscription_form.php'; ?>
    </div>
</div>
<?php
    wp_footer();
    do_action('fluent_crm/manage_subscription_footer', $subscriber);
?>
</body>
</html>
