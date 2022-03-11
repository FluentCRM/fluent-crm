<?php

namespace FluentCrm\App\Hooks\Handlers;

/**
 *  UrlMetrics Class - For Internal Debugging usage only
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class WpQueryLogger
{
    static $logInFile = true;

    public static function getQueryLog($withStack = true)
    {
        $trace = debug_backtrace(2, 0);
        $trace = reset($trace);
        $file = explode('/', $trace['file']);
        $caller = end($file);
        $caller = substr($caller, 0, strpos($caller, '.'));

        $class = explode('\\', __CLASS__);
        $class = end($class);

        $self = false;
        if ($caller == $class) {
            $self = true;
        }

        if (!defined('SAVEQUERIES') || !SAVEQUERIES) {

            if ($self) return;

            return [
                'message'           => __('Please enable query logging by calling enableQueryLog() before queries ran.', 'fluent-crm'),
                'Total Queries Ran' => null,
                'Query Logs'        => null
            ];
        }

        if (!current_user_can('administrator')) {
            return [
                'message'           => __('Oops! You are not able to see query logs.', 'fluent-crm'),
                'Total Queries Ran' => null,
                'Query Logs'        => null
            ];
        }

        if (FluentCrm()->request->get('action') == 'heartbeat') {
            return;
        }

        $result = [];
        $queries = (array)$GLOBALS['wpdb']->queries;

        foreach ($queries as $key => $query) {
            $query = array_slice($query, 0, 3);

            if ($withStack) {
                $stackArray = [];
                $stack = explode(', ', $query[2]);

                foreach ($stack as $skey => $sValue) {
                    $stackArray[++$skey] = $sValue;
                }

                $query[2] = $stackArray;

                $result[++$key] = array_combine([
                    'query', 'execution_time', 'stack'
                ], $query);
            } else {
                $result[++$key] = array_combine([
                    'query', 'execution_time'
                ], array_slice($query, 0, 2));
            }
        }

        return [
            'Total Queries Ran' => count($queries),
            'Query Logs'        => array_filter($result)
        ];
    }

    public function logQueries()
    {
        if (!static::$logInFile) return;

        $result = static::getQueryLog();

        if (!$result) return;

        error_log('[' . fluentCrmTimestamp() . ']: ' . json_encode(
                $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) . PHP_EOL, 3, FluentCrm()->path . 'query.log');
    }

    public static function enableQueryLog($inFile = false)
    {
        if (!defined('SAVEQUERIES')) {
            define('SAVEQUERIES', true);
            static::$logInFile = $inFile;
        }
    }

    public static function init()
    {
        add_action('shutdown', [get_class(), 'logQueries'], 100);
    }
}
