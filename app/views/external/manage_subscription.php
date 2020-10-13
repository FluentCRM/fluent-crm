<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Imagetoolbar" content="No"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Update your preferences', 'fluent-crm') ?></title>
    <?php
        wp_head();
        do_action('fluentcrm_confirmation_head', $subscriber);
    ?>
</head>
<body>
<div class="fluentcrm_unsubscribe_wrapper">
    <div class="fluentcrm_un_title">
        <?php if ($business['logo']): ?>
            <div class="fluentcrm_un_logo_wrapper">
                <img src="<?php echo $business['logo']; ?>" alt="<?php echo $business['business_name']; ?>"/>
            </div>
        <?php else: ?>
        <h3><?php echo $business['business_name']; ?></h3>
        <?php endif; ?>
    </div>
    <div class="fluentcrm_un_form_wrapper">
        <h3><?php _e('Update your preferences', 'fluent-crm'); ?></h3>
        <form id="fluentcrm_preferences_form" class="fluentcrm_public_pref_form">
            <input type="hidden" name="_abs_email" value="<?php echo $abs_email; ?>" />
            <input type="hidden" name="_abs_hash" value="<?php echo $abs_hash; ?>" />
            <input type="hidden" name="_original_hash" value="<?php echo $subscriber->hash; ?>" />
            <input type="hidden" name="action" value="fluentcrm_manage_preferences_ajax" />
            <div class="fluentcrm_form_item">
                <label for="fc_email"><?php _e('Your Email Address', 'fluent-crm'); ?></label>
                <input id="fc_email" required value="<?php echo $abs_email; ?>" placeholder="Your Email Address" class="fluentcrm_form_control" type="text" name="email" />
            </div>
            <div class="fluentcrm_form_item">
                <label for="fc_first_name"><?php _e('First Name', 'fluent-crm'); ?></label>
                <input id="fc_first_name" required value="<?php echo $subscriber->first_name; ?>" placeholder="First Name" class="fluentcrm_form_control" type="text" name="first_name" />
            </div>
            <div class="fluentcrm_form_item">
                <label for="fc_last_name"><?php _e('Last Name', 'fluent-crm'); ?></label>
                <input id="fc_last_name" required value="<?php echo $subscriber->last_name; ?>" placeholder="Last Name" class="fluentcrm_form_control" type="text" name="last_name" />
            </div>

            <?php if($list_options): ?>
            <div class="fluentcrm_form_item">
                <h4 class="mainling_list_group_title"><?php _e('Mailing List Groups', 'fluent-crm') ?></h4>
                <?php foreach ($list_options as $list_option): ?>
                <label class="fc_list_items">
                    <input <?php echo ($list_option['selected']) ? 'checked' : ''; ?> type="checkbox" name="lists[]" value="<?php echo $list_option['id']; ?>" /> <?php echo $list_option['label']; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="fluentcrm_form_item">
                <input id="fluentcrm_preferences_submit" type="submit" value="<?php _e('Update Profile', 'fluent-crm'); ?>"></input>
            </div>
            <?php if(apply_filters('fluentcrm_show_unsubscribe_on_pref', false)): ?>
            <div class="fluentcrm_form_item">
                or <a id="pref_unsubscribe" href="#">Unsubscribe</a>
            </div>
            <?php endif; ?>
        </form>
        <div class="fluentcrm_form_responses"></div>
    </div>
</div>
<?php
    wp_footer();
    do_action('fluentcrm_confirmation_footer', $subscriber);
?>
</body>
</html>
