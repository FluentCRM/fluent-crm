<?php
$width = $config['content_width'];
$hFont = $config['headings_font_family'];
$mainColor = $config['text_color'];
$hColor = $config['headings_color'];
$linkColor = $config['link_color'];
$bodBgyColor = $config['body_bg_color'];
$contentBg = $config['content_bg_color'];
$footerColor = $config['footer_text_color'];
$mainFont = $config['content_font_family'];


$alignLeft = 'left';
$alignRight = 'right';
if(fluentcrm_is_rtl()) {
    $alignLeft = 'right';
    $alignRight = 'left';
}

?>

<style type="text/css">
    <?php echo \FluentCrm\App\Services\Helper::generateThemePrefCss(); ?>
</style>

<style type="text/css" rel="stylesheet" media="all">
    /* SettingsDefaults */
    body {
        background: <?php echo $bodBgyColor; ?>;
        background-color: <?php echo $bodBgyColor; ?>;
    }
    .templateContainer {
        max-width: <?php echo $width; ?>px;
    }

    #templateWrapper {
        background: <?php echo $bodBgyColor; ?>;
        background-color: <?php echo $bodBgyColor; ?>;
    }

    #templateFooter .fcTextContent, #templateFooter .fcTextContent p {
        font-size: 12px;
        line-height: 170%;
        text-align: center;
        color: <?php echo $footerColor; ?>;
    }

    #templateFooter .fcTextContent a, #templateFooter .fcTextContent p a {
        font-weight: normal;
        text-decoration: underline;
        color: <?php echo $footerColor; ?>;
    }

    <?php if($linkColor): ?>
    a {
        color: <?php echo $linkColor; ?>;
    }
    <?php endif; ?>

    <?php if($mainFont): ?>
    #templateFooter {
        font-family: <?php echo $mainFont; ?>;
    }
    <?php endif; ?>

    #templateBody .fcTextContentBody {
        background: <?php echo $contentBg; ?> none no-repeat center/cover;
        background-color: <?php echo $contentBg; ?>;
        <?php if($hColor) : ?>
        color: <?php echo $mainColor; ?>;
        <?php endif; ?>
        <?php if($mainFont): ?>
        font-family: <?php echo $mainFont; ?>;
        <?php endif; ?>
    }

    .fcTextContentBody h1, .fcTextContentBody h2, .fcTextContentBody h3, .fcTextContentBody h4, .fcTextContentBody h5, .fcTextContentBody h6 {
        <?php if($hFont): ?>
        font-family: <?php echo $hFont; ?>;
        <?php endif; ?>
        <?php if($hColor): ?>
        color: <?php echo $hColor; ?>;
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

    .wp-block-image img {
        margin: 0;
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
        text-align: <?php echo $alignRight; ?> !important;
    }
    .has-text-align-left {
        text-align: <?php echo $alignLeft; ?> !important;
    }
    .has-text-align-center {
        text-align: center !important;
    }

    .alignleft {
        text-align: <?php echo $alignLeft?> !important;
    }

    .alignright {
        text-align: <?php echo $alignRight?> !important;
    }

    figure.wp-block-media-text__media {
        background-size: cover;
        background-repeat: no-repeat;
    }
    .has_bg_image figure.wp-block-media-text__media img {
        opacity: 0;
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
        height: auto;
        outline: none;
        text-decoration: none;
        max-width: 100%;
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

    #bodyTable {
        background-image: none;
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
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
        margin: 15px 0px;
    }

    h2 {
        font-size: 22px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        margin: 15px 0px;
    }

    h3 {
        font-size: 20px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        margin: 15px 0px;
    }

    h4 {
        font-size: 18px;
        font-style: normal;
        font-weight: bold;
        line-height: 125%;
        letter-spacing: normal;
        margin: 15px 0px;
    }

    #templateHeader .fcTextContent, #templateHeader .fcTextContent p {
        font-size: 16px;
        line-height: 180%;
        text-align: <?php echo $alignLeft; ?>;
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
        text-align: <?php echo $alignLeft; ?>;
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
    .aligncenter {
        text-align: center !important;
    }
    .alignright {
        text-align: <?php echo $alignRight; ?> !important;
    }

    .fce_buttons_row.tb_btn_right {
        margin-<?php echo $alignLeft; ?>: auto;
        width: auto !important;
    }

    .tb_btn_right .wp-block-button {
        text-align: <?php echo $alignRight; ?>;
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
    img.aligncenter {
        margin: 0 auto;
        display: block;
    }

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
        .fce_column {
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
    }


    .wp-block-table.is-style-stripes tbody tr:nth-child(odd) {
        background-color: #f0f0f0;
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
