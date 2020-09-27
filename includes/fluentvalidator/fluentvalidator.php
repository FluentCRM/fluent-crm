<?php defined('ABSPATH') or die;

/*
Plugin Name: Fluent Validator
Description: Fluent Validator WordPress Plugin to validate data.
Version: 1.0.0
Author: 
Author URI: 
Plugin URI: 
License: GPLv2 or later
Text Domain: fluentvalidator
Domain Path: /resources/languages
*/
require 'autoload.php';

if (! function_exists('fluentcrmFluentValidator')) {
    function fluentcrmFluentValidator($data = [], $rules = [], $messages = []) {
        return (new \FluentValidator\Validator($data, $rules, $messages));
    }
}
