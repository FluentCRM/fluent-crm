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
    body {
        font-family: <?php echo $content_font_family; ?>;
        line-height: 150%;
    }
    p {
        font-family: <?php echo $content_font_family; ?>;        line-height: 150%;
        font-size: 16px;
    }
    li, ol {
        font-family: <?php echo $content_font_family; ?>;        line-height: 120%;
        font-size: 16px;
        margin-bottom: 5px;
        padding-bottom: 0px;
    }

    #footer_section p, #footer_section li {
        font-size: 12px;
    }

    h1, h2, h3, h4 {
        line-height: 120%;
        font-family: <?php echo $content_font_family; ?>;
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
        margin: 15px 0px;
    }

    h2 {
        font-size: 20px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        margin: 14px 0px;
    }

    h3 {
        font-size: 18px;
        font-style: normal;
        line-height: 140%;
        letter-spacing: normal;
        margin: 12px 0px;
    }

    h4 {
        font-size: 17px;
        font-style: normal;
        font-weight: bold;
        line-height: 125%;
        letter-spacing: normal;
        margin: 12px 0px;
    }
    .aligncenter {
        text-align: center !important;
    }
    .alignright {
        text-align: <?php echo $alignRight; ?> !important;
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
</style>
