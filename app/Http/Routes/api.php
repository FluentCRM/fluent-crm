<?php

/**
 * @var $router FluentCrm\Framework\Http\Router
 */


/*
 * /tags endpoints
 */
$router->prefix('tags')->withPolicy('TagPolicy')->group(function ($router) {

    $router->get('/', 'TagsController@index');
    $router->post('/', 'TagsController@create');

    $router->get('{id}', 'TagsController@find')->int('id');
    $router->put('{id}', 'TagsController@store')->int('id');
    $router->delete('{id}', 'TagsController@remove')->int('id');

    $router->post('/bulk', 'TagsController@storeBulk');

});

/*
 * /lists endpoints
 */
$router->prefix('lists')->withPolicy('ListPolicy')->group(function ($router) {

    $router->get('/', 'ListsController@index');
    $router->post('/', 'ListsController@create');

    $router->get('{id}', 'ListsController@find')->int('id');
    $router->put('{id}', 'ListsController@update')->int('id');
    $router->delete('/{id}', 'ListsController@remove')->int('id');

    $router->post('/bulk', 'ListsController@storeBulk');

});

/*
 * /subscribers endpoints
 */
$router->prefix('subscribers')->withPolicy('SubscriberPolicy')->group(function ($router) {

    $router->get('/', 'SubscriberController@index');
    $router->post('/', 'SubscriberController@store');
    $router->put('subscribers-property', 'SubscriberController@updateProperty');
    $router->delete('/', 'SubscriberController@deleteSubscribers');
    $router->post('sync-segments', 'SubscriberController@tagger');
    $router->post('do-bulk-action', 'SubscriberController@handleBulkActions');

    $router->get('{id}', 'SubscriberController@show')->int('id');
    $router->put('{id}', 'SubscriberController@updateSubscriber')->int('id');
    $router->get('{id}/emails', 'SubscriberController@emails')->int('id');
    $router->get('{id}/emails/template-mock', 'SubscriberController@getTemplateMock')->int('id');
    $router->post('{id}/emails/send', 'SubscriberController@sendCustomEmail')->int('id');
    $router->delete('{id}/emails', 'SubscriberController@deleteEmails')->int('id');
    $router->get('{id}/purchase-history', 'PurchaseHistoryController@getOrders')->int('id');
    $router->get('{id}/form-submissions', 'SubscriberController@getFormSubmissions')->int('id');
    $router->get('{id}/support-tickets', 'SubscriberController@getSupportTickets')->int('id');
    $router->post('{id}/send-double-optin', 'SubscriberController@sendDoubleOptinEmail')->int('id');

    $router->get('{id}/notes', 'SubscriberController@getNotes')->int('id');
    $router->post('{id}/notes', 'SubscriberController@addNote')->int('id');

    $router->get('{id}/external_view', 'SubscriberController@getExternalView')->int('id');

    $router->put('{id}/notes/{note_id}', 'SubscriberController@updateNote')->where([
        'id'      => 'int',
        'note_id' => 'int'
    ]);

    $router->delete('{id}/notes/{note_id}', 'SubscriberController@deleteNote')->where([
        'id'      => 'int',
        'note_id' => 'int'
    ]);

});

$router->prefix('campaigns')->withPolicy('CampaignPolicy')->group(function ($router) {

    $router->get('/', 'CampaignController@campaigns');
    $router->post('/', 'CampaignController@create');
    $router->post('/send-test-email', 'CampaignController@sendTestEmail');
    $router->post('/email-preview-html', 'CampaignController@getEmailPreviewBody');
    $router->get('emails/{email_id}/preview', 'CampaignController@previewEmail')->int('email_id');

    $router->post('estimated-contacts', 'CampaignController@getContactEstimation');

    $router->get('{id}', 'CampaignController@campaign')->int('id');
    $router->put('{id}', 'CampaignController@update')->int('id');
    $router->post('{id}/step', 'CampaignController@updateStep')->int('id');

    $router->post('{id}/pause', 'CampaignController@pauseCampaign')->int('id');
    $router->post('{id}/duplicate', 'CampaignController@duplicateCampaign')->int('id');
    $router->post('{id}/resume', 'CampaignController@resumeCampaign')->int('id');
    $router->put('{id}/title', 'CampaignController@updateCampaignTitle')->int('id');
    $router->delete('{id}', 'CampaignController@delete')->int('id');

    $router->post('{id}/subscribe', 'CampaignController@subscribe')->int('id');
    $router->get('{id}/emails', 'CampaignController@campaignEmails')->int('id');
    $router->delete('{id}/emails', 'CampaignController@deleteCampaignEmails')->int('id');
    $router->post('{id}/schedule', 'CampaignController@schedule')->int('id');
    $router->post('{id}/un-schedule', 'CampaignController@unSchedule')->int('id');


    $router->get('{id}/status', 'CampaignController@getCampaignStatus')->int('id');
    $router->get('{id}/link-report', 'CampaignAnalyticsController@getLinksReport')->int('id');
    $router->get('{id}/revenues', 'CampaignAnalyticsController@getRevenueReport')->int('id');
    $router->get('{id}/unsubscribers', 'CampaignAnalyticsController@getUnsubscribers')->int('id');

});

$router->prefix('templates')->withPolicy('TemplatePolicy')->group(function ($router) {

    $router->get('/', 'TemplateController@templates');
    $router->get('/all', 'TemplateController@allTemplates');
    $router->get('/smartcodes', 'TemplateController@getSmartCodes');
    $router->post('/', 'TemplateController@create');

    $router->get('{id}', 'TemplateController@template')->int('id');
    $router->put('{id}', 'TemplateController@update')->int('id');
    $router->post('/duplicate/{id}', 'TemplateController@duplicate')->int('id');
    $router->delete('{id}', 'TemplateController@delete')->int('id');

});


/*
 * Funnels Route
 */
$router->prefix('funnels')->withPolicy('FunnelPolicy')->group(function ($router) {

    $router->get('/', 'FunnelController@funnels');
    $router->post('/', 'FunnelController@create');
    $router->post('import', 'FunnelController@importFunnel');

    $router->get('triggers', 'FunnelController@getTriggersRest');
    $router->put('{id}/change-trigger', 'FunnelController@changeTrigger')->int('id');

    $router->get('subscriber/{subscriber_id}/automations', 'FunnelController@subscriberAutomations');

    $router->get('{id}', 'FunnelController@getFunnel')->int('id');
    $router->post('{id}/clone', 'FunnelController@cloneFunnel')->int('id');
    $router->post('{id}/sequences', 'FunnelController@saveSequences')->int('id');
    $router->get('{id}/subscribers', 'FunnelController@getSubscribers')->int('id');
    $router->delete('{id}/subscribers', 'FunnelController@deleteSubscribers')->int('id');
    $router->delete('{id}', 'FunnelController@delete')->int('id');
    $router->get('{id}/report', 'FunnelController@report')->int('id');


    $router->get('{id}/email_reports', 'FunnelController@getEmailReports')->int('id');

    $router->put('{id}/subscribers/{subscriber_id}/status', 'FunnelController@updateSubscriptionStatus')->int('id')->int('subscriber_id');

});


/*
 * Reporting Route
 */
$router->prefix('reports')->withPolicy('ReportPolicy')->group(function ($router) {

    $router->get('dashboard-stats', 'DashboardController@getStats');
    $router->get('subscribers', 'ReportingController@getContactGrowth');
    $router->get('email-sents', 'ReportingController@getEmailSentStats');
    $router->get('email-opens', 'ReportingController@getEmailOpenStats');
    $router->get('email-clicks', 'ReportingController@getEmailClickStats');

    $router->get('options', 'OptionsController@index');
    $router->get('ajax-options', 'OptionsController@getAjaxOptions');
    $router->get('taxonomy-terms', 'OptionsController@getTaxonomyTerms');

    $router->get('emails', 'ReportingController@getEmails');
    $router->delete('emails', 'ReportingController@deleteEmails');

    $router->get('advanced-providers', 'ReportingController@getAdvancedReportProviders');

});


$router->prefix('setting')->withPolicy('SettingsPolicy')->group(function ($router) {

    $router->get('/', 'SettingsController@get');
    $router->put('/', 'SettingsController@save');
    $router->post('complete-installation', 'SetupController@CompleteWizard');
    $router->get('double-optin', 'SettingsController@getDoubleOptinSettings');
    $router->put('double-optin', 'SettingsController@saveDoubleOptinSettings');

    $router->post('install-fluentform', 'SetupController@handleFluentFormInstall');
    $router->post('install-fluentsmtp', 'SetupController@handleFluentSmtpInstall');
    $router->post('install-fluentconnect', 'SetupController@handleFluentConnectInstall');

    $router->get('bounce_configs', 'SettingsController@getBounceConfigs');

    $router->get('auto_subscribe_settings', 'SettingsController@getAutoSubscribeSettings');
    $router->post('auto_subscribe_settings', 'SettingsController@saveAutoSubscribeSettings');

    $router->get('test', 'SettingsController@TestRequestResolver');
    $router->put('test', 'SettingsController@TestRequestResolver');
    $router->post('test', 'SettingsController@TestRequestResolver');
    $router->delete('test', 'SettingsController@TestRequestResolver');

    $router->post('reset_db', 'SettingsController@resetDB');
    $router->get('old_logs', 'SettingsController@getOldLogDetails');
    $router->delete('old_logs', 'SettingsController@removeOldLogs');

    $router->get('cron_status', 'SettingsController@getCronStatus');
    $router->post('run_cron', 'SettingsController@runCron');

    $router->get('rest-keys', 'SettingsController@getRestKeys');
    $router->post('rest-keys', 'SettingsController@createRestKey');

    $router->get('integrations', 'SettingsController@getIntegrations');
    $router->post('integrations', 'SettingsController@saveIntegration');

});


$router->prefix('custom-fields')->withPolicy('CustomFieldsPolicy')->group(function ($router) {

    $router->get('contacts', 'CustomContactFieldsController@getGlobalFields');
    $router->put('contacts', 'CustomContactFieldsController@saveGlobalFields');

});

$router->prefix('webhooks')->withPolicy('WebhookPolicy')->group(function ($router) {
    $router->get('/', 'WebhookController@index');
    $router->post('/', 'WebhookController@create');
    $router->put('/{id}', 'WebhookController@update')->int('id');
    $router->delete('/{id}', 'WebhookController@delete')->int('id');
});

/*
 * Imports
 */
$router->prefix('import')->withPolicy('UsersPolicy')->group(function ($router) {

    $router->get('users', 'UsersController@index');
    $router->post('users', 'UsersController@import');
    $router->get('users/roles', 'UsersController@roles');

    $router->post('csv-upload', 'CsvController@upload');
    $router->post('csv-import', 'CsvController@import');

    $router->get('drivers', 'ImporterController@getDrivers');
    $router->get('drivers/{driver}', 'ImporterController@getDriver')->alphaNumDash('driver');
    $router->post('drivers/{driver}', 'ImporterController@importData')->alphaNumDash('driver');

});


/*
 * Fluent Forms Wrapper
 */
$router->prefix('forms')->withPolicy('FormsPolicy')->group(function ($router) {

    $router->get('/', 'FormsController@index');
    $router->post('/', 'FormsController@create');
    $router->get('templates', 'FormsController@getTemplates');

});


/*
 * Fluent Forms Wrapper
 */
$router->prefix('docs')->withPolicy('SettingsPolicy')->group(function ($router) {
    $router->get('/', 'DocsController@index');
    $router->get('/addons', 'DocsController@getAddons');
});

/*
 * Public EndPoints
 */
$router->prefix('public')->withPolicy('PublicPolicy')->group(function ($router) {

    $router->any('bounce_handler/{service_name}/handle/{security_code}', 'WebhookBounceController@handleBounce')
        ->alphaNumDash('service_name')
        ->alphaNumDash('security_code');

    $router->any('bounce_handler/{service_name}/{security_code}', 'WebhookBounceController@handleBounce')
        ->alphaNumDash('service_name')
        ->alphaNumDash('security_code');

});


$router->prefix('migrators')->withPolicy('SettingsPolicy')->group(function ($router) {
    $router->get('/', 'MigratorController@getDrivers');
    $router->post('/verify-cred', 'MigratorController@verifyCredential');
    $router->get('/list-tag-mappings', 'MigratorController@getListTagMappings');

    $router->post('/summary', 'MigratorController@getImportSummary');
    $router->post('/import', 'MigratorController@handleImport');
});
