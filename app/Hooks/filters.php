<?php

/**
 * @var $app  \FluentCrm\App\App
 */

/*
 * Note: Namespace will be added automatically. For example, if you use MyClass
 * as the controller name then it will become FluentCrm\App\Hooks\Handlers\MyClass.
 */
$app->addFilter('fluentcrm_countries', 'CountryNames@get');

$app->addFilter('fluentcrm_email-design-template-plain', 'EmailDesignTemplates@addPlainTemplate', 10, 3);
$app->addFilter('fluentcrm_email-design-template-simple', 'EmailDesignTemplates@addSimpleTemplate', 10, 3);
$app->addFilter('fluentcrm_email-design-template-classic', 'EmailDesignTemplates@addClassicTemplate', 10, 3);
$app->addFilter('fluentcrm_email-design-template-raw_classic', 'EmailDesignTemplates@addRawClassicTemplate', 10, 3);

$app->addFilter('fluentcrm_get_purchase_history_woocommerce', 'PurchaseHistory@wooOrders', 10, 2);
$app->addFilter('fluentcrm_get_purchase_history_edd', 'PurchaseHistory@eddOrders', 10, 2);
$app->addFilter('fluentcrm_get_purchase_history_payform', 'PurchaseHistory@payformSubmissions', 10, 2);

$app->addFilter('fluentcrm_form_submission_providers', 'FormSubmissions@pushDefaultFormProviders');
$app->addFilter('fluentcrm_get_form_submissions_fluentform', 'FormSubmissions@getFluentFormSubmissions', 10, 2);

$app->addFilter('fluentcrm_parse_campaign_email_text', function ($text, $subscriber) {
    return \FluentCrm\App\Services\Libs\Parser\Parser::parse($text, $subscriber);
}, 10, 2);

$app->addFilter('comment_form_submit_field', 'AutoSubscribeHandler@addSubscribeCheckbox', 10, 1);
