<?php

namespace FluentCrm\App\Hooks\Handlers;

class UpgradationHandler
{
    public static function maybeUpdateDbTables()
    {
        $currentDbVerson = get_option('_fluentcrm_db_version');
        if (!$currentDbVerson || version_compare($currentDbVerson, FLUENTCRM_DB_VERSION, '<')) {
            require_once(FLUENTCRM_PLUGIN_PATH . 'database/FluentCRMDBMigrator.php');
        }
    }

    public static function updateTables()
    {
        // Run DB Migrations
        require_once(FLUENTCRM_PLUGIN_PATH . 'database/FluentCRMDBMigrator.php');
    }
}
