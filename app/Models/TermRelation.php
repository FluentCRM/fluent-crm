<?php

namespace FluentCrm\App\Models;

use FluentCrm\Framework\Database\Orm\Model;

class TermRelation extends Model
{
    protected $table = 'fc_term_relations';

    protected $fillable = [
        'term_id',
        'object_type',
        'object_id',
        'settings'
    ];

    public $timestamps = false;

    protected $casts = [
        'settings' => 'array'
    ];

}
