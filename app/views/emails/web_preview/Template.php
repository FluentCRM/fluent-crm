<?php include(FLUENTCRM_PLUGIN_PATH.'app/views/emails/classic-style.php'); ?>
<table<?php if (fluentcrm_is_rtl()) { echo ' dir="rtl"';} ?> align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;height: 100%;margin: 0;padding: 0;width: 100%;background-image: none;background-repeat: no-repeat;background-position: center;background-size: cover;">
    <tr>
        <td align="left" valign="top" id="bodyCell" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;height: 100%;margin: 0;padding: 0;width: 100%;border-top: 0;">
            <?php echo wp_kses_post($email_body); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
