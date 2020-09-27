<?php

/**
 * @var $app \FluentCrm\Includes\Core\Application
 */

/*
 * Note: Namespace will be added automatically. For example, if you use MyClass
 * as the controller name then it will become FluentCrm\App\Hooks\Handlers\MyClass.
 */

$app->addCustomFilter('countries', 'CountryNames@get');

$app->addCustomFilter('email-design-template-plain', 'EmailDesignTemplates@addPlainTemplate', 10, 3);
$app->addCustomFilter('email-design-template-simple', 'EmailDesignTemplates@addSimpleTemplate', 10, 3);
$app->addCustomFilter('email-design-template-classic', 'EmailDesignTemplates@addClassicTemplate', 10, 3);
$app->addCustomFilter('email-design-template-raw_html', 'EmailDesignTemplates@addRawHtmlTemplate', 10, 3);

$app->addCustomFilter('get_purchase_history_woocommerce', 'PurchaseHistory@wooOrders', 10, 2);
$app->addCustomFilter('get_purchase_history_edd', 'PurchaseHistory@eddOrders', 10, 2);
$app->addCustomFilter('get_purchase_history_payform', 'PurchaseHistory@payformSubmissions', 10, 2);

$app->addCustomFilter('form_submission_providers', 'FormSubmissions@pushDefaultFormProviders');
$app->addCustomFilter('get_form_submissions_fluentform', 'FormSubmissions@getFluentFormSubmissions', 10, 2);

$app->addCustomFilter('support_tickets_providers', 'SupportTicketsProviders@pushDefaultProviders');
$app->addCustomFilter('get_support_tickets_awesome_support', 'SupportTicketsProviders@awesomeSupoortTickets', 10, 2);

$app->addCustomFilter('parse_campaign_email_text', function ($text, $subscriber) {
    return \FluentCrm\Includes\Parser\Parser::parse($text, $subscriber);
}, 10, 2);

$app->addFilter('comment_form_submit_field', 'AutoSubscribeHandler@addSubscribeCheckbox', 10, 1);
