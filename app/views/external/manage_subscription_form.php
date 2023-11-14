<div class="fc_manage_sub_form">
    <form id="fluentcrm_preferences_form" class="fluentcrm_public_pref_form fc_public_form">
        <input type="hidden" name="_abs_email" value="<?php echo esc_html($abs_email); ?>"/>
        <input type="hidden" name="_abs_hash" value="<?php echo esc_html($abs_hash); ?>"/>
        <input type="hidden" name="_original_hash" value="<?php echo esc_html($subscriber->hash); ?>"/>
        <input type="hidden" name="action" value="fluentcrm_manage_preferences_ajax"/>
        <input type="hidden" name="_secure_hash" value="<?php echo esc_html($secure_hash); ?>"/>
        <div class="fc_field">
            <label for="fc_email"><?php esc_html_e('Your Email Address', 'fluent-crm'); ?></label>
            <input id="fc_email" required value="<?php echo esc_html($abs_email); ?>" placeholder="Your Email Address"
                   class="fc_input_control" type="text" name="email"/>
        </div>
        <div class="fc_field">
            <label for="fc_first_name"><?php esc_html_e('First Name', 'fluent-crm'); ?></label>
            <input id="fc_first_name" required value="<?php echo esc_html($subscriber->first_name); ?>"
                   placeholder="<?php esc_html_e('First Name', 'fluent-crm'); ?>" class="fc_input_control" type="text"
                   name="first_name"/>
        </div>
        <div class="fc_field">
            <label for="fc_last_name"><?php esc_html_e('Last Name', 'fluent-crm'); ?></label>
            <input id="fc_last_name" required value="<?php echo esc_html($subscriber->last_name); ?>" placeholder="<?php esc_html_e('Last Name', 'fluent-crm'); ?>" class="fc_input_control" type="text" name="last_name"/>
        </div>

        <?php if ($list_options): ?>
            <div class="fc_field">
                <h4 class="mainling_list_group_title"><?php esc_html_e('Mailing List Groups', 'fluent-crm') ?></h4>
                <?php foreach ($list_options as $list_option): ?>
                    <label class="fc_list_items">
                        <input <?php echo ($list_option['selected']) ? 'checked' : ''; ?> type="checkbox" name="lists[]" value="<?php echo esc_attr($list_option['id']); ?>"/> <?php echo esc_attr($list_option['label']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="fc_field">
            <input id="fluentcrm_preferences_submit" type="submit" value="<?php esc_html_e('Update Profile', 'fluent-crm'); ?>"></input>
        </div>
        <?php if (apply_filters('fluent_crm/show_unsubscribe_on_pref', false)): ?>
            <div class="fc_field">
                <?php esc_html_e('or', 'fluent-crm'); ?> <a id="pref_unsubscribe" href="#"><?php esc_html_e('Unsubscribe', 'fluent-crm'); ?></a>
            </div>
        <?php endif; ?>
    </form>
    <div class="fluentcrm_form_responses"></div>
</div>
