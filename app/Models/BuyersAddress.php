<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyersAddress extends Model
{
    protected $table = 'buyer_address';
    public $timestamps = false;
    protected $guarded = ['id'];
}
