<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Buyer extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens;

    protected $guarded = ['id'];
}
