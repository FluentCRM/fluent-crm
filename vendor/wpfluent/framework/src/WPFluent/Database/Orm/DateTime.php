<?php

namespace FluentCrm\Framework\Database\Orm;

use DateTime as PHPDateTime;
use FluentCrm\Framework\Database\Orm\ModelHelperTrait;

Class DateTime extends PHPDateTime
{
    use ModelHelperTrait;

    public function __construct($datetime = "now", $timezone = null)
    {
        $timezone = $timezone ?: $this->getTimezone();

        parent::__construct($datetime, $timezone);
    }

    public function __toString()
    {
        return $this->format($this->getDateFormat());
    }
}
