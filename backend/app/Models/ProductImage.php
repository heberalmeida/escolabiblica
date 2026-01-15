<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'url', 'position'];

    public function getUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $baseUrl = config('app.asset_url') ?: config('app.url');

        return rtrim($baseUrl, '/') . '/storage/' . ltrim($value, '/');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
