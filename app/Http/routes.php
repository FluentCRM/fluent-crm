<?php

/**
 * @var $app \FluentCrm\Includes\Core\Application
 */

/*
 * Note: Namespace will be added automatically. For example, if you use MyClass
 * as the controller name then it will become FluentCrm\App\Http\Controllers\MyClass.
 * $app->get('path/{param_1}', 'Controller@method')->where([
        'param_1' => 'int'
        // 'name' => 'alpha',
        // 'user_name' => 'alpha_num',
        // 'slug' => 'alpha_num_dash'
    ])->int('param_1');
    // Also supports: ->int('id', 'user_id') or ->int(['id', 'user_id'])
    // Other methods: alpha('name'), alphaNum('user_name'), alphaNumDash('slug')
 */

/*
 * /tags endpoints
 */
$app->group(function ($app) {

    $app->get('/', 'TagsController@index');
    $app->post('/', 'TagsController@create');

    $app->get('{id}', 'TagsController@find')->int('id');
    $app->put('{id}', 'TagsController@store')->int('id');
    $app->delete('{id}', 'TagsController@remove')->int('id');

    $app->post('/bulk', 'TagsController@storeBulk');

})->prefix('tags')->withPolicy('TagPolicy');

/*
 * /lists endpoints
 */
$app->group(['prefix' => 'lists', 'policy' => 'ListPolicy'], function ($app) {

    $app->get('/', 'ListsController@index');
    $app->post('/', 'ListsController@create');

    $app->get('{id}', 'ListsController@find')->int('id');
    $app->put('{id}', 'ListsController@update')->int('id');
    $app->delete('/{id}', 'ListsController@remove')->int('id');

    $app->post('/bulk', 'ListsController@storeBulk');

})->prefix('lists')->withPolicy('ListPolicy');

/*
 * /subscribers endpoints
 */
$app->group(function ($app) {

    $app->get('/', 'SubscriberController@index');
    $app->post('/', 'SubscriberController@store');
    $app->put('subscribers-property', 'SubscriberController@updateProperty');
    $app->delete('/', 'SubscriberController@deleteSubscribers');
    $app->post('sync-segments', 'SubscriberController@tagger');

    $app->get('{id}', 'SubscriberController@show')->int('id');
    $app->put('{id}', 'SubscriberController@updateSubscriber')->int('id');
    $app->get('{id}/emails', 'SubscriberController@emails')->int('id');
    $app->get('{id}/emails/template-mock', 'SubscriberController@getTemplateMock')->int('id');
    $app->post('{id}/emails/send', 'SubscriberController@sendCustomEmail')->int('id');
    $app->delete('{id}/emails', 'SubscriberController@deleteEmails')->int('id');
    $app->get('{id}/purchase-history', 'PurchaseHistoryController@getOrders')->int('id');
    $app->get('{id}/form-submissions', 'SubscriberController@getFormSubmissions')->int('id');
    $app->get('{id}/support-tickets', 'SubscriberController@getSupportTickets')->int('id');
    $app->post('{id}/send-double-optin', 'SubscriberController@sendDoubleOptinEmail')->int('id');

    $app->get('{id}/notes', 'SubscriberController@getNotes')->int('id');
    $app->post('{id}/notes', 'SubscriberController@addNote')->int('id');

    $app->put('{id}/notes/{note_id}', 'SubscriberController@updateNote')->where([
        'id' => 'int',
        'note_id' => 'int'
    ]);

    $app->delete('{id}/notes/{note_id}', 'SubscriberController@deleteNote')->where([
        'id' => 'int',
        'note_id' => 'int'
    ]);

})->prefix('subscribers')->withPolicy('SubscriberPolicy');

$app->group(function ($app) {

    $app->get('/', 'CampaignController@campaigns');
    $app->post('/', 'CampaignController@create');
    $app->post('/send-test-email', 'CampaignController@sendTestEmail');
    $app->get('emails/{email_id}/preview', 'CampaignController@previewEmail')->int('email_id');

    $app->post('estimated-contacts', 'CampaignController@getContactEstimation');

    $app->get('{id}', 'CampaignController@campaign')->int('id');
    $app->put('{id}', 'CampaignController@update')->int('id');
    $app->put('{id}/step', 'CampaignController@updateStep')->int('id');
    $app->post('{id}/pause', 'CampaignController@pauseCampaign')->int('id');
    $app->post('{id}/duplicate', 'CampaignController@duplicateCampaign')->int('id');
    $app->post('{id}/resume', 'CampaignController@resumeCampaign')->int('id');
    $app->put('{id}/title', 'CampaignController@updateCampaignTitle')->int('id');
    $app->delete('{id}', 'CampaignController@delete')->int('id');

    $app->post('{id}/subscribe', 'CampaignController@subscribe')->int('id');
    $app->get('{id}/emails', 'CampaignController@campaignEmails')->int('id');
    $app->delete('{id}/emails', 'CampaignController@deleteCampaignEmails')->int('id');
    $app->post('{id}/schedule', 'CampaignController@schedule')->int('id');
    $app->get('{id}/status', 'CampaignController@getCampaignStatus')->int('id');
    $app->get('{id}/link-report', 'CampaignAnalyticsController@getLinksReport')->int('id');

})->prefix('campaigns')->withPolicy('CampaignPolicy');

$app->group(function ($app) {

    $app->get('/', 'TemplateController@templates');
    $app->get('/all', 'TemplateController@allTemplates');
    $app->get('/smartcodes', 'TemplateController@getSmartCodes');
    $app->post('/', 'TemplateController@create');

    $app->get('{id}', 'TemplateController@template')->int('id');
    $app->put('{id}', 'TemplateController@update')->int('id');
    $app->delete('{id}', 'TemplateController@delete')->int('id');

})->prefix('templates')->withPolicy('TemplatePolicy');


/*
 * Funnels Route
 */
$app->group(function ($app) {

    $app->get('/', 'FunnelController@funnels');
    $app->post('/', 'FunnelController@create');

    $app->get('subscriber/{subscriber_id}/automations', 'FunnelController@subscriberAutomations');

    $app->get('{id}', 'FunnelController@getFunnel')->int('id');
    $app->post('{id}/clone', 'FunnelController@cloneFunnel')->int('id');
    $app->post('{id}/sequences', 'FunnelController@saveSequences')->int('id');
    $app->get('{id}/subscribers', 'FunnelController@getSubscribers')->int('id');
    $app->delete('{id}/subscribers', 'FunnelController@deleteSubscribers')->int('id');
    $app->delete('{id}', 'FunnelController@delete')->int('id');
    $app->get('{id}/report', 'FunnelController@report')->int('id');

    $app->put('{id}/subscribers/{subscriber_id}/status', 'FunnelController@updateSubscriptionStatus')->int('id')->int('subscriber_id');

})->prefix('funnels')->withPolicy('FunnelPolicy');


/*
 * Reporting Route
 */
$app->group(function ($app) {

    $app->get('dashboard-stats', 'DashboardController@getStats');
    $app->get('subscribers', 'ReportingController@getContactGrowth');
    $app->get('email-sents', 'ReportingController@getEmailSentStats');
    $app->get('email-opens', 'ReportingController@getEmailOpenStats');
    $app->get('email-clicks', 'ReportingController@getEmailClickStats');

    $app->get('options', 'OptionsController@index');

})->prefix('reports')->withPolicy('ReportPolicy');


$app->group(function ($app) {

    $app->get('/', 'SettingsController@get');
    $app->put('/', 'SettingsController@save');
    $app->post('complete-installation', 'SetupController@CompleteWizard');
    $app->get('double-optin', 'SettingsController@getDoubleOptinSettings');
    $app->put('double-optin', 'SettingsController@saveDoubleOptinSettings');

    $app->post('install-fluentform', 'SetupController@handleFluentFormInstall');

    $app->get('bounce_configs', 'SettingsController@getBounceConfigs');

    $app->get('auto_subscribe_settings', 'SettingsController@getAutoSubscribeSettings');
    $app->post('auto_subscribe_settings', 'SettingsController@saveAutoSubscribeSettings');

    $app->get('test', 'SettingsController@TestRequestResolver');
    $app->put('test', 'SettingsController@TestRequestResolver');
    $app->post('test', 'SettingsController@TestRequestResolver');
    $app->delete('test', 'SettingsController@TestRequestResolver');

    $app->post('reset_db', 'SettingsController@resetDB');

    $app->get('cron_status', 'SettingsController@getCronStatus');
    $app->post('run_cron', 'SettingsController@runCron');

})->prefix('setting')->withPolicy('SettingsPolicy');


$app->group(function ($app) {

    $app->get('contacts', 'CustomContactFieldsController@getGlobalFields');
    $app->put('contacts', 'CustomContactFieldsController@saveGlobalFields');

})->prefix('custom-fields')->withPolicy('CustomFieldsPolicy');

$app->group(function ($app) {
    $app->get('/', 'WebhookController@index');
    $app->post('/', 'WebhookController@create');
    $app->put('/{id}', 'WebhookController@update');
    $app->delete('/{id}', 'WebhookController@delete');
})->prefix('webhooks')->withPolicy('WebhookPolicy');

/*
 * Imports
 */
$app->group(function ($app) {

    $app->get('users', 'UsersController@index');
    $app->post('users', 'UsersController@import');
    $app->get('users/roles', 'UsersController@roles');

    $app->post('csv-upload', 'CsvController@upload');
    $app->post('csv-import', 'CsvController@import');

})->prefix('import')->withPolicy('UsersPolicy');


/*
 * Fluent Forms Wrapper
 */
$app->group(function ($app) {
    $app->get('/', 'FormsController@index');
    $app->post('/', 'FormsController@create');
    $app->get('templates', 'FormsController@getTemplates');

})->prefix('forms')->withPolicy('FormsPolicy');
