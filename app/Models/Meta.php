<?php

namespace FluentCrm\App\Models;

class Meta extends Model
{
    protected $table = 'fc_meta';

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = maybe_serialize($value);
    }

    public function getValueAttribute($value)
    {
        return maybe_unserialize($value);
    }
}
