<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_price',
        'old_price',
        'active',
        'infinite_stock',
    ];

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}

