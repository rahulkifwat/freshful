<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyersAddress extends Model
{
    protected $table = 'buyer_address';
    public $timestamps = false;
    protected $guarded = ['id'];

    // buyer_address has no created_at/updated_at (it uses date_added, filled by MySQL).
    public $timestamps = false;
}
