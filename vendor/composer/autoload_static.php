<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3ec9aaa182e9c7febe2801c6e81775d2
{
    public static $files = array (
        '9680a2abca0f3f510cf2fd1b6d61afe6' => __DIR__ . '/../..' . '/boot/globals.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPFluent\\' => 9,
        ),
        'F' => 
        array (
            'FluentCrm\\Includes\\' => 19,
            'FluentCrm\\Framework\\' => 20,
            'FluentCrm\\App\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPFluent\\' => 
        array (
            0 => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent',
        ),
        'FluentCrm\\Includes\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
        'FluentCrm\\Framework\\' => 
        array (
            0 => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent',
        ),
        'FluentCrm\\App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'FluentCRMDBMigrator' => __DIR__ . '/../..' . '/database/FluentCRMDBMigrator.php',
        'FluentCrmMigrations\\CampaignEmails' => __DIR__ . '/../..' . '/database/migrations/CampaignEmails.php',
        'FluentCrmMigrations\\CampaignUrlMetrics' => __DIR__ . '/../..' . '/database/migrations/CampaignUrlMetrics.php',
        'FluentCrmMigrations\\Campaigns' => __DIR__ . '/../..' . '/database/migrations/Campaigns.php',
        'FluentCrmMigrations\\CompaniesMigrator' => __DIR__ . '/../..' . '/database/migrations/CompaniesMigrator.php',
        'FluentCrmMigrations\\FunnelMetrics' => __DIR__ . '/../..' . '/database/migrations/FunnelMetrics.php',
        'FluentCrmMigrations\\FunnelSequences' => __DIR__ . '/../..' . '/database/migrations/FunnelSequences.php',
        'FluentCrmMigrations\\FunnelSubscribers' => __DIR__ . '/../..' . '/database/migrations/FunnelSubscribers.php',
        'FluentCrmMigrations\\Funnels' => __DIR__ . '/../..' . '/database/migrations/Funnels.php',
        'FluentCrmMigrations\\Lists' => __DIR__ . '/../..' . '/database/migrations/Lists.php',
        'FluentCrmMigrations\\Meta' => __DIR__ . '/../..' . '/database/migrations/Meta.php',
        'FluentCrmMigrations\\SubscriberMeta' => __DIR__ . '/../..' . '/database/migrations/SubscriberMeta.php',
        'FluentCrmMigrations\\SubscriberNotes' => __DIR__ . '/../..' . '/database/migrations/SubscriberNotes.php',
        'FluentCrmMigrations\\SubscriberPivot' => __DIR__ . '/../..' . '/database/migrations/SubscriberPivot.php',
        'FluentCrmMigrations\\Subscribers' => __DIR__ . '/../..' . '/database/migrations/Subscribers.php',
        'FluentCrmMigrations\\Tags' => __DIR__ . '/../..' . '/database/migrations/Tags.php',
        'FluentCrmMigrations\\UrlStores' => __DIR__ . '/../..' . '/database/migrations/UrlStores.php',
        'FluentCrm\\App\\Api\\Api' => __DIR__ . '/../..' . '/app/Api/Api.php',
        'FluentCrm\\App\\Api\\Classes\\Companies' => __DIR__ . '/../..' . '/app/Api/Classes/Companies.php',
        'FluentCrm\\App\\Api\\Classes\\Contacts' => __DIR__ . '/../..' . '/app/Api/Classes/Contacts.php',
        'FluentCrm\\App\\Api\\Classes\\Extender' => __DIR__ . '/../..' . '/app/Api/Classes/Extender.php',
        'FluentCrm\\App\\Api\\Classes\\Lists' => __DIR__ . '/../..' . '/app/Api/Classes/Lists.php',
        'FluentCrm\\App\\Api\\Classes\\Tags' => __DIR__ . '/../..' . '/app/Api/Classes/Tags.php',
        'FluentCrm\\App\\Api\\FCApi' => __DIR__ . '/../..' . '/app/Api/FCApi.php',
        'FluentCrm\\App\\App' => __DIR__ . '/../..' . '/app/App.php',
        'FluentCrm\\App\\Hooks\\CLI\\Commands' => __DIR__ . '/../..' . '/app/Hooks/CLI/Commands.php',
        'FluentCrm\\App\\Hooks\\Handlers\\ActivationHandler' => __DIR__ . '/../..' . '/app/Hooks/Handlers/ActivationHandler.php',
        'FluentCrm\\App\\Hooks\\Handlers\\AdminBar' => __DIR__ . '/../..' . '/app/Hooks/Handlers/AdminBar.php',
        'FluentCrm\\App\\Hooks\\Handlers\\AdminMenu' => __DIR__ . '/../..' . '/app/Hooks/Handlers/AdminMenu.php',
        'FluentCrm\\App\\Hooks\\Handlers\\AutoSubscribeHandler' => __DIR__ . '/../..' . '/app/Hooks/Handlers/AutoSubscribeHandler.php',
        'FluentCrm\\App\\Hooks\\Handlers\\CampaignGuard' => __DIR__ . '/../..' . '/app/Hooks/Handlers/CampaignGuard.php',
        'FluentCrm\\App\\Hooks\\Handlers\\Cleanup' => __DIR__ . '/../..' . '/app/Hooks/Handlers/Cleanup.php',
        'FluentCrm\\App\\Hooks\\Handlers\\ContactActivityLogger' => __DIR__ . '/../..' . '/app/Hooks/Handlers/ContactActivityLogger.php',
        'FluentCrm\\App\\Hooks\\Handlers\\CountryNames' => __DIR__ . '/../..' . '/app/Hooks/Handlers/CountryNames.php',
        'FluentCrm\\App\\Hooks\\Handlers\\DeactivationHandler' => __DIR__ . '/../..' . '/app/Hooks/Handlers/DeactivationHandler.php',
        'FluentCrm\\App\\Hooks\\Handlers\\EmailDesignTemplates' => __DIR__ . '/../..' . '/app/Hooks/Handlers/EmailDesignTemplates.php',
        'FluentCrm\\App\\Hooks\\Handlers\\ExternalPages' => __DIR__ . '/../..' . '/app/Hooks/Handlers/ExternalPages.php',
        'FluentCrm\\App\\Hooks\\Handlers\\FormSubmissions' => __DIR__ . '/../..' . '/app/Hooks/Handlers/FormSubmissions.php',
        'FluentCrm\\App\\Hooks\\Handlers\\FunnelHandler' => __DIR__ . '/../..' . '/app/Hooks/Handlers/FunnelHandler.php',
        'FluentCrm\\App\\Hooks\\Handlers\\Integrations' => __DIR__ . '/../..' . '/app/Hooks/Handlers/Integrations.php',
        'FluentCrm\\App\\Hooks\\Handlers\\PrefFormHandler' => __DIR__ . '/../..' . '/app/Hooks/Handlers/PrefFormHandler.php',
        'FluentCrm\\App\\Hooks\\Handlers\\PurchaseHistory' => __DIR__ . '/../..' . '/app/Hooks/Handlers/PurchaseHistory.php',
        'FluentCrm\\App\\Hooks\\Handlers\\RedirectionHandler' => __DIR__ . '/../..' . '/app/Hooks/Handlers/RedirectionHandler.php',
        'FluentCrm\\App\\Hooks\\Handlers\\Scheduler' => __DIR__ . '/../..' . '/app/Hooks/Handlers/Scheduler.php',
        'FluentCrm\\App\\Hooks\\Handlers\\SetupWizard' => __DIR__ . '/../..' . '/app/Hooks/Handlers/SetupWizard.php',
        'FluentCrm\\App\\Hooks\\Handlers\\UrlMetrics' => __DIR__ . '/../..' . '/app/Hooks/Handlers/UrlMetrics.php',
        'FluentCrm\\App\\Hooks\\Handlers\\WpQueryLogger' => __DIR__ . '/../..' . '/app/Hooks/Handlers/WpQueryLogger.php',
        'FluentCrm\\App\\Http\\Controllers\\CampaignAnalyticsController' => __DIR__ . '/../..' . '/app/Http/Controllers/CampaignAnalyticsController.php',
        'FluentCrm\\App\\Http\\Controllers\\CampaignController' => __DIR__ . '/../..' . '/app/Http/Controllers/CampaignController.php',
        'FluentCrm\\App\\Http\\Controllers\\CompanyController' => __DIR__ . '/../..' . '/app/Http/Controllers/CompanyController.php',
        'FluentCrm\\App\\Http\\Controllers\\Controller' => __DIR__ . '/../..' . '/app/Http/Controllers/Controller.php',
        'FluentCrm\\App\\Http\\Controllers\\CsvController' => __DIR__ . '/../..' . '/app/Http/Controllers/CsvController.php',
        'FluentCrm\\App\\Http\\Controllers\\CustomContactFieldsController' => __DIR__ . '/../..' . '/app/Http/Controllers/CustomContactFieldsController.php',
        'FluentCrm\\App\\Http\\Controllers\\DashboardController' => __DIR__ . '/../..' . '/app/Http/Controllers/DashboardController.php',
        'FluentCrm\\App\\Http\\Controllers\\DocsController' => __DIR__ . '/../..' . '/app/Http/Controllers/DocsController.php',
        'FluentCrm\\App\\Http\\Controllers\\FormsController' => __DIR__ . '/../..' . '/app/Http/Controllers/FormsController.php',
        'FluentCrm\\App\\Http\\Controllers\\FunnelController' => __DIR__ . '/../..' . '/app/Http/Controllers/FunnelController.php',
        'FluentCrm\\App\\Http\\Controllers\\ImporterController' => __DIR__ . '/../..' . '/app/Http/Controllers/ImporterController.php',
        'FluentCrm\\App\\Http\\Controllers\\ListsController' => __DIR__ . '/../..' . '/app/Http/Controllers/ListsController.php',
        'FluentCrm\\App\\Http\\Controllers\\MigratorController' => __DIR__ . '/../..' . '/app/Http/Controllers/MigratorController.php',
        'FluentCrm\\App\\Http\\Controllers\\OptionsController' => __DIR__ . '/../..' . '/app/Http/Controllers/OptionsController.php',
        'FluentCrm\\App\\Http\\Controllers\\PurchaseHistoryController' => __DIR__ . '/../..' . '/app/Http/Controllers/PurchaseHistoryController.php',
        'FluentCrm\\App\\Http\\Controllers\\ReportingController' => __DIR__ . '/../..' . '/app/Http/Controllers/ReportingController.php',
        'FluentCrm\\App\\Http\\Controllers\\SettingsController' => __DIR__ . '/../..' . '/app/Http/Controllers/SettingsController.php',
        'FluentCrm\\App\\Http\\Controllers\\SetupController' => __DIR__ . '/../..' . '/app/Http/Controllers/SetupController.php',
        'FluentCrm\\App\\Http\\Controllers\\SubscriberController' => __DIR__ . '/../..' . '/app/Http/Controllers/SubscriberController.php',
        'FluentCrm\\App\\Http\\Controllers\\TagsController' => __DIR__ . '/../..' . '/app/Http/Controllers/TagsController.php',
        'FluentCrm\\App\\Http\\Controllers\\TemplateController' => __DIR__ . '/../..' . '/app/Http/Controllers/TemplateController.php',
        'FluentCrm\\App\\Http\\Controllers\\UsersController' => __DIR__ . '/../..' . '/app/Http/Controllers/UsersController.php',
        'FluentCrm\\App\\Http\\Controllers\\WebhookBounceController' => __DIR__ . '/../..' . '/app/Http/Controllers/WebhookBounceController.php',
        'FluentCrm\\App\\Http\\Controllers\\WebhookController' => __DIR__ . '/../..' . '/app/Http/Controllers/WebhookController.php',
        'FluentCrm\\App\\Http\\Policies\\BasePolicy' => __DIR__ . '/../..' . '/app/Http/Policies/BasePolicy.php',
        'FluentCrm\\App\\Http\\Policies\\CampaignPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/CampaignPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\CompanyPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/CompanyPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\CustomFieldsPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/CustomFieldsPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\FormsPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/FormsPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\FunnelPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/FunnelPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\ImportUserPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/ImportUserPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\ListPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/ListPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\PublicPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/PublicPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\ReportPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/ReportPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\SettingsPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/SettingsPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\SubscriberPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/SubscriberPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\TagPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/TagPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\TemplatePolicy' => __DIR__ . '/../..' . '/app/Http/Policies/TemplatePolicy.php',
        'FluentCrm\\App\\Http\\Policies\\UsersPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/UsersPolicy.php',
        'FluentCrm\\App\\Http\\Policies\\WebhookPolicy' => __DIR__ . '/../..' . '/app/Http/Policies/WebhookPolicy.php',
        'FluentCrm\\App\\Models\\Campaign' => __DIR__ . '/../..' . '/app/Models/Campaign.php',
        'FluentCrm\\App\\Models\\CampaignEmail' => __DIR__ . '/../..' . '/app/Models/CampaignEmail.php',
        'FluentCrm\\App\\Models\\CampaignUrlMetric' => __DIR__ . '/../..' . '/app/Models/CampaignUrlMetric.php',
        'FluentCrm\\App\\Models\\Company' => __DIR__ . '/../..' . '/app/Models/Company.php',
        'FluentCrm\\App\\Models\\CompanyNote' => __DIR__ . '/../..' . '/app/Models/CompanyNote.php',
        'FluentCrm\\App\\Models\\CustomContactField' => __DIR__ . '/../..' . '/app/Models/CustomContactField.php',
        'FluentCrm\\App\\Models\\CustomEmailCampaign' => __DIR__ . '/../..' . '/app/Models/CustomEmailCampaign.php',
        'FluentCrm\\App\\Models\\Funnel' => __DIR__ . '/../..' . '/app/Models/Funnel.php',
        'FluentCrm\\App\\Models\\FunnelCampaign' => __DIR__ . '/../..' . '/app/Models/FunnelCampaign.php',
        'FluentCrm\\App\\Models\\FunnelMetric' => __DIR__ . '/../..' . '/app/Models/FunnelMetric.php',
        'FluentCrm\\App\\Models\\FunnelSequence' => __DIR__ . '/../..' . '/app/Models/FunnelSequence.php',
        'FluentCrm\\App\\Models\\FunnelSubscriber' => __DIR__ . '/../..' . '/app/Models/FunnelSubscriber.php',
        'FluentCrm\\App\\Models\\Lists' => __DIR__ . '/../..' . '/app/Models/Lists.php',
        'FluentCrm\\App\\Models\\Meta' => __DIR__ . '/../..' . '/app/Models/Meta.php',
        'FluentCrm\\App\\Models\\Model' => __DIR__ . '/../..' . '/app/Models/Model.php',
        'FluentCrm\\App\\Models\\Subject' => __DIR__ . '/../..' . '/app/Models/Subject.php',
        'FluentCrm\\App\\Models\\Subscriber' => __DIR__ . '/../..' . '/app/Models/Subscriber.php',
        'FluentCrm\\App\\Models\\SubscriberMeta' => __DIR__ . '/../..' . '/app/Models/SubscriberMeta.php',
        'FluentCrm\\App\\Models\\SubscriberNote' => __DIR__ . '/../..' . '/app/Models/SubscriberNote.php',
        'FluentCrm\\App\\Models\\SubscriberPivot' => __DIR__ . '/../..' . '/app/Models/SubscriberPivot.php',
        'FluentCrm\\App\\Models\\Tag' => __DIR__ . '/../..' . '/app/Models/Tag.php',
        'FluentCrm\\App\\Models\\Template' => __DIR__ . '/../..' . '/app/Models/Template.php',
        'FluentCrm\\App\\Models\\UrlStores' => __DIR__ . '/../..' . '/app/Models/UrlStores.php',
        'FluentCrm\\App\\Models\\User' => __DIR__ . '/../..' . '/app/Models/User.php',
        'FluentCrm\\App\\Models\\Webhook' => __DIR__ . '/../..' . '/app/Models/Webhook.php',
        'FluentCrm\\App\\Services\\AutoSubscribe' => __DIR__ . '/../..' . '/app/Services/AutoSubscribe.php',
        'FluentCrm\\App\\Services\\BlockParser' => __DIR__ . '/../..' . '/app/Services/BlockParser.php',
        'FluentCrm\\App\\Services\\BlockParserHelper' => __DIR__ . '/../..' . '/app/Services/BlockParserHelper.php',
        'FluentCrm\\App\\Services\\BlockRender\\WooProduct' => __DIR__ . '/../..' . '/app/Services/BlockRender/WooProduct.php',
        'FluentCrm\\App\\Services\\CampaignProcessor' => __DIR__ . '/../..' . '/app/Services/CampaignProcessor.php',
        'FluentCrm\\App\\Services\\ContactsQuery' => __DIR__ . '/../..' . '/app/Services/ContactsQuery.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\ActiveCampaignMigrator' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/ActiveCampaignMigrator.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\Api\\ActiveCampaign' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/Api/ActiveCampaign.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\Api\\ConvertKit' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/Api/ConvertKit.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\Api\\Drip' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/Api/Drip.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\Api\\MailChimp' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/Api/MailChimp.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\Api\\MailerLite' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/Api/MailerLite.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\BaseMigrator' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/BaseMigrator.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\ConvertKitMigrator' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/ConvertKitMigrator.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\DripMigrator' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/DripMigrator.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\MailChimpMigrator' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/MailChimpMigrator.php',
        'FluentCrm\\App\\Services\\CrmMigrator\\MailerLiteMigrator' => __DIR__ . '/../..' . '/app/Services/CrmMigrator/MailerLiteMigrator.php',
        'FluentCrm\\App\\Services\\ExternalIntegrations\\FluentForm\\Bootstrap' => __DIR__ . '/../..' . '/app/Services/ExternalIntegrations/FluentForm/Bootstrap.php',
        'FluentCrm\\App\\Services\\ExternalIntegrations\\FluentForm\\FluentFormInit' => __DIR__ . '/../..' . '/app/Services/ExternalIntegrations/FluentForm/FluentFormInit.php',
        'FluentCrm\\App\\Services\\ExternalIntegrations\\MailComplaince\\Webhook' => __DIR__ . '/../..' . '/app/Services/ExternalIntegrations/MailComplaince/Webhook.php',
        'FluentCrm\\App\\Services\\ExternalIntegrations\\Maintenance' => __DIR__ . '/../..' . '/app/Services/ExternalIntegrations/Maintenance.php',
        'FluentCrm\\App\\Services\\ExternalIntegrations\\Oxygen\\ConditionBuilder' => __DIR__ . '/../..' . '/app/Services/ExternalIntegrations/Oxygen/ConditionBuilder.php',
        'FluentCrm\\App\\Services\\Funnel\\Actions\\ApplyCompanyAction' => __DIR__ . '/../..' . '/app/Services/Funnel/Actions/ApplyCompanyAction.php',
        'FluentCrm\\App\\Services\\Funnel\\Actions\\ApplyListAction' => __DIR__ . '/../..' . '/app/Services/Funnel/Actions/ApplyListAction.php',
        'FluentCrm\\App\\Services\\Funnel\\Actions\\ApplyTagAction' => __DIR__ . '/../..' . '/app/Services/Funnel/Actions/ApplyTagAction.php',
        'FluentCrm\\App\\Services\\Funnel\\Actions\\DetachCompanyAction' => __DIR__ . '/../..' . '/app/Services/Funnel/Actions/DetachCompanyAction.php',
        'FluentCrm\\App\\Services\\Funnel\\Actions\\DetachListAction' => __DIR__ . '/../..' . '/app/Services/Funnel/Actions/DetachListAction.php',
        'FluentCrm\\App\\Services\\Funnel\\Actions\\DetachTagAction' => __DIR__ . '/../..' . '/app/Services/Funnel/Actions/DetachTagAction.php',
        'FluentCrm\\App\\Services\\Funnel\\Actions\\SendEmailAction' => __DIR__ . '/../..' . '/app/Services/Funnel/Actions/SendEmailAction.php',
        'FluentCrm\\App\\Services\\Funnel\\Actions\\WaitTimeAction' => __DIR__ . '/../..' . '/app/Services/Funnel/Actions/WaitTimeAction.php',
        'FluentCrm\\App\\Services\\Funnel\\BaseAction' => __DIR__ . '/../..' . '/app/Services/Funnel/BaseAction.php',
        'FluentCrm\\App\\Services\\Funnel\\BaseBenchMark' => __DIR__ . '/../..' . '/app/Services/Funnel/BaseBenchMark.php',
        'FluentCrm\\App\\Services\\Funnel\\BaseTrigger' => __DIR__ . '/../..' . '/app/Services/Funnel/BaseTrigger.php',
        'FluentCrm\\App\\Services\\Funnel\\Benchmarks\\ListAppliedBenchmark' => __DIR__ . '/../..' . '/app/Services/Funnel/Benchmarks/ListAppliedBenchmark.php',
        'FluentCrm\\App\\Services\\Funnel\\Benchmarks\\RemoveFromListBenchmark' => __DIR__ . '/../..' . '/app/Services/Funnel/Benchmarks/RemoveFromListBenchmark.php',
        'FluentCrm\\App\\Services\\Funnel\\Benchmarks\\RemoveFromTagBenchmark' => __DIR__ . '/../..' . '/app/Services/Funnel/Benchmarks/RemoveFromTagBenchmark.php',
        'FluentCrm\\App\\Services\\Funnel\\Benchmarks\\TagAppliedBenchmark' => __DIR__ . '/../..' . '/app/Services/Funnel/Benchmarks/TagAppliedBenchmark.php',
        'FluentCrm\\App\\Services\\Funnel\\FunnelHelper' => __DIR__ . '/../..' . '/app/Services/Funnel/FunnelHelper.php',
        'FluentCrm\\App\\Services\\Funnel\\FunnelProcessor' => __DIR__ . '/../..' . '/app/Services/Funnel/FunnelProcessor.php',
        'FluentCrm\\App\\Services\\Funnel\\ProFunnelItems' => __DIR__ . '/../..' . '/app/Services/Funnel/ProFunnelItems.php',
        'FluentCrm\\App\\Services\\Funnel\\SequencePoints' => __DIR__ . '/../..' . '/app/Services/Funnel/SequencePoints.php',
        'FluentCrm\\App\\Services\\Funnel\\Triggers\\FluentFormSubmissionTrigger' => __DIR__ . '/../..' . '/app/Services/Funnel/Triggers/FluentFormSubmissionTrigger.php',
        'FluentCrm\\App\\Services\\Funnel\\Triggers\\UserRegistrationTrigger' => __DIR__ . '/../..' . '/app/Services/Funnel/Triggers/UserRegistrationTrigger.php',
        'FluentCrm\\App\\Services\\Helper' => __DIR__ . '/../..' . '/app/Services/Helper.php',
        'FluentCrm\\App\\Services\\Html\\FormElementBuilder' => __DIR__ . '/../..' . '/app/Services/Html/FormElementBuilder.php',
        'FluentCrm\\App\\Services\\Html\\TableBuilder' => __DIR__ . '/../..' . '/app/Services/Html/TableBuilder.php',
        'FluentCrm\\App\\Services\\Libs\\ConditionAssessor' => __DIR__ . '/../..' . '/app/Services/Libs/ConditionAssessor.php',
        'FluentCrm\\App\\Services\\Libs\\Emogrifier\\Emogrifier' => __DIR__ . '/../..' . '/app/Services/Libs/Emogrifier/Emogrifier.php',
        'FluentCrm\\App\\Services\\Libs\\FileSystem' => __DIR__ . '/../..' . '/app/Services/Libs/FileSystem.php',
        'FluentCrm\\App\\Services\\Libs\\Mailer\\CampaignEmailIterator' => __DIR__ . '/../..' . '/app/Services/Libs/Mailer/CampaignEmailIterator.php',
        'FluentCrm\\App\\Services\\Libs\\Mailer\\Handler' => __DIR__ . '/../..' . '/app/Services/Libs/Mailer/Handler.php',
        'FluentCrm\\App\\Services\\Libs\\Mailer\\Mailer' => __DIR__ . '/../..' . '/app/Services/Libs/Mailer/Mailer.php',
        'FluentCrm\\App\\Services\\Libs\\Parser\\Parser' => __DIR__ . '/../..' . '/app/Services/Libs/Parser/Parser.php',
        'FluentCrm\\App\\Services\\Libs\\Parser\\ShortcodeParser' => __DIR__ . '/../..' . '/app/Services/Libs/Parser/ShortcodeParser.php',
        'FluentCrm\\App\\Services\\PermissionManager' => __DIR__ . '/../..' . '/app/Services/PermissionManager.php',
        'FluentCrm\\App\\Services\\Reporting' => __DIR__ . '/../..' . '/app/Services/Reporting.php',
        'FluentCrm\\App\\Services\\ReportingHelperTrait' => __DIR__ . '/../..' . '/app/Services/ReportingHelperTrait.php',
        'FluentCrm\\App\\Services\\RoleBasedTagging' => __DIR__ . '/../..' . '/app/Services/RoleBasedTagging.php',
        'FluentCrm\\App\\Services\\Sanitize' => __DIR__ . '/../..' . '/app/Services/Sanitize.php',
        'FluentCrm\\App\\Services\\Stats' => __DIR__ . '/../..' . '/app/Services/Stats.php',
        'FluentCrm\\App\\Services\\TransStrings' => __DIR__ . '/../..' . '/app/Services/TransStrings.php',
        'FluentCrm\\Framework\\Database\\BaseGrammar' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/BaseGrammar.php',
        'FluentCrm\\Framework\\Database\\ConnectionInterface' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/ConnectionInterface.php',
        'FluentCrm\\Framework\\Database\\ConnectionResolver' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/ConnectionResolver.php',
        'FluentCrm\\Framework\\Database\\ConnectionResolverInterface' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/ConnectionResolverInterface.php',
        'FluentCrm\\Framework\\Database\\Orm\\Builder' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Builder.php',
        'FluentCrm\\Framework\\Database\\Orm\\Collection' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Collection.php',
        'FluentCrm\\Framework\\Database\\Orm\\DateTime' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/DateTime.php',
        'FluentCrm\\Framework\\Database\\Orm\\MassAssignmentException' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/MassAssignmentException.php',
        'FluentCrm\\Framework\\Database\\Orm\\Model' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Model.php',
        'FluentCrm\\Framework\\Database\\Orm\\ModelHelperTrait' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/ModelHelperTrait.php',
        'FluentCrm\\Framework\\Database\\Orm\\ModelNotFoundException' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/ModelNotFoundException.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\BelongsTo' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/BelongsTo.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\BelongsToMany' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/BelongsToMany.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\HasMany' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/HasMany.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\HasManyThrough' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/HasManyThrough.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\HasOne' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/HasOne.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\HasOneOrMany' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/HasOneOrMany.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\MorphMany' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/MorphMany.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\MorphOne' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/MorphOne.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\MorphOneOrMany' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/MorphOneOrMany.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\MorphPivot' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/MorphPivot.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\MorphTo' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/MorphTo.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\MorphToMany' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/MorphToMany.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\Pivot' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/Pivot.php',
        'FluentCrm\\Framework\\Database\\Orm\\Relations\\Relation' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Relations/Relation.php',
        'FluentCrm\\Framework\\Database\\Orm\\Scope' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/Scope.php',
        'FluentCrm\\Framework\\Database\\Orm\\ScopeInterface' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/ScopeInterface.php',
        'FluentCrm\\Framework\\Database\\Orm\\SoftDeletes' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/SoftDeletes.php',
        'FluentCrm\\Framework\\Database\\Orm\\SoftDeletingScope' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Orm/SoftDeletingScope.php',
        'FluentCrm\\Framework\\Database\\QueryException' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/QueryException.php',
        'FluentCrm\\Framework\\Database\\Query\\Builder' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Query/Builder.php',
        'FluentCrm\\Framework\\Database\\Query\\Expression' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Query/Expression.php',
        'FluentCrm\\Framework\\Database\\Query\\Grammar' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Query/Grammar.php',
        'FluentCrm\\Framework\\Database\\Query\\JoinClause' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Query/JoinClause.php',
        'FluentCrm\\Framework\\Database\\Query\\JsonExpression' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Query/JsonExpression.php',
        'FluentCrm\\Framework\\Database\\Query\\MySqlGrammar' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Query/MySqlGrammar.php',
        'FluentCrm\\Framework\\Database\\Query\\Processor' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Query/Processor.php',
        'FluentCrm\\Framework\\Database\\Query\\WPDBConnection' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Database/Query/WPDBConnection.php',
        'FluentCrm\\Framework\\Foundation\\App' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/App.php',
        'FluentCrm\\Framework\\Foundation\\Application' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/Application.php',
        'FluentCrm\\Framework\\Foundation\\BindingResolutionException' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/BindingResolutionException.php',
        'FluentCrm\\Framework\\Foundation\\ComponentBinder' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/ComponentBinder.php',
        'FluentCrm\\Framework\\Foundation\\Config' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/Config.php',
        'FluentCrm\\Framework\\Foundation\\Container' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/Container.php',
        'FluentCrm\\Framework\\Foundation\\ContainerContract' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/ContainerContract.php',
        'FluentCrm\\Framework\\Foundation\\ContextualBindingBuilder' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/ContextualBindingBuilder.php',
        'FluentCrm\\Framework\\Foundation\\ContextualBindingBuilderContract' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/ContextualBindingBuilderContract.php',
        'FluentCrm\\Framework\\Foundation\\Dispatcher' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/Dispatcher.php',
        'FluentCrm\\Framework\\Foundation\\ForbiddenException' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/ForbiddenException.php',
        'FluentCrm\\Framework\\Foundation\\FoundationTrait' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/FoundationTrait.php',
        'FluentCrm\\Framework\\Foundation\\Policy' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/Policy.php',
        'FluentCrm\\Framework\\Foundation\\RequestGuard' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/RequestGuard.php',
        'FluentCrm\\Framework\\Foundation\\UnAuthorizedException' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Foundation/UnAuthorizedException.php',
        'FluentCrm\\Framework\\Http\\Controller' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Http/Controller.php',
        'FluentCrm\\Framework\\Http\\Route' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Http/Route.php',
        'FluentCrm\\Framework\\Http\\Router' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Http/Router.php',
        'FluentCrm\\Framework\\Pagination\\AbstractPaginator' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Pagination/AbstractPaginator.php',
        'FluentCrm\\Framework\\Pagination\\LengthAwarePaginator' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Pagination/LengthAwarePaginator.php',
        'FluentCrm\\Framework\\Pagination\\LengthAwarePaginatorInterface' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Pagination/LengthAwarePaginatorInterface.php',
        'FluentCrm\\Framework\\Pagination\\Paginator' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Pagination/Paginator.php',
        'FluentCrm\\Framework\\Pagination\\PaginatorInterface' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Pagination/PaginatorInterface.php',
        'FluentCrm\\Framework\\Pagination\\Presenter' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Pagination/Presenter.php',
        'FluentCrm\\Framework\\Request\\Cleaner' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Request/Cleaner.php',
        'FluentCrm\\Framework\\Request\\File' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Request/File.php',
        'FluentCrm\\Framework\\Request\\FileHandler' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Request/FileHandler.php',
        'FluentCrm\\Framework\\Request\\Request' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Request/Request.php',
        'FluentCrm\\Framework\\Response\\Response' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Response/Response.php',
        'FluentCrm\\Framework\\Support\\Arr' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/Arr.php',
        'FluentCrm\\Framework\\Support\\ArrayableInterface' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/ArrayableInterface.php',
        'FluentCrm\\Framework\\Support\\Collection' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/Collection.php',
        'FluentCrm\\Framework\\Support\\Helper' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/Helper.php',
        'FluentCrm\\Framework\\Support\\Htmlable' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/Htmlable.php',
        'FluentCrm\\Framework\\Support\\JsonableInterface' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/JsonableInterface.php',
        'FluentCrm\\Framework\\Support\\MacroableTrait' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/MacroableTrait.php',
        'FluentCrm\\Framework\\Support\\Pluralizer' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/Pluralizer.php',
        'FluentCrm\\Framework\\Support\\QueueableCollectionInterface' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/QueueableCollectionInterface.php',
        'FluentCrm\\Framework\\Support\\QueueableEntity' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/QueueableEntity.php',
        'FluentCrm\\Framework\\Support\\Str' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/Str.php',
        'FluentCrm\\Framework\\Support\\UrlRoutable' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Support/UrlRoutable.php',
        'FluentCrm\\Framework\\Validator\\Contracts\\File' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Validator/Contracts/File.php',
        'FluentCrm\\Framework\\Validator\\MessageBag' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Validator/MessageBag.php',
        'FluentCrm\\Framework\\Validator\\ValidatesAttributes' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Validator/ValidatesAttributes.php',
        'FluentCrm\\Framework\\Validator\\ValidationData' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Validator/ValidationData.php',
        'FluentCrm\\Framework\\Validator\\ValidationException' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Validator/ValidationException.php',
        'FluentCrm\\Framework\\Validator\\ValidationRuleParser' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Validator/ValidationRuleParser.php',
        'FluentCrm\\Framework\\Validator\\Validator' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/Validator/Validator.php',
        'FluentCrm\\Framework\\View\\View' => __DIR__ . '/..' . '/wpfluent/framework/src/WPFluent/View/View.php',
        'FluentCrm\\Includes\\Helpers\\Arr' => __DIR__ . '/../..' . '/includes/Helpers/Arr.php',
        'FluentCrm\\Includes\\Helpers\\ConditionAssesor' => __DIR__ . '/../..' . '/includes/Helpers/ConditionAssesor.php',
        'FluentCrm\\Includes\\Helpers\\Str' => __DIR__ . '/../..' . '/includes/Helpers/Str.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3ec9aaa182e9c7febe2801c6e81775d2::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3ec9aaa182e9c7febe2801c6e81775d2::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3ec9aaa182e9c7febe2801c6e81775d2::$classMap;

        }, null, ClassLoader::class);
    }
}
