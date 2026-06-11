<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AccountManager extends Authenticatable
{
    protected $table = 'account_managers';
    protected $guarded = ['id'];
    protected $hidden = ['password'];
    public $timestamps = false;
}
