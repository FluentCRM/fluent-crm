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
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" >
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <?php do_action('fluent_crm/email_header', 'classic'); ?>
    <?php include(FLUENTCRM_PLUGIN_PATH.'app/views/emails/common-style.php'); ?>
</head>
<body class="fc_classic_template" style="background: none no-repeat center/cover;height: 100%;margin: 0;padding: 0;width: 100%;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-image: none;background-repeat: no-repeat;background-position: center;background-size: cover;">
<?php if ($preHeader): ?><span class="fcPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;"><?php echo esc_html($preHeader); ?></span><?php endif; ?>
<center>
    <table<?php if(fluentcrm_is_rtl()) { echo ' dir="rtl"'; }  ?> align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="templateWrapper" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;height: 100%;margin: 0;padding: 0;width: 100%;background-image: none;background-repeat: no-repeat;background-position: center;background-size: cover;color:inherit;">
        <tr>
            <td align="left" valign="top" id="bodyCell" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;height: 100%;margin: 0;padding: 0;width: 100%;border-top: 0;">
                <!-- BEGIN TEMPLATE // -->
                <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0"  style="max-width:100%;color:inherit">
                <tr>
                    <td align="center" valign="top" style="max-width:100%">
                <![endif]-->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateContainer" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;border: 0;color:inherit;">
                    <tr>
                        <td valign="top" id="templateBody" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;border-top: 0;border-bottom: 0;"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="fcTextBlock" style="min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color:inherit;">
                                <tbody class="fcTextBlockOuter">
                                <tr>
                                    <td valign="top" class="fcTextBlockInner" style="padding-top: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                                        <!--[if mso]>
                                        <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;color:inherit;">
                                        <tr>
                                        <![endif]-->

                                        <!--[if mso]>
                                        <td valign="top" width="600" style="width:600px;">
                                        <![endif]-->
                                        <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width: 100%;min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color:inherit;" width="100%" class="fcTextContentContainer">
                                            <tbody>
                                            <tr>
                                                <td valign="top" class="fcTextContentBody" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-image: none;background-repeat: no-repeat;background-position: center;background-size: cover;border-top: 0;padding-top: 0;padding-bottom: 9px;">
                                                    <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width: 100%;min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color:inherit;" width="100%" class="fcTextContentContainer">
                                                        <tbody class="mcnTextBlockOuter">
                                                        <tr>
                                                            <td class="fc_email_body"  align="left" valign="top" style="padding-top: 20px;padding-right: 20px;padding-bottom: 10px;padding-left: 20px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;word-break: break-word;font-size: 16px;line-height: 180%;text-align: left;">
                                                                <?php echo $email_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            </tbody></table>
                                        <!--[if mso]>
                                        </td>
                                        <![endif]-->
                                        <!--[if mso]>
                                        </tr>
                                        </table>
                                        <![endif]-->
                                    </td>
                                </tr>
                                </tbody>
                            </table></td>
                    </tr>
                    <?php if(!empty($footer_text)): ?>
                        <tr>
                            <td valign="top" id="templateFooter" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;border-top: 0;border-bottom: 0;"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="fcTextBlock" style="min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                                    <tbody class="fcTextBlockOuter">
                                    <tr>
                                        <td valign="top" class="fcTextBlockInner" style="padding-top: 9px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                                            <!--[if mso]>
                                            <table align="left" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100%;color:inherit;">
                                            <tr>
                                            <![endif]-->

                                            <!--[if mso]>
                                            <td valign="top" width="600" style="width:600px;">
                                            <![endif]-->
                                            <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width: 100%;min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;color:inherit;" width="100%" class="fcTextContentContainer">
                                                <tbody><tr>
                                                    <td valign="top" class="fcTextContent" style="padding-top: 0;padding-right: 18px;padding-bottom: 9px;padding-left: 18px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;word-break: break-word;font-size: 12px;line-height: 150%;text-align: left;">
                                                        <?php echo $footer_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                        <?php if(!defined('FLUENTCAMPAIGN')): ?>
                                                            <p><?php esc_html_e('Powered By', 'fluent-crm'); ?> <a href="http://fluentcrm.com/?utm_source=wp&utm_medium=wp_mail&utm_campaign=footer"><?php esc_html_e('FluentCRM', 'fluent-crm'); ?></a></p>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                </tbody></table>
                                            <!--[if mso]>
                                            </td>
                                            <![endif]-->

                                            <!--[if mso]>
                                            </tr>
                                            </table>
                                            <![endif]-->
                                        </td>
                                    </tr>
                                    </tbody>
                                </table></td>
                        </tr>
                    <?php endif; ?>
                </table>
                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
                <!-- // END TEMPLATE -->
            </td>
        </tr>
    </table>
</center>
</body>
</html>
