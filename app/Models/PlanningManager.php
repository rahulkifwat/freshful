<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class PlanningManager extends Authenticatable
{
    protected $table = 'planning_managers';
    protected $guarded = ['id'];
    protected $hidden = ['password'];
    public $timestamps = false;
}
