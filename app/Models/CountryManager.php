<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class CountryManager extends Authenticatable
{
    protected $table = 'country_managers';
    protected $guarded = ['id'];
    protected $hidden = ['password'];
    public $timestamps = false;
}
