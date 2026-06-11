<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class HubUser extends Authenticatable
{
    protected $table    = 'hub_user';
    protected $guarded  = ['id'];
    protected $hidden   = ['password'];
    public    $timestamps = false;
}
