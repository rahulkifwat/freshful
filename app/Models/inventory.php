<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class inventory extends Model
{
    protected $table = 'inventory';
    protected $primaryKey = 'id';
    protected $fillable = [
        'product_id',
        'live_stock',
        'created_at',
        'updated_at'
    ];

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }
}
