<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AreaManager extends Authenticatable
{
    protected $table = 'area_managers';
    protected $guarded = ['id'];
    protected $hidden = ['password'];
    public $timestamps = false;
}
