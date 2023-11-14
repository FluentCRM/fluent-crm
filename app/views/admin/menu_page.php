<?php
/**
 * @var $base_url string
 * @var $logo string
 * @var $menuItems array
 */
?>
<?php do_action('fluent_crm/before_admin_app_wrap'); ?>
<div class="fluentcrm_app_wrapper">
    <div class="fluentcrm_main_menu_items">
        <div class="fluentcrm_menu_logo_holder">
            <a href="<?php echo esc_url($base_url); ?>">
                <img style="height: 36px;" src="<?php echo esc_url($logo); ?>" />
                <?php if(defined('FLUENTCAMPAIGN_PLUGIN_PATH')): ?>
                    <span><?php esc_html_e('Pro', 'fluent-crm'); ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="fluentcrm_handheld"><span class="dashicons dashicons-menu-alt3"></span></div>
        <ul class="fluentcrm_menu">
            <?php foreach ($menuItems as $item): ?>
            <?php $hasSubMenu = !empty($item['sub_items']); ?>
            <li data-key="<?php echo esc_attr($item['key']); ?>" class="fluentcrm_menu_item <?php echo ($hasSubMenu) ? 'fluentcrm_has_sub_items' : ''; ?> fluentcrm_item_<?php echo esc_attr($item['key']); ?>">
                <a class="fluentcrm_menu_primary" href="<?php echo esc_url($item['permalink']); ?>">
                    <?php echo esc_attr($item['label']); ?>
                    <?php if($hasSubMenu){ ?>
                        <span class="fc_submenu_handler dashicons dashicons-arrow-down-alt2"></span>
                    <?php } ?></a>
                <?php if($hasSubMenu): ?>

                <?php $layoutClass = \FluentCrm\Framework\Support\Arr::get($item, 'layout_class'); ?>
                <div class="fluentcrm_submenu_items <?php echo esc_attr($layoutClass); ?>">
                    <?php foreach ($item['sub_items'] as $sub_item): ?>
                    <a href="<?php echo esc_url($sub_item['permalink']); ?>">
                        <?php
                            if(!$layoutClass) {
                                echo esc_html($sub_item['label']);
                            } else {
                                ?>
                                <div class="fc_menu_card">
                                    <span class="fc_menu_title"><?php echo  esc_html($sub_item['label']); ?></span>
                                    <?php if(!empty($sub_item['description'])): ?>
                                    <p class="fc_menu_description"><?php echo wp_kses_post($sub_item['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                        ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div id='fluentcrm_app'></div>
    <?php do_action('fluent_crm/admin_app'); ?>
</div>
