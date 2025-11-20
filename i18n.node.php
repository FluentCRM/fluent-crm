<?php


// load WordPress
require_once __DIR__ . '/../../../wp-load.php';

$jsSourceStrings = require_once 'translationStrings.php';
require_once __DIR__ . '/app/Services/TransStrings.php';
$manualStrings = \FluentCrm\App\Services\TransStrings::getStrings();


$newStrings = [];

foreach ($jsSourceStrings as $key => $value) {
    if (!isset($manualStrings[$key])) {
        $manualStrings[$key] = $value;
    }
}

// short the $manualStrings

ksort($manualStrings);

// print as php array

$string = "<?php\n\nnamespace FluentCrm\App\Services;\n\n//This is an auto-generated file. Please do not edit manually\nclass TransStrings\n{\n\n  public static function getStrings()\n  {\n";

$string .= "return [\n";
foreach ($manualStrings as $key => $value) {
    // escape single quote
    if(is_string($value) && strpos($value, "'") !== false) {
        $value = str_replace("\'", "'", $value);
        $value = str_replace("'", "\'", $value);
    }

    if(is_string($key) && strpos($key, "'") !== false) {
        $key = str_replace("\'", "'", $key);
        $key = str_replace("'", "\'", $key);
    }

    $string .= "    '$key' => __('$value', 'fluent-crm'),\n";
}

$string .= "];\n }\n\n}\n";


// save the string in all-translations.php file

file_put_contents(__DIR__ . '/transStrings.php', $string);
