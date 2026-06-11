<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class HrManager extends Authenticatable
{
    protected $table    = 'hr_managers';
    protected $guarded  = ['id'];
    protected $hidden   = ['password'];
    public    $timestamps = false;
}
