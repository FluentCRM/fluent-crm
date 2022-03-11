<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\Helper;

/**
 *  CustomEmailCampaign Model - DB Model for Custom Emails
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */

class CustomEmailCampaign extends Campaign
{
    protected static $type = 'custom_email_campaign';

    public static function getMock()
    {
        $defaultTemplate = Helper::getDefaultEmailTemplate();
        return [
            'id'               => '',
            'title'            => __('Custom Email', 'fluent-crm'),
            'status'           => 'published',
            'template_id'      => '',
            'email_subject'    => '',
            'email_pre_header' => '',
            'email_body'       => '',
            'utm_status'       => 0,
            'utm_source'       => '',
            'utm_medium'       => '',
            'utm_campaign'     => '',
            'utm_term'         => '',
            'utm_content'      => '',
            'design_template'  => $defaultTemplate,
            'settings'         => (object)[
                'template_config' => Helper::getTemplateConfig($defaultTemplate)
            ]
        ];
    }

}
