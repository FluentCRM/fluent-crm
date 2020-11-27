<?php

namespace FluentCrm\App\Services;

class BlockParserHelper
{
    private static $subscriber;

    public static function setSubscriber($subscriber)
    {
        static::$subscriber = $subscriber;
    }

    public static function getSubscriber()
    {
        return static::$subscriber;
    }
}
