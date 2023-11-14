<?php

/**
 * @var $app \FluentCrm\Framework\Foundation\Application $app
 */

/*
 * Note: Namespace will be added automatically. For example, if you use MyClass
 * as the controller name then it will become FluentCrm\App\Hooks\Handlers\MyClass.
 */
$app->addFilter('fluent_crm/countries', 'CountryNames@get');

$app->addFilter('fluent_crm/email-design-template-plain', 'EmailDesignTemplates@addPlainTemplate', 10, 3);
$app->addFilter('fluent_crm/email-design-template-simple', 'EmailDesignTemplates@addSimpleTemplate', 10, 3);
$app->addFilter('fluent_crm/email-design-template-classic', 'EmailDesignTemplates@addClassicTemplate', 10, 3);
$app->addFilter('fluent_crm/email-design-template-raw_classic', 'EmailDesignTemplates@addRawClassicTemplate', 10, 3);
$app->addFilter('fluent_crm/email-design-template-web_preview', 'EmailDesignTemplates@addWebPreviewTemplate', 10, 3);

$app->addFilter('fluent_crm/purchase_history_woocommerce', 'PurchaseHistory@wooOrders', 10, 2);
$app->addFilter('fluent_crm/purchase_history_edd', 'PurchaseHistory@eddOrders', 10, 2);
$app->addFilter('fluent_crm/purchase_history_payform', 'PurchaseHistory@payformSubmissions', 10, 2);

$app->addFilter('fluent_crm/form_submission_providers', 'FormSubmissions@pushDefaultFormProviders');
$app->addFilter('fluentcrm_get_form_submissions_fluentform', 'FormSubmissions@getFluentFormSubmissions', 10, 2);

add_filter('fluent_crm/parse_campaign_email_text', function ($text, $subscriber) {
    if (!$subscriber) {
        return $text;
    }
    return \FluentCrm\App\Services\Libs\Parser\Parser::parse($text, $subscriber);
}, 10, 2);

$app->addFilter('fluent_crm/parse_extended_crm_text', function ($text, $subscriber) {
    if (!$subscriber) {
        return $text;
    }

    return \FluentCrm\App\Services\Libs\Parser\Parser::parseCrmValue($text, $subscriber);
}, 10, 2);

$app->addFilter('comment_form_submit_field', 'AutoSubscribeHandler@addSubscribeCheckbox', 10, 1);
$app->addFilter('wp_privacy_personal_data_exporters', 'Cleanup@attachCrmExporter');


$app->addFilter('wp_privacy_personal_data_exporters', 'Cleanup@attachCrmExporter');

if (defined('FLUENTFORM')) {
    $app->addFilter('fluentform/editor_shortcode_callback_group_fluentcrm', 'FormSubmissions@parseEditorCodes', 10, 3);

    add_filter('fluentform/editor_shortcodes', function ($smartCodes) {
        $smartCodes[0]['shortcodes']['{fluentcrm.CONTACT_DATA_KEY}'] = 'FluentCRM Data';
        return $smartCodes;
    }, 100, 1);
}

/*
 * deprecated Hooks
 * @todo: Remove this by January 2023
 */
add_filter('fluentcrm_parse_campaign_email_text', function ($text, $subscriber) {
    if (!$subscriber) {
        return $text;
    }

    _deprecated_hook('fluentcrm_parse_campaign_email_text', '2.6.6', 'fluent_crm/parse_campaign_email_text', 'Use fluent_crm/parse_campaign_email_text filter hook instead');

    return \FluentCrm\App\Services\Libs\Parser\Parser::parse($text, $subscriber);
}, 10, 2);

$app->addFilter('fluentcrm_email-design-template-plain', function ($emailBody, $templateData, $campaign) {
    _deprecated_hook('fluentcrm_email-design-template-plain', '2.6.6', 'fluent_crm/email-design-template-plain', 'Use fluent_crm/email-design-template-plain filter hook instead');
    return (new \FluentCrm\App\Hooks\Handlers\EmailDesignTemplates())->addPlainTemplate($emailBody, $templateData, $campaign);
}, 10, 3);

$app->addFilter('fluentcrm_email-design-template-simple', function ($emailBody, $templateData, $campaign) {
    _deprecated_hook('fluentcrm_email-design-template-simple', '2.6.6', 'fluent_crm/email-design-template-simple', 'Use fluent_crm/email-design-template-simple filter hook instead');
    return (new \FluentCrm\App\Hooks\Handlers\EmailDesignTemplates())->addSimpleTemplate($emailBody, $templateData, $campaign);
}, 10, 3);

$app->addFilter('fluentcrm_email-design-template-classic', function ($emailBody, $templateData, $campaign) {
    _deprecated_hook('fluentcrm_email-design-template-classic', '2.6.6', 'fluent_crm/email-design-template-classic', 'Use fluent_crm/email-design-template-classic filter hook instead');
    return (new \FluentCrm\App\Hooks\Handlers\EmailDesignTemplates())->addClassicTemplate($emailBody, $templateData, $campaign);
}, 10, 3);

$app->addFilter('fluentcrm_email-design-template-raw_classic', function ($emailBody, $templateData, $campaign) {
    _deprecated_hook('fluentcrm_email-design-template-raw_classic', '2.6.6', 'fluent_crm/email-design-template-raw_classic', 'Use fluent_crm/email-design-template-raw_classic filter hook instead');
    return (new \FluentCrm\App\Hooks\Handlers\EmailDesignTemplates())->addRawClassicTemplate($emailBody, $templateData, $campaign);
}, 10, 3);

$app->addFilter('fluentcrm_email-design-template-web_preview', function ($emailBody, $templateData, $campaign) {
    _deprecated_hook('fluentcrm_email-design-template-web_preview', '2.6.6', 'fluent_crm/email-design-template-web_preview', 'Use fluent_crm/email-design-template-web_preview filter hook instead');
    return (new \FluentCrm\App\Hooks\Handlers\EmailDesignTemplates())->addWebPreviewTemplate($emailBody, $templateData, $campaign);
}, 10, 3);

/*
 * </deprecated_hooks_end>
 */


