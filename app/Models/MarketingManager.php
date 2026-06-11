<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class MarketingManager extends Authenticatable
{
    protected $table    = 'marketing_managers';
    protected $guarded  = ['id'];
    protected $hidden   = ['password'];
    public    $timestamps = false;
}
