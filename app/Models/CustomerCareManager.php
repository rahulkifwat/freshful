<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class CustomerCareManager extends Authenticatable
{
    protected $table = 'customer_care_managers';
    protected $guarded = ['id'];
    protected $hidden = ['password'];
    public $timestamps = false;
}
