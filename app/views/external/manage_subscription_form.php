<div class="fc_manage_sub_form">
    <form id="fluentcrm_preferences_form" class="fluentcrm_public_pref_form">
        <input type="hidden" name="_abs_email" value="<?php echo $abs_email; ?>"/>
        <input type="hidden" name="_abs_hash" value="<?php echo $abs_hash; ?>"/>
        <input type="hidden" name="_original_hash" value="<?php echo $subscriber->hash; ?>"/>
        <input type="hidden" name="action" value="fluentcrm_manage_preferences_ajax"/>
        <div class="fluentcrm_form_item">
            <label for="fc_email"><?php _e('Your Email Address', 'fluent-crm'); ?></label>
            <input id="fc_email" required value="<?php echo $abs_email; ?>" placeholder="Your Email Address"
                   class="fluentcrm_form_control" type="text" name="email"/>
        </div>
        <div class="fluentcrm_form_item">
            <label for="fc_first_name"><?php _e('First Name', 'fluent-crm'); ?></label>
            <input id="fc_first_name" required value="<?php echo $subscriber->first_name; ?>"
                   placeholder="<?php _e('First Name', 'fluent-crm'); ?>" class="fluentcrm_form_control" type="text"
                   name="first_name"/>
        </div>
        <div class="fluentcrm_form_item">
            <label for="fc_last_name"><?php _e('Last Name', 'fluent-crm'); ?></label>
            <input id="fc_last_name" required value="<?php echo $subscriber->last_name; ?>"
                   placeholder="<?php _e('Last Name', 'fluent-crm'); ?>" class="fluentcrm_form_control" type="text"
                   name="last_name"/>
        </div>

        <?php if ($list_options): ?>
            <div class="fluentcrm_form_item">
                <h4 class="mainling_list_group_title"><?php _e('Mailing List Groups', 'fluent-crm') ?></h4>
                <?php foreach ($list_options as $list_option): ?>
                    <label class="fc_list_items">
                        <input <?php echo ($list_option['selected']) ? 'checked' : ''; ?> type="checkbox" name="lists[]"
                                                                                          value="<?php echo $list_option['id']; ?>"/> <?php echo $list_option['label']; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="fluentcrm_form_item">
            <input id="fluentcrm_preferences_submit" type="submit"
                   value="<?php _e('Update Profile', 'fluent-crm'); ?>"></input>
        </div>
        <?php if (apply_filters('fluentcrm_show_unsubscribe_on_pref', false)): ?>
            <div class="fluentcrm_form_item">
                <?php _e('or', 'fluent-crm'); ?> <a id="pref_unsubscribe"
                                                    href="#"><?php _e('Unsubscribe', 'fluent-crm'); ?></a>
            </div>
        <?php endif; ?>
    </form>
    <div class="fluentcrm_form_responses"></div>
</div>
