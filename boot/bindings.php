<?php

/**
 * Add only the plugin specific bindings here.
 *
 * @var $app FluentCrm\Framework\Foundation\App
 * @var $app->app FluentCrm\Framework\Foundation\ComponentBinder
 */

$app->app->singleton('FluentCrm\App\Api\Api', function ($app) {
    return new FluentCrm\App\Api\Api($app);
});

$app->app->alias('FluentCrm\App\Api\Api', 'api');
