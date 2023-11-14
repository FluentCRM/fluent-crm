<?php
$alignLeft = 'left';
$alignRight = 'right';
if(fluentcrm_is_rtl()) {
    $alignLeft = 'right';
    $alignRight = 'left';
}
$content_font_family = $config['content_font_family'];
?>

<style type="text/css">
    <?php echo \FluentCrm\App\Services\Helper::generateThemePrefCss(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</style>

<style type="text/css">
    .fc_column_content {
        padding: 0;
    }
    body {
        font-family: <?php echo esc_html($content_font_family); ?>;
        line-height: 150%;
    }
    p {
        font-family: <?php echo esc_html($content_font_family); ?>;
        line-height: 150%;
        font-size: 16px;
        width: 100%;
    }
    li, ol {
        font-family: <?php echo esc_html($content_font_family); ?>;
        line-height: 120%;
        font-size: 16px;
        margin-bottom: 5px;
        padding-bottom: 0px;
    }

    #footer_section p, #footer_section li {
        font-size: 12px;
    }

    h1, h2, h3, h4 {
        line-height: 120%;
        font-family: <?php echo esc_html($content_font_family); ?>;
    }

    .has-text-align-right {
        text-align: <?php echo esc_attr($alignRight); ?> !important;
    }
    .has-text-align-left {
        text-align: <?php echo esc_attr($alignLeft); ?> !important;
    }
    .has-text-align-center {
        text-align: center !important;
    }

    .alignleft {
        text-align: <?php echo esc_attr($alignLeft);?> !important;
    }

    .alignright {
        text-align: <?php echo esc_attr($alignRight);?> !important;
    }

    p {
        margin: 10px 0;
        padding: 0;
    }

    table {
        border-collapse: collapse;
    }

    h1, h2, h3, h4, h5, h6 {
        display: block;
        margin: 0;
        padding: 0;
    }

    img, a img {
        border: 0;
        height: auto;
        outline: none;
        text-decoration: none;
        max-width: 100%;
        display: block;
    }

    .fcPreviewText {
        display: none !important;
    }
    img {
        -ms-interpolation-mode: bicubic;
    }

    table {
        mso-table-lspace: 0pt;
        mso-table-rspace: 0pt;
    }

    a {
        text-decoration: underline;
    }

    p, a, li, td, blockquote {
        mso-line-height-rule: exactly;
    }

    ul li {
        padding-bottom: 0px;
        line-height: 120%;
    }

    ul ol {
        padding-bottom: 0px;
        line-height: 120%;
    }

    a[href^=tel], a[href^=sms] {
        color: inherit;
        cursor: default;
        text-decoration: none;
    }

    p, a, li, td, body, table, blockquote {
        -ms-text-size-adjust: 100%;
        -webkit-text-size-adjust: 100%;
    }

    a[x-apple-data-detectors] {
        color: inherit !important;
        text-decoration: none !important;
        font-size: inherit !important;
        font-family: inherit !important;
        font-weight: inherit !important;
        line-height: inherit !important;
    }

    h1 {
        font-size: 24px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        margin: 7px 0px;
    }

    h2 {
        font-size: 20px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        /*margin: 7px 0px;*/
    }

    h3 {
        font-size: 18px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        /*margin: 7px 0px;*/
    }

    h4 {
        font-size: 17px;
        font-style: normal;
        font-weight: bold;
        line-height: 125%;
        letter-spacing: normal;
        /*margin: 7px 0px;*/
    }
    .aligncenter {
        text-align: center !important;
    }
    .alignright {
        text-align: <?php echo esc_attr($alignRight); ?> !important;
    }
    /*
    * Classic Editor
     */
    .wp-block-buttons .wp-block-button a {
        display: inline-block;
    }
    .wp-block-image.aligncenter img,
    img.aligncenter {
        margin: 0 auto;
        display: block;
    }

    .wp-block-image.alignright img,
    img.alignright {
        display: block;
        margin: 0 0 0 auto;
    }

    <?php if(fluentcrm_is_rtl()) : ?>
    p,ul,li {
        text-align: right;
    }
    <?php endif; ?>

    /* Latest Post Block */
    .fc_latest_post_item {
        border:1px solid #edeef4;
    }
    .fc_latest_post_item.layout-6,
    .fc_latest_post_item.layout-4 {
        border: none;
        border-bottom: 1px solid #edeef4;
    }
    .fc_latest_post_item.layout-5 {
        border: none;
    }
    .fc_latest_post_item.layout-6:first-child {
        border-top: 1px solid #edeef4;
    }
    .fc_latest_post_item.layout-6 .fc_latest_post_content .title {
        font-size: 20px;
    }
    .fc_latest_post_item.layout-6 .fc_latest_post_content .meta {
        margin: 0;
    }
    .fc_latest_post_item .fc_latest_post_content .title {
        font-size: 22px;
        line-height: 1.4;
        margin: 0 0 12px 0;
    }
    .fc_latest_post_item .fc_latest_post_content .description {
        margin: 0 0 15px 0;
        font-size: 15px;
        line-height: 180%;
    }
    .fc_latest_post_item .fc_latest_post_content .fc_latest_post_btn {
        display: inline-block;
    }

    .fc_latest_post_item .fc_latest_post_content .meta {
        display: flex;
        align-items: center;
        margin: 0 0 8px 0;
    }
    .fc_latest_post_item .fc_latest_post_content .meta .author {
        display: flex;
        align-items: center;
        margin-right: 15px;
    }
    .fc_latest_post_item .fc_latest_post_content .meta .author img {
        margin-right: 7px;
    }
    .fc_latest_post_item .fc_latest_post_content .meta .comments {
        display: block;
        margin-left: 15px;
    }
    .fc_latest_post_item.layout-2 .fc_latest_post_content .fc_latest_post_btn {
        display: inline-block;
    }
    .fc_latest_post_item.layout-4 .fc_latest_post_content .description {
        margin: 0;
    }
    .fc_latest_post_item.layout-4 .fc_latest_post_content .meta {
        margin: 20px 0 0 0;
    }
    .fc_latest_post_item.layout-5 {
        padding: 0;
        list-style: none;
    }
    .fc_latest_post_item.layout-5 tbody tr td {
        border: none;
        padding: 5px 0;
        display: flex;
    }
    .fc_latest_post_item.layout-5 tbody tr td a {
        font-size: 16px;
        font-weight: 600;
    }
    .fc_latest_post_item.layout-5 tbody tr td .fc_latest_post_marker {
        display: block;
        width: 5px;
        height: 5px;
        background: #000;
        border-radius: 20px;
        margin-top: 8px;
        margin-right: 5px;
    }
    .fc_latest_post_item.layout-7 {
        border: none;
    }
    .fc_latest_post_item.layout-7 .fc_latest_post_content {
        padding: 0;
    }
    .fc_latest_post_item.layout-7 .fc_latest_post_content .title {
        font-size: 25px;
        padding: 0;
        line-height: 1.4;
        margin: 0 0 10px 0;
    }
    .fc_latest_post_item.layout-7 .fc_latest_post_content .fc_latest_post_btn {
        border-radius: 4px;
        font-weight: 500;
        padding: 4px 14px;
    }

    @media screen and (max-width: 600px) {
        .fc_latest_post_item.layout-2 > tbody .fc_latest_post_item_tr td,
        .fc_latest_post_item.layout-2 > tbody .fc_latest_post_item_tr,
        .fc_latest_post_item.layout-3 > tbody .fc_latest_post_item_tr td,
        .fc_latest_post_item.layout-3 > tbody .fc_latest_post_item_tr,
        .fc_latest_post_item.layout-4 > tbody .fc_latest_post_item_tr td,
        .fc_latest_post_item.layout-4 > tbody .fc_latest_post_item_tr {
            display:block !important;
            flex-wrap: wrap;
        }
    }
</style>
