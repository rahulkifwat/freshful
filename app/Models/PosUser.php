<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class PosUser extends Authenticatable
{
    protected $table = 'pos_user';
    protected $guarded = ['id'];
    protected $hidden = ['password'];
    public $timestamps = false;
}
