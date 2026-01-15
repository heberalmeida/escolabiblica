<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantImage extends Model
{
    protected $fillable = ['variant_id', 'url', 'position'];

    public function getUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $baseUrl = config('app.asset_url') ?: config('app.url');
        return rtrim($baseUrl, '/') . '/storage/' . ltrim($value, '/');
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
