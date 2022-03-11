<?php

namespace FluentCrm\Framework\Database\Orm;

use DateTimeZone;
use FluentCrm\Framework\Database\Orm\DateTime;

trait ModelHelperTrait
{
    public static function classBasename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    public static function classUsesRecursive($class)
    {
        $results = [];

        foreach (array_merge([$class => $class], class_parents($class)) as $class) {
            if ($class != get_class()) {
                $results += static::traitUsesRecursive($class);
            }
        }
        
        return array_unique($results);
    }

    public static function traitUsesRecursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += static::traitUsesRecursive($trait);
        }

        return $traits;
    }

    public function getTimezone()
    {
        // if site timezone string exists, return it
        $timezone = get_option('timezone_string');
        if ($timezone) {
            return new DateTimeZone($timezone);
        }

        // get UTC offset, if it isn't set then return UTC
        $utcOffset = get_option('gmt_offset', 0);
        if ($utcOffset === 0) {
            return new DateTimeZone('UTC');
        }

        // Adjust UTC offset from hours to seconds
        $utcOffset *= 3600;

        // Attempt to guess the timezone string from the UTC offset
        $timezone = timezone_name_from_abbr('', $utcOffset, 0);
        if ($timezone) {
            return new DateTimeZone($timezone);
        }

        // Guess timezone string manually
        $isDst = date('I');
        foreach (timezone_abbreviations_list() as $abbr) {
            foreach ($abbr as $city) {
                if ($city['dst'] == $isDst && $city['offset'] == $utcOffset) {
                    $timezoneId = $city['timezone_id'];
                    $timezone = $timezoneId ?: timezone_name_from_abbr('', $timezoneId, 0);
                    if ($timezone) return new DateTimeZone($timezone);
                }
            }
        }

        // Fallback
        return new DateTimeZone('UTC');
    }

    protected function getDateFormat()
    {
        if (isset($this->dateFormat)) {
            return $this->dateFormat;
        }
        
        return 'Y-m-d H:i:s';
    }

    public static function createFromFormat($format, $datetime, $timezone = null)
    {
        $instance = new static;
        return new static($datetime, $instance->getTimezone());
    }
}
