<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Production extends Authenticatable
{
    protected $table    = 'production';
    protected $guarded  = ['id'];
    protected $hidden   = ['password'];
    public    $timestamps = false;
}
