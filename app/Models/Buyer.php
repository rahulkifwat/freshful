<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;

class Buyer extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $guarded = ['id'];
}
