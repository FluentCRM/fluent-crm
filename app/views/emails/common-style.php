<?php
$width       = $config['content_width'];
$hFont       = $config['heading_font_family'];
$hColor      = $config['headings_color'];
$mainColor   = $config['text_color'];
$linkColor   = $config['link_color'];
$bodBgyColor = $config['body_bg_color'];
$contentBg   = $config['content_bg_color'];
$footerColor = $config['footer_text_color'];
$mainFont    = $config['content_font_family'];

$pColor      = $config['paragraph_color'];
$pSize       = $config['paragraph_font_size'];
$pFontFamily = $config['paragraph_font_family'];
$pLHeight    = $config['paragraph_line_height'];

$alignLeft = 'left';
$alignRight = 'right';
if(fluentcrm_is_rtl()) {
    $alignLeft = 'right';
    $alignRight = 'left';
}

?>

<style type="text/css">
    <?php echo \FluentCrm\App\Services\Helper::generateThemePrefCss(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</style>

<style type="text/css" rel="stylesheet" media="all">
    /* SettingsDefaults */
    body {
        background: <?php echo esc_attr($bodBgyColor); ?>;
        background-color: <?php echo esc_attr($bodBgyColor); ?>;
    }
    .templateContainer {
        max-width: <?php echo esc_attr($width); ?>px;
    }

    #templateWrapper {
        background: <?php echo esc_attr($bodBgyColor); ?>;
        background-color: <?php echo esc_attr($bodBgyColor); ?>;
    }

    .fc_email_body {
        background: <?php echo esc_attr($contentBg); ?>;
        background-color: <?php echo esc_attr($contentBg); ?>;
        <?php if($mainFont): ?>
            font-family: <?php echo sanitize_text_field($mainFont); ?>;
        <?php endif; ?>
    }

    #templateFooter .fcTextContent, #templateFooter .fcTextContent p {
        font-size: 12px;
        line-height: 170%;
        text-align: center;
        color: <?php echo esc_attr($footerColor); ?>;
    }

    #templateFooter .fcTextContent a, #templateFooter .fcTextContent p a {
        font-weight: normal;
        text-decoration: underline;
        color: <?php echo esc_attr($footerColor); ?>;
    }

    <?php if($linkColor): ?>
    a {
        color: <?php echo esc_attr($linkColor); ?>;
    }
    <?php endif; ?>
    a {
        text-decoration: underline;
    }

    <?php if($mainFont): ?>
    #templateFooter {
        font-family: <?php echo sanitize_text_field($mainFont); ?>;
    }
    <?php endif; ?>

    #templateBody .fcTextContentBody {
        background: <?php echo esc_attr($contentBg); ?> none no-repeat center/cover;
        background-color: <?php echo esc_attr($contentBg); ?>;
        <?php if($hColor) : ?>
        color: <?php echo esc_attr($mainColor); ?>;
        <?php endif; ?>
        <?php if($mainFont): ?>
        font-family: <?php echo sanitize_text_field($mainFont); ?>;
        <?php endif; ?>
    }

    .fcTextContentBody p {
        <?php if ($pColor): ?>
        color: <?php echo esc_attr($pColor) ?>;
        <?php endif; ?>
        <?php if ($pSize): ?>
        font-size: <?php echo sanitize_text_field($pSize) ?>px;
        <?php endif; ?>
        <?php if ($pFontFamily): ?>
        font-family: <?php echo sanitize_text_field($pFontFamily) ?>;
        <?php endif; ?>
        <?php if ($pLHeight): ?>
        line-height: <?php echo sanitize_text_field($pLHeight) ?>px;
        <?php endif; ?>
    }
    .fcTextContentBody h1, .fcTextContentBody h2, .fcTextContentBody h3, .fcTextContentBody h4, .fcTextContentBody h5, .fcTextContentBody h6 {
        <?php if($hFont): ?>
        font-family: <?php echo sanitize_text_field($hFont); ?>;
        <?php endif; ?>
        <?php if($hColor): ?>
        color: <?php echo esc_attr($hColor); ?>;
        <?php endif; ?>
    }

    img.emoji {
        width: 14px;
    }

    /* /SettingsDefaults */

    /*Block Editor*/
    .wp-block-group {
        padding: 20px 20px 20px 20px;
        margin: 20px 0px;
    }

    .is-style-rounded img {
        border-radius: 50%;
    }

    .wp-block-image {
        /*margin: 7px 0;*/
    }

    .wp-block-image img {
        display: block;
        padding: 0;
    }

    .wp-block-image p {
        text-align: center;
        font-size: 80%;
        margin: 0;
        padding: 0;
    }

    .wp-block-table table {
        width: 100%;
        border: 1px solid #868686;
    }

    .wp-block-table table th, figure.wp-block-table table td {
        border: 1px solid #5f5f5f;
        padding: 5px 10px;
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
        text-align: <?php echo esc_attr($alignLeft); ?> !important;
    }

    .alignright {
        text-align: <?php echo esc_attr($alignRight); ?> !important;
    }

    figure.wp-block-media-text__media {
        background-size: cover;
        background-repeat: no-repeat;
    }
    .has_bg_image figure.wp-block-media-text__media img {
        opacity: 0;
    }

    ol.has-background, ul.has-background {
        padding: 20px 40px;
    }

    td.no_image_fill img {
        margin-bottom: -9px;
    }

    .fc_btn a {
        font-size: 16px;
        text-decoration: none;
        border-radius: 0px;
        padding: 12px 18px;
        display: block;
        border: 0px solid white;
    }

    .fc_d_btn_bg a {
        background: #32373c;
        border: 1px solid #32373c;
    }
    .fc_d_btn_color a {
        color: white;
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
        border-radius: inherit;
        outline: none;
        text-decoration: none;
        max-width: 100%;
        display: block;
    }

    ul {
        margin: 7px 0;
    }
    ul li {
        margin: 7px 0;
        padding-bottom: 10px;
    }

    pre {
        margin: 7px 0;
    }

    .fcPreviewText {
        display: none !important;
    }

    #outlook a {
        padding: 0;
    }

    img {
        -ms-interpolation-mode: bicubic;
    }

    table {
        mso-table-lspace: 0pt;
        mso-table-rspace: 0pt;
    }

    p, a, li, td, blockquote {
        mso-line-height-rule: exactly;
    }

    ul li {
        padding-bottom: 10px;
        line-height: 170%;
    }

    ul ol {
        padding-bottom: 10px;
        line-height: 170%;
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

    #bodyCell {
        padding: 10px;
    }


    .fcTextContent {
        word-break: break-word;
    }

    .fcTextContent img {
        height: auto !important;
    }

    body {
        font-family: Helvetica;
    }

    #bodyCell {
        border-top: 0;
    }

    .templateContainer {
        border: 0;
    }

    h1 {
        font-size: 26px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        margin: 7px 0px;
    }

    h2 {
        font-size: 22px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        margin: 7px 0px;
    }

    h3 {
        font-size: 20px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        margin: 7px 0px;
        /*padding: 15px 0;*/
    }

    h4 {
        font-size: 18px;
        font-style: normal;
        font-weight: bold;
        line-height: 125%;
        letter-spacing: normal;
        margin: 7px 0px;
        /*padding: 15px 0;*/
    }
    h5,h6 {
        margin: 7px 0;
        line-height: 180%;
    }

    #templateHeader .fcTextContent, #templateHeader .fcTextContent p {
        font-size: 16px;
        line-height: 180%;
        text-align: <?php echo esc_attr($alignLeft); ?>;
    }

    #templateHeader .fcTextContent a, #templateHeader .fcTextContent p a {
        font-weight: normal;
        text-decoration: underline;
    }

    #templateBody {
        border-top: 0;
        border-bottom: 0;
    }

    #templateBody .fcTextContent, #templateBody .fcTextContent p {
        font-size: 16px;
        line-height: 180%;
        text-align: <?php echo esc_attr($alignLeft); ?>;
    }

    #templateBody .fcTextContent a, #templateBody .fcTextContent p a {
        font-weight: normal;
        text-decoration: underline;
    }

    #templateFooter {
        border-top: 0;
        border-bottom: 0;
    }

    #templateFooter .fcTextContent, #templateFooter .fcTextContent p {
        font-size: 12px;
        line-height: 170%;
        text-align: center;
    }

    #templateFooter .fcTextContent a, #templateFooter .fcTextContent p a {
        font-weight: normal;
        text-decoration: underline;
    }
    .wp-block-image.alignleft, .wp-block-image.alignright, .wp-block-image.aligncenter,
    .wp-block-image .alignleft,
    .wp-block-image .alignright,
    .wp-block-image .aligncenter {
        width: 100%;
    }
    .aligncenter {
        text-align: center !important;
    }
    .alignright {
        text-align: <?php echo esc_attr($alignRight); ?> !important;
    }
    .wp-block-image p {
        text-align: inherit !important;
    }
    .fc_editor_body .alignright figcaption {
        text-align: right;
    }

    #templateBody h1, #templateBody h2 {
        font-style: normal;
        font-weight: 700;
        letter-spacing: normal;
        line-height: 125%;
        /*padding: 15px 0;*/
    }
    .fce_buttons_row.tb_btn_right {
        margin-<?php echo esc_attr($alignLeft); ?>: auto;
        width: auto !important;
    }

    .tb_btn_right .wp-block-button {
        text-align: <?php echo esc_attr($alignRight); ?>;
    }

    .tb_btn_center {
        margin-left: auto;
        margin-right: auto;
    }

    .tb_btn_center .wp-block-button {
        text-align: center;
    }

    /*
    * Classic Editor
     */
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

    table.wp-block-button__width-100, table.wp-block-button__width-75, table.wp-block-button__width-50 {
        width: 100% !important;
    }
    .wp-block-button__width-100 table {
        width: 100%;
    }

    .wp-block-button__width-75 table {
        width: 75%;
    }

    .wp-block-button__width-50 table {
        width: 50%;
    }

    .fc_btn_count_2.wp-block-button__width-50 table {
        width: 100%;
    }

</style>


<style type="text/css">
    @media only screen and (max-width: 768px) {
        .wp-block-group {
            padding: 10px;
            margin: 20px 0px;
        }
        .fce_row {
            width:100% !important;
        }
        .fce_stacked .fce_column {
            display: block !important;
            width: 100% !important;
            padding-right: 0 !important;
        }
    }


    @media only screen and (max-width: 480px) {
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: none !important;
        }
        #bodyCell {
            padding: 0px !important;
        }
        .fcTextBlockInner {
            padding-top: 0px !important;
        }
        .mcnTextBlockOuter .fc_email_body {
            padding-top: 10px !important;
            padding-right: 10px !important;
            padding-bottom: 10px !important;
            padding-left: 10px !important;
        }
        .fc_column_content {
            padding: 0;
        }
        table.fc_media_table {
            width: 100% !important;
            display: block;
        }
        table.fc_media_text {
            width: 100% !important;
            display: block;
        }
        .wp-block-media-text__media {
            background-image: none !important;
        }
        .wp-block-media-text__media img {
            opacity: 1 !important;
        }
        table.fce_buttons_row {
            margin-bottom: 10px;
        }
        table.fce_buttons_row .fce_column {
            margin-bottom: 10px;
            margin-top: 10px;
            text-align: center;
        }
        img, a img {
            height: auto !important;
        }
    }

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
        flex: none;
    }
    .fc_latest_post_item .fc_latest_post_content .meta .comments {
        display: block;
        margin-left: 15px;
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


    .fc_woo_products .template-default .fc_woo_product,
    .fc_woo_products .template-layout-3 .fc_woo_product {
        width: calc(50% - 20px);
        display: inline-table;
    }
    .fc_woo_products .template-layout-3 .fc_woo_product:nth-child(even),
    .fc_woo_products .template-default .fc_woo_product:nth-child(even) {
        margin-left: 20px;
    }
    .fc_woo_product .fc_woo_product_img {
        height: 280px;
        position: relative;
        margin-bottom: 20px;
    }
    .fc_woo_product .fc_woo_product_img img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        background: #eee;
    }
    .fc_woo_product .fc_woo_product_info .title {
        font-size: 20px;
        line-height: 1.2;
        margin: 0 0 8px 0;
        /*color: #2a363d;*/
        font-weight: 500 !important;
    }
    .fc_woo_product .fc_woo_product_info .description {
        font-size: 16px;
        line-height: 1.5;
    }
    .fc_woo_product .fc_woo_product_info .price {
        display: block;
        line-height: 1.2;
        font-size: 16px;
        /*color: #37454e;*/
        margin-bottom: 10px;
    }
    .fc_woo_product .fc_woo_product_info .price del {
        opacity: 0.4;
    }
    .fc_woo_product .fc_woo_product_info .price ins {
        text-decoration: none;
    }
    .fc_woo_product .fc_woo_product_info .add-to-cart-btn {
        display: inline-block;
        font-weight: 600;
        font-size: 14px;
        color: #202020;
    }


    .fc_woo_product.layout-2 .fc_woo_product_img {
        height: auto;
        margin: 0;
    }
    .fc_woo_product.layout-2 .fc_woo_product_info .price {
        margin-bottom: 25px;
    }
    .fc_woo_product.layout-2 .fc_woo_product_info .add-to-cart-btn {
        padding: 15px;
        color: #fff;
        display: block;
        text-align: center;
        line-height: 1.8;
    }


    .fc_woo_products .template-layout-3 .fc_woo_product {
        text-align: center;
    }
    .fc_woo_products .template-layout-3 .fc_woo_product .fc_woo_product_img img,
    .fc_woo_products .template-layout-3 .fc_woo_product .fc_woo_product_img {
        border-radius: 6px;
    }


    .wp-block-table.is-style-stripes tbody tr:nth-child(odd) {
        background-color: #f0f0f0;
    }

    .fce_buttons_row.is-content-justification-right table {
        float: right !important;
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

    @media only screen and (max-width: 480px) {
        body {
            width: 100% !important;
            min-width: 100% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        .fcTextContentContainer {
            max-width: 100% !important;
            width: 100% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        .fcCaptionLeftContentOuter .fcTextContent, .fcCaptionRightContentOuter .fcTextContent {
            padding-top: 9px !important;
        }
    }

    @media only screen and (max-width: 480px) {
        .fcCaptionBlockInner .fcCaptionTopContent:last-child .fcTextContent {
            padding-top: 18px !important;
        }
    }

    @media only screen and (max-width: 480px) {
        .fcTextContent {
            padding-right: 18px !important;
            padding-left: 18px !important;
        }
    }

    @media only screen and (max-width: 480px) {
        h1 {
            font-size: 22px !important;
            line-height: 125% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        h2 {
            font-size: 20px !important;
            line-height: 125% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        h3 {
            font-size: 18px !important;
            line-height: 125% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        h4 {
            font-size: 16px !important;
            line-height: 150% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        table.fcBoxedTextContentContainer td.fcTextContent, td.fcBoxedTextContentContainer td.fcTextContent p {
            font-size: 14px !important;
            line-height: 180% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        td#templateHeader td.fcTextContent, td#templateHeader td.fcTextContent p {
            font-size: 16px !important;
            line-height: 180% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        td#templateBody td.fcTextContent, td#templateBody td.fcTextContent p {
            font-size: 16px !important;
            line-height: 180% !important;
        }
    }

    @media only screen and (max-width: 480px) {
        td#templateFooter td.fcTextContent, td#templateFooter td.fcTextContent p {
            font-size: 14px !important;
            line-height: 180% !important;
        }
    }
</style>

<?php if(fluentcrm_is_rtl()): ?>
<style>
    html[dir=rtl]
</style>
<?php endif; ?>
