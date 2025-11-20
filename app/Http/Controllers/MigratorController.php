<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Services\CrmMigrator\ActiveCampaignMigrator;
use FluentCrm\App\Services\CrmMigrator\ConvertKitMigrator;
use FluentCrm\App\Services\CrmMigrator\DripMigrator;
use FluentCrm\App\Services\CrmMigrator\MailChimpMigrator;
use FluentCrm\App\Services\CrmMigrator\MailerLiteMigrator;
use FluentCrm\Framework\Request\Request;

/**
 *  MigratorController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class MigratorController extends Controller
{
    public function getDrivers(Request $request)
    {
        return [
            'drivers' => $this->getMigrators()
        ];
    }

    public function verifyCredential(Request $request)
    {
        $driver = $request->get('driver');

        $driverClassName = $this->getDriverClass($driver);

        if (!$driverClassName) {
            return $this->sendError([
                'message' => __('Sorry no driver found for the selected CRM', 'fluent-crm')
            ]);
        }

        $credential = $request->get('credential', []);

        $driverClass = new $driverClassName;

        $result = $driverClass->verifyCredentials($credential);

        if (is_wp_error($result)) {
            return $this->sendError([
                'message' => $result->get_error_message(),
            ], 422);
        }

        return [
            'message' => __('Your provided API key is valid', 'fluent-crm')
        ];
    }

    public function getListTagMappings(Request $request)
    {
        $driver = $request->get('driver');

        $driverClassName = $this->getDriverClass($driver);

        if (!$driverClassName) {
            return $this->sendError([
                'message' => __('Sorry no driver found for the selected CRM', 'fluent-crm')
            ]);
        }

        $credential = $request->get('credential', []);

        $result = (new $driverClassName)->getListTagMappings($request->all());
        if (is_wp_error($result)) {
            return $this->sendError([
                'message' => $result->get_error_message(),
            ], 422);
        }

        return [
            'options' => $result
        ];
    }


    public function getImportSummary(Request $request)
    {
        $driver = $request->get('driver');
        $driverClassName = $this->getDriverClass($driver);

        if (!$driverClassName) {
            return $this->sendError([
                'message' => __('Sorry no driver found for the selected CRM', 'fluent-crm')
            ]);
        }

        $credential = $request->get('credential', []);
        $mapSettings = $request->get('map_settings', []);


        $summary = (new $driverClassName)->getSummary($request->all());

        if (is_wp_error($summary)) {
            return $this->sendError([
                'message' => $summary->get_error_message(),
            ], 422);
        }

        return [
            'import_summary' => $summary
        ];
    }

    public function handleImport(Request $request)
    {
        if (!defined('FLUENTCRM_DOING_BULK_IMPORT')) {
            define('FLUENTCRM_DOING_BULK_IMPORT', true);
        }

        $driver = $request->get('driver');
        $driverClassName = $this->getDriverClass($driver);

        if (!$driverClassName) {
            return $this->sendError([
                'message' => __('Sorry no driver found for the selected CRM', 'fluent-crm')
            ]);
        }

        $summary = (new $driverClassName)->runImport($request->all());

        if (is_wp_error($summary)) {
            return $this->sendError([
                'message' => $summary->get_error_message(),
            ], 422);
        }

        return [
            'import_info' => $summary
        ];
    }

    private function getDriverClass($driver)
    {
        if ($driver == 'mailchimp') {
            return MailChimpMigrator::class;
        } else if ($driver == 'ConvertKit') {
            return ConvertKitMigrator::class;
        } else if ($driver == 'MailerLite') {
            return MailerLiteMigrator::class;
        } else if ($driver == 'Drip') {
            return DripMigrator::class;
        } else if ($driver == 'ActiveCampaign') {
            return ActiveCampaignMigrator::class;
        }

        /**
         * Filter the migrator driver class.
         *
         * This filter allows you to modify the migrator driver class.
         *
         * @since 2.7.0
         *
         * @param mixed  $class  The current migrator driver class. Default null.
         * @param string $driver The driver name.
         */
        return apply_filters('fluent_crm/migrator_driver_class', null, $driver);
    }

    private function getMigrators()
    {
        /**
         * Filter the list of available SaaS migrators.
         *
         * This filter allows modification of the list of available SaaS migrators
         * by adding, removing, or modifying the migrators.
         *
         * @since 2.7.0
         *
         * @param array $migrators {
         *     An associative array of migrators.
         *
         *     @type array $mailchimp {
         *         Information about the MailChimp migrator.
         *     }
         *     @type array $ConvertKit {
         *         Information about the ConvertKit migrator.
         *     }
         *     @type array $MailerLite {
         *         Information about the MailerLite migrator.
         *     }
         *     @type array $Drip {
         *         Information about the Drip migrator.
         *     }
         *     @type array $ActiveCampaign {
         *         Information about the ActiveCampaign migrator.
         *     }
         * }
         */
        return apply_filters('fluent_crm/saas_migrators', [
            'mailchimp'      => (new MailChimpMigrator())->getInfo(),
            'ConvertKit'     => (new ConvertKitMigrator())->getInfo(),
            'MailerLite'     => (new MailerLiteMigrator())->getInfo(),
            'Drip'           => (new DripMigrator())->getInfo(),
            'ActiveCampaign' => (new ActiveCampaignMigrator())->getInfo()
        ]);
    }

}
