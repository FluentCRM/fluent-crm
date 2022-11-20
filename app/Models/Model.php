<?php

namespace FluentCrm\App\Models;

use FluentCrm\Framework\Database\Orm\DateTime;
use FluentCrm\Framework\Database\Orm\Model as BaseModel;

class Model extends BaseModel
{
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    }

    public function scopeLatest($query, $field = 'created_at')
    {
        return $query->orderBy($field, 'desc');
    }

    public function scopeNewest($query, $field = 'created_at')
    {
        return $query->orderBy($field, 'asc');
    }

    public function getPerPage()
    {
        return (isset($_REQUEST['per_page'])) ? intval($_REQUEST['per_page']) : 15;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \FluentCrm\Framework\Database\Orm\DateTime
     */
    public function freshTimestamp()
    {
        return new \DateTime(current_time('mysql'));
    }

    public function getTimezone()
    {
        return '';
    }

}
