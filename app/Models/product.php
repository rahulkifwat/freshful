<?php

namespace App\Models;
use App\Models\category;
use App\Models\item;

use Illuminate\Database\Eloquent\Model;

class product extends Model
{
    protected $table = 'products';
    protected $guarded = ['id'];


    public function category()
    {
        return $this->belongsTo(category::class, 'category_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(item::class, 'item_id', 'id');
    }

    public function getCategoryNameAttribute()
    {
        return $this->category?->group_type;
    }

    public function getItemNameAttribute()
    {
        return $this->item?->item;
    }
}
