<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <!--[if gte mso 15]>
    <xml>
    <o:OfficeDocumentSettings>
        <o:AllowPNG/>
        <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <?php do_action('fluent_crm/email_header', 'raw_classic'); ?>
    <?php include(FLUENTCRM_PLUGIN_PATH.'app/views/emails/classic-style.php'); ?>

</head>
<body class="fc_classic_template"
      style="background: none no-repeat center/cover;height: 100%;margin: 0;padding: 0;width: 100%;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-image: none;background-repeat: no-repeat;background-position: center;background-size: cover;">
<?php if ($preHeader): ?><span class="fcPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;"><?php echo esc_html($preHeader); ?></span><?php endif; ?>
<div style="padding: 0 10px;">
    <table<?php if (fluentcrm_is_rtl()) { echo ' dir="rtl"';} ?> align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;height: 100%;margin: 0;padding: 0;width: 100%;background-image: none;background-repeat: no-repeat;background-position: center;background-size: cover;">
        <tr>
            <td align="left" valign="top" id="bodyCell" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;height: 100%;margin: 0;padding: 0;width: 100%;border-top: 0;">
                <?php echo $email_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </td>
        </tr>
        <?php if (!empty($footer_text)): ?>
            <tr>
                <td align="left" valign="top" id="footer_section" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;height: 100%;margin: 0;padding: 0;width: 100%;border-top: 0;">
                    <div style="margin-top: 80px; text-align: left;">
                        <?php echo $footer_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php if (!defined('FLUENTCAMPAIGN')): ?>
                            <p><?php esc_html_e('Powered By', 'fluent-crm'); ?> <a href="http://fluentcrm.com/?utm_source=wp&utm_medium=wp_mail&utm_campaign=footer"><?php esc_html_e('FluentCRM', 'fluent-crm'); ?></a></p>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
