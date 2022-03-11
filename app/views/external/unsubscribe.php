<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Imagetoolbar" content="No"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Unsubscribe', 'fluent-crm') ?></title>
    <?php
        wp_head();
        do_action('fluentcrm_unsubscribe_head', $subscriber, $campaign_email);
    ?>
</head>
<body>
<div class="fluentcrm_unsubscribe_wrapper">
    <?php do_action('fluentcrm_before_unsubscribe_content', $subscriber, $campaign_email); ?>
    <div class="fluentcrm_un_title">
        <?php if (!empty($business['logo'])): ?>
            <div class="fluentcrm_un_logo_wrapper">
                <img src="<?php echo $business['logo']; ?>" alt="<?php echo $business['business_name']; ?>"/>
            </div>
        <?php else: ?>
        <h3><?php echo $business['business_name']; ?></h3>
        <?php endif; ?>
    </div>
    <div class="fluentcrm_un_form_wrapper">
        <h3><?php echo $texts['heading']; ?></h3>
        <p><?php echo $texts['heading_description']; ?></p>
        <?php do_action('fluentcrm_before_unsubscribe_form', $subscriber, $campaign_email); ?>
        <form id="fluentcrm_unsubscribe_form" class="fluentcrm_public_pref_form">
            <input type="hidden" name="_e_id" value="<?php echo $campaign_email->id; ?>" />
            <input type="hidden" name="action" value="fluentcrm_unsubscribe_ajax" />
            <input type="hidden" name="combined_hash" value="<?php echo $combined_hash; ?>" />
            <input type="hidden" name="sub_hash" value="<?php echo $subscriber->hash; ?>" />
            <div class="fluentcrm_form_item">
                <label><?php echo $texts['email_label']; ?></label>
                <input readonly="true" value="<?php echo $mask_email; ?>" class="fluentcrm_form_control" type="text" name="email_address" />
            </div>
            <?php if($reasons): ?>
            <div class="fluentcrm_form_item">
                <label><?php echo $texts['reason_label']; ?></label>
                <div class="fluentcrm_radio_group">
                    <?php foreach ($reasons as $reasonKey => $reason): ?>
                    <label>
                        <input type="radio" name="reason" value="<?php echo $reasonKey; ?>"></input> <?php echo $reason; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display: none;" id="fluentcrm_other_reason_wrapper" class="fluentcrm_form_item">
                <input placeholder="<?php _e('Please specify', 'fluent-crm'); ?>" class="fluentcrm_form_control" type="text" name="other_reason" />
            </div>
            <?php endif; ?>
            <?php do_action('fluentcrm_before_unsubscribe_submit', $campaign_email); ?>
            <div class="fluentcrm_form_item">
                <input id="fluentcrm_unsubscribe_submit" type="submit" value="<?php echo $texts['button_text']; ?>"></input>
            </div>
        </form>
        <div class="fluentcrm_form_responses"></div>
    </div>
    <?php do_action('fluentcrm_after_unsubscribe_content', $subscriber, $campaign_email); ?>
</div>
<?php
    wp_footer();
    do_action('fluentcrm_unsubscribe_footer', $subscriber, $campaign_email);
?>
</body>
</html>
