<?php

namespace FluentCrm\App\Models;

/**
 *  Meta Model - DB Model for Meta table
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */

class Meta extends Model
{
    protected $table = 'fc_meta';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $fillable = [
        'object_type',
        'object_id',
        'key',
        'value',
        'created_at',
        'updated_at'
    ];

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = maybe_serialize($value);
    }

    public function getValueAttribute($value)
    {
        return maybe_unserialize($value);
    }
}
