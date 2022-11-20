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

    #[\ReturnTypeWillChange]
    public function getTimezone()
    {
        return wp_timezone();
    }

    protected function getDateFormat()
    {
        if (isset($this->dateFormat)) {
            return $this->dateFormat;
        }

        return 'Y-m-d H:i:s';
    }

    #[\ReturnTypeWillChange]
    public static function createFromFormat($format, $datetime, $timezone = null)
    {
        $instance = new static;
        return new static($datetime, $instance->getTimezone());
    }
}
