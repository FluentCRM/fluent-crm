<?php

/**
 * @var $app \FluentCrm\Includes\Core\Application
 */

/*
 * Note: Namespace will be added automatically. For example, if you use MyClass
 * as the controller name then it will become FluentCrm\App\Hooks\Handlers\MyClass.
 */

// fluentCrmMaybeRegisterQueryLoggerIfAvailable($app);

$app->addAction('fluentcrm_scheduled_minute_tasks', 'Scheduler@process');
$app->addAction('fluentcrm_scheduled_hourly_tasks', 'Scheduler@processHourly');
$app->addAction('fluentcrm_process_contact_jobs', 'Scheduler@processForSubscriber', 999, 1);

// Add admin init

$app->addAction('wp_loaded', 'AdminMenu@init');

$app->addCustomAction('campaign_status_active', 'CampaignGuard@checkIsActive');

$app->addCustomAction('campaign_status_working', 'CampaignGuard@checkIsWorking');

$app->addAction('init', 'ExternalPages@route', 99);

$app->addAction('wp_ajax_fluentcrm_unsubscribe_ajax', 'ExternalPages@handleUnsubscribe');
$app->addAction('wp_ajax_nopriv_fluentcrm_unsubscribe_ajax', 'ExternalPages@handleUnsubscribe');

$app->addAction('wp_ajax_fluentcrm_manage_preferences_ajax', 'ExternalPages@handleManageSubPref');
$app->addAction('wp_ajax_nopriv_fluentcrm_manage_preferences_ajax', 'ExternalPages@handleManageSubPref');

/*
 * Integrations
 */
$app->addAction('init', 'Integrations@register');

/*
 * Funnel
 */
$app->addAction('init', 'FunnelHandler@handle', 2);
$app->addAction('fluentcrm_subscriber_status_to_subscribed', 'FunnelHandler@resumeSubscriberFunnels', 1, 2);

/*
 * Cleanup Hooks
 */
$app->addAction('fluentcrm_after_subscribers_deleted', 'Cleanup@deleteSubscribersAssets', 10, 1);
$app->addAction('fluentcrm_campaign_deleted', 'Cleanup@deleteCampaignAssets', 10, 1);
$app->addAction('fluentcrm_list_deleted', 'Cleanup@deleteListAssets', 10, 1);
$app->addAction('fluentcrm_tag_deleted', 'Cleanup@deleteTagAssets', 10, 1);


/*
 * Admin Bar
 */

$app->addAction('admin_bar_menu', 'AdminBar@init');

// This is required to instantly send emails
add_action('wp_ajax_nopriv_fluentcrm-post-campaigns-send-now', function () use ($app) {
    (new \FluentCrm\Includes\Mailer\Handler)->handle(
        $app->request->get('campaign_id')
    );
});

/*
 * For Short URL Redirect
 */
add_action('wp', function () use ($app) {
    if (isset($_GET['ns_url'])) {
        (new \FluentCrm\App\Hooks\Handlers\RedirectionHandler())->redirect($_GET);
    }

    if (isset($_GET['do_fluentcrm_scheduled_tasks'])) {
        do_action('fluentcrm_scheduled_minute_tasks');
    }

    if (isset($_GET['fluentcrm_scheduled_hourly_tasks'])) {
        do_action('fluentcrm_scheduled_hourly_tasks');
    }

});

/*
 * Contact Activity Logger Class Init
 */
add_action('init', function () {
    (new \FluentCrm\App\Hooks\Handlers\ContactActivityLogger())->register();
});

/*
 * Setup-wizard
 */

if (!empty($_GET['page']) && 'fluentcrm-setup' == $_GET['page']) {
    add_action('admin_menu', function () {
        add_dashboard_page('FluentCRM Setup', 'FluentCRM Setup', 'manage_options', 'fluentcrm-setup', function () {
            return '';
        });
    });

    add_action('current_screen', function () {
         new \FluentCrm\App\Hooks\Handlers\SetupWizard();
    },999);
}


$app->addAction('user_register', 'AutoSubscribeHandler@userRegistrationHandler', 99, 1);
$app->addAction('comment_post', 'AutoSubscribeHandler@handleCommentPost', 99, 3);
