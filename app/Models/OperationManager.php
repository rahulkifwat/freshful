<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class OperationManager extends Authenticatable
{
    protected $table    = 'operation_managers';
    protected $guarded  = ['id'];
    protected $hidden   = ['password'];
    public    $timestamps = false;
}
