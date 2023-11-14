<?php
/*
 * @var array $business
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Imagetoolbar" content="No"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Request Unsubscribe', 'fluent-crm') ?></title>
    <meta name="robots" content="noindex">
    <?php
        wp_head();
        do_action('fluent_crm/unsubscribe_request_head');
    ?>
</head>
<body class="fc_unsub">
<div class="fluentcrm_unsubscribe_wrapper">
    <div class="fluentcrm_un_title">
        <?php if (!empty($business['logo'])): ?>
            <div class="fluentcrm_un_logo_wrapper">
                <img src="<?php echo esc_url($business['logo']); ?>" alt="<?php echo (isset($business['business_name'])) ? esc_html($business['business_name']) : ''; ?>"/>
            </div>
        <?php elseif(!empty($business['business_name'])): ?>
        <h3><?php echo esc_html($business['business_name']); ?></h3>
        <?php endif; ?>
    </div>
    <div class="fluentcrm_un_form_wrapper">
        <h4><?php _e('Get Unsubscribe Link', 'fluent-crm') ?></h4>
        <p><?php _e('Looks like we could not determine your info. Please fill up the form and get unsubscribe link via email.', 'fluent-crm'); ?></p>

        <form method="POST" class="fluentcrm_public_pref_form fc_public_form" action="/" id="fc_unsub_req_form">
            <input type="hidden" name="action" value="fluentcrm_request_unsubscribe_ajax"/>
            <div class="fc_field">
                <label for="fc_email"><?php esc_html_e('Your Email Address', 'fluent-crm'); ?></label>
                <input id="fc_email" required placeholder="Your Email Address"
                       class="fc_input_control" type="text" name="email"/>
            </div>
            <div class="fc_field">
                <input id="fluentcrm_preferences_submit" type="submit" value="<?php esc_html_e('Email me the link', 'fluent-crm'); ?>"></input>
            </div>
        </form>

        <div class="fluentcrm_form_responses"></div>
    </div>
    <?php do_action('fluent_crm/after_unsubscribe_request_content'); ?>
</div>
<?php
    wp_footer();
    do_action('fluent_crm/unsubscribe_request_footer');
?>
</body>
</html>
