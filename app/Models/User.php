<?php

namespace FluentCrm\App\Models;

/**
 *  User Model - DB Model for WordPress Users Table
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */

class User extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'ID';

    protected $hidden = ['user_pass'];
}
