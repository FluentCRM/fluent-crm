<?php

namespace FluentCrm\App\Models;

class User extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'ID';

    protected $hidden = ['user_pass'];
}
