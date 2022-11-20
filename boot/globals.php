<?php

/**
 ***** DO NOT CALL ANY FUNCTIONS DIRECTLY FROM THIS FILE ******
 *
 * This file will be loaded even before the framework is loaded
 * so the $app is not available here, only declare functions here.
 */

$globalsDevFile = __DIR__ . '/globals_dev.php';

is_readable($globalsDevFile) && include $globalsDevFile;

if (!function_exists('dd')) {
    function dd()
    {
        foreach (func_get_args() as $arg) {
            echo "<pre>";
            print_r($arg); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo "</pre>";
        }
        die();
    }
}
