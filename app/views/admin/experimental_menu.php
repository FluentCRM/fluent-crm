<div class="fc_full_menu_wrap">

    <div class="fc-navigation-header">
        <a id="fc_site_heading" href="<?php echo esc_url(site_url()); ?>" title="<?php bloginfo('title'); ?>"
           class="fc_nav_header_title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="-2 -2 24 24" width="36px" height="36px" aria-hidden="true"
                 focusable="false">
                <path
                    d="M20 10c0-5.51-4.49-10-10-10C4.48 0 0 4.49 0 10c0 5.52 4.48 10 10 10 5.51 0 10-4.48 10-10zM7.78 15.37L4.37 6.22c.55-.02 1.17-.08 1.17-.08.5-.06.44-1.13-.06-1.11 0 0-1.45.11-2.37.11-.18 0-.37 0-.58-.01C4.12 2.69 6.87 1.11 10 1.11c2.33 0 4.45.87 6.05 2.34-.68-.11-1.65.39-1.65 1.58 0 .74.45 1.36.9 2.1.35.61.55 1.36.55 2.46 0 1.49-1.4 5-1.4 5l-3.03-8.37c.54-.02.82-.17.82-.17.5-.05.44-1.25-.06-1.22 0 0-1.44.12-2.38.12-.87 0-2.33-.12-2.33-.12-.5-.03-.56 1.2-.06 1.22l.92.08 1.26 3.41zM17.41 10c.24-.64.74-1.87.43-4.25.7 1.29 1.05 2.71 1.05 4.25 0 3.29-1.73 6.24-4.4 7.78.97-2.59 1.94-5.2 2.92-7.78zM6.1 18.09C3.12 16.65 1.11 13.53 1.11 10c0-1.3.23-2.48.72-3.59C3.25 10.3 4.67 14.2 6.1 18.09zm4.03-6.63l2.58 6.98c-.86.29-1.76.45-2.71.45-.79 0-1.57-.11-2.29-.33.81-2.38 1.62-4.74 2.42-7.1z"></path>
            </svg>
            <span><?php bloginfo('title'); ?></a></span>
    </div>

    <div class="fc_backto_wp">
        <a href="<?php echo esc_url(admin_url()); ?>" title="Back to WP Dashboard" class="fc_nav_header_title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"
                 focusable="false">
                <path d="M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z"></path>
            </svg>
            <span>WordPress Dashboard</span>
        </a>
    </div>

    <div class="fc_menu_items">
        <ul class="fc_items">
            <?php foreach ($menu_items as $item): ?>
                <?php if (!current_user_can($item['capability'])) {
                    continue;
                } ?>
                <li>
                    <?php if (empty($item['is_parent'])) : ?>
                        <a class="fc_key_<?php echo esc_attr($item['key']); ?>"
                           href="<?php echo esc_url($item['uri']); ?>">
                            <?php echo esc_attr($item['menu_title']); ?>
                        </a>
                    <?php else: ?>
                        <h3> <?php echo esc_attr($item['menu_title']); ?></h3>
                        <ul class="fc_sub_items">
                            <?php foreach ($item['children'] as $sub_item): ?>
                                <li>
                                    <a class="fc_key_<?php echo esc_attr($sub_item['key']); ?>"
                                       href="<?php echo esc_url($sub_item['uri']); ?>">
                                        <?php echo esc_attr($sub_item['title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
