<div class="fluentcrm_app_wrapper">
    <div class="fluentcrm_main_menu_items">
        <div class="fluentcrm_menu_logo_holder">
            <a href="<?php echo $base_url; ?>"><img src="<?php echo $logo; ?>" /></a>
        </div>
        <div class="fluentcrm_handheld"><span class="dashicons dashicons-menu-alt3"></span></div>
        <ul class="fluentcrm_menu">
            <?php foreach ($menuItems as $item): ?>
            <?php $hasSubMenu = !empty($item['sub_items']); ?>
            <li data-key="<?php echo $item['key']; ?>" class="fluentcrm_menu_item <?php echo ($hasSubMenu) ? 'fluentcrm_has_sub_items' : ''; ?> fluentcrm_item_<?php echo $item['key']; ?>">
                <a class="fluentcrm_menu_primary" href="<?php echo $item['permalink']; ?>">
                    <?php echo $item['label']; ?>
                    <?php if($hasSubMenu){ ?>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <?php } ?></a>
                <?php if($hasSubMenu): ?>
                <div class="fluentcrm_submenu_items">
                    <?php foreach ($item['sub_items'] as $sub_item): ?>
                    <a href="<?php echo $sub_item['permalink']; ?>"><?php echo $sub_item['label']; ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div id='fluentcrm_app'></div>
</div>
